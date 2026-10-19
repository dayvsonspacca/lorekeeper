# Lorekeeper Recorder

Runs a script in AdventureQuest Worlds and records every message the client sends and every message the server
sends back, so a session can be studied before the protocol is turned into events.

```sh
composer install
AQW_PLAYER=name AQW_PASSWORD=secret bin/record scripts/Watch.php --for=30
```

The recorder knows no script: it plays whatever script the given PHP file returns, for `--for` seconds (60 by default)
or until Ctrl+C. A file is as small as `return new LevelUpScript(new PlayerLevel(12));`. Keep yours in `scripts/`,
which git ignores.

The account comes only from the environment; `AQW_SERVER` picks a server. Each run writes one JSON line per message to
`records/<date>-<script>.jsonl`, with the time, the way (`sent` or `received`) and the raw message, unpacked when the
server compressed it. The login token is replaced by `<token>`.
