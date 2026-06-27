# CLAUDE.md

Guidance to Claude Code when working with code in this repository.

## Project context

SEO Audit Suite — a single-admin PHP web app that replaces a 5-hour manual Excel audit workflow. Targets shared hosting; developed locally on **Laragon (Apache + PHP 8.3 + MySQL)**. The MVP spec lives in [GEMINI.md](GEMINI.md) and is the source of truth for scope. Re-read it whenever scope is unclear.

## Running the app

There is no build step, package manager, or test suite. The code runs directly under Apache via Laragon.

- **Local URL**: served from `C:\laragon\www` — open `http://localhost/index.php` (auth required) or `http://localhost/login.php`.
- **Default credentials**: `admin` / `admin123` (seeded by [schema.sql](schema.sql)).
- **Database setup**: import [schema.sql](schema.sql) into MySQL (creates the `seo_audit_tool` database, all tables, and seeds the admin user).
- **DB / API credentials**: hardcoded in [db.php](db.php) (`DB_*` constants and `PAGESPEED_API_KEY`). No `.env` file.
- **Asset uploads** land in `uploads/` (gitignored except `.gitkeep`). Old files are deleted on replacement/audit-delete — preserve that behavior when editing upload handlers.

After every code update, commit and push.

## Architecture

The app is intentionally flat — **5 top-level PHP files, no framework, no autoloader, no JS bundler**. Everything is vanilla PHP + vanilla JS + Lucide icons loaded from a CDN.

### Request flow

```
login.php  ──► POST api.php?action=login ──► sets $_SESSION['user_id']
index.php  ──► (auth-gated) renders full SPA shell; all data via fetch() to api.php
share.php  ──► public, token-gated; calls only api.php?action=get_share_audit
```

- [api.php](api.php) is a **single ~1600-line switch statement** dispatching on `$_GET['action']`. Every action returns JSON. All actions except `login` and `get_share_audit` require `$_SESSION['user_id']`. When adding features, add a new `case` here.
- [crawler.php](crawler.php) — `Crawler::fetchAndAnalyze($url)` does cURL + DOMDocument/XPath extraction (meta, headings, links, alt-less images) and returns a normalized array. It also returns `internal_urls`, which `api.php` uses for the recursive site-crawl action.
- [db.php](db.php) — PDO singleton + `safe_session_start()`. `require_once 'db.php'` at the top of every entry point.
- PageSpeed Insights data is fetched inside the `cwt_fetch` action (see [api.php:802](api.php)) using `PAGESPEED_API_KEY` from `db.php`. There is a mock-fallback path per GEMINI.md — keep it when editing.

### Frontend SPA (inside index.php)

[index.php](index.php) is a single ~7000-line file: PHP auth guard at the top, then HTML for every tab/modal, then one large inline `<script>` holding the SPA state and ~all event handlers. Key globals to be aware of when editing JS:

- `activeClientId`, `activeAuditId`, `activeAuditCountry`
- `pagesData`, `searchTermsData`, `competitorsData`, `competitorAnalysesData`
- `cwtData` (cached Core Web Vitals for the audit)
- Collapse/interaction sets: `collapsedSearchTerms`, `collapsedCompetitorTech`, `interactedCompetitorTech`, `collapsedCompetitorPerf`, `collapsedAuditTech`

Auto-save indicators are wired through `getSavedIndicatorHTML()` / `triggerIndicator()`. Most editable cells POST to `api.php?action=update_field` or a field-specific action. Keep the auto-save UX when adding new editable fields.

### Share view (share.php)

[share.php](share.php) is a separate ~3500-line file that **mirrors index.php's rendering but strips all inputs/forms** — all editable controls become read-only text, tables, or `<img>` previews. It hits exactly one endpoint: `api.php?action=get_share_audit&token=…`, which returns the full audit payload (audit + pages + search_terms + competitors + competitor_analyses + core_web_vitals) in one shot. **When you change how data is displayed in index.php, mirror the change in share.php** or the client-facing report drifts.

### Data model

Single MySQL database `seo_audit_tool`. Cascade-delete is on every FK — deleting a client cleans up everything below.

```
users
clients ──┐
          └─► audits ──┬─► audit_pages              (per-URL SEO + technical state)
                       ├─► search_terms ──► competitors  (organic/sponsored, per search term)
                       ├─► competitor_analyses      (full audit-shaped record per competitor)
                       └─► core_web_vitals          (1:1 cache, audit-wide PSI scores)
```

Notes:
- `audits.share_token` is the unguessable per-audit token used by `share.php`.
- `audit_pages` and `competitor_analyses` overlap heavily in columns (same SEO+manual metrics shape); `competitor_analyses` additionally carries its own desktop/mobile PSI columns (whereas pages share the audit-wide `core_web_vitals` row).
- `headers_structure` is a JSON-encoded array of `{tag, text}` items in document order, produced by `Crawler::analyzeHtml`.

## Conventions worth respecting

- **All data points stay manually editable**, including auto-filled ones (GEMINI.md, MVP rule). Don't lock fields just because a crawler populated them.
- **No frameworks, no build step.** Don't introduce npm/composer/webpack or split files into a framework layout without a discussion — the deployment target is shared hosting.
- **CSS is cache-busted via `?v=<?php echo time(); ?>`** on the `<link>` in index.php. Keep that pattern when adding new stylesheets.
- **Crawler errors are surfaced, not swallowed.** `Crawler::fetchAndAnalyze` throws on bad URL / HTTP != 200 / 403 bot-block; callers in `api.php` catch and return `{error: ...}`. When the search-term → competitor-analysis workflow can't crawl a target, it creates a placeholder row for manual editing instead of failing the batch.
