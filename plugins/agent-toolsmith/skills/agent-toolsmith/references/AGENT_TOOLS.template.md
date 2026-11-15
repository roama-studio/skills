# Agent tools

Ready commands for jobs we used to rewrite by hand. **Before writing a helper script, look here first.**
Writing the same code a second time means it should become a tool: propose it and add a row here once it exists.

Commands run from the repo root unless the row says otherwise. Every tool has `--dry-run`, stops at the first
failure with the next manual step, and installs nothing.

## Git and releases

| Tool | What it does | When | Command |
|---|---|---|---|
| merge_branch | rebase an agent branch, run the tests it touched, merge fast-forward, push, clean the worktree | an agent branch is ready | `python -m tools.merge_branch <branch> [--dry-run]` |

## Services

| Tool | What it does | When | Command |
|---|---|---|---|
| restart_backend | refuses while jobs run, stops the server, starts it, waits for `/health` | after backend changes | `python -m tools.restart_backend [--dry-run]` |

## Data

| Tool | What it does | When | Command |
|---|---|---|---|
| db_peek | read-only query, UTF-8 table output | checking a table or a value | `python -m tools.db_peek schema <table>` / `q "select ..."` |

## Pictures and reports

| Tool | What it does | When | Command |
|---|---|---|---|
| print_kit | HTML -> PNG, or an A4 PDF checked to be exactly N pages | a printable sheet, a picture of a table | `python -m tools.print_kit pdf page.html out.pdf --pages 1` |
