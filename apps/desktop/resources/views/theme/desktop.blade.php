<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400&display=swap">
<script>
    if (localStorage.getItem('filament:sidebar:desktop_open') === null) {
        localStorage.setItem('filament:sidebar:open', 'false');
        localStorage.setItem('filament:sidebar:desktop_open', 'false');
    }
</script>
<style>
    .fi-header-heading { font-size: 1.125rem; font-weight: 600; letter-spacing: 0; }
    .fi-main { padding-top: 1.25rem; }
    .fi-page-content { row-gap: 1rem; }
    :is(.dark) .fi-sidebar { background: #0b0d11; border-inline-end: 1px solid var(--gray-800); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined) { background: var(--primary-400); color: var(--gray-950); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined) .fi-icon { color: var(--gray-950); }
    :is(.dark) .fi-btn.fi-color-primary:not(.fi-outlined):hover { background: var(--primary-300); }

    .lk-board { display: flex; flex-direction: column; gap: 1rem; font-size: .8125rem; }
    .lk-cut { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .lk-sum { display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 1px; background: var(--gray-800); border: 1px solid var(--gray-800); border-radius: .75rem; overflow: hidden; }
    .lk-sum__cell { padding: .75rem 1rem; background: var(--gray-900); }
    .lk-sum__label { color: var(--lk-muted); font-size: .75rem; }
    .lk-sum__value { font-size: 1.25rem; font-weight: 600; }

    .lk-table { overflow-x: auto; border: 1px solid var(--gray-800); border-radius: .75rem; }
    .lk-table__inner { min-width: 58rem; }
    .lk-row { display: grid; grid-template-columns: 1.75rem minmax(9rem, 1.4fr) 3.75rem 8rem minmax(7rem, 1.2fr) minmax(12rem, 2fr) 5.5rem 10rem; gap: .75rem; align-items: center; padding: .625rem .875rem; border-bottom: 1px solid var(--gray-800); }
    .lk-row[data-open] { background: color-mix(in oklab, var(--primary-500) 8%, var(--gray-900)); }
    .lk-row--head { padding-block: .625rem; color: var(--lk-muted); font-size: .75rem; background: var(--gray-900); }
    .lk-row__toggle { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: .375rem; color: var(--lk-muted); }
    .lk-row__toggle:hover { background: var(--gray-800); }
    .lk-row__toggle svg { width: 1rem; height: 1rem; transition: transform .15s; }
    .lk-row[data-open] .lk-row__toggle svg { transform: rotate(90deg); }
    .lk-row__who { min-width: 0; text-align: start; }
    .lk-row__name { display: block; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lk-row__sub { display: block; color: var(--lk-muted); font-size: .75rem; }
    .lk-row__doing { display: flex; flex-direction: column; gap: .3125rem; min-width: 0; }
    .lk-row__actions { display: flex; justify-content: flex-end; gap: .375rem; }
    .lk-track { display: block; max-width: 14rem; height: .25rem; border-radius: 999px; background: var(--gray-800); overflow: hidden; }
    .lk-track > span { display: block; height: 100%; background: var(--primary-400); transition: width .4s ease; }

    .lk-tag { display: inline-block; padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 500; white-space: nowrap; color: var(--gray-300); background: var(--gray-800); }
    .lk-tag[data-state="idle"] { color: #86efac; background: #14301f; }
    .lk-tag[data-state="fighting"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="dead"], .lk-tag[data-state="failed"] { color: #fca5a5; background: #3b1517; }
    .lk-tag[data-state="away"] { color: #c4b5fd; background: #261d40; }
    .lk-tag[data-state="entering"], .lk-tag[data-state="pending"], .lk-tag[data-state="stopping"] { color: #93c5fd; background: #16233d; }
    .lk-tag[data-state="disconnected"] { color: #fcd34d; background: #3a2a10; }

    .lk-detail { display: flex; flex-wrap: wrap; gap: 1.25rem; padding: .875rem .875rem 1.125rem 3.375rem; border-bottom: 1px solid var(--gray-800); background: color-mix(in oklab, var(--gray-950) 85%, black); }
    .lk-detail__col { flex: 1 1 15rem; min-width: 0; display: flex; flex-direction: column; gap: .5rem; }
    .lk-detail__label { color: var(--lk-muted); font-size: .75rem; margin-top: .25rem; }
    .lk-queue { display: flex; align-items: center; gap: .5rem; }
    .lk-queue__waiting { width: .75rem; height: .75rem; flex-shrink: 0; border-radius: 50%; border: 1.5px solid var(--lk-muted); }
    .lk-queue__playing { width: .875rem; height: .875rem; flex-shrink: 0; color: var(--primary-400); }

    .lk-log { flex: 2 1 20rem; min-width: 0; padding: .625rem .75rem; border-radius: .5rem; background: #08090c; color: #b4b9c6; font-family: 'IBM Plex Mono', ui-monospace, monospace; font-size: .75rem; line-height: 1.7; }
    .lk-log__line { display: flex; gap: .75rem; word-break: break-word; }
    .lk-log__at { color: #6b7180; flex-shrink: 0; }
    .lk-log [data-level="success"] { color: #86efac; }
    .lk-log [data-level="warning"] { color: #fbbf24; }
    .lk-log [data-level="error"] { color: #f87171; }
    .lk-log__more { display: inline-block; margin-top: .25rem; color: #a5b4fc; }
</style>
