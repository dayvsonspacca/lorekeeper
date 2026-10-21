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


    .lk-table { overflow-x: auto; border: 1px solid var(--gray-800); border-radius: .75rem; }
    .lk-table__inner { min-width: 46rem; }
    .lk-row { display: grid; grid-template-columns: 1.75rem minmax(9rem, 1.4fr) 3.75rem 8rem minmax(7rem, 2fr) 5.5rem 10rem; gap: .75rem; align-items: center; padding: .625rem .875rem; border-bottom: 1px solid var(--gray-800); }
    .lk-row[data-open] { background: color-mix(in oklab, var(--primary-500) 8%, var(--gray-900)); }
    .lk-row--head { padding-block: .625rem; color: var(--lk-muted); font-size: .75rem; background: var(--gray-900); }
    .lk-row__toggle { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: .375rem; color: var(--lk-muted); }
    .lk-row__toggle:focus:not(:focus-visible) { outline: none; box-shadow: none; }
    .lk-row__toggle:hover { background: var(--gray-800); }
    .lk-row__toggle svg { width: 1rem; height: 1rem; transition: transform .15s; }
    .lk-row[data-open] .lk-row__toggle svg { transform: rotate(90deg); }
    .lk-row__who { min-width: 0; text-align: start; }
    .lk-row__name { display: block; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lk-row__sub { display: block; color: var(--lk-muted); font-size: .75rem; }
    .lk-row__actions { display: flex; justify-content: flex-end; gap: .375rem; }

    .lk-tag { display: inline-block; padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 500; white-space: nowrap; color: var(--gray-300); background: var(--gray-800); }
    .lk-tag[data-state="idle"] { color: #86efac; background: #14301f; }
    .lk-tag[data-state="fighting"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="dead"], .lk-tag[data-state="failed"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="away"] { color: #c4b5fd; background: #261d40; }
    .lk-tag[data-state="entering"], .lk-tag[data-state="starting"], .lk-tag[data-state="pending"], .lk-tag[data-state="stopping"] { color: #93c5fd; background: #16233d; }
    .lk-tag[data-state="disconnected"] { color: #fcd34d; background: #3a2a10; }

    .lk-detail { display: flex; flex-wrap: wrap; gap: 1.25rem; padding: .875rem .875rem 1.125rem 3.375rem; border-bottom: 1px solid var(--gray-800); background: color-mix(in oklab, var(--gray-950) 85%, black); }
    .lk-detail__col { flex: 1 1 15rem; min-width: 0; display: flex; flex-direction: column; gap: .5rem; }

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
