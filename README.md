# Lorekeeper

[![CI](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml/badge.svg)](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-report-blue)](https://dayvsonspacca.github.io/lorekeeper/)

A self-updating knowledge base and wiki for [AdventureQuest Worlds](https://www.aq.com/).

Lorekeeper learns about the game straight from the game itself: it talks to the game servers like the client does, reads the
public travel map and decompiles map files. Everything it learns is stored as small, dated **facts** ("this monster has 970
health", "this NPC in Battleon offers these quests", "this gray arrow leads to Greenguard West") and a wiki is generated on
top of them.

> Lorekeeper is an unofficial fan project. It is not affiliated with, endorsed by or connected to Artix Entertainment.
> AdventureQuest Worlds and all related names and assets are trademarks of Artix Entertainment, LLC.

## What it knows

- **Maps**: id, file, monsters, the gray arrows you can walk through (with their "Go to …?" prompt) and the regions of the
  travel map they belong to.
- **NPCs**: every `!` placed on a map, with the name and title drawn on its popup, the quests it offers, the shops it runs and
  the maps it can send you to.
- **Quests**: description, requirements, items to turn in and rewards.
- **Shops**: what they sell and for how much, including the items merge shops ask in exchange.
- **Items**: name, type, slot, price and description, plus an in-browser preview of weapons, helms, capes and armors worn by a
  silhouette character.
- **Monsters, classes and factions**.

## How it is built

The code is split into packages with enforced boundaries (`just guard`):

```
core ← protocol ← miner → persistence ← wiki
```

| Package | Role |
| --- | --- |
| `core` | The game's concepts as value objects, and the `Fact` that ties a subject to a value. |
| `protocol` | Commands sent to the game server and the events parsed from its messages. |
| `persistence` | SQLite fact store with observation history and explicit, stable codecs. |
| `miner` | Logs in, listens and runs routines that collect facts (travel map, maps, NPCs, quests, shops). |
| `wiki` | JSON API over the fact store and a React frontend, with Flash previews rendered by [Ruffle](https://ruffle.rs/). |

## Requirements

- PHP 8.4 or 8.5 with `pdo_sqlite`, `curl` and `xmlreader`
- [Composer](https://getcomposer.org/) and [just](https://github.com/casey/just)
- Java, to decompile map files (the [JPEXS decompiler](https://github.com/jindrapetrik/jpexs-decompiler) is downloaded on first use)
- Node.js, to build the wiki frontend
- Optional: the [Apache Flex SDK](https://flex.apache.org/), to rebuild the Flash previews (`just wiki-swf`)

## Getting started

```sh
just install      # PHP dependencies
just test         # run the test suite
```

### Mining

The miner needs an AQW account. The password is read from `AQW_PASSWORD` or asked for without echo; it is never stored.

```sh
just mine travel-map                                   # regions and maps from the public travel map
just mine discover-maps   --user=Name [--limit=50]     # visit maps and analyze their files
just mine discover-quests --user=Name                  # ask NPCs for the quests found on maps
just mine discover-shops  --user=Name                  # open the shops found on maps
just mine explore-map     --user=Name --map=battleontown   # do all of the above for one map
just mine listen          --user=Name [--duration=60]  # just listen and record what the server says
```

Every routine paces its requests, stops cleanly on `Ctrl+C` and logs out. Facts go to `lorekeeper.sqlite`.

### Wiki

```sh
just wiki-build   # build the frontend
just wiki         # serve it on http://127.0.0.1:8765
just wiki-dev     # API on 8765 and Vite with hot reload on 5173
```

The wiki proxies game files (monsters, items, armors) from the game servers when you open a preview and caches them under
`var/`. The avatar preview embeds the character skeleton of the current game client, so it is built locally with
`just wiki-swf` and never committed.

## Quality

```sh
just lint      # mago lint
just analyze   # mago static analysis
just fmt       # mago formatter
just guard     # layer boundaries
just coverage  # tests with coverage (pcov or Xdebug), HTML report in var/coverage
```

CI runs all of the above on every push, plus the tests on PHP 8.4 and a frontend build. Each push to `main` publishes
the [coverage report](https://dayvsonspacca.github.io/lorekeeper/) to GitHub Pages.

## License

[MIT](LICENSE)
