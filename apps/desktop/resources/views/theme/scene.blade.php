<style>
    :root {
        --lk-ink: #0b0d11;
        --lk-panel: #111318;
        --lk-panel-2: #171a21;
        --lk-line: rgba(148, 163, 184, .13);
        --lk-line-2: rgba(148, 163, 184, .24);
        --lk-text: #e6ebf5;
        --lk-muted: #8a90a0;
        --lk-faint: #6b7180;
        --lk-hp-a: #b91c1c;
        --lk-hp-b: #f87171;
        --lk-mp-a: #1d4ed8;
        --lk-mp-b: #60a5fa;
        --lk-xp: #c4b5fd;
        --lk-gold: #fbbf24;
        --lk-good: #4ade80;
        --lk-bad: #f87171;
    }

    .lk { color: var(--lk-text); font-variant-numeric: tabular-nums; }
    .lk-dim { color: var(--lk-muted); }
    .lk-faint { color: var(--lk-faint); }
    .lk-num { font-variant-numeric: tabular-nums; }

    .lk-meters { display: grid; gap: .45rem; }
    .lk-meter__label { display: flex; justify-content: space-between; align-items: baseline; font-size: .7rem; font-weight: 700; margin-bottom: .2rem; letter-spacing: .05em; }
    .lk-meter__label > span:first-child { color: var(--lk-muted); }
    .lk-bar { position: relative; height: .62rem; border-radius: 999px; overflow: hidden; background: rgba(2, 6, 23, .85); box-shadow: inset 0 1px 3px rgba(0, 0, 0, .7), 0 0 0 1px rgba(148, 163, 184, .08); }
    .lk-bar__trail, .lk-bar__fill { position: absolute; inset: 0 auto 0 0; width: var(--w); border-radius: inherit; }
    .lk-bar__trail { background: rgba(255, 244, 214, .7); transition: width 1.2s cubic-bezier(.2, .8, .2, 1) .45s; }
    .lk-bar__fill { transition: width .35s ease-out; }
    .lk-bar__fill::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(180deg, rgba(255, 255, 255, .38), transparent 55%); }
    .lk-bar::after { content: ''; position: absolute; inset: 0; background: repeating-linear-gradient(90deg, transparent 0 calc(10% - 1px), rgba(0, 0, 0, .45) calc(10% - 1px) 10%); pointer-events: none; }
    [data-kind="health"] .lk-bar__fill { background: linear-gradient(90deg, var(--lk-hp-a), var(--lk-hp-b)); box-shadow: 0 0 12px -2px var(--lk-hp-b); }
    [data-kind="mana"] .lk-bar__fill { background: linear-gradient(90deg, var(--lk-mp-a), var(--lk-mp-b)); box-shadow: 0 0 12px -2px var(--lk-mp-b); }
    [data-kind="experience"] .lk-bar__fill { background: linear-gradient(90deg, #6d28d9, var(--lk-xp)); box-shadow: 0 0 12px -2px var(--lk-xp); }
    [data-kind="experience"] .lk-bar { height: .4rem; }
    [data-kind="health"][data-low] .lk-bar__fill { animation: lk-low .9s ease-in-out infinite; }
    @keyframes lk-low { 50% { filter: brightness(1.6) saturate(1.3); } }
    .lk-meter--compact .lk-bar { height: .32rem; }
    .lk-meter--compact .lk-bar::after { display: none; }

    .lk-foe { display: flex; align-items: center; gap: .6rem; font-size: .76rem; }
    .lk-foe__name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-foe__bar { flex: 1; min-width: 4rem; }




</style>
