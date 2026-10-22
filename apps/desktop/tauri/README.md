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

On GitHub Actions, `.github/workflows/desktop-release.yml` runs the same `build` on Ubuntu 22.04, so the bundle works on
older glibc. Pushing a `desktop-v<version>` tag publishes both files as a release of that version, a prerelease when the
version has a suffix like `-alpha`; running the workflow by hand only keeps them as an artifact of the run.

## Linux only, for now

Everything that depends on the system sits behind the `Platform` trait in `src/platform/`, implemented by
`src/platform/linux.rs`: the process group, `PR_SET_PDEATHSIG`, `/proc`, the signals and the notifications. Building on
another system stops with a `compile_error!`; a port adds `src/platform/<os>.rs`, a `tauri.<os>.conf.json` with its
bundle targets and a `scripts/<os>/php.sh` for its static PHP. The PHP side launches bots with `nohup` and `posix_kill`,
so `ProcessLauncher` needs its own port too.

## How the shell talks to the app

- At start it runs `artisan desktop:boot`, then serves `public/` with `php -S` on a free port in its own process group.
- Every 3 seconds it polls `/shell` with a secret only it knows, to refresh the tray, show notifications and reload a window
  that went blank.
- On quit, or on `SIGTERM`/`SIGINT`/`SIGHUP`, it stops the whole group: the server and every bot it launched. A group left
  behind by a crash is stopped on the next start.
- Data lives in the app data folder (`~/.local/share/com.dayvsonspacca.lorekeeper.desktop` on Linux, `dev/` inside it
  for `just desktop dev`). Laravel's bootstrap caches there are dropped whenever a different bundle starts.
