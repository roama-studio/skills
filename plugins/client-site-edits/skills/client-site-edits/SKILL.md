---
name: client-site-edits
description: Make edits to a client's existing live website (static HTML, any CMS, WordPress or Elementor) without breaking it - full backup first, work on a copy, read the project's own docs and bug history, baseline screenshots before and pixel diff after, content stress test, deploy with a rollback point, the client checks on their live site. Use when asked to change, fix or update someone else's existing site or project.
---

# Edits to a client's existing site

The original is sacred. Every step can be undone.

## 1. Backup first - always, before anything else

- Live site: files + database dump (WordPress: `wp db export` or the host's backup; static: download the web
  root). Store it with the date; check it by content (`unzip -l`, `file`), not by name - a «.zip» may be a tar.
- A project folder from the client: a full copy; compare with `diff -rq`. Never commit into the client's own git
  unless they ask.
- Pull a FRESH copy of live before editing: real content (hundreds of items, long names, odd photos) shows bugs
  the design data never had.

## 2. Understand before touching

- Read the project's own docs (README, CHANGELOG, setup/migration notes). Areas fixed 2-4 times before (sliders,
  masks, line breaks) are touched only with that history in mind.
- Find the exact element in code for each requested edit; ask the client only the real choices (one page or all,
  what replaces it, how strong).
- Check the design source before calling something a bug: an «invisible» element may simply not exist in the design.
- Price and scope agreed in writing before work.

## 3. Edit on the copy

- Baseline before: every page at 390 / 768 / 1024 / 1440 / 1920 px, full page + header strip, reduced motion.
- Change only what was asked - no refactor, no new dependency.
- Resilient CSS: `min-height` instead of `height` for text boxes; `overflow-wrap: anywhere` for text;
  `[hidden]{display:none}` where a `display` rule would override `hidden`; images in a fixed frame with
  `object-fit: cover` and an `object-position` chosen by where the subject sits.
- Every copy of the code gets the edit (e.g. a static version AND a theme).

## 4. QA - never trust the developer's report

- Pixel diff after vs before at the same widths; mask non-deterministic layers (canvas, WebGL, video) in both,
  so «unchanged» means 0.000%. Any change above noise is explained or reverted.
- Compare against the client's ORIGINAL served read-only, not against the developer's own baseline.
- Content stress test with what the client can enter: +2-3 menu items, texts x3 and one word, 1 / 2 / many items,
  images square / 2:3 / 9:16 / 3:1 / missing.
- Keyboard and resize edge cases of changed behaviour; prove a cause by toggling one property before reporting.

## 5. Deploy with a rollback point

- Deploy the smallest set of changed files (or a DB change through the CMS's own tools), not the whole site.
- Keep the backup of step 1 as the rollback; write down the one command or steps to restore it.
- The client checks on their own live site; give them a short list of what changed and where to look.

## 6. Evidence and hand-over

Keep reports, every bug screenshot and one before/after per page (JPG ~80 quality); delete bulk screenshots.
Write the lessons into the project's CHANGELOG so the next edit starts smarter.

---
Built by ROAMA web studio - https://roama-studio.github.io/
