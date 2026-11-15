---
name: de-behoerden-letter
description: Draft a formal German letter (DIN 5008) to a German authority, landlord or company for a Ukrainian speaker - one clear request per letter (Antrag, Widerspruch, Stellungnahme, Akteneinsicht, Belegeinsicht), only legal bases actually opened, placeholders instead of invented facts, a Ukrainian summary of what is asked, what to enclose, how and by when to send; plus a plain-text copy for online portals. Use when someone asks for a letter to the Jobcenter, Ausländerbehörde, Finanzamt, Familienkasse, landlord, insurer or a company.
---

# Letters to German authorities

Start with the person's own words: what happened, which letter they got (date, reference number), what they
want. Ask only for what is missing, in one short message. Urgent first: is a deadline running (skill `de-fristen`)?

## Shape (DIN 5008)

1. Sender: name, address (unknown -> `[Name]`, `[Adresse]`).
2. Recipient: the exact office and address from their letter.
3. Place, date: «Berlin, 12.11.2026».
4. Betreff: what + the reference number from their letter
   («Kundennummer 123... - Antrag auf Übernahme der Betriebskostennachzahlung 2025»).
5. Body: «Sehr geehrte Damen und Herren,» - one sentence what happened (with dates) - the request in one clear
   sentence - the reason and legal basis ONLY if opened (skill `de-legal-research`) - what is enclosed - a date
   for their answer if useful («Ich bitte um Ihre Antwort bis zum ...») - «Mit freundlichen Grüßen» - space for
   the signature.
6. Anlagen: copies, never originals.

## Requests that work (one per letter)

| Situation | Request |
|---|---|
| a Bescheid looks wrong | «Widerspruch gegen den Bescheid vom ... (Az. ...)»; reasons may follow: «Die Begründung reiche ich nach.» |
| missing information | «Antrag auf Akteneinsicht» (§ 25 SGB X) or «Bitte um Erläuterung» |
| a service-charge bill from the landlord | «Bitte um Belegeinsicht» to the landlord |
| an extra cost on benefits (e.g. a service-charge back-payment) | «Antrag auf Übernahme ... als Bedarf für Unterkunft und Heizung» - open § 22 SGB II first |
| a change to report | «Mitteilung über ... ab dem ...» with the proof enclosed |

## Rules

- One letter = one recipient. Short, factual, polite; no emotions, threats or legal theory.
- Never invent a §, an amount or a date: `[...]` placeholders, listed for the person.
- First person singular - the person signs it themselves. You never send anything.
- A Widerspruch always gets its deadline and the advice to keep proof of sending.

## Then in Ukrainian

`Коротко:` 2-4 sentences - what the letter asks, what to enclose, how to send (post, Einschreiben, the office's
online portal, in person with a stamped copy) and by when.

## Online portals

Portals often reject special characters. Also give a plain-text copy: a short Betreff (reference + 3-6 words),
no «§» (write «Paragraph 22 Absatz 1»), no long dashes or typographic quotes; if still refused, write umlauts as
ae / oe / ue / ss.

Before handing over, run the skill `de-letter-review`.

---
Built by ROAMA web studio - https://roama-studio.github.io/
