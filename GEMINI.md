# How To Use This Spec Folder

- This document is the source of truth. Refer back to it whenever scope is unclear.
- Edit this file as your understanding of the project evolves.
- Commit and push to the GitHub repository after every code update.



# Goal of the application

- Problem: SEO audits are currently done by hand in Excel — opening every client page in a browser tab, running Chrome extensions (SEOquake, Similar Web), manually copying ~40+ data points per audit. Takes ~5 hours, output is hard for clients to read. This app improves this audit by automating some data points, and globally building a cleaner interface.

# MVP Scope

## In scope

- Client management: create, list/search, edit/update, and delete client profiles (name, homepage URL, industry).
- Audit management per client: create a new blank audit campaign, list past audits, reopen past audits, delete audits, and save global strategy/analysis variables.
- Single shared admin login.
- Per-audit read-only share link: unique token per audit, opens a no-nav, no-edit viewer (`share.php`) scoped to that one audit only.
- Dashboard: simple counts (clients, audits, etc.).
- Hosting/deployment — local dev using Laragon using Apache + PHP 8.3 + MySQL, targeting shared hosting environment.

## Out of scope

- Automating traffic/audience data, Google Search Console account authentication, or automated SERP/competitor discovery.


# Feature Behavior within an audit

## Client management

- Create client (name, homepage URL, industry).
- List/search existing clients.
- Edit/update client details.
- Delete client.
- Select a client to work within their scope.

## Audit management (per client)

- Create new audit (always blank, no duplication), save audit.
- List past audits for this client, reopen any of them (read/write).
- Delete audit.
- Generate/copy a read-only share link for any specific audit.
- All data points in the audit stay manually editable, including auto-filled ones.


## Website audit

The website audit is organized into three tabs: SEO State, Technical State, and Traffic & Performance.

### SEO State

- **Adding Web Pages**: The user can add web pages to the audit using two methods:
	- **Single / Bulk URLs**: Paste one or multiple URLs (separated by newlines, commas, or spaces) to crawl and analyze.
	- **Crawl Website**: Paste a seed URL to crawl the domain recursively, automatically discovering and adding internal pages up to a user-specified limit (1 to 50 pages).
- **Automated Crawling Metrics**: For each added page, the crawler automatically extracts and counts:
	- Meta title (with character count)
	- Meta description (with character count)
	- H1 (with character count)
	- Number of H1, H2, H3, H4, H5, and H6 headings
	- Representation of the headers' structure (hierarchical tree)
	- Number of Internal links
	- Number of External links
	- Images missing the `alt` text attribute
- **Manual Input Metrics**: The user manually adds or edits:
	- Monthly visits
	- Avg. time per visit (seconds)
	- Audience's country + proportion in %
	- Global ranking
	- Ranking in the website's country
	- Search terms
- **Additional Page Actions**:
	- Individual entry deletion
	- **Re-fetch / Refresh**: Re-run the crawler on the page to retrieve updated metadata.
	- **Semantic Headers Modal**: View the hierarchical header tree, delete/clear the structure, re-crawl, or upload/paste (Ctrl+V) a custom screenshot of the headers structure.
	- **Page Notes**: Save text notes for each page.

### Technical state 

- **Page-Level Technical State**: For each audited URL, the user manually adds:
	- Indexing in Google Search Console status (yes, no, or empty)
	- Crawl errors status (yes, no, or empty)
- **Website-Wide Technical State** (audited once using the homepage, stored in the `core_web_vitals` table):
	- **Core Web Vitals & Page Speed (Desktop & Mobile)**: Automatically fetch Performance, Accessibility, Best Practices, and SEO scores along with Lighthouse metrics (First Contentful Paint (FCP), Largest Contentful Paint (LCP), Total Blocking Time (TBT), Cumulative Layout Shift (CLS), Speed Index (SI)) via Google PageSpeed Insights API. Falls back to pre-defined mock scores if offline or API limits are reached.
	- **Agentic Browsing Score**: Automatically calculates a custom rating out of 3 based on whether the page passes these thresholds:
		- Cumulative Layout Shift (CLS) <= 0.1 (passed)
		- Accessibility Score >= 80 (passed)
		- SEO Score >= 90 (passed)
	- **Sitemap submission**: The user manually adds details about "Submit sitemap to Google Search Console".

### Traffic and Performances 

- **Website-wide Metrics**: The user manually adds:
	- Bounce rate of the website (%)
	- Number of Pages per Visit
	- Average Monthly Visits
	- Average Visit duration (seconds)
	- Breakdown by country
- **Upload Assets**: The user can upload image files for:
	- **Main channels** (automatically stored in `uploads/` and links are saved in the database)
	- **Traffic trends (last 3-6 months)** (automatically stored in `uploads/` and links are saved in the database)
	- Old files are automatically deleted from the server when replaced or when the audit is deleted.


## Search terms 

- **Keyword Management**: The user adds and deletes search terms manually.
- **Competitors Input**: For each search term:
	- The user manually adds 5 organic competitors found for that term (URL).
	- The user manually adds 3 sponsored competitors found for that term (URL).
- **Organic Competitors Metrics**: For each organic competitor card, the user manually adds:
	- Bounce rate of the competitor's website
	- Number of Pages per Visit
	- Average Monthly Visits
	- Average Visit duration
- **Competitor suggestion ranking engine**: Once search terms are filled in, the user can click a button to automatically generate a ranked competitor suggestion list:
	- Groups all organic competitor URLs by domain (ignoring sponsored ones).
	- Counts how many times each domain appears across all search terms in this audit.
	- Ranks domains from most frequent to least frequent (equal counts share the same rank).
	- For each ranked domain, automatically shows: rank, number of appearances, competitor name (auto-derived from the domain), associated search terms, and one representative URL (the exact URL if it was always the same, otherwise the domain's homepage URL).
- **Workflow Integration**: The user selects which competitors to send to Competitor Analysis. Once confirmed, the system creates entries in Competitor Analysis and automatically runs the crawler to fetch their metadata. If a crawl fails, a placeholder entry is created for manual editing.


## Competitor analysis 

- **Adding Competitors**: Competitors can be added directly to Competitor Analysis manually (by pasting a URL and search terms) or through the search-term suggestion workflow.
- **Tabbed Competitor Interface**: Like website audits, competitor analyses are organized into SEO State, Technical State, and Traffic & Performance.
- **SEO State**:
	- Automatically fetch and show the Meta title (with length), Meta description (with length), H1 (with length), H1-H6 headings counts, internal/external links, and images missing `alt` tags.
	- Display semantic headers structure as a tree view, supporting custom screenshot uploads, re-fetching, and clearing.
	- Manually edit monthly visits, avg. time per visit, audience country + proportion, global/country rankings, search terms, and page notes.
- **Technical State**:
	- Automatically fetch PageSpeed/Lighthouse scores and Core Web Vitals (Performance, Accessibility, Best Practices, SEO, Speed Index, LCP, FCP, TBT, CLS, and Agentic Browsing score) for Desktop and Mobile strategies using the PageSpeed Insights API, with mock fallbacks.
	- Add custom technical notes.
- **Traffic & Performance**:
	- Manually edit competitor-wide bounce rate, pages per visit, average monthly visits, average visit duration, and breakdown by country.
- **Competitor Actions**: Individual competitor entries can be deleted.


## Global Report & Strategy

- **Dedicated Reporting Tab**: A tab containing audit-level textareas that auto-save as the user types:
	- **Global Analysis & Audit Meaning**: Summarize overall findings, traffic trends, keyword gaps, and technical audits.
	- **Recommendations & Strategy to Adopt**: Outline next steps and execution strategy.
- **Audit-wide Rankings**: Supports setting the client's Global Ranking, Country Ranking, Target Country, Sitemap details, and Additional Notes.


## Client read-only view

- **Token-based Access**: Shareable URL (`share.php?token=<token>`) accessible without logging in.
- **Strict Scope**: Scoped to the specific audit only.
- **Read-Only Experience**:
	- No navigation to other audits or clients.
	- All editing inputs, scrape forms, and file upload fields are replaced with clean read-only text displays, tables, or image previews.
	- Displays the complete audit results: SEO State (including page lists and headers trees/screenshots), Technical State (including Google PageSpeed metrics), Traffic & Performance (including uploaded graphs and metrics), Search Terms, Competitor Analysis, and the Global Analysis & Strategic Report.
