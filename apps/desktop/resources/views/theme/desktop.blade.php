<script>
    (() => {
        const frame = window.requestAnimationFrame.bind(window);
        const cancel = window.cancelAnimationFrame.bind(window);
        const waiting = new Map();

        window.requestAnimationFrame = (callback) => {
            let id;
            const run = (time) => {
                if (!waiting.has(id)) return;
                clearTimeout(waiting.get(id));
                waiting.delete(id);
                callback(time);
            };
            id = frame(run);
            waiting.set(id, setTimeout(() => {
                cancel(id);
                run(performance.now());
            }, 1000));

            return id;
        };

        window.cancelAnimationFrame = (id) => {
            clearTimeout(waiting.get(id));
            waiting.delete(id);
            cancel(id);
        };
    })();

    localStorage.setItem('filament:sidebar:open', 'false');
    localStorage.setItem('filament:sidebar:desktop_open', 'false');
</script>
<style>
    .fi-sidebar-open-collapse-sidebar-btn, .fi-sidebar-close-collapse-sidebar-btn { display: none !important; }
    .lk-sidebar-logo { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: .5rem; }
    .lk-sidebar-logo:hover { background: var(--gray-800); }
    .lk-sidebar-logo svg { width: 1.75rem; height: 1.75rem; }
    .lk-tabs .fi-tabs { gap: .25rem; padding: .375rem; border-bottom: 1px solid var(--gray-800); }
    .lk-tabs .fi-tabs-item { padding: .5rem .875rem; border-radius: .5rem; color: var(--gray-400); font-weight: 500; }
    .lk-tabs .fi-tabs-item:hover { color: var(--gray-200); background: var(--gray-800); }
    .lk-tabs .fi-tabs-item.fi-active { color: var(--gray-950); background: var(--primary-400); }
    .lk-tabs .fi-tabs-item.fi-active .fi-icon, .lk-tabs .fi-tabs-item.fi-active .fi-tabs-item-label { color: var(--gray-950); }
    .lk-unavailable .fi-fo-field-label, .lk-unavailable [id$="-helper-text"] { opacity: .4; }
    .lk-unavailable, .lk-unavailable * { cursor: not-allowed !important; }
    .lk-sidebar-bottom { flex: none; overflow: visible; padding-block: .75rem; }
    .fi-header-heading { font-size: 1.125rem; font-weight: 600; letter-spacing: 0; }
    .fi-main { padding-top: 1.25rem; }
    .fi-page-content { row-gap: 1rem; }
    :is(.dark) .fi-sidebar { background: #0b0d11; border-inline-end: 1px solid var(--gray-800); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined) { background: var(--primary-400); color: var(--gray-950); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined) .fi-icon { color: var(--gray-950); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined):hover { background: var(--primary-300); }


    .lk-board { display: flex; flex-direction: column; gap: 1rem; font-size: .8125rem; }
    .lk-cut { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }


    .lk-seg { display: inline-flex; align-self: flex-start; gap: .125rem; padding: .1875rem; border: 1px solid var(--gray-800); border-radius: .5rem; }
    .lk-seg button { padding: .25rem .625rem; border-radius: .375rem; color: var(--gray-300); font-size: .75rem; }
    .lk-seg button:hover { background: var(--gray-900); }
    .lk-seg button[aria-pressed="true"] { color: var(--gray-100); background: var(--gray-800); }

    .lk-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(21rem, 1fr)); gap: .875rem; }
    .lk-card { box-sizing: border-box; height: 33rem; overflow: hidden; display: flex; flex-direction: column; gap: .75rem; padding: 1rem; border: 1px solid var(--gray-800); border-radius: .75rem; background: var(--gray-900); }
    .lk-card[data-off] { background: var(--gray-950); }
    .lk-card__head { display: flex; align-items: center; gap: .75rem; }
    .lk-card__avatar { flex: none; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: .625rem; font-size: .9375rem; font-weight: 600; color: var(--gray-300); background: var(--gray-800); }
    .lk-card__avatar[style] { color: oklch(85% .09 var(--hue)); background: oklch(30% .07 var(--hue)); }
    .lk-card__who { flex: 1; min-width: 0; font-size: .75rem; }
    .lk-card__name { font-size: .875rem; font-weight: 600; }
    .lk-card__place { display: flex; align-items: center; gap: .375rem; color: var(--gray-300); font-size: .75rem; }
    .lk-card__place svg { flex: none; width: .875rem; height: .875rem; }
    .lk-card__place .lk-cut { flex: 1; }
    .lk-card .lk-meters[data-unknown] { opacity: .4; }
    .lk-card__note { flex: none; height: 1.125rem; line-height: 1.125rem; font-size: .75rem; color: var(--lk-muted); }
    .lk-card__note[data-state="script"] { color: #c7d2fe; }
    .lk-card__note[data-state="disconnected"] { color: #fcd34d; }
    .lk-card__note[data-state="failed"] { color: #fca5a5; }
    .lk-card__note[data-state="entering"] { color: #93c5fd; }
    .lk-card__log { flex: 1 1 0; min-height: 0; overflow-y: auto; display: flex; flex-direction: column-reverse; scrollbar-width: thin; scrollbar-color: var(--gray-700) transparent; }
    .lk-card__log .lk-log__line { flex: none; animation: lk-log-in .4s ease-out; }
    .lk-card__log .lk-log__line > :last-child { min-width: 0; }
    @keyframes lk-log-in { from { opacity: 0; transform: translateY(.375rem); } }
    .lk-card__actions { display: flex; justify-content: flex-end; align-items: center; gap: .375rem; padding-top: .75rem; border-top: 1px solid var(--gray-800); }

    .lk-tag { display: inline-block; padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 500; white-space: nowrap; color: var(--gray-300); background: var(--gray-800); }
    .lk-tag[data-state="idle"] { color: #86efac; background: #14301f; }
    .lk-tag[data-state="fighting"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="dead"], .lk-tag[data-state="failed"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="away"] { color: #c4b5fd; background: #261d40; }
    .lk-tag[data-state="entering"], .lk-tag[data-state="starting"], .lk-tag[data-state="pending"], .lk-tag[data-state="stopping"] { color: #93c5fd; background: #16233d; }
    .lk-tag[data-state="disconnected"] { color: #fcd34d; background: #3a2a10; }

    .lk-log { flex: 2 1 20rem; min-width: 0; padding: .625rem .75rem; border-radius: .5rem; background: #08090c; color: #b4b9c6; font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: .75rem; line-height: 1.7; }
    .lk-log__line { display: flex; gap: .75rem; word-break: break-word; }
    .lk-log__at { color: #6b7180; flex-shrink: 0; }
    .lk-log [data-level="success"] { color: #86efac; }
    .lk-log [data-level="warning"] { color: #fbbf24; }
    .lk-log [data-level="error"] { color: #f87171; }
    .lk-tag[data-state="running"] { color: #c7d2fe; background: #1e2048; }
    .lk-tag[data-state="done"] { color: #86efac; background: #14301f; }
    .lk-tag[data-state="cancelled"], .lk-tag[data-state="cancelling"] { color: var(--gray-400); background: var(--gray-800); }


    .fi-header-subheading { font-size: .8125rem; margin-top: .125rem; }
</style>
