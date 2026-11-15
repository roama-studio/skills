#!/usr/bin/env python3
"""Self-check of the order bot before upload: the menu JSON, callback_data sizes, the config placeholders and
the webhook's safety lines. Standard library only.

Usage: python check_kit.py <folder>   (the folder with webhook.php and the menu/config files; defaults to here)
Exit code 0 = ok, 1 = problems (printed one per line).
"""
import json
import re
import sys
from pathlib import Path


def menu_problems(menu: dict) -> list:
    out = []
    if not isinstance(menu.get("categories"), list) or not menu["categories"]:
        return ["menu: no categories"]
    seen = set()
    for cat in menu["categories"]:
        if len(("c:" + str(cat.get("id", ""))).encode()) > 64:
            out.append(f"menu: category id too long for callback_data: {cat.get('id')}")
        for item in cat.get("items", []):
            iid = str(item.get("id", ""))
            if not iid:
                out.append(f"menu: an item without id in {cat.get('id')}")
            if iid in seen:
                out.append(f"menu: duplicate item id {iid}")
            seen.add(iid)
            if len(("a:" + iid).encode()) > 64:
                out.append(f"menu: item id too long for callback_data: {iid}")
            if not isinstance(item.get("price"), (int, float)) or item["price"] < 0:
                out.append(f"menu: item {iid} has no valid price")
    booking = menu.get("booking") or {}
    if booking.get("enabled"):
        for t in booking.get("times", []):
            if len(("t:" + str(t)).encode()) > 64:
                out.append(f"menu: booking time too long: {t}")
        if not booking.get("times"):
            out.append("menu: booking enabled but no times")
    if not menu.get("currency"):
        out.append("menu: no currency")
    return out


TOKEN = re.compile(r"\b\d{6,12}:[A-Za-z0-9_-]{30,}\b")


def config_problems(text: str, example: bool) -> list:
    out = []
    if TOKEN.search(text) and example:
        out.append("config.example.php contains something that looks like a real bot token")
    if not example:
        if "PASTE_THE_BOT_TOKEN_HERE" in text:
            out.append("config: the token is still the placeholder")
        if "PASTE_A_RANDOM_SECRET_HERE" in text:
            out.append("config: the secret is still the placeholder")
    return out


def webhook_problems(text: str) -> list:
    out = []
    for needle, why in (("HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN", "the secret header check"),
                        ("hash_equals", "a constant-time secret comparison"),
                        ("obot_redact", "token redaction in logs"),
                        ("answerCallbackQuery", "answering button presses")):
        if needle not in text:
            out.append(f"webhook.php: missing {why} ({needle})")
    if TOKEN.search(text):
        out.append("webhook.php contains something that looks like a bot token")
    return out


def check(folder: Path) -> list:
    problems = []
    hook = folder / "webhook.php"
    problems += webhook_problems(hook.read_text(encoding="utf-8")) if hook.exists() else ["webhook.php not found"]
    for name, example in (("config.example.php", True), ("order-bot-config.php", False)):
        f = folder / name
        if f.exists():
            problems += config_problems(f.read_text(encoding="utf-8"), example)
    menus = [folder / "order-bot-menu.json", folder / "menu.example.json"]
    menu_file = next((m for m in menus if m.exists()), None)
    if not menu_file:
        problems.append("no menu JSON found")
    else:
        try:
            problems += menu_problems(json.loads(menu_file.read_text(encoding="utf-8")))
        except ValueError as e:
            problems.append(f"{menu_file.name}: not valid JSON ({e})")
    return problems


if __name__ == "__main__":
    folder = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).parent
    found = check(folder)
    print("\n".join(found) if found else "ok")
    sys.exit(1 if found else 0)
