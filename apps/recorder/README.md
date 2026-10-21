# Lorekeeper Recorder

Runs a script in AdventureQuest Worlds and records every message the client sends and every message the server
sends back, so a session can be studied before the protocol is turned into events.

```sh
composer install
AQW_PLAYER=name AQW_PASSWORD=secret bin/record scripts/Watch.php --for=30
```
