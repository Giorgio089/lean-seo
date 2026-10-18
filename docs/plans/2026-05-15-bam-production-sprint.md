# Lean SEO – BAM Production Sprint

**Date:** 2026-05-15
**Status:** Planned
**Driver:** BAM Relaunch (bambooconsulting.de) – Go-Live 23.06.2026
**Goal:** Lean SEO 0.1.0 → 0.2.0 – production-ready for the first real client deployment.

---

## Context

Bamboo Consulting is a B2B PR & LinkedIn agency based in Hamburg. Their relaunch site
(7 pages + blog) needs exactly the SEO primitives Lean SEO already has – plus the three
features that are still missing:

1. **Open Graph + Twitter Card** – Melanie shares blog posts on LinkedIn. No OG = broken
   previews. Non-negotiable.
2. **Canonical URL** – New site, clean URL structure. Prevents self-duplication issues from
   day one.
3. **Global site settings** – Fallback OG image, organisation data, homepage meta title/desc.
   Without this there is no sensible default for untouched pages.

The remaining tickets are small but complete the production surface:

4. **Enhanced LocalBusiness schema** – Address + phone pulled from global settings.
5. **Organization schema type** – Homepage needs `Organization`, not only `LocalBusiness`.
6. **Sitemap noindex filter** – Posts marked `noindex` must be excluded from the WP core
   sitemap. Our data lives in the custom table, so a dedicated subquery filter is required.

LLMs.txt and agents.json are already implemented and are a key differentiator vs. Rank Math /
Yoast (both have these as paid features). They will be configured for BAM as part of the
launch setup – no further dev work needed.

---

## Sprint Map

| # | Ticket | Priority | Est. |
|---|--------|----------|------|
| T01 | [Open Graph + Twitter Card](2026-05-15-ticket-01-open-graph.md) | 🔴 Critical | 3–4 h |
| T02 | [Canonical URL](2026-05-15-ticket-02-canonical.md) | 🟠 High | 1 h |
| T03 | [Global Site Settings](2026-05-15-ticket-03-global-settings.md) | 🟠 High | 2–3 h |
| T04 | [Enhanced LocalBusiness Schema](2026-05-15-ticket-04-localbusiness-schema.md) | 🟡 Medium | 1 h |
| T05 | [Organization Schema Type](2026-05-15-ticket-05-organization-schema.md) | 🟡 Medium | 1 h |
| T06 | [Sitemap noindex Filter](2026-05-15-ticket-06-sitemap-noindex.md) | 🟡 Medium | 1 h |
| T07 | Version bump + README | ⚪ Wrap-up | 30 min |

**Total estimate: ~10–11 h (one weekend)**

---

## DB Schema Changes (T01 + T02 share one migration)

T01 adds `og_image TEXT NULL`.
T02 adds `canonical TEXT NULL`.

Both land in a single `dbDelta()` call. Bump `LEAN_SEO_SCHEMA_VERSION` from `'1'` to `'2'`.

```sql
ALTER TABLE {prefix}leanseo_data
  ADD COLUMN og_image  TEXT NULL,
  ADD COLUMN canonical TEXT NULL;
```

`dbDelta()` handles ADD COLUMN idempotently – safe on re-activation.

---

## Recommended order

```
T02 → T01 → T03 → T04 → T05 → T06 → T07
```

Start with Canonical (smallest change, exercises the migration path) then build OG on top of
the same migration. Global Settings before schema tickets because T04/T05 depend on them.

---

## Out of scope for this sprint

- Yoast / Rank Math importer (new site, nothing to import)
- FAQ schema (no FAQ content on BAM)
- Scoring / content analysis (Melanie doesn't write in WP herself)
- Redirect manager (handled via `.htaccess` for BAM)
- XML sitemap generation (WP core sitemap at `/wp-sitemap.xml` is sufficient;
  T06 only adds the noindex exclusion filter)
