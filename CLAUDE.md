# Lorekeeper

A toolbox for AdventureQuest Worlds: framework-free PHP packages (`packages/`) and apps built on them (`apps/`).

## Working together

- Commit and push only when I say so. Open a branch only when I ask.

## Code

### Rules

- No comments. Docblocks carry only annotations (`@param`, `@return`, `@var`, `@template`, `@throws`, `@mago-expect`...)
- Layers only point down: `core` ← `server` ← `client` ← `bot` ← apps.
- Never write AQW accounts or credentials to a file.

### Testing

- Ensure 100% coverage on `packages`.
- Always write a regression test when a bug is found.