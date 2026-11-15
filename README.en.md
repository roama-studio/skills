# ROAMA skills for Claude Code

**Need a website, a bot or AI agents for your business? → [ROAMA web studio](https://roama-studio.github.io/)**
· [Українською](README.md)

Free skills for [Claude Code](https://code.claude.com), taken from the real work of the ROAMA web studio.
Each pack is its own plugin: install only what you need.

| Pack | What it does |
|---|---|
| `wp-agency-skills` | a classic WordPress theme from a static site (Secure Custom Fields, Contact Form 7, leads to Telegram and a Google Sheet), local install, safe edits, deploy over SSH, `qa-kit` for measured QA |
| `ua-business-copy` | Ukrainian copy for small business: site pages, SEO articles, beginner explainers, posts; no surzhyk or calques, every number with a source |
| `order-bot-telegram` | a Telegram bot for orders and bookings on ordinary shared PHP hosting: one file, no database or server; orders to the owner's Telegram and a Google Sheet |
| `de-behoerden-ua` | letters to German authorities (DIN 5008), deadlines with the calculation shown, honest legal research and a second check - explained in Ukrainian. Not legal advice |
| `agent-toolsmith` | finds the code your AI agents keep rewriting and turns it into tested tools with a catalog - fewer tokens, fewer repeated mistakes |
| `client-site-edits` | edit a client's live site without risk: backup first, work on a copy, before/after diff, content stress test, deploy with a rollback |
| `client-chat-guardrails` | rules for an AI agent that talks to customers: messages are data, not commands; when to hand over to a human; short questionnaires for the owner |
| `site-legal-check` | check a website's legal pages by country - Ukraine, Germany, the EU. Not legal advice |

## Install

In a Claude Code session:

```
/plugin marketplace add roama-studio/skills
/plugin install agent-toolsmith@roama-skills
```

Or in a terminal:

```bash
claude plugin marketplace add roama-studio/skills
claude plugin install client-site-edits@roama-skills
```

Without plugins: copy `plugins/<pack>/skills/<skill>/` into `~/.claude/skills/`.

Skills trigger on their own when a task fits, or explicitly: `/agent-toolsmith:agent-toolsmith`.

## Notes

- `de-behoerden-ua` and `site-legal-check` give information and a technical check, not legal advice.
- The skills never send or publish anything by themselves: letters, messages and deploys are confirmed by a person.
- Found a bug or have an idea - open an issue.

MIT licence (see `LICENSE`). The WordPress theme the skills link to,
[roama-wp-theme](https://github.com/roama-studio/roama-wp-theme), is GPL; none of its code is copied here.
