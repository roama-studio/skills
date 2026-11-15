# Order bot kit

A small Telegram bot for orders or bookings: one PHP file, no WordPress, no database.
Read `../SKILL.md` first (what the client gives, the test checklist). Self-check: `python check_kit.py .`

## Setup

1. The client creates the bot in BotFather on THEIR account and gives the token.
2. On the hosting (PHP 7.4+, curl, https), with `public_html` as the web root:
   - `public_html/bot/webhook.php` - the only file reachable by URL;
   - in the home folder, OUTSIDE `public_html`: `order-bot-config.php` (from
     `config.example.php`, `chmod 600`) and `order-bot-menu.json` (the client's menu, from
     `menu.example.json`); the guests' states go to `order-bot-state/` there too.
   - The webhook finds the config at `dirname(__DIR__, 2)`; another layout -> set the
     environment variable `ORDER_BOT_CONFIG` to its path. Check in a browser that the config
     is NOT reachable by any URL.
3. Fill `order-bot-config.php`: token, a random `secret`, the owner chat ids (the owner presses Start,
   then `https://api.telegram.org/bot<token>/getUpdates` once shows the id - never paste the
   token into a chat or a letter), the sheet URL and secret (or empty).
4. Register the webhook once (from a terminal, the token from the config):
   `curl -s "https://api.telegram.org/bot<token>/setWebhook" -d url=https://<site>/bot/webhook.php -d secret_token=<secret> -d 'allowed_updates=["message","callback_query"]'`
   then `getWebhookInfo` must show the URL, `pending_update_count` 0 and no `last_error`.
5. Run the test checklist of the skill. The «Замовити в Telegram» button links to
   `https://t.me/<bot_username>?start=site`.

## Never

- The token or the config in git, a letter, a log or a screenshot.
- A bot created on our account for a client, or a message to a guest who did not press Start.
- Payments, mailings or other messengers without a separate agreement with the client.
