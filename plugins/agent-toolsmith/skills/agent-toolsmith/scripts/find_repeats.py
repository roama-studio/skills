#!/usr/bin/env python3
"""Find code an AI agent keeps rewriting: repeated shell commands across sessions, scripts written again
and again, and how many tokens they cost. Standard library only, read-only.

Usage:
  python find_repeats.py --transcripts ~/.claude/projects/<project-folder> [--scripts DIR ...]
                         [--days 30] [--min-sessions 3] [--top 10] [--json]

--transcripts  a folder of Claude Code session logs (*.jsonl), searched recursively
--scripts      folders with throwaway scripts (scratchpads, run folders), searched one level deep
Token numbers are rough: characters / 4 of the command, the written script and the tool output.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import re
import sys
import time
from collections import defaultdict
from pathlib import Path

SCRIPT_EXT = {".py", ".sh", ".mjs", ".js", ".cjs", ".ts", ".ps1"}
SHELL_TOOLS = {"Bash", "PowerShell", "shell", "bash"}
WRITE_TOOLS = {"Write", "write_file", "create_file"}
SKIP_DIRS = {"node_modules", ".git", ".venv", "venv", "__pycache__", "dist", "build", "vendor"}
MAX_BYTES = 200_000

_PATH = re.compile(r"(?:[A-Za-z]:)?(?:[\\/][^\s\\/'\"]+){2,}")
_NUM = re.compile(r"\b\d+\b")
_QUOTED = re.compile(r"(['\"]).*?\1", re.S)
_ENV = re.compile(r"^(?:[A-Za-z_][A-Za-z0-9_]*=\S*\s+)+")
# Output filters and plain reads say nothing about a missing tool.
FILTERS = {"head", "tail", "wc", "sort", "uniq", "cut", "tr", "less", "more", "true", "cd", "echo", "export", "set"}
READS = {"cat", "ls", "pwd", "grep", "rg", "find", "sed", "awk", "git status", "git diff", "git log", "git show"}


def tokens(chars: int) -> int:
    return chars // 4


def signature(command: str) -> str:
    """The shape of a shell command: programs and sub-commands, with paths, numbers and strings masked.
    Output filters (head, tail, wc ...) are dropped; a command made only of plain reads (cat, grep, git status ...)
    returns "" and is not counted."""
    masked = _QUOTED.sub("STR", command)
    parts = []
    for piece in re.split(r"&&|\|\||;|\||\n", masked):
        piece = _ENV.sub("", re.sub(r"\d?>&?\d?\S*|<\s*\S+", " ", piece).strip())  # redirections out
        piece = _PATH.sub("PATH", piece)
        piece = _NUM.sub("N", piece)
        words = [w for w in piece.split() if not w.startswith("-")][:3]
        if not words or words[0] in FILTERS:
            continue
        parts.append(" ".join(words))
    if all(p.split()[0] in READS or " ".join(p.split()[:2]) in READS for p in parts):
        return ""
    return " && ".join(parts[:4])


def fingerprint(text: str) -> str:
    return hashlib.sha1(re.sub(r"\s+", " ", text).strip().encode("utf-8", "replace")).hexdigest()[:12]


def _content_text(content) -> str:
    if isinstance(content, str):
        return content
    if isinstance(content, list):
        return "".join(c.get("text", "") for c in content if isinstance(c, dict))
    return ""


def read_transcripts(folder: Path, since: float):
    """Yield (session, kind, key, chars, is_use) for shell commands, their output and written scripts."""
    for log in sorted(folder.rglob("*.jsonl")):
        if log.stat().st_mtime < since:
            continue
        session = log.stem
        pending = {}  # tool_use_id -> (kind, key)
        for line in log.read_text(encoding="utf-8", errors="replace").splitlines():
            try:
                entry = json.loads(line)
            except ValueError:
                continue
            message = entry.get("message") if isinstance(entry, dict) else None
            if not isinstance(message, dict) or not isinstance(message.get("content"), list):
                continue
            for block in message["content"]:
                if not isinstance(block, dict):
                    continue
                if block.get("type") == "tool_use":
                    name, data = block.get("name"), block.get("input") or {}
                    if name in SHELL_TOOLS and isinstance(data.get("command"), str):
                        key = signature(data["command"])
                        if key:
                            pending[block.get("id")] = ("command", key)
                            yield session, "command", key, len(data["command"]), True
                    elif name in WRITE_TOOLS and Path(str(data.get("file_path", ""))).suffix in SCRIPT_EXT:
                        body = str(data.get("content", ""))
                        stem = Path(str(data["file_path"])).name
                        yield session, "script", "code:" + fingerprint(body), len(body), True
                        yield session, "script-name", "name:" + stem, 0, True
                elif block.get("type") == "tool_result" and block.get("tool_use_id") in pending:
                    kind, key = pending.pop(block["tool_use_id"])
                    yield session, kind, key, len(_content_text(block.get("content"))), False


def read_script_folders(folders, since: float):
    for root in folders:
        for path in Path(root).rglob("*"):
            if any(part in SKIP_DIRS for part in path.parts) or not path.is_file():
                continue
            if path.suffix not in SCRIPT_EXT or path.name.endswith(".min.js"):
                continue
            stat = path.stat()
            if stat.st_mtime < since or stat.st_size > MAX_BYTES:
                continue
            session = path.parent.name
            body = path.read_text(encoding="utf-8", errors="replace")
            yield session, "script", "code:" + fingerprint(body), len(body), True
            yield session, "script-name", "name:" + path.name, 0, True


def rank(events, min_sessions: int, top: int):
    groups = defaultdict(lambda: {"sessions": set(), "uses": 0, "chars": 0, "kind": ""})
    for session, kind, key, chars, is_use in events:
        g = groups[(kind, key)]
        g["kind"] = kind
        g["sessions"].add(session)
        g["chars"] += chars
        g["uses"] += int(is_use)
    rows = []
    for (kind, key), g in groups.items():
        need = min_sessions if kind != "script" else 2  # identical code twice is already a tool
        if len(g["sessions"]) < need:
            continue
        rows.append({"kind": kind, "what": key, "sessions": len(g["sessions"]), "uses": g["uses"],
                     "tokens": tokens(g["chars"])})
    rows.sort(key=lambda r: (r["sessions"] * max(r["tokens"], 1), r["sessions"]), reverse=True)
    return rows[:top]


def markdown(rows) -> str:
    if not rows:
        return "No repeats above the threshold."
    out = ["| # | kind | what repeats | sessions | uses | ~tokens |", "|---|---|---|---|---|---|"]
    for i, r in enumerate(rows, 1):
        what = r["what"].replace("|", "\\|")
        out.append(f"| {i} | {r['kind']} | `{what}` | {r['sessions']} | {r['uses']} | {r['tokens']:,} |")
    return "\n".join(out)


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--transcripts", type=Path)
    ap.add_argument("--scripts", type=Path, nargs="*", default=[])
    ap.add_argument("--days", type=int, default=30)
    ap.add_argument("--min-sessions", type=int, default=3)
    ap.add_argument("--top", type=int, default=10)
    ap.add_argument("--json", action="store_true")
    a = ap.parse_args(argv)
    if not a.transcripts and not a.scripts:
        ap.error("give --transcripts and/or --scripts")
    since = time.time() - a.days * 86400
    events = []
    if a.transcripts:
        events.extend(read_transcripts(a.transcripts, since))
    events.extend(read_script_folders(a.scripts, since))
    rows = rank(events, a.min_sessions, a.top)
    print(json.dumps(rows, ensure_ascii=False, indent=2) if a.json else markdown(rows))
    return 0


if __name__ == "__main__":
    sys.exit(main())
