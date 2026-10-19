# Lorekeeper

[![CI](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml/badge.svg)](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-report-blue)](https://dayvsonspacca.github.io/lorekeeper/)

A toolbox for [AdventureQuest Worlds](https://www.aq.com/): apps that play the game, built on framework-free PHP
packages that model the game, read what its server says and play it.

> Lorekeeper is an unofficial fan project. It is not affiliated with, endorsed by or connected to Artix Entertainment.
> AdventureQuest Worlds and all related names and assets are trademarks of Artix Entertainment, LLC.

## Apps

### `desktop`

Keeps several AQW characters playing on your own machine:

- add your accounts and start a bot on any of them;
- queue tasks for each bot and follow how each one is going;
- see every bot on a dashboard and read the log of what it does;
- get told when a task is done or fails;
- keep the bots playing from the tray, with the window closed;
- pick up the tasks a bot left unfinished.

## Packages

Framework-free libraries, each one building on the ones before it:

| Package | What it does |
|---|---|
| [`core`](packages/core) | the game as plain values: players, classes, items, maps, monsters, quests, shops |
| [`server`](packages/server) | reads what the game server says into typed events |
| [`client`](packages/client) | logs in, enters the game, keeps your character up to date and runs scripts |
| [`bot`](packages/bot) | bots that play on their own: they join maps, walk to spots and fight |

## Getting started

Requirements: PHP 8.4 or 8.5 with `curl`, `dom` and `zlib`, [Composer](https://getcomposer.org/) and
[just](https://github.com/casey/just).

```sh
just install      # dependencies
just test         # tests
just lint         # also: just fmt, just analyze, just guard
```

## License

[MIT](LICENSE)
