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

Plays the game. Commands are the small parts, one message to the server each; a script is a whole flow that sends
commands and moves on only when the server answers, stopping whenever you ask and reporting a dropped connection as
a result.

```php
$joined = yield $this->send(new JoinMapCommand($player, new MapName('forest'), Option\none()))
    ->awaits(Duration::seconds(15), MapJoins::class, MapRefusals::class);

if (MapRefusals::contains($joined)) {
    throw new RuntimeException("Could not join forest: {$joined->summary()}");
}
```

Ready-made scripts enter the game and level a character up, telling how far along they are.

To play, log in to get a `Client` already in the game, then run scripts on it. The client keeps the character up to
date with what the server says and tells an `Observer` about every message and step; a `Narrator` in front of the
observer turns those steps into plain-word log lines. What to play next, and when to stop, stays with the app.

```php
$client = Login::enter(new Credentials($player, getenv('AQW_PASSWORD')), new Narrator($observer));
$outcome = Outcome::of($client->run(new LevelUpScript(new PlayerLevel(50))));
Login::leave($client);
```

## Apps

### `desktop`

A desktop app, built with [NativePHP](https://nativephp.com/), to keep several AQW characters playing on your own
machine. There is no login: it only talks to the game. Add your accounts, start a bot on any of them and queue tasks
for it; each bot runs in its own process, the log shows what it does and each task reports how it is going. Data is
kept in SQLite.

```sh
cd apps/desktop
composer install && php artisan migrate
php artisan native:install   # once, sets up Electron
php artisan native:run       # or: php artisan serve, to use it in a browser while developing
```

### `wiki`

A structured wiki of the game, filled by bots that stay in the game and record what the server says.

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
