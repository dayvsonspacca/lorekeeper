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
    .lk-head__line { display: flex; align-items: center; gap: .4rem; color: var(--lk-muted); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-head__line svg { width: .85rem; height: .85rem; flex-shrink: 0; }

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
    .lk-member { flex-shrink: 0; padding: .05rem .35rem; border-radius: .3rem; font-size: .6rem; font-weight: 900; letter-spacing: .06em; color: #1c1917; background: var(--lk-gold); }
    [data-kind="health"][data-low] .lk-bar__fill { animation: lk-low .9s ease-in-out infinite; }
    @keyframes lk-low { 50% { filter: brightness(1.6) saturate(1.3); } }
    .lk-meter--compact .lk-bar { height: .32rem; }
    .lk-meter--compact .lk-bar::after { display: none; }

    .lk-foe { display: flex; align-items: center; gap: .6rem; font-size: .76rem; }
    .lk-foe__name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-foe__bar { flex: 1; min-width: 4rem; }

    .lk-spark { width: 100%; height: 2.3rem; color: var(--lk-xp); overflow: visible; }
    .lk-pace { display: flex; align-items: end; gap: .8rem; }
    .lk-pace__graph { flex: 1; min-width: 0; }
    .lk-pace__value { text-align: right; line-height: 1.1; }
    .lk-pace__value strong { display: block; font-size: 1.05rem; font-weight: 800; color: var(--lk-xp); }
    .lk-pace__value span { font-size: .66rem; color: var(--lk-faint); text-transform: uppercase; letter-spacing: .08em; }
    .lk-calm { font-size: .74rem; color: var(--lk-faint); display: flex; align-items: center; gap: .4rem; }
    .lk-calm svg { width: .9rem; height: .9rem; }
    @media (max-width: 64rem) { .lk-stage__row, .lk-stage__row--low { grid-template-columns: minmax(0, 1fr); } }
    .lk-panel__title { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .9rem; }
    .lk-panel__title h3 { display: flex; align-items: center; gap: .45rem; font-size: .74rem; letter-spacing: .12em; text-transform: uppercase; color: var(--lk-muted); font-weight: 800; }
    .lk-panel__title h3 svg { width: 1rem; height: 1rem; }

    .lk-hotbar { display: flex; justify-content: center; gap: .45rem; margin-top: 1.1rem; flex-wrap: wrap; }
    .lk-slot { position: relative; width: 3.05rem; height: 3.05rem; border-radius: .8rem; display: grid; place-items: center; color: var(--lk-text);
        background: linear-gradient(180deg, #222b41, #10162a); border: 1px solid var(--lk-line-2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), 0 6px 14px -8px rgba(0, 0, 0, .9); }
    .lk-slot svg { width: 1.3rem; height: 1.3rem; }
    .lk-slot[data-target="enemy"] svg { color: #fca5a5; }
    .lk-slot[data-target="self"] svg { color: #86efac; }
    .lk-slot[data-target="ally"] svg { color: #93c5fd; }
    .lk-slot[data-slot="Potion"] svg { color: #f0abfc; }
    .lk-slot__key { position: absolute; top: 2px; left: 5px; font-size: .58rem; font-weight: 800; color: var(--lk-faint); }
    .lk-slot__cost { position: absolute; bottom: 2px; right: 4px; font-size: .58rem; font-weight: 800; color: var(--lk-mp-b); }
    .lk-slot__cost[data-restores] { color: var(--lk-good); }
    .lk-slot__aoe { position: absolute; top: -.4rem; right: -.4rem; padding: 0 .3rem; border-radius: 999px; font-size: .58rem; font-weight: 900; color: #1c1917; background: var(--lk-gold); box-shadow: 0 0 8px rgba(251, 191, 36, .6); }
    .lk-slot[data-locked] { opacity: .3; filter: grayscale(1); }
    .lk-tabs { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .55rem; margin-bottom: .6rem; scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, .25) transparent; }
    .lk-tab { display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0; padding: .35rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--lk-muted); background: rgba(2, 6, 23, .45); border: 1px solid var(--lk-line); cursor: pointer; transition: color .15s, border-color .15s, background .15s; }
    .lk-tab:hover { color: var(--lk-text); border-color: var(--lk-line-2); }
    .lk-tab svg { width: .85rem; height: .85rem; }
    .lk-tab__count { font-weight: 600; letter-spacing: 0; color: var(--lk-faint); }
    .lk-tab[data-engaged] { color: var(--lk-gold); border-color: rgba(251, 191, 36, .4); }
    .lk-tab[data-empty] { opacity: .55; }
    .lk-tab[aria-selected="true"] { color: var(--lk-text); background: color-mix(in oklab, var(--lk-hue, #fbbf24) 18%, rgba(2, 6, 23, .6)); border-color: color-mix(in oklab, var(--lk-hue, #fbbf24) 55%, transparent); }
    .lk-tab[aria-selected="true"][data-engaged] { color: var(--lk-gold); background: rgba(251, 191, 36, .14); border-color: rgba(251, 191, 36, .6); }
    .lk-cell__follow { margin-left: auto; display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; letter-spacing: .06em; color: var(--lk-gold); background: rgba(251, 191, 36, .1); border: 1px solid rgba(251, 191, 36, .35); cursor: pointer; text-transform: uppercase; }
    .lk-cell { padding: .7rem; border-radius: 1rem; border: 1px dashed var(--lk-line-2); background: rgba(2, 6, 23, .35); }
    .lk-cell[data-engaged] { border-style: solid; border-color: rgba(251, 191, 36, .45); background: radial-gradient(100% 120% at 0% 0%, rgba(251, 191, 36, .1), transparent 60%), rgba(2, 6, 23, .45); }
    .lk-cell__name { display: flex; align-items: center; gap: .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--lk-faint); margin-bottom: .55rem; }
    .lk-cell[data-engaged] .lk-cell__name { color: var(--lk-gold); }
    .lk-cell__name svg { width: .9rem; height: .9rem; }
    .lk-cell__mobs { display: grid; grid-template-columns: repeat(auto-fill, minmax(11.5rem, 1fr)); gap: .5rem; max-height: 22rem; overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, .25) transparent; }
    .lk-mob { position: relative; padding: .5rem .6rem .55rem .7rem; border-radius: .75rem; background: linear-gradient(180deg, rgba(34, 43, 65, .85), rgba(15, 21, 37, .85)); border: 1px solid var(--lk-line); border-left: 3px solid var(--race); transition: opacity .6s, filter .6s; }
    .lk-mob__top { display: flex; justify-content: space-between; gap: .5rem; align-items: baseline; margin-bottom: .35rem; }
    .lk-mob__name { font-size: .78rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lk-mob__level { font-size: .64rem; font-weight: 800; color: var(--race); flex-shrink: 0; }
    .lk-mob__hp { margin-top: .25rem; display: flex; justify-content: space-between; font-size: .62rem; color: var(--lk-faint); }
    .lk-mob[data-struck] { border-color: rgba(248, 113, 113, .55); box-shadow: 0 0 18px -6px rgba(239, 68, 68, .9); animation: lk-hit .45s ease-out; }
    .lk-mob[data-state="dead"] { opacity: .38; filter: grayscale(1); }
    .lk-mob__skull { position: absolute; top: .45rem; right: .5rem; width: 1rem; height: 1rem; color: var(--lk-muted); }
    @keyframes lk-hit { 0% { transform: translateX(0); } 20% { transform: translateX(-3px) rotate(-.6deg); } 45% { transform: translateX(3px); } 70% { transform: translateX(-1px); } 100% { transform: none; } }

    .lk-chart { display: flex; align-items: end; gap: 3px; height: 7rem; padding-top: .5rem; }
    .lk-chart__bar { position: relative; flex: 1; min-width: 3px; height: 100%; display: flex; align-items: end; }
    .lk-chart__bar > i { display: block; width: 100%; height: var(--v); min-height: 2px; border-radius: 3px 3px 1px 1px; background: linear-gradient(180deg, var(--lk-xp), color-mix(in oklab, var(--lk-xp) 25%, transparent)); transition: height .6s ease; opacity: .85; }
    .lk-chart__bar:last-child > i { background: linear-gradient(180deg, #fff, var(--lk-xp)); animation: lk-glow 1.6s ease-in-out infinite; }
    .lk-chart__bar:hover > i { opacity: 1; filter: brightness(1.3); }
    .lk-chart__axis { display: flex; justify-content: space-between; margin-top: .4rem; font-size: .62rem; color: var(--lk-faint); text-transform: uppercase; letter-spacing: .08em; }
    @keyframes lk-glow { 50% { filter: brightness(1.5) drop-shadow(0 0 6px var(--lk-xp)); } }
    .lk-pulse__head { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .lk-pulse__stat strong { display: block; font-size: 1.5rem; font-weight: 900; letter-spacing: -.02em; }
    .lk-pulse__stat span { font-size: .66rem; color: var(--lk-faint); text-transform: uppercase; letter-spacing: .1em; }
    @keyframes lk-in { from { opacity: 0; transform: translateY(-6px); } }
    .lk-backpack__tools { display: flex; gap: .75rem; align-items: start; flex-wrap: wrap; margin-bottom: .4rem; }
    .lk-backpack__tools .lk-tabs { flex: 1; min-width: 0; margin-bottom: 0; }
    .lk-backpack__tools .lk-tab { text-transform: capitalize; letter-spacing: .02em; }
    .lk-search { display: flex; align-items: center; gap: .4rem; padding: .3rem .65rem; border-radius: 999px; background: rgba(2, 6, 23, .55); border: 1px solid var(--lk-line); color: var(--lk-faint); }
    .lk-search svg { width: .85rem; height: .85rem; }
    .lk-search input { width: 11rem; background: transparent; border: 0; outline: 0; padding: 0; color: var(--lk-text); font-size: .78rem; box-shadow: none; }
    .lk-backpack__label { margin: .9rem 0 .5rem; font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--lk-muted); }
    .lk-bag { display: grid; grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr)); gap: .45rem; max-height: 21rem; overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, .25) transparent; }
    .lk-item { display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .45rem .55rem; border-radius: .7rem; background: linear-gradient(180deg, rgba(34, 43, 65, .7), rgba(15, 21, 37, .7)); border: 1px solid var(--lk-line); }
    .lk-item[data-temporary] { border-style: dashed; border-color: rgba(251, 191, 36, .45); background: radial-gradient(120% 140% at 0% 0%, rgba(251, 191, 36, .1), transparent 60%), rgba(15, 21, 37, .7); }
    .lk-item__icon { width: 1.9rem; height: 1.9rem; flex-shrink: 0; display: grid; place-items: center; border-radius: .55rem; color: var(--tone, #94a3b8); background: color-mix(in oklab, var(--tone, #94a3b8) 16%, transparent); }
    .lk-item__icon svg { width: 1rem; height: 1rem; }
    .lk-item__icon[data-kind="class"] { --tone: #fbbf24; }
    .lk-item__icon[data-kind="armor"] { --tone: #60a5fa; }
    .lk-item__icon[data-kind="helm"] { --tone: #a5b4fc; }
    .lk-item__icon[data-kind="cape"] { --tone: #c084fc; }
    .lk-item__icon[data-kind="weapon"] { --tone: #f87171; }
    .lk-item__icon[data-kind="pet"] { --tone: #4ade80; }
    .lk-item__icon[data-kind="necklace"] { --tone: #22d3ee; }
    .lk-item__icon[data-kind="house"] { --tone: #fb923c; }
    .lk-item__icon[data-kind="boost"], .lk-item__icon[data-kind="enhancement"] { --tone: #f0abfc; }
    .lk-item__name { flex: 1; min-width: 0; font-size: .78rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-item__name small { display: block; font-size: .64rem; font-weight: 500; color: var(--lk-faint); }
    .lk-item__qty { flex-shrink: 0; font-size: .72rem; font-weight: 800; color: var(--lk-muted); }
    .lk-item__member { flex-shrink: 0; padding: 0 .3rem; border-radius: .3rem; font-size: .6rem; font-weight: 900; color: #1c1917; background: var(--lk-gold); }
    .lk-steps { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: .45rem; counter-reset: step; }
    .lk-steps li { counter-increment: step; display: flex; align-items: center; gap: .6rem; font-size: .82rem; color: var(--lk-muted); }
    .lk-steps li::before { content: counter(step); width: 1.4rem; height: 1.4rem; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; font-size: .7rem; font-weight: 800; border: 1px solid var(--lk-line-2); }
    .lk-steps li[data-done] { color: var(--lk-text); }
    .lk-steps li[data-done]::before { content: '✓'; color: #052e16; background: var(--lk-good); border-color: var(--lk-good); }

</style>
