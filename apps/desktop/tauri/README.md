# Desktop shell

The desktop runs in a Tauri window: the system WebView shows the Laravel app, served by a static PHP binary that the
shell starts as a sidecar.

```
just desktop dev    # a window on apps/desktop, served by the php on your PATH
just desktop php    # the static PHP 8.5 the bundle ships with → tauri/.php/php
just desktop build  # tauri/target/release/bundle/{deb,appimage}
```

`dev` runs with `APP_DEBUG=true` and keeps its data apart from the installed app. `build` stages the app without dev
dependencies into `build/`, and only `tauri.bundle.json` points the bundle at it.

## How the shell talks to the app

- At start it runs `artisan desktop:boot`, then serves `public/` with `php -S` on a free port in its own process group.
- Every 3 seconds it polls `/shell` with a secret only it knows, to refresh the tray, show notifications and reload a window
  that went blank.
- On quit, or on `SIGTERM`/`SIGINT`/`SIGHUP`, it stops the whole group: the server and every bot it launched. A group left
  behind by a crash is stopped on the next start.
- Data lives in the app data folder (`~/.local/share/com.dayvsonspacca.lorekeeper.desktop` on Linux, `dev/` inside it
  for `just desktop dev`). Laravel's bootstrap caches there are dropped whenever a different bundle starts.
