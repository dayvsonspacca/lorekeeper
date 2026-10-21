<x-filament-panels::page>
    @assets
        <link rel="stylesheet" href="{{ asset('drawflow/drawflow.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/flow-editor.css') }}">
        <script src="{{ asset('drawflow/drawflow.min.js') }}"></script>
        <script src="{{ asset('js/flow-editor.js') }}"></script>
    @endassets

    <div class="lk-flow" x-data="flowEditor" wire:ignore>
        <aside class="lk-flow__palette">
            <template x-for="(kinds, group) in groups" :key="group">
                <section>
                    <h3 class="lk-flow__group" x-text="group"></h3>
                    <template x-for="kind in kinds" :key="kind">
                        <button
                            type="button"
                            class="lk-flow__tool"
                            :class="about(kind).check && 'lk-flow__tool--check'"
                            draggable="true"
                            @dragstart="$event.dataTransfer.setData('text/plain', kind)"
                            @click="add(kind)"
                            :data-kind="kind"
                        >
                            <span class="lk-flow__tool-icon" x-html="about(kind).icon"></span>
                            <span x-text="about(kind).label"></span>
                        </button>
                    </template>
                </section>
            </template>
        </aside>

        <div class="lk-flow__stage">
            <div class="lk-flow__canvas" x-ref="canvas" @dragover.prevent @drop.prevent="dropped($event)"></div>

            <div class="lk-flow__bar">
                <span class="lk-flow__state" :data-state="state()" x-text="stateText()"></span>
                <button type="button" class="lk-flow__zoom" @click="zoom(-1)" aria-label="Zoom out">−</button>
                <button type="button" class="lk-flow__zoom" @click="zoom(1)" aria-label="Zoom in">+</button>
            </div>

            <template x-if="review.others.length > 0">
                <ul class="lk-flow__others">
                    <template x-for="problem in review.others" :key="problem">
                        <li x-text="problem"></li>
                    </template>
                </ul>
            </template>
        </div>

        <aside class="lk-flow__panel">
            <template x-if="selected === null">
                <div class="lk-flow__hint">
                    <p>Drag a step from the left onto the canvas, or click it.</p>
                    <p>Pull a wire from a node's exit to the next node. Start is where the bot begins.</p>
                    <p>Select a node to fill it in. Delete removes what is selected.</p>
                </div>
            </template>

            <template x-if="selected !== null">
                <div class="lk-flow__fields">
                    <div class="lk-flow__title">
                        <span class="lk-flow__tool-icon" x-html="about(editing).icon"></span>
                        <span x-text="about(editing).label"></span>
                    </div>

                    <template x-for="field in about(editing).fields" :key="editing + field.name">
                        <label class="lk-flow__field">
                            <span>
                                <span x-text="field.label"></span>
                                <span class="lk-flow__optional" x-show="field.optional">optional</span>
                            </span>
                            <template x-if="field.type === 'choice'">
                                <select x-model="values[field.name]" @change="changed()" :name="field.name">
                                    <option value="" x-show="field.otherwise === null">Pick one</option>
                                    <template x-for="option in field.options" :key="option">
                                        <option :value="option" x-text="option" :selected="values[field.name] === option"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="field.type !== 'choice'">
                                <input
                                    :type="field.type === 'number' ? 'number' : 'text'"
                                    :min="field.type === 'number' ? 1 : null"
                                    :max="field.most"
                                    :placeholder="field.placeholder ?? ''"
                                    :name="field.name"
                                    x-model="values[field.name]"
                                    @input="changed()"
                                >
                            </template>
                        </label>
                    </template>

                    <ul class="lk-flow__problems" x-show="(review.nodes[values.id] ?? []).length > 0">
                        <template x-for="problem in review.nodes[values.id] ?? []" :key="problem">
                            <li x-text="problem"></li>
                        </template>
                    </ul>

                    <button type="button" class="lk-flow__remove" @click="remove()">Remove node</button>
                </div>
            </template>
        </aside>
    </div>

    @script
        <script>
            Alpine.data('flowEditor', () => window.LorekeeperFlow.editor(@js($this->editor()), $wire));
        </script>
    @endscript
</x-filament-panels::page>
