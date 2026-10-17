# Lorekeeper

A toolbox for AdventureQuest Worlds: framework-free PHP packages (`packages/`) and apps built on them (`apps/`).

## Packages and what belongs where

- `core` is the definition of the game as plain value objects, as the author defines them.
- `server` reads what the game server says. It is the author's interpretation of the protocol, not a copy of it:
  name things by what they mean in the game, shape them the way Lorekeeper wants to use them, and hide the
  protocol's quirks. Do not mirror wire field names, command codes or raw numeric values into the API; for example,
  do not back an enum with the integers the server happens to send.
- `client` plays the game: commands, scripts, sessions.
- Layers only point down: `core` ← `server` ← `client` ← apps. `just guard` enforces it.
- Before creating a new concept, discuss with the user which package it belongs to, especially when it could be
  `core`. Do not decide placement alone.

## Code

- No comments. Docblocks carry only annotations: `@param`, `@return`, `@var`, `@template`, `@implements`,
  `@property`, `@throws`, `@mago-expect`. Name things clearly instead of explaining them.
- Classes are `final readonly` unless something forbids it. Value objects extend `NonEmptyString`,
  `PositiveInteger` or `NonNegativeInteger` from `core`.
- Validate input with PSL `Type` and throw `MalformedEventException` when a message cannot be read.
- Follow mago instead of fighting it; when a rule must be broken, use `@mago-expect` on that spot.
- Never write AQW account credentials to a file; pass them through environment variables.

## Tests

- Event tests use real messages captured from the game, trimmed but never made up.
- Each app has its own PHPUnit suite; run it from the app's directory with `vendor/bin/phpunit`.

## Commands

```sh
just install   # dependencies
just test      # package tests
just fmt       # format
just lint
just analyze
just guard     # layer boundaries
```

Run `just fmt`, `just lint`, `just analyze` and the tests before committing.

## Commits

- Work on a branch and commit each finished part.
- Subject: `type(scope): what it does`, lowercase, imperative, no period. The scope is the package or app (`core`,
  `server`, `client`, `wiki`, `desktop`); leave it out when a change spans several. Mark breaking changes with `!`,
  as in `refactor(core)!: ...`.
- Body: `- ` bullets wrapped at about 76 columns, or a short paragraph; a paragraph of context may come before the
  bullets. Describe what changed and why, in plain words.
- End with the `Co-Authored-By` trailer.
