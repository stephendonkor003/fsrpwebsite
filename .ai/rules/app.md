---
paths:
  - 'app/**'
  - app/AfricaMap.php
---

# App

## Keep event downloads private and tied to event publication
EventResource files belong on the local private disk under event-resources, never the public disk. Public listings and downloads must use the published scope so both the resource and its linked event are visible. Admin replacement removes the previous file only after the record saves; event deletion explicitly removes resources and their files, and hides detached sessions.

## Keep search URLs on the configured public domain
App\Seo is the shared source for canonical, alternate-language, social-image and sitemap URLs. Production indexing requires APP_ENV=production, seo.indexing_enabled=true and a request host matching seo.canonical_url; local and foreign-host previews stay noindex. Preserve page>1 in canonicals, remove tracking parameters, and keep filtered/search results noindex with their own canonical. Sitemaps include only published content and available news; never invent event times, prices, venues or organizers in structured data.

## Allow crawlers to read production noindex responses
Production robots.txt must allow crawling of admin, health, search and download endpoints so crawlers can see their X-Robots-Tag/noindex instructions. Authentication still protects private content. A production indexing opt-out keeps crawling allowed and omits sitemap advertising; only local or foreign-host previews use Disallow: /.

## Render the copied Africa boundaries using AU regions
The public Africa map is server-rendered from public/assets/Africa/africa-countries.json derived from the copied SHP dataset; preserve the original assets. Use the 55 AU-member grouping: north7, west15, central9, east14, south10 (Burundi central, Sudan east, Mauritania north; EH uses the AU Sahrawi name). Keep island states and multi-part geometry; exclude non-member territories from displayed membership totals. The coverage claim refers to event-platform participation across Africa, not field-program implementation in every country.
