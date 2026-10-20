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

    .lk-task-list { max-height: 52vh; overflow-y: auto; padding-right: .375rem; scrollbar-width: thin; scrollbar-color: var(--gray-700) transparent; }

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
    .lk-row__toggle:focus:not(:focus-visible) { outline: none; box-shadow: none; }
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
    .lk-tag[data-state="running"] { color: #c7d2fe; background: #1e2048; }
    .lk-tag[data-state="done"] { color: #86efac; background: #14301f; }
    .lk-tag[data-state="cancelled"], .lk-tag[data-state="cancelling"] { color: var(--gray-400); background: var(--gray-800); }

    .lk-box { display: flex; flex-direction: column; gap: .625rem; min-width: 0; padding: .875rem 1rem; border: 1px solid var(--gray-800); border-radius: .75rem; background: var(--gray-900); }
    .lk-box__head { display: flex; justify-content: space-between; gap: .75rem; color: var(--lk-muted); font-size: .75rem; }
    .lk-box__head > span:first-child { color: var(--gray-200); font-weight: 600; }

    .fi-header-subheading { font-size: .8125rem; margin-top: .125rem; }
    .lk-run__rowhead .lk-tag, .lk-run__task .lk-tag { justify-self: start; flex-shrink: 0; }
    .lk-run__panel .lk-panel__title > span { display: none; }
    .lk-run .lk-tab { padding: .25rem .625rem; font-size: .75rem; font-weight: 500; letter-spacing: 0; text-transform: none; }
    .lk-run .lk-cell { border: 1px solid var(--gray-800); border-radius: .625rem; }
    .lk-run .lk-cell__name { letter-spacing: 0; text-transform: none; font-weight: 500; font-size: .75rem; }
    .lk-run .lk-sum { display: flex; flex-wrap: wrap; }
    .lk-run .lk-sum__cell { flex: 1 1 9.5rem; min-width: 0; }
    .lk-run__line { display: flex; align-items: center; gap: .375rem; min-width: 0; white-space: nowrap; margin-top: .25rem; font-size: .875rem; font-weight: 500; }
    .lk-run__meter { display: flex; flex-direction: column; justify-content: center; }
    .lk-run__main { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 1rem; }
    .lk-run__col { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
    .lk-run__task { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .lk-run__progress { display: flex; align-items: center; gap: .625rem; }
    .lk-run__progress .lk-track { flex: 1; }
    .lk-run__panel { padding-top: .75rem; border-top: 1px solid var(--gray-800); }
    .lk-run__panel .lk-panel__title { margin-bottom: .5rem; }
    .lk-run__panel .lk-panel__title h3 { font-size: .75rem; letter-spacing: 0; text-transform: none; font-weight: 500; }
    .lk-run__panel .lk-pulse__head { gap: 1.75rem; }
    .lk-run__panel .lk-pulse__stat strong { font-size: 1.125rem; font-weight: 600; letter-spacing: 0; }
    .lk-run__panel .lk-pulse__stat span { font-size: .75rem; text-transform: none; letter-spacing: 0; color: var(--lk-muted); }
    .lk-run__panel .lk-steps { margin-top: .75rem; }
    .lk-run__panel .lk-chart { height: 4.5rem; }
    .lk-run__tasks { max-height: 28rem; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--gray-700) transparent; }
    .lk-run__row { border-top: 1px solid var(--gray-800); }
    .lk-run__tasks > .lk-run__row:first-child { border-top: 0; }
    .lk-run__rowhead { display: grid; grid-template-columns: 1.75rem 2.25rem 5.5rem minmax(0, 1fr) 4.5rem 2rem; gap: .5rem; align-items: center; padding-block: .375rem; }
    .lk-run__rowhead .lk-row__toggle svg { width: 1rem; height: 1rem; transition: transform .15s; }
    .lk-run__what { display: flex; flex-direction: column; min-width: 0; }
    .lk-run__report { padding: .5rem 0 .75rem 2.25rem; }
    .lk-run__log { flex: 1; height: 0; min-height: 20rem; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--gray-700) transparent; }
    .lk-run__tabs { display: flex; gap: 1.25rem; margin-bottom: .25rem; border-bottom: 1px solid var(--gray-800); }
    .lk-run__tab { padding: .25rem 0 .625rem; margin-bottom: -1px; border-bottom: 2px solid transparent; color: var(--lk-muted); font-weight: 500; }
    .lk-run__tab:hover { color: var(--gray-200); }
    .lk-run__tab[aria-selected="true"] { color: var(--gray-100); border-bottom-color: var(--primary-400); }
    .lk-run__facts { display: flex; gap: 1.5rem; flex-wrap: wrap; padding-top: .75rem; }
    .lk-run .lk-cell, .lk-run .lk-mob, .lk-run .lk-item { background: var(--gray-950); }
    .lk-run .lk-cell[data-engaged] { background: color-mix(in oklab, #fbbf24 6%, var(--gray-950)); }
    .lk-gear { display: flex; align-items: center; gap: .625rem; min-width: 0; padding: .5rem .625rem; border-radius: .625rem; border: 1px solid var(--gray-800); background: var(--gray-950); }
    .lk-gear__what { display: flex; flex-direction: column; flex: 1; min-width: 0; }
    .lk-gear__name { font-weight: 600; }
    .lk-gear__enh { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; color: #f0abfc; font-weight: 600; font-size: .75rem; }
    .lk-gear__enh small { color: var(--lk-muted); font-weight: 400; }
    .lk-gear__bare { flex-shrink: 0; font-size: .75rem; }
    .lk-sheet__rank { max-width: none; }
    .lk-sheet__class { align-self: start; }
    .lk-sheet .lk-backpack__tools .lk-tabs { flex: 1 1 100%; flex-wrap: wrap; overflow: visible; padding-bottom: 0; }
    .lk-sheet__label { margin-top: .375rem; color: var(--lk-muted); font-size: .75rem; }
    .lk-skill { display: flex; gap: .625rem; padding: .5rem 0; border-top: 1px solid var(--gray-800); }
    .lk-skill[data-locked] { opacity: .45; }
    .lk-skill__key { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; flex-shrink: 0; border-radius: .5rem; font-size: .75rem; font-weight: 700; color: #fca5a5; background: color-mix(in oklab, #fca5a5 14%, transparent); }
    .lk-skill__key[data-target="self"] { color: #86efac; background: color-mix(in oklab, #86efac 14%, transparent); }
    .lk-skill__key[data-target="ally"] { color: #93c5fd; background: color-mix(in oklab, #93c5fd 14%, transparent); }
    .lk-skill__body { display: flex; flex-direction: column; gap: .125rem; min-width: 0; }
    .lk-skill__head { display: flex; align-items: center; gap: .5rem; }
    .lk-skill__name { font-weight: 600; }
    .lk-skill__text { color: var(--gray-400); line-height: 1.5; }
    .lk-passive { display: flex; flex-direction: column; gap: .125rem; padding: .5rem 0; border-top: 1px solid var(--gray-800); }
    @media (max-width: 64rem) { .lk-run__main { grid-template-columns: 1fr; } }
</style>
