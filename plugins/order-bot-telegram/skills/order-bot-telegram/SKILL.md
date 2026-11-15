---
name: order-bot-telegram
description: Build a Telegram bot for orders or table bookings for a small business that runs on ordinary shared PHP hosting - one PHP webhook file (kit/), no database, no server, no framework; setWebhook with a secret token, the conversation on inline buttons (menu -> basket -> name -> phone -> confirm), every order to the owner's Telegram and optionally a Google Sheet row, the token outside the web root and never logged, plus a test checklist. Use when asked for an order bot, a booking bot, a Telegram shop for a cafe, restaurant, salon or shop, or a cheap bot without a server.
---

# Order and booking bot in Telegram (PHP, shared hosting)

A guest taps buttons in the business's own bot, leaves a name and a phone, confirms; the owner gets the order in
Telegram at once (and as a row in a Google Sheet if wanted). Runs on any hosting with PHP 7.4+, curl and https -
the same hosting as the business's website.

Out of scope unless agreed separately: payments in the bot, delivery tracking, stock or POS integrations,
mailings to guests, other messengers.

## What the business gives

1. Their own bot from @BotFather on THEIR Telegram account (`/newbot`) -> the token. Never create a client's bot
   on your account.
2. The menu or the service list: names, prices, opening hours (up to ~30 items reads well on buttons).
3. Who receives orders: the owner's or admin's chat id (they press Start in the bot, then `getUpdates` once
   shows the id).
4. Hosting with PHP 7.4+ and https. (Google Apps Script cannot be the webhook: it answers with a redirect, which
   Telegram counts as an error.)
5. Optional: a Google account for the sheet.

## Build it from `kit/` - never from zero

| File | What |
|---|---|
| `kit/webhook.php` | the webhook: checks the secret header, runs the conversation, sends the order |
| `kit/config.example.php` | token, secret, owner chat ids, optional sheet URL and secret, texts - the real config lives OUTSIDE the web root |
| `kit/menu.example.json` | categories, items with prices, booking times |
| `kit/README.md` | setup step by step, setWebhook, the test |
| `kit/check_kit.py` | a self-check of the menu and config before upload (`python kit/check_kit.py kit/`) |

The optional sheet receives a JSON POST (`secret, id, time, site, form, page, fields[{label, value}], source`).
A ready Apps Script for such a sheet: `setup/leads-apps-script.gs` in https://github.com/roama-studio/roama-wp-theme
(GPL - link to it, do not copy it into an MIT project).

## The conversation

`/start` -> greeting + categories (+ «Забронювати» if booking is on) -> item buttons («+» adds, the basket line
updates) -> «Далі» -> name -> phone (the «Поділитися номером» button or text, 9-15 digits) -> summary with
«Підтвердити» / «Змінити» -> «Дякуємо, замовлення прийнято» + hours. Each guest's state is a small JSON file per
chat outside the web root; it expires after 2 hours.

## Telegram rules

- `setWebhook` with `secret_token`; requests without the matching `X-Telegram-Bot-Api-Secret-Token` header get 403.
- Answer every `callback_query` (`answerCallbackQuery`), else the button keeps spinning.
- `callback_data` is at most 64 bytes - keep menu ids short (the self-check enforces it).
- Write to a guest only after they pressed Start. No mailings.
- One line before asking the phone: why it is needed.
- The token is the client's secret: only in the config; never in a log, an error text, a commit, a message or a
  screenshot. The kit redacts it from every logged error.

## Test checklist (before the client sees it)

1. `php -l webhook.php` (or the hosting's PHP check); `python kit/check_kit.py <folder with config and menu>`.
2. A request without the secret header -> 403; with it -> 200 and `ok`.
3. A real run from a test Telegram account: the whole flow, a wrong phone, «Змінити», two guests at once.
4. The order reached every owner chat (and is one row in the sheet).
5. A wrong owner chat id -> the guest still gets «прийнято», the error is in the log without the token.
6. The «Замовити в Telegram» button on the site: `https://t.me/<bot>?start=site`.
7. A one-page guide for the client: change the menu, change who gets orders, «бот мовчить» -> `getWebhookInfo`.

---
Built by ROAMA web studio - https://roama-studio.github.io/
