---
name: agent-toolsmith
description: Cut the token bill of AI coding agents by turning code they keep rewriting into tested tools - audit session logs and scratch scripts for repeated commands and scripts (scripts/find_repeats.py), rank them by sessions x tokens, build the top ones as one command each with --dry-run, tests and a clear stop on failure, list them in an AGENT_TOOLS.md catalog, add a «tools first» rule for the agents, and re-audit monthly. Use when agents keep writing the same scripts, when a project burns tokens on routine (merges, restarts, DB queries, screenshots, reports), or when asked to make reusable tools for agents.
---

# Agent toolsmith

A script the agent runs costs only its output in context. A script the agent writes again costs the whole code,
the retries and the debugging - every session. Repeats are the cheapest tokens to save.

## 1. Audit (read-only)

```
python scripts/find_repeats.py --transcripts ~/.claude/projects/<project-folder> \
       [--scripts <scratch folders> <agent run folders>] --days 30 --top 20
```

It reports shell-command shapes used in 3+ sessions (paths, numbers and strings masked, plain reads and output
filters dropped), scripts with identical code in 2+ sessions, script names reused in 3+ sessions, each with
uses and rough tokens. Then look at the top rows yourself:
- **what** repeats (one line), **how often** (sessions = the main measure), **who** writes it, **what it costs**
  (tokens, minutes, the same mistakes again - encoding errors, forgotten steps);
- does a tool **already exist** that the agent did not find? Then the fix is the catalog, not new code;
- one-off edit scripts (`fix_*`, `patch_*`) are not candidates - they are how big edits are done.

Write the audit as a table and pick the top 3-5 with the owner of the project before building.

## 2. Build each tool

- One command, one job, named by the job: `merge_branch <branch>`, `restart_backend`, `db peek "select ..."`.
- `--dry-run` that prints every step it would take; read-only where possible.
- Stops at the first failure and prints what to do next (the exact manual command), never half-finishes silently.
- Safe by default: refuses when work is running (e.g. agents busy before a restart), backs up before writing data,
  never deletes without a flag.
- UTF-8 everywhere (set `PYTHONUTF8=1` in the launcher rather than fixing each script).
- Tests next to it; for tools that touch git or databases, tests on a throwaway copy.
- Replace the old copies: delete the 39 near-identical scripts once the tool covers them.

## 3. The catalog

`AGENT_TOOLS.md` in the repo root (template: `references/AGENT_TOOLS.template.md`): one table per area,
columns **tool | what it does | when | command**. Short. A vague or duplicate row makes the library worse.

## 4. The rule for agents

Add to each agent's instructions (or CLAUDE.md):

> Before writing a helper script, look in `AGENT_TOOLS.md`. Writing the same code a second time means it should
> become a tool: propose it instead of writing it a third time.

## 5. Re-audit monthly

Run the audit again on the last 30 days. New repeats -> new rows; tools nobody uses -> remove them.
Report the before/after: sessions and tokens the top repeats cost last month vs this month.

## A real example (one project, 21 days)

Merging an agent's branch was written by hand in 12 sessions at ~15-25k tokens each; a backend restart in ~20
sessions; encoding retries in 23 sessions; 39 near-identical screenshot scripts (4,464 lines). Four tools
(`merge_branch`, `records task/dedupe`, `restart_backend`, `db peek`) replaced all of it with one command each.

---
Built by ROAMA web studio - https://roama-studio.github.io/
