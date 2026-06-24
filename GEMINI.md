# How To Use This Spec Folder

- This document is the source of truth. Refer back to it whenever scope is unclear.
- Edit this file as your understanding of the project evolves.
- Commit and push to the GitHub repository after every code update.



# Goal of the application

- Problem: SEO audits are currently done by hand in Excel — opening every client page in a browser tab, running Chrome extensions (SEOquake, Similar Web), manually copying ~40+ data points per audit. Takes ~5 hours, output is hard for clients to read. This app improves this audit by automating some data points, and globally building a cleaner interface.

# MVP Scope

## In scope

- Client management: create, list, select a client.
- Audit management per client: create a new audit (always blank, no duplication), list and reopen past audits for that client — like projects in FL Studio.
- Single shared admin login.
- Per-audit read-only share link: unique token per audit, opens a no-nav, no-edit viewer scoped to that one audit only.
- Dashboard: simple counts (clients, audits, etc.) — low priority, build last.
- Hosting/deployment — local dev using Laragon using Apache + PHP 8.3 + MySQL, targeting shared hosting enviro.

## Out of scope

- Automating traffic/audience data, GSC data, or SERP/competitor discovery.


# Feature Behavior within an audit

## Client management

- Create client (name, homepage URL, industry).
- List/search existing clients.
- Select a client to work within their scope.

## Audit management (per client)

- Create new audit (always blank, no duplication), save audit
- List past audits for this client, reopen any of them (read/write, same as a new one).
- Generate/copy a read-only share link for any specific audit.
- All data points in the audit stay manually editable, including auto-filled ones.


## Website audit

### SEO State

- The user manually pastes the url of each web pages from the website the user wants to audit. 
- For each url :
	- Automatically fetch and show the Meta title of the web page (with the number of characters)
	- Automatically fetch and show the Meta description of the web page (with the number of characters)
	- Automatically fetch and show the H1 of the web page (with the number of characters)
	- Automatically fetch and show the number of H1, H2, H3, H4, H5 and H6 in the web page
	- Automatically fetch and show the representation of the headers’ structure of the web page (Semantic headers)
	- Automatically fetch and show the Number of Internal links in the web page
	- Automatically fetch and show the Number of External links in the web page
	- Automatically analyze all images in the web page and show the number of images that doesn’t have the alt text attribute
	- The user manually adds the Monthly visits of the web page
	- The user manually add the Avg. time per visit of the web page
	- The user manually adds the Audience’s country + proportion in % of the web page
	- The user manually adds the Global ranking of the web page + the Ranking in the website’s country
	- The user manually adds the search terms of the web page
- Each audited URL entry can be individually deleted by the user

### Traffic and Performances 

- The user manually adds the Bounce rate of the website
- The user manually adds the number of Pages per Visit
- The user manually adds the Average Monthly Visits
- The user manually adds the Average Visit duration
- The user manually adds the Breakdown by country
- The user manually adds the Main channels
- The user manually adds the Traffic trends (last 3-6 months)

### Technical state 

- The user manually pastes the URL of each web page from the website they want to audit.
- For each URL :
	- The user manually adds the Indexing in Google Search Console status (yes or no)
	- The user manually adds the Crawl errors status (yes or no)
- Once for the whole website (checked using the homepage as the representative page):
	- Automatically fetch and show the Core Web Vitals (Mobile and Desktop)
	- Automatically fetch and show the Page speed (Mobile and Desktop)
	- The user manually adds details about "Submit sitemap to Google Search Console"


## Search terms 

- The user adds a search term manually.
- For each search term :
	- The user manually adds 5 organic competitors found for that term (URL).
	- The user manually adds 3 sponsored competitors found for that term (URL).
- For each organic competitors : 
	- The user manually adds the Bounce rate of the organic competitors's website
	- The user manually adds the number of Pages per Visit of the organic competitors's website
	- The user manually adds the Average Monthly Visits of the organic competitors's website
	- The user manually adds the Average Visit duration of the organic competitors's website
- Once search terms are filled in, the user can click a button to automatically generate a ranked competitor suggestion list, based on all organic competitor URLs entered across this audit's search terms:
	- Automatically group all organic competitor URLs by domain (ignoring sponsored ones).
	- Automatically count how many times each domain appears across all search terms in this audit.
	- Automatically rank domains from most frequent to least frequent; domains with equal counts share the same rank.
	- For each ranked domain, Automatically show: rank, number of appearances, competitor name (auto-derived from the domain), and one representative URL : the exact URL if it was always the same, otherwise the domain's homepage URL.
- The user manually selects, from this ranked list, which competitors to send to Competitor Analysis.
- Once the selection is confirmed, automatically create a Competitor Analysis entry for each selected competitor and starts the audit for them.


## Competitor analysis 

- The user can also add competitors directly to Competitor Analysis manually, without going through the search-term suggestion process.
- When adding a competitor manually, the user pastes an url of the competitor the user wants to audit
- For each url :
	- Automatically fetch and show the Meta title of the web page (with the number of characters)
	- Automatically fetch and show the Meta description of the web page (with the number of characters)
	- Automatically fetch and show the H1 of the web page (with the number of characters)
	- Automatically fetch and show the number of H1, H2, H3, H4, H5 and H6 in the web page
	- Automatically fetch and show the representation of the headers’ structure of the web page (Semantic headers)
	- Automatically fetch and show the Number of Internal links in the web page
	- Automatically fetch and show the Number of External links in the web page
	- Automatically analyze all images in the web page and show the number of images that doesn’t have the alt text attribute
	- The user manually adds the Monthly visits of the web page
	- The user manually add the Avg. time per visit of the web page
	- The user manually adds the Audience’s country + proportion in % of the web page
	- The user manually adds the Global ranking of the web page + the Ranking in the website’s country
	- Automatically auto-fills with the search term(s) that led to this competitor if the competitor entry came from the keyword-suggestion workflow. otherwise, the user manually adds the search terms of the web page
- Each competitor entry can be individually deleted by the user

## Client read-only view

- Shareable URL with no login (token based)
- No navigation to other audits, other clients, or any edit control.



