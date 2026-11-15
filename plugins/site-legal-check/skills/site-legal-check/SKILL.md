---
name: site-legal-check
description: Check the legal pages and legal behaviour of a business website by its country - first scan what the site really uses (forms, analytics, pixels, embeds, fonts, shop, payments), then a pass / fail / unverifiable checklist for Ukraine (personal data notice, seller details for e-commerce, Ukrainian-language version), Germany (Impressum, Datenschutzerklärung, cookie consent, shop duties, Button-Lösung) and the EU. A technical and content check, never legal advice. Use before handing over a site or when auditing an existing one.
---

# Legal check of a website

This is a technical and content check, not legal advice: never write «сайт юридично відповідає вимогам».
Every legal reference below must be opened at its source before you rely on it - laws change.

## 1. Scan: what does the site use?

Visit home, contact, every form, shop and checkout, footer, legal pages; read the code and network requests:
contact or booking forms, newsletter, analytics and ad pixels (GA4, Meta, GTM), chat widgets, embeds (YouTube,
Maps, Instagram), external fonts or CDNs, payments, a shop, user accounts. Only duties that follow from what you
found apply - no blanket lists.

## 2. Checklist - pass / fail / unverifiable / n.a.

### Every site

| Item | What to look for |
|---|---|
| Privacy notice | who collects data, for what, how long, how to ask for deletion; linked in the footer and next to every form |
| Forms | only needed fields; consent boxes not pre-ticked; a link to the privacy notice next to submit |
| Third-party requests | for EU visitors: no non-essential script, font, map or video loads before consent |

### Ukraine (UA)

| Item | What to look for | Open first |
|---|---|---|
| Personal data | the visitor is told who processes the data, the purpose and their rights before or when the data is collected | Law «Про захист персональних даних» - https://zakon.rada.gov.ua/laws/show/2297-17 |
| Seller details (online sales) | the seller's name, location, contact (e-mail, phone), identification code (ЄДРПОУ / РНОКПП), prices with taxes and delivery costs | Law «Про електронну комерцію», the article on seller information - https://zakon.rada.gov.ua/laws/show/675-19 |
| Consumer terms | terms of sale, delivery, returns and the right to withdraw for distance sales | the consumer-protection law in force (check which version applies) - https://zakon.rada.gov.ua/ |
| Language | a business registered in Ukraine: the Ukrainian version exists and opens by default; other languages may be added | Law on the state language, the article on websites - https://zakon.rada.gov.ua/laws/show/2704-19 |
| Cookies | no separate cookie-consent law: a banner is still needed when the site tracks visitors from the EU | - |

### Germany (DE) and Austria (AT)

| Item | What to look for |
|---|---|
| Impressum | § 5 DDG: name and legal form, postal address (no P.O. box), e-mail plus a second fast contact, register and number, VAT ID if any, responsible person for editorial content (§ 18 MStV); reachable in 2 clicks from every page |
| Datenschutzerklärung | Art. 13/14 DSGVO: controller, purposes and legal bases for every processing found, recipients and third countries, storage time, rights, the supervisory authority |
| Cookie consent | § 25 TDDDG: nothing non-essential before consent; «Accept» and «Reject» equally easy; nothing pre-ticked; consent can be withdrawn |
| Shop | AGB; Widerrufsbelehrung + Muster-Widerrufsformular; pre-contract information; gross prices incl. VAT and shipping (PAngV) |
| Button-Lösung | the order button says «zahlungspflichtig bestellen» or equivalent (§ 312j Abs. 3 BGB) |
| Fonts and embeds | fonts self-hosted; embeds behind consent or a two-click solution |

### Other EU countries

The every-site rules strictly, plus the GDPR privacy notice (controller, purposes, legal basis, storage time,
rights, recipients) and, for shops, terms, withdrawal right, delivery and payment terms. Check the country's own
imprint rules.

## 3. Report

A table: item - status - where (URL) - what to fix (one line). `unverifiable` = you could not see it (behind a
login, a test payment needed) - never a guess. Missing texts from the business are listed as what the owner must
supply, with a note that a template is ordinary text, not a legal service, and should be checked by their lawyer.

---
Built by ROAMA web studio - https://roama-studio.github.io/
