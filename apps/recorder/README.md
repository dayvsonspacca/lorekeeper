# Lorekeeper Recorder

Runs a script in AdventureQuest Worlds and records every message the client sends and every message the server
sends back, so a session can be studied before the protocol is turned into events.

```sh
composer install
AQW_PLAYER=name AQW_PASSWORD=secret bin/record watch --for=30
AQW_PLAYER=name AQW_PASSWORD=secret bin/record level-up --level=12 --for=300
```

The account comes only from the environment; `AQW_SERVER` picks a server. Each run writes one JSON line per message to
`records/<date>-<scenario>.jsonl`, with the time, the way (`sent` or `received`) and the raw message, unpacked when the
server compressed it. The login token is replaced by `<token>`. Ctrl+C stops the run and logs out.
