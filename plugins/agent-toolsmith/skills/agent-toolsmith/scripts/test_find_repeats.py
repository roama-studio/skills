"""Self-test: python -m unittest test_find_repeats (run inside this scripts/ folder). Uses fake transcripts only."""
import json
import tempfile
import unittest
from pathlib import Path

import find_repeats as fr


def _session(folder: Path, name: str, commands, scripts=()):
    lines = []
    for i, cmd in enumerate(commands):
        tid = f"{name}-{i}"
        lines.append({"message": {"role": "assistant", "content": [
            {"type": "tool_use", "id": tid, "name": "Bash", "input": {"command": cmd}}]}})
        lines.append({"message": {"role": "user", "content": [
            {"type": "tool_result", "tool_use_id": tid, "content": "x" * 400}]}})
    for j, (path, body) in enumerate(scripts):
        lines.append({"message": {"role": "assistant", "content": [
            {"type": "tool_use", "id": f"{name}-w{j}", "name": "Write", "input": {"file_path": path, "content": body}}]}})
    (folder / f"{name}.jsonl").write_text("\n".join(json.dumps(l) for l in lines) + "\nnot json\n", encoding="utf-8")


class FindRepeats(unittest.TestCase):
    def test_signature_masks_paths_numbers_and_strings(self):
        a = fr.signature('cd /a/b && git worktree remove --force /tmp/wt/agent-12 && echo "done"')
        b = fr.signature("cd /x/y && git worktree remove --force /tmp/wt/agent-99")
        self.assertEqual(a, b)
        self.assertEqual(a, "git worktree remove")

    def test_plain_reads_and_output_filters_are_not_tool_candidates(self):
        self.assertEqual(fr.signature("cat a.txt | head -5"), "")
        self.assertEqual(fr.signature('grep -n "x | y" src/app.py && git status'), "")
        self.assertEqual(fr.signature("PYTHONUTF8=1 npm test 2>&1 | tail -3"), "npm test")

    def test_command_in_three_sessions_is_reported_with_tokens(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            for n in range(3):
                _session(root, f"s{n}", [f"curl -s http://localhost:{8000 + n}/health", "ls"])
            _session(root, "s3", ["pytest -q tests/test_a.py"])
            rows = fr.rank(fr.read_transcripts(root, 0), min_sessions=3, top=10)
            health = [r for r in rows if r["what"].startswith("curl")][0]
            self.assertEqual(health["sessions"], 3)
            self.assertGreater(health["tokens"], 250)  # 3 x (command + 400 chars of output) / 4
            self.assertFalse([r for r in rows if r["what"].startswith("pytest")])

    def test_same_script_written_twice_is_a_tool_candidate(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            body = "import sys\nprint(sys.argv)\n"
            _session(root, "a", [], [("/w/a/merge.py", body)])
            _session(root, "b", [], [("/w/b/merge.py", body.replace("\n", "\n\n"))])
            rows = fr.rank(fr.read_transcripts(root, 0), min_sessions=3, top=10)
            self.assertTrue([r for r in rows if r["kind"] == "script" and r["sessions"] == 2])

    def test_script_folders_group_by_name_and_skip_libraries(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            for n in range(3):
                d = root / f"run-{n}"
                d.mkdir()
                (d / "shots.mjs").write_text(f"// variant {n}\n", encoding="utf-8")
                (d / "node_modules").mkdir()
                (d / "node_modules" / "lib.js").write_text("same", encoding="utf-8")
            rows = fr.rank(fr.read_script_folders([root], 0), min_sessions=3, top=10)
            self.assertEqual([r["what"] for r in rows if r["kind"] == "script-name"], ["name:shots.mjs"])
            self.assertFalse([r for r in rows if "lib.js" in r["what"]])

    def test_cli_prints_a_markdown_table(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            for n in range(3):
                _session(root, f"s{n}", ["docker compose restart api"])
            self.assertEqual(fr.main(["--transcripts", str(root), "--days", "1"]), 0)


if __name__ == "__main__":
    unittest.main()
