<style>
    :root {
        --lk-ink: #070a12;
        --lk-panel: #0e1322;
        --lk-panel-2: #131a2c;
        --lk-line: rgba(148, 163, 184, .13);
        --lk-line-2: rgba(148, 163, 184, .24);
        --lk-text: #e6ebf5;
        --lk-muted: #8b96ab;
        --lk-faint: #5b6579;
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
    .lk-hue { --lk-hue: oklch(74% .16 var(--h, 70)); --lk-hue-deep: oklch(38% .12 var(--h, 70)); }

    .lk-realm { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .lk-tile { position: relative; overflow: hidden; padding: .9rem 1rem; border-radius: 1rem; background: linear-gradient(160deg, var(--lk-panel-2), var(--lk-ink)); border: 1px solid var(--lk-line); }
    .lk-tile::before { content: ''; position: absolute; inset: -40% -10% auto auto; width: 7rem; height: 7rem; border-radius: 50%; background: radial-gradient(circle, color-mix(in oklab, var(--tone) 35%, transparent), transparent 70%); }
    .lk-tile__label { display: flex; align-items: center; gap: .4rem; color: var(--lk-muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 600; }
    .lk-tile__label svg { width: .95rem; height: .95rem; color: var(--tone); }
    .lk-tile__value { margin-top: .3rem; font-size: 1.6rem; font-weight: 800; letter-spacing: -.02em; background: linear-gradient(180deg, #fff, color-mix(in oklab, var(--tone) 70%, white)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .lk-tile__hint { font-size: .72rem; color: var(--lk-faint); }

    .lk-deck { display: grid; grid-template-columns: repeat(auto-fill, minmax(21rem, 1fr)); gap: 1rem; }
    .lk-plate { position: relative; display: flex; flex-direction: column; gap: .9rem; padding: 1.1rem 1.15rem 1rem; border-radius: 1.25rem; overflow: hidden; isolation: isolate;
        background: radial-gradient(120% 90% at 0% 0%, color-mix(in oklab, var(--lk-hue) 20%, transparent), transparent 60%), linear-gradient(180deg, var(--lk-panel-2), var(--lk-ink));
        border: 1px solid var(--lk-line); box-shadow: 0 18px 40px -24px rgba(0, 0, 0, .9); transition: transform .25s ease, border-color .25s ease; }
    .lk-plate:hover { transform: translateY(-2px); border-color: var(--lk-line-2); }
    .lk-plate[data-state="fighting"] { border-color: color-mix(in oklab, var(--lk-hue) 45%, transparent); animation: lk-breathe 2.6s ease-in-out infinite; }
    .lk-plate[data-state="dead"] > * { filter: grayscale(.9) brightness(.75); }
    .lk-plate[data-state="dead"]::after { content: 'FALLEN'; position: absolute; top: 1.35rem; right: -2.6rem; transform: rotate(38deg); padding: .2rem 3rem; background: linear-gradient(90deg, #7f1d1d, #b91c1c); color: #fee2e2; font-size: .62rem; font-weight: 900; letter-spacing: .3em; box-shadow: 0 4px 12px rgba(0, 0, 0, .5); }
    .lk-plate[data-online="false"] { background: linear-gradient(180deg, #10141f, #0a0d15); }
    .lk-plate[data-online="false"] .lk-crest { filter: grayscale(1) brightness(.7); }
    @keyframes lk-breathe { 50% { box-shadow: 0 0 0 1px color-mix(in oklab, var(--lk-hue) 35%, transparent), 0 0 34px -8px color-mix(in oklab, var(--lk-hue) 70%, transparent), 0 18px 40px -24px rgba(0, 0, 0, .9); } }

    .lk-head { display: flex; gap: .9rem; align-items: center; }
    .lk-head__who { flex: 1; min-width: 0; }
    .lk-head__name { font-size: 1.12rem; font-weight: 800; letter-spacing: -.01em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lk-head__line { display: flex; align-items: center; gap: .4rem; color: var(--lk-muted); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-head__line svg { width: .85rem; height: .85rem; flex-shrink: 0; }

    .lk-crest { position: relative; flex-shrink: 0; width: 3.6rem; height: 3.6rem; border-radius: 50%; display: grid; place-items: center;
        background: conic-gradient(var(--lk-hue) calc(var(--p, 0) * 1%), rgba(148, 163, 184, .14) 0); box-shadow: 0 0 22px -6px color-mix(in oklab, var(--lk-hue) 70%, transparent); transition: background .6s; }
    .lk-crest__face { width: calc(100% - 7px); height: calc(100% - 7px); border-radius: 50%; display: grid; place-items: center; font-size: 1.35rem; font-weight: 900; color: #fff; text-shadow: 0 2px 6px rgba(0, 0, 0, .6);
        background: radial-gradient(circle at 32% 26%, color-mix(in oklab, var(--lk-hue) 85%, white), var(--lk-hue-deep) 72%); border: 2px solid var(--lk-ink); }
    .lk-crest__level { position: absolute; bottom: -.45rem; left: 50%; transform: translateX(-50%); min-width: 1.6rem; padding: .05rem .4rem; border-radius: 999px; text-align: center; font-size: .66rem; font-weight: 900; color: #fff; background: var(--lk-ink); border: 1px solid var(--lk-hue); }
    .lk-crest--lg { width: 5.5rem; height: 5.5rem; }
    .lk-crest--lg .lk-crest__face { font-size: 2.1rem; width: calc(100% - 9px); height: calc(100% - 9px); }
    .lk-crest--lg .lk-crest__level { font-size: .8rem; bottom: -.55rem; padding: .1rem .55rem; }

    .lk-pips { display: inline-flex; gap: 2px; vertical-align: middle; }
    .lk-pips i { width: .42rem; height: .42rem; transform: rotate(45deg); background: rgba(148, 163, 184, .22); border-radius: 1px; }
    .lk-pips i[data-on] { background: var(--lk-hue); box-shadow: 0 0 6px var(--lk-hue); }

    .lk-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; border: 1px solid transparent; }
    .lk-chip svg { width: .85rem; height: .85rem; }
    .lk-chip[data-state="fighting"] { color: #fecaca; background: rgba(239, 68, 68, .14); border-color: rgba(239, 68, 68, .35); }
    .lk-chip[data-state="fighting"] svg { animation: lk-clash 1.2s ease-in-out infinite; }
    .lk-chip[data-state="idle"] { color: #bbf7d0; background: rgba(34, 197, 94, .12); border-color: rgba(34, 197, 94, .3); }
    .lk-chip[data-state="dead"] { color: #e5e7eb; background: rgba(127, 29, 29, .5); border-color: rgba(248, 113, 113, .4); }
    .lk-chip[data-state="away"] { color: #ddd6fe; background: rgba(139, 92, 246, .15); border-color: rgba(139, 92, 246, .35); }
    .lk-chip[data-state="entering"] { color: #fde68a; background: rgba(245, 158, 11, .12); border-color: rgba(245, 158, 11, .35); }
    .lk-chip[data-state="entering"] svg { animation: lk-spin 1.2s linear infinite; }
    .lk-chip[data-state="offline"] { color: var(--lk-muted); background: rgba(148, 163, 184, .08); border-color: var(--lk-line); }
    @keyframes lk-clash { 0%, 100% { transform: rotate(0); } 40% { transform: rotate(-14deg) scale(1.1); } 60% { transform: rotate(10deg); } }

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
    [data-kind="health"][data-low] .lk-bar__fill { animation: lk-low .9s ease-in-out infinite; }
    @keyframes lk-low { 50% { filter: brightness(1.6) saturate(1.3); } }
    .lk-meters--lg .lk-bar { height: .95rem; }
    .lk-meters--lg .lk-meter__label { font-size: .78rem; }
    .lk-meter--compact .lk-bar { height: .32rem; }
    .lk-meter--compact .lk-bar::after { display: none; }
    .lk-feed { scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, .25) transparent; }

    .lk-quest { display: flex; align-items: center; gap: .8rem; padding: .7rem .8rem; border-radius: .9rem; background: rgba(2, 6, 23, .45); border: 1px solid var(--lk-line); }
    .lk-quest__body { flex: 1; min-width: 0; }
    .lk-quest__title { font-size: .85rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lk-quest__meta { font-size: .72rem; color: var(--lk-muted); }

    .lk-ring { flex-shrink: 0; }
    .lk-ring__track { fill: none; stroke: rgba(148, 163, 184, .15); stroke-width: 3.2; }
    .lk-ring__value { fill: none; stroke: var(--lk-hue, var(--lk-gold)); stroke-width: 3.2; stroke-linecap: round; transition: stroke-dasharray .6s ease; filter: drop-shadow(0 0 3px var(--lk-hue, var(--lk-gold))); }
    .lk-ring text { fill: var(--lk-text); font-size: .55rem; font-weight: 800; }

    .lk-foe { display: flex; align-items: center; gap: .6rem; font-size: .76rem; }
    .lk-foe__name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lk-foe__bar { flex: 1; min-width: 4rem; }

    .lk-spark { width: 100%; height: 2.3rem; color: var(--lk-xp); overflow: visible; }
    .lk-pace { display: flex; align-items: end; gap: .8rem; }
    .lk-pace__graph { flex: 1; min-width: 0; }
    .lk-pace__value { text-align: right; line-height: 1.1; }
    .lk-pace__value strong { display: block; font-size: 1.05rem; font-weight: 800; color: var(--lk-xp); }
    .lk-pace__value span { font-size: .66rem; color: var(--lk-faint); text-transform: uppercase; letter-spacing: .08em; }

    .lk-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: auto; border-top: 1px solid var(--lk-line); padding-top: .8rem; }
    .lk-plate[data-state="dead"] .lk-head .lk-chip { visibility: hidden; }
    .lk-calm { font-size: .74rem; color: var(--lk-faint); display: flex; align-items: center; gap: .4rem; }
    .lk-calm svg { width: .9rem; height: .9rem; }

    .lk-stage { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .lk-stage__row { display: grid; gap: 1rem; grid-template-columns: minmax(19rem, 25rem) minmax(0, 1fr); align-items: start; }
    .lk-stage__row--low { grid-template-columns: minmax(0, 1fr) minmax(19rem, 25rem); }
    .lk-stage__row > * { min-width: 0; }
    @media (max-width: 64rem) { .lk-stage__row, .lk-stage__row--low { grid-template-columns: minmax(0, 1fr); } }
    .lk-panel { position: relative; padding: 1.15rem 1.2rem; border-radius: 1.25rem; background: linear-gradient(180deg, var(--lk-panel-2), var(--lk-ink)); border: 1px solid var(--lk-line); box-shadow: 0 20px 50px -30px rgba(0, 0, 0, .9); }
    .lk-panel__title { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .9rem; }
    .lk-panel__title h3 { display: flex; align-items: center; gap: .45rem; font-size: .74rem; letter-spacing: .12em; text-transform: uppercase; color: var(--lk-muted); font-weight: 800; }
    .lk-panel__title h3 svg { width: 1rem; height: 1rem; }
    .lk-hero { background: radial-gradient(110% 70% at 50% 0%, color-mix(in oklab, var(--lk-hue) 26%, transparent), transparent 70%), linear-gradient(180deg, var(--lk-panel-2), var(--lk-ink)); }
    .lk-hero[data-state="fighting"] { animation: lk-breathe 2.6s ease-in-out infinite; }
    .lk-hero[data-state="dead"] .lk-crest { filter: grayscale(1); }
    .lk-hero__top { display: flex; flex-direction: column; align-items: center; text-align: center; gap: .55rem; margin-bottom: 1.1rem; }
    .lk-hero__name { font-size: 1.45rem; font-weight: 900; letter-spacing: -.02em; margin-top: .4rem; }
    .lk-hero__class { font-size: .82rem; color: var(--lk-muted); display: flex; align-items: center; gap: .45rem; }
    .lk-hero__facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-top: 1rem; }
    .lk-fact { padding: .55rem .6rem; border-radius: .75rem; background: rgba(2, 6, 23, .5); border: 1px solid var(--lk-line); text-align: center; }
    .lk-fact strong { display: block; font-size: .98rem; font-weight: 800; }
    .lk-fact span { font-size: .64rem; color: var(--lk-faint); text-transform: uppercase; letter-spacing: .08em; }

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
    .lk-slot__aoe { position: absolute; top: -.4rem; right: -.4rem; padding: 0 .3rem; border-radius: 999px; font-size: .58rem; font-weight: 900; color: #1c1917; background: var(--lk-gold); box-shadow: 0 0 8px rgba(251, 191, 36, .6); }
    .lk-slot[data-locked] { opacity: .3; filter: grayscale(1); }
    .lk-slot__lock { position: absolute; inset: 0; display: grid; place-items: center; }

    .lk-field__head { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .lk-field__map { font-size: 1.6rem; font-weight: 900; letter-spacing: -.02em; line-height: 1; }
    .lk-field__sub { font-size: .78rem; color: var(--lk-muted); margin-top: .3rem; }
    .lk-crowd { display: flex; gap: .35rem; flex-wrap: wrap; justify-content: end; }
    .lk-crowd span { padding: .18rem .5rem; border-radius: 999px; font-size: .7rem; background: rgba(148, 163, 184, .1); border: 1px solid var(--lk-line); color: var(--lk-muted); }
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

    .lk-feed { list-style: none; margin: 0; padding: 0; display: grid; gap: .15rem; max-height: 19rem; overflow-y: auto; }
    .lk-feed li { display: flex; gap: .65rem; align-items: center; padding: .45rem .3rem; border-bottom: 1px solid var(--lk-line); animation: lk-in .5s ease-out; }
    .lk-feed li:last-child { border-bottom: 0; }
    .lk-feed__icon { width: 1.75rem; height: 1.75rem; flex-shrink: 0; border-radius: .55rem; display: grid; place-items: center; background: color-mix(in oklab, var(--tone) 18%, transparent); color: var(--tone); }
    .lk-feed__icon svg { width: .95rem; height: .95rem; }
    .lk-feed__text { flex: 1; min-width: 0; font-size: .8rem; font-weight: 600; }
    .lk-feed__text small { display: block; font-size: .7rem; font-weight: 500; color: var(--lk-muted); }
    .lk-feed__at { font-size: .66rem; color: var(--lk-faint); white-space: nowrap; }
    @keyframes lk-in { from { opacity: 0; transform: translateY(-6px); } }

    .lk-waiting { display: grid; place-items: center; gap: .8rem; padding: 3rem 1rem; text-align: center; color: var(--lk-muted); }
    .lk-waiting__orb { width: 3.5rem; height: 3.5rem; border-radius: 50%; background: conic-gradient(from 0deg, var(--lk-gold), transparent 70%); animation: lk-spin 1.4s linear infinite; mask: radial-gradient(circle, transparent 55%, #000 57%); }
    @keyframes lk-spin { to { transform: rotate(360deg); } }
</style>
