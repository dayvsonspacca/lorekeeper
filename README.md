# Lorekeeper

[![CI](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml/badge.svg)](https://github.com/dayvsonspacca/lorekeeper/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-report-blue)](https://dayvsonspacca.github.io/lorekeeper/)

A toolbox for [AdventureQuest Worlds](https://www.aq.com/): framework-free PHP packages that model the game, read what
its server says and play it.

> Lorekeeper is an unofficial fan project. It is not affiliated with, endorsed by or connected to Artix Entertainment.
> AdventureQuest Worlds and all related names and assets are trademarks of Artix Entertainment, LLC.

## Packages

### `core`

Describes the game. It names and validates what you meet in AQW: items and what kind they are, maps, monsters,
quests with what they ask for and what they give, shops and their prices, classes, factions and ranks.

### `server`

Reads the game server. It turns the raw messages the server sends into typed events: where you landed and who and
what is there, the quests and shops you opened, your inventory, class and equipment changes, login results and
server notices. When a message does not read as expected, it says so instead of guessing.

### `client`

Plays the game. It logs in, picks a server, connects and runs scripts: you describe what a bot does as a sequence of
steps, such as "send this and wait for that", and the client plays it against the live server, moving on only when
the server answers, stopping whenever you ask and reporting a dropped connection as a result.

```php
$player = new PlayerName('Hero');
$login = new LoginClient()->login($player, getenv('AQW_PASSWORD'));
$client = new Client(Connection::open(ServerChoice::pick($login->servers)->unwrap()));

$client->run(new EnterGameScript(new GameLogin($player, $login->token, new ClientVersion('5.1'))));
$client->run(new JoinMapScript($player, new MapName('forest'), Option\none()));
```

Ready-made scripts enter the game, move between maps and houses, ask for quests, open shops and idle; they compose
into bigger ones.

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
