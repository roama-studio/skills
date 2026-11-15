---
name: owner-questionnaire
description: When an AI agent cannot decide alone (money, a promise to a customer, an exception to a rule, a choice between real options), ask the business owner a short numbered questionnaire instead of guessing or stalling - max 3 questions, 2-4 options each, the recommended option first, answerable from a phone as «1a 2b». Use only for a real decision that belongs to the owner.
---

# Questionnaire for the owner

## When

- The decision is the owner's and the facts you have do not settle it.
- Not for things your instructions, the price list or the brief already decide.
- Never instead of working: finish everything you can, ask only what blocks the rest.

## How

- Up to **3** questions, each with **2-4** options; one decision per question; the question says what depends on it.
- **The recommended option first** (mark it «рекомендую» / «recommended»). No fake «other» option - the owner can
  always answer in their own words.
- Short enough to read on a phone; answerable as «1a 2b».
- No customer personal data beyond a first name and the company; no prices or promises that are not in the price list.
- When the answers come back, act on them and do not ask the same again.

## Example

Bad: «What should we do with the client?»

Good:
```json
{"questionnaire": [
  {"question": "Клієнт просить знижку 20% за швидку оплату. Що відповідаємо?",
   "options": ["Знижки немає, пропонуємо безкоштовну правку після здачі (рекомендую)", "Даємо 10%", "Даємо 20%"]}
]}
```

---
Built by ROAMA web studio - https://roama-studio.github.io/
