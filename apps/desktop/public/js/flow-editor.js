(() => {
    const EXITS = { action: ['next', 'failed'], check: ['yes', 'no'] };
    const SAVE_AFTER = 600;
    const REMEMBER_AFTER = 350;
    const HISTORY = 100;
    const WHEEL = 0.0015;
    const BEND = 60;

    const plain = (value) => JSON.parse(JSON.stringify(value));

    const escape = (text) => String(text).replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`);

    const typing = () => {
        const focused = document.activeElement;

        return focused !== null && (['INPUT', 'TEXTAREA', 'SELECT'].includes(focused.tagName) || focused.isContentEditable);
    };

    function editor(config, wire) {
        const kinds = config.kinds;
        let flow = null;
        let start = null;
        let timer = null;
        let shortcut = null;
        let framing = null;
        let remembering = null;
        let restoring = false;
        let history = [];
        let step = -1;
        let panning = null;
        let pan = null;
        let released = null;
        let grab = null;

        const exits = (kind) => (kinds[kind]?.check ? EXITS.check : EXITS.action);

        const summary = (kind, data) => kinds[kind].summary.replace(/\{(\w+)\}/g, (_, name) => {
            const value = data[name];

            return value === undefined || value === null || value === '' ? '?' : String(value);
        });

        const card = (kind, data) => `
            <div class="lk-node__head">
                <span class="lk-node__icon">${kinds[kind].icon}</span>
                <span class="lk-node__label">${escape(kinds[kind].label)}</span>
            </div>
            <div class="lk-node__summary">${escape(summary(kind, data))}</div>`;

        const nodes = () => flow.export().drawflow.Home.data;

        const isStart = (id) => String(id) === String(start);

        return {
            groups: config.groups,
            review: config.review,
            selected: null,
            editing: null,
            values: {},
            status: 'saved',

            about(kind) {
                return kinds[kind] ?? { label: String(kind), icon: '', fields: [], check: false, summary: '' };
            },

            init() {
                this.$el.style.setProperty('--lk-flow-top', `${this.$el.getBoundingClientRect().top + window.scrollY}px`);
                flow = new Drawflow(this.$refs.canvas);
                flow.curvature = 0.45;
                flow.zoom_min = 0.4;
                flow.zoom_max = 1.6;
                const curve = flow.createCurvature.bind(flow);
                flow.createCurvature = (fromX, fromY, toX, toY, curvature, type) => {
                    if (toX >= fromX) {
                        return curve(fromX, fromY, toX, toY, curvature, type);
                    }

                    const middle = (fromY + toY) / 2;

                    return ` M ${fromX} ${fromY} C ${fromX + BEND} ${fromY} ${fromX + BEND} ${middle} ${fromX} ${middle}`
                        + ` L ${toX} ${middle} C ${toX - BEND} ${middle} ${toX - BEND} ${toY} ${toX} ${toY}`;
                };
                flow.zoom_enter = (event) => this.wheel(event);
                flow.start();

                const removeNode = flow.removeNodeId.bind(flow);
                flow.removeNodeId = (id) => {
                    if (typing() || isStart(String(id).replace('node-', ''))) {
                        return;
                    }

                    removeNode(id);
                };
                const removeConnection = flow.removeConnection.bind(flow);
                flow.removeConnection = () => {
                    if (!typing()) {
                        removeConnection();
                    }
                };

                this.draw(config.drawing ?? {});
                this.remember();
                this.fit();
                this.listen();
                framing = new ResizeObserver(() => this.fit());
                framing.observe(this.$refs.canvas);
                for (const event of ['translate', 'zoom', 'nodeMoved', 'nodeCreated', 'connectionCreated']) {
                    flow.on(event, () => framing.disconnect());
                }
                this.mark();

                shortcut = (event) => this.key(event);
                released = (event) => {
                    if (event.code === 'Space') {
                        this.panning(false);
                    }
                };
                grab = (event) => this.grab(event);
                window.addEventListener('keydown', shortcut);
                window.addEventListener('keyup', released);
                window.addEventListener('blur', released);
                this.$refs.canvas.parentElement.addEventListener('mousedown', grab, true);
            },

            key(event) {
                const command = event.ctrlKey || event.metaKey;
                const letter = event.key.toLowerCase();
                if (command && letter === 's') {
                    event.preventDefault();
                    this.save();

                    return;
                }

                if (typing()) {
                    return;
                }

                if (command && (letter === 'y' || (letter === 'z' && event.shiftKey))) {
                    event.preventDefault();
                    this.redo();
                } else if (command && letter === 'z') {
                    event.preventDefault();
                    this.undo();
                } else if (event.code === 'Space') {
                    event.preventDefault();
                    this.panning(true);
                }
            },

            panning(on) {
                panning = on ? panning ?? {} : null;
                this.$refs.canvas.classList.toggle('lk-flow__canvas--panning', on);
                if (!on) {
                    this.$refs.canvas.classList.remove('lk-flow__canvas--grabbing');
                }
            },

            grab(event) {
                if (panning === null || event.button !== 0) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                const from = { x: event.clientX, y: event.clientY, canvasX: flow.canvas_x, canvasY: flow.canvas_y };
                this.$refs.canvas.classList.add('lk-flow__canvas--grabbing');
                const move = (moved) => {
                    this.view(from.canvasX + moved.clientX - from.x, from.canvasY + moved.clientY - from.y, flow.zoom);
                };
                const drop = () => {
                    window.removeEventListener('mousemove', move);
                    window.removeEventListener('mouseup', drop);
                    pan = null;
                    this.$refs.canvas.classList.remove('lk-flow__canvas--grabbing');
                    flow.dispatch('translate', { x: flow.canvas_x, y: flow.canvas_y });
                };
                pan = drop;
                window.addEventListener('mousemove', move);
                window.addEventListener('mouseup', drop);
            },

            wheel(event) {
                event.preventDefault();
                const box = this.$refs.canvas.getBoundingClientRect();
                this.zoomAt(event.clientX - box.left, event.clientY - box.top, Math.exp(-event.deltaY * WHEEL));
            },

            zoomAt(x, y, factor) {
                const zoom = Math.min(flow.zoom_max, Math.max(flow.zoom_min, flow.zoom * factor));
                const scale = zoom / flow.zoom;
                this.view(x - (x - flow.canvas_x) * scale, y - (y - flow.canvas_y) * scale, zoom);
                flow.dispatch('zoom', zoom);
            },

            view(x, y, zoom) {
                flow.canvas_x = x;
                flow.canvas_y = y;
                flow.zoom = zoom;
                flow.zoom_last_value = zoom;
                flow.precanvas.style.transform = `translate(${x}px, ${y}px) scale(${zoom})`;
            },

            destroy() {
                if (this.status === 'unsaved') {
                    this.save();
                }

                window.removeEventListener('keydown', shortcut);
                window.removeEventListener('keyup', released);
                window.removeEventListener('blur', released);
                pan?.();
                clearTimeout(remembering);
                framing.disconnect();
                flow.node_selected = null;
                flow.connection_selected = null;
            },

            draw(drawing) {
                const placed = new Map();
                const kindOf = new Map();
                for (const node of drawing.nodes ?? []) {
                    if (kinds[node.do] === undefined || typeof node.id !== 'string' || placed.has(node.id)) {
                        continue;
                    }

                    const [x, y] = Array.isArray(node.at) ? node.at : [0, 0];
                    const data = plain(node);
                    delete data.at;
                    placed.set(node.id, this.place(node.do, data, x, y));
                    kindOf.set(node.id, node.do);
                }

                const first = (drawing.nodes ?? []).find((node) => node.id === drawing.start);
                const [x, y] = drawing.canvas?.start
                    ?? (first && Array.isArray(first.at) ? [first.at[0] - 300, first.at[1]] : [80, 200]);
                start = flow.addNode('start', 0, 1, x, y, 'lk-node lk-node--start', {}, `
                    <div class="lk-node__head">
                        <span class="lk-node__label">Start</span>
                    </div>
                    <div class="lk-node__summary">the bot begins here</div>`);
                if (placed.has(drawing.start)) {
                    flow.addConnection(start, placed.get(drawing.start), 'output_1', 'input_1');
                }

                for (const wire of drawing.wires ?? []) {
                    if (!placed.has(wire.from) || !placed.has(wire.to)) {
                        continue;
                    }

                    const ways = exits(kindOf.get(wire.from));
                    const index = ways.indexOf(wire.exit ?? ways[0]);
                    if (index >= 0) {
                        flow.addConnection(placed.get(wire.from), placed.get(wire.to), `output_${index + 1}`, 'input_1');
                    }
                }
            },

            fit() {
                const all = Object.values(nodes());
                const shown = this.$refs.canvas.getBoundingClientRect();
                const left = Math.min(...all.map((node) => node.pos_x));
                const top = Math.min(...all.map((node) => node.pos_y));
                const right = Math.max(...all.map((node) => node.pos_x)) + 240;
                const bottom = Math.max(...all.map((node) => node.pos_y)) + 90;
                const zoom = Math.max(flow.zoom_min, Math.min(1, (shown.width - 96) / (right - left), (shown.height - 128) / (bottom - top)));
                flow.zoom = zoom;
                flow.zoom_last_value = zoom;
                flow.canvas_x = Math.max(48, (shown.width - (right - left) * zoom) / 2) - left * zoom;
                flow.canvas_y = Math.max(64, (shown.height - (bottom - top) * zoom) / 2) - top * zoom;
                flow.precanvas.style.transform = `translate(${flow.canvas_x}px, ${flow.canvas_y}px) scale(${zoom})`;
            },

            place(kind, data, x, y) {
                const type = kinds[kind].check ? 'check' : 'action';

                return flow.addNode(kind, 1, 2, x, y, `lk-node lk-node--${type}`, data, card(kind, data));
            },

            listen() {
                flow.on('nodeSelected', (id) => this.select(id));
                flow.on('nodeUnselected', () => {
                    this.selected = null;
                });
                flow.on('nodeMoved', () => this.touched());
                flow.on('nodeRemoved', (id) => {
                    if (String(id) === String(this.selected)) {
                        this.selected = null;
                    }

                    this.touched();
                });
                flow.on('connectionCreated', (connection) => {
                    this.single(connection);
                    this.touched();
                });
                flow.on('connectionRemoved', () => this.touched());
            },

            add(kind) {
                const box = this.$refs.canvas.getBoundingClientRect();
                this.dropAt(kind, box.x + box.width / 2 - 110, box.y + box.height / 2 - 40);
            },

            dropped(event) {
                const kind = event.dataTransfer.getData('text/plain');
                if (kinds[kind] !== undefined) {
                    this.dropAt(kind, event.clientX, event.clientY);
                }
            },

            dropAt(kind, clientX, clientY) {
                const box = flow.precanvas.getBoundingClientRect();
                const data = { id: this.freshId(kind), do: kind };
                for (const field of kinds[kind].fields) {
                    if (field.type === 'choice' && field.otherwise !== null) {
                        data[field.name] = field.otherwise;
                    }
                }

                const id = this.place(kind, data, (clientX - box.x) / flow.zoom, (clientY - box.y) / flow.zoom);
                this.select(id);
                this.touched();
            },

            freshId(kind) {
                const taken = new Set(Object.values(nodes()).map((node) => node.data.id));
                let number = 1;
                while (taken.has(`${kind}-${number}`)) {
                    number++;
                }

                return `${kind}-${number}`;
            },

            select(id) {
                if (isStart(id)) {
                    this.selected = null;

                    return;
                }

                const node = flow.getNodeFromId(id);
                this.editing = node.data.do ?? node.name;
                this.values = plain(node.data);
                this.selected = String(id);
            },

            changed() {
                const data = this.typed();
                flow.updateNodeDataFromId(this.selected, data);
                const shown = this.$refs.canvas.querySelector(`#node-${this.selected} .lk-node__summary`);
                if (shown !== null) {
                    shown.textContent = summary(this.editing, data);
                }

                this.touched();
            },

            typed() {
                const data = { id: this.values.id, do: this.editing };
                for (const field of kinds[this.editing].fields) {
                    const value = this.values[field.name];
                    if (value === undefined || value === null || String(value).trim() === '') {
                        continue;
                    }

                    data[field.name] = field.type === 'number' && /^-?\d+$/.test(String(value).trim())
                        ? Number(String(value).trim())
                        : String(value);
                }

                return data;
            },

            single(connection) {
                const outputs = flow.getNodeFromId(connection.output_id).outputs[connection.output_class];
                for (const other of [...outputs.connections]) {
                    if (String(other.node) !== String(connection.input_id)) {
                        flow.removeSingleConnection(connection.output_id, other.node, connection.output_class, other.output);
                    }
                }
            },

            remove() {
                if (this.selected !== null) {
                    flow.removeNodeId(`node-${this.selected}`);
                }
            },

            zoom(direction) {
                const box = this.$refs.canvas.getBoundingClientRect();
                this.zoomAt(box.width / 2, box.height / 2, direction > 0 ? 1.2 : 1 / 1.2);
            },

            remember() {
                clearTimeout(remembering);
                const now = JSON.stringify(this.drawing());
                if (history[step] === now) {
                    return;
                }

                history = [...history.slice(0, step + 1), now].slice(-HISTORY);
                step = history.length - 1;
            },

            undo() {
                this.remember();
                if (step > 0) {
                    step--;
                    this.restore(history[step]);
                }
            },

            redo() {
                this.remember();
                if (step < history.length - 1) {
                    step++;
                    this.restore(history[step]);
                }
            },

            restore(snapshot) {
                restoring = true;
                flow.node_selected = null;
                flow.connection_selected = null;
                flow.clear();
                this.draw(JSON.parse(snapshot));
                restoring = false;
                this.selected = null;
                this.mark();
                this.unsaved();
            },

            drawing() {
                const all = nodes();
                const drawing = { start: null, nodes: [], wires: [], canvas: {} };
                for (const [id, node] of Object.entries(all)) {
                    const at = [Math.round(node.pos_x), Math.round(node.pos_y)];
                    if (isStart(id)) {
                        const first = node.outputs.output_1?.connections[0];
                        drawing.start = first === undefined ? null : all[first.node]?.data.id ?? null;
                        drawing.canvas.start = at;

                        continue;
                    }

                    drawing.nodes.push({ ...plain(node.data), at });
                    exits(node.name).forEach((exit, index) => {
                        for (const connection of node.outputs[`output_${index + 1}`]?.connections ?? []) {
                            const target = all[connection.node];
                            if (target !== undefined && !isStart(connection.node)) {
                                drawing.wires.push({ from: node.data.id, exit, to: target.data.id });
                            }
                        }
                    });
                }

                return drawing;
            },

            touched() {
                if (restoring) {
                    return;
                }

                clearTimeout(remembering);
                remembering = setTimeout(() => this.remember(), REMEMBER_AFTER);
                this.unsaved();
            },

            unsaved() {
                this.status = 'unsaved';
                clearTimeout(timer);
                timer = setTimeout(() => this.save(), SAVE_AFTER);
            },

            async save() {
                clearTimeout(timer);
                this.status = 'saving';
                try {
                    const review = await wire.save(this.drawing());
                    this.review = review;
                    if (this.status === 'saving') {
                        this.status = 'saved';
                    }
                } catch (error) {
                    this.status = 'failed';
                }

                this.mark();
            },

            mark() {
                for (const [id, node] of Object.entries(nodes())) {
                    const shown = this.$refs.canvas.querySelector(`#node-${id}`);
                    if (shown !== null) {
                        shown.classList.toggle('lk-node--wrong', !isStart(id) && this.review.nodes[node.data.id] !== undefined);
                    }
                }
            },

            problems() {
                return this.review.others.length + Object.values(this.review.nodes).reduce((sum, list) => sum + list.length, 0);
            },

            state() {
                if (this.status !== 'saved') {
                    return this.status;
                }

                return this.review.ready ? 'ready' : 'problems';
            },

            stateText() {
                const problems = this.problems();

                return {
                    saving: 'Saving…',
                    unsaved: 'Unsaved changes',
                    failed: 'Could not save',
                    ready: 'Saved · ready to play',
                    problems: `Saved · ${problems} ${problems === 1 ? 'problem' : 'problems'}`,
                }[this.state()];
            },
        };
    }

    window.LorekeeperFlow = { editor };
})();
