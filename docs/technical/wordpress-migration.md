# WordPress Migration

## Goal

The new Laravel platform should make it easy to migrate valuable content from the current WordPress website without carrying over WordPress as a runtime dependency.

The migration should be repeatable, testable, and selective. Not everything from WordPress needs to move one-to-one. The goal is to preserve useful content, SEO value, media, and historical news while mapping it into stronger Laravel domain models.

For the step-by-step operator procedure, manifest schema, staging rehearsal, manual review, troubleshooting, and cutover handling, use [WordPress Importer Operator Runbook](./wordpress-importer-runbook.md).

## Recommended Strategy

Use a staged import:

1. Inventory the public WordPress REST responses, then capture only approved posts, required page records, selected attachment files, and the administrator XML cross-check in one checksummed offline source bundle while WordPress is still available.
2. Store that frozen bundle outside the application runtime, for example in `storage/app/imports/wordpress` during local development, and copy it securely to staging and production for the migration.
3. Generate a small, editable selection manifest whose published posts default to `import` but can be changed individually to `skip`.
4. Use explicit Laravel import phases for selected media, posts, deliberate page mappings, content cleanup, and redirects.
5. Normalize imported data into first-class models such as `Article`, `Location`, `Event`, and `MediaAsset`, while manually approved partner data moves into the code-owned partner catalogue.
6. Run dry-runs and repeat the import in staging until the output is clean.
7. Remove the temporary source snapshots, manifest, diagnostics, and import-only code after cutover verification and the rollback window.

## Keep The Importer Small

This is a one-time migration, not a permanent integration. Prefer direct, readable migration code over a generalized import framework.

- use REST only for the one-time source capture and use the verified local JSON/media bundle as the active rehearsal and cutover source;
- retain the WordPress XML export as one archive and cross-check file; do not copy unselected template or gallery attachments into the working bundle;
- use a file-based selection manifest and generated text or Markdown report instead of database-backed import administration;
- keep source IDs and mappings out of permanent domain tables;
- implement only the content types and transformations approved below;
- do not build extension points, plugin abstractions, background synchronization, an import dashboard, or a reusable ETL subsystem;
- optimize for safe dry-runs, idempotent rehearsal, clear failures, and easy deletion after the migration.

## Current Site Import Priorities

Based on the current website audit, import priority should be:

1. posts and featured images;
2. location pages;
3. training page content and current season schedule;
4. house rules;
5. media mentions;
6. partners and sponsors;
7. team content.

Do not import generic theme sections, comments, social sharing widgets, post view counters, duplicated navigation/footer content, or placeholder copy.

## DDS-015 Approved Inventory And Selection

The public REST inventory reviewed during DDS-015 contains:

- 21 published posts from 2018 through 2025;
- 12 published pages;
- 8 categories and 16 tags;
- 118 media records according to the REST response headers, while the paginated payload currently returns 115 unique records;
- 7 comments referenced by published post metadata;
- no publicly accessible author directory.

Every returned media record currently has an empty reusable alt-text value. The count discrepancy and missing alt text must be reported during media rehearsal rather than silently accepted.

| WordPress content                                       | Decision       | Laravel destination or handling                                                                                       |
| ------------------------------------------------------- | -------------- | --------------------------------------------------------------------------------------------------------------------- |
| 21 published posts                                      | Import-select  | Default every post to `import` in the selection manifest; allow an explicit per-post `skip` before dry-run or import. |
| Featured and inline media used by selected posts        | Import-select  | `MediaAsset`; import only referenced assets plus separately approved brand and partner assets.                        |
| Categories                                              | Normalize      | Map to `News`, `Announcement`, `Community`, or `RaceReport`.                                                          |
| Tags                                                    | Skip           | Use as temporary classification hints only; do not add permanent taxonomy tables.                                     |
| Sportpaleis, Koggenhal, and Oosterhout pages            | Rewrite/merge  | Review useful facts and selected photography against the existing `Location` records.                                 |
| Trainings page                                          | Rewrite        | Move only current, approved information into `Season` and `Event` records; do not import a generic page.              |
| House Rules page                                        | Rewrite        | Maintain the reviewed Dutch safety and participation rules on the code-owned `/house-rules` destination.              |
| In the media page                                       | Rewrite        | Maintain all nine reviewed mentions on the code-owned `/media` overview; do not create thin `Article` records.         |
| Home, Contact, News, and About content                  | Rewrite        | Use the implemented public destinations; redirect legacy paths where the path changed.                                |
| `/about-us/`                                            | Redirect       | Redirect to `/about`.                                                                                                 |
| `/our-work/` and `/stories/`                            | Skip with 410  | Remove unused template pages deliberately without inventing replacement destinations.                                 |
| Comments                                                | Skip           | Keep only in the XML archive; do not import the 7 legacy comments.                                                    |
| Published-post authors                                  | Map            | Match XML authors to an existing Laravel user where possible; otherwise use a fixed `Team DDS` content author.        |
| WordPress user accounts                                 | Skip           | Never create Laravel login accounts automatically from WordPress users.                                               |
| Drafts and private content                              | Archive only   | Keep in the XML export when present; exclude from the Laravel import.                                                 |
| Plugin data, theme markup, widgets, counters, and menus | Skip/normalize | Extract only approved content; do not reproduce WordPress presentation or runtime state.                              |

The selection manifest must include at least the WordPress ID, slug, title, publication date, proposed Laravel category, decision, and optional reason. A dry-run must report the exact selected and skipped records before it writes anything.

### Representative Source-To-Target Samples

| Source sample                                                                   | Target review path                                                                                                                         |
| ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Post `49916`, `seizoen-25-26`, with featured media `49925`                      | `Article` with category `Announcement`, reviewed through Article admin and `/news/{slug}` after its selected media is available.           |
| Post `1158`, `dennis-mennema-wint-de-eerste-ranking-indoor-nk-2020`             | `Article` with category `RaceReport`, preserving its publication date and legacy URL redirect.                                             |
| Page `49498`, `sportpaleis`                                                     | Manual comparison with the existing Sportpaleis `Location`; merge only approved facts and selected media instead of creating another page. |
| Page `49764`, `media`, containing nine external mentions from 2017 through 2023 | Code-owned `/media` overview containing all nine mentions; unavailable publications remain visible as unlinked archive references.         |

## Export Options

### WordPress REST API

Decision: primary structured capture source. The importer uses the frozen local copy after capture and therefore does not depend on a live WordPress site during rehearsal or cutover.

Useful for structured incremental imports:

```txt
/wp-json/wp/v2/posts
/wp-json/wp/v2/pages
/wp-json/wp/v2/media
/wp-json/wp/v2/categories
/wp-json/wp/v2/tags
```

Pros:

- easy to fetch as JSON;
- good fit for a Laravel import command;
- can be filtered and paginated;
- includes IDs that can be stored as legacy references.

Cons:

- custom fields or plugin data may need extra endpoints;
- media references inside post HTML need additional handling.

### WordPress XML Export

Decision: retain as an administrator-provided archive and completeness check. It is not a posts, pages, or media importer. The working bundle contains only records and files selected in `selection.json`.

Pros:

- available from WordPress admin;
- captures posts, pages, authors, terms, and attachments in one export;
- good as an archival source.

Cons:

- more parsing work;
- media download still needs care;
- repeated imports need stable deduplication logic.

### Direct Database Export

Not part of the approved migration. Reopen discovery before considering it if both REST and the XML archive prove insufficient for approved content.

Pros:

- complete access to raw data.

Cons:

- couples the migration to WordPress internals;
- more fragile;
- higher risk of importing plugin/theme noise.

## Content Mapping

### Posts To Articles

WordPress posts should usually become `Article` records.

Suggested mapping:

| WordPress                | Laravel                                          |
| ------------------------ | ------------------------------------------------ |
| `post_title`             | `title`                                          |
| `post_name`              | `slug`                                           |
| `post_excerpt`           | ignored; frontend derives summaries from content |
| `post_content`           | `content`                                        |
| `post_date_gmt`          | `published_at`                                   |
| `post_status`            | `status`                                         |
| featured image           | `cover_image_id`                                 |
| author                   | `author_id` or imported author metadata          |
| selected categories/tags | `category` or future taxonomy tables             |

Keep WordPress IDs, source URLs, checksums, target model IDs, and import outcomes in a temporary import manifest outside the permanent domain tables. The manifest makes rehearsal runs idempotent and debuggable, while the resulting `Article`, `Location`, `Event`, and `MediaAsset` records stay source-agnostic. Retain the manifest only through launch verification and the agreed rollback window.

### Pages To Structured Content

WordPress pages should not automatically become generic pages. Map them deliberately:

| Current WordPress page type | Laravel target                                                                            |
| --------------------------- | ----------------------------------------------------------------------------------------- |
| homepage content            | homepage sections or seed content                                                         |
| training days               | `Event` records with `type = training` or static training content, depending on structure |
| location pages              | `Location`                                                                                |
| house rules                 | static page or managed content record                                                     |
| contact                     | dedicated contact page                                                                    |
| partners                    | manually reviewed entries and selected logo assets in the code-owned partner catalogue    |
| in the media                | `Article` category or future media mention model                                          |

Observed legacy page mapping:

| WordPress URL   | Laravel target                                                      |
| --------------- | ------------------------------------------------------------------- |
| `/trainingen/`  | `/events?type=training` plus `Event` records with `type = training` |
| `/sportpaleis/` | `/locations/sportpaleis-alkmaar`                                    |
| `/koggenhal/`   | `/locations/sporthal-koggenhal`                                     |
| `/oosterhout/`  | `/locations/sporthal-oosterhout`                                    |
| `/huisregels/`  | `/house-rules`                                                      |
| `/media/`       | `/media` or article category `media`                                |
| `/nieuws/`      | `/news`                                                             |

### Media To MediaAsset

WordPress media should become `MediaAsset` records.

Suggested mapping:

| WordPress            | Import target                      |
| -------------------- | ---------------------------------- |
| attachment ID        | temporary import manifest key      |
| source URL           | temporary import manifest metadata |
| filename             | `original_filename`                |
| MIME type            | `mime_type`                        |
| file size            | `size_bytes`, if available         |
| image dimensions     | `width` and `height`, if available |
| alt text             | `alt_text`                         |
| downloaded file path | `path`                             |

The importer should download media, store it on the configured disk, and rewrite known WordPress media URLs inside article content to Laravel media URLs.

Imported locale-keyed content must not pretend Dutch copy is English. For fields whose domain validation requires English, the import review should flag missing English content so it can be translated, rewritten, or deliberately skipped before publication. Media alt text is optional and may keep its known supported locale because it is only a reusable default for later rendering contexts.

## URL Redirects

SEO preservation requires an explicit redirect map.

Examples:

```txt
/2025/09/18/indoor-seizoen-25-26-we-zijn-er-klaar-voor/ -> /news/indoor-seizoen-25-26-we-zijn-er-klaar-voor
/locaties/sportpaleis-alkmaar/ -> /locations/sportpaleis-alkmaar
/trainingsdagen/ -> /events?type=training
/trainingen/ -> /events?type=training
/agenda/ -> /events
/contact/ -> /contact
```

Possible storage approaches were:

- static webserver redirects for a small fixed list;
- database-backed `Redirect` model if there are many URLs;
- generated config file during deploy.

The implemented approach uses a database-backed `Redirect` model. It stores the source path, target, HTTP status, active state, hit count, and review notes. Laravel checks active redirects only after normal routes fail to match, so regular application requests do not perform a redirect lookup. Admins and editors can review the map in the dashboard, and the importer can create or update records idempotently without requiring a deployment.

Initial fixed mappings are provided by `RedirectSeeder`. The redirect import phase derives post and page redirects from the completed source-to-target mappings, then reuses matching seeded or manually created records without overwriting their admin-maintained active state or notes. The approved page policy redirects `/about-us/` to `/about`; the unused `/our-work/` and `/stories/` template pages remain explicit `410 Gone` decisions and do not create misleading `Redirect` records.

## Import Commands

Keep the command surface explicit and temporary. The authoritative execution order and operational safeguards are in [WordPress Importer Operator Runbook](./wordpress-importer-runbook.md). The command surface is:

```txt
php artisan wordpress:snapshot --manifest=storage/app/imports/wordpress/selection.json --xml=storage/app/imports/wordpress/dutchdronesquad.WordPress.2026-08-08.xml --output=storage/app/imports/wordpress/source-inventory
php artisan wordpress:import media --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import posts --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import pages --manifest=storage/app/imports/wordpress/selection.json --report=storage/app/imports/wordpress/page-review.md --dry-run
php artisan wordpress:import cleanup --manifest=storage/app/imports/wordpress/selection.json --report=storage/app/imports/wordpress/cleanup-review.md --dry-run
php artisan wordpress:import redirects --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import report --manifest=storage/app/imports/wordpress/selection.json --report=storage/app/imports/wordpress/import-review.md
php artisan wordpress:rehearse --manifest=storage/app/imports/wordpress/selection.json --base-url=https://staging.example.com
```

The media phase expects the temporary manifest to contain the REST endpoint and an explicit media selection:

```json
{
    "source": {
        "snapshot_directory": "storage/app/imports/wordpress/source-inventory",
        "media_endpoint": "https://dutchdronesquad.nl/wp-json/wp/v2/media"
    },
    "media": [
        {
            "wordpress_id": 49925,
            "decision": "import",
            "alt_text": "Dronepiloot bestuurt een FPV-racedrone tijdens een indoorwedstrijd"
        },
        {
            "wordpress_id": 49926,
            "decision": "skip",
            "reason": "Not referenced by selected content."
        }
    ]
}
```

An optional non-empty `media[].alt_text` stores the reviewed reusable Dutch default independently of generated database mappings. It overrides the WordPress source value after plain-text normalization, which keeps reviewed alt text portable between local rehearsal and a clean staging or production database. Without an override, the importer retains the source behavior and reports missing image alt text.

An import writes `mappings.media.{wordpress_id}` back to this file with the `MediaAsset` ID, original URL and filename, MIME type, size, dimensions, alt text, caption, checksum, and import timestamp. Later post and page phases use this mapping to resolve WordPress attachment IDs without adding WordPress fields to permanent domain tables. Existing mappings whose media asset and file still exist are reused without overwriting later editorial changes; missing targets are imported again. When `source.snapshot_directory` is configured, media metadata and bytes come from the checksummed local bundle. A media dry-run validates metadata without changing the database, storage, or manifest.

The post phase uses the same manifest and adds a reviewed post selection plus a fallback content author:

```json
{
    "source": {
        "posts_endpoint": "https://dutchdronesquad.nl/wp-json/wp/v2/posts"
    },
    "defaults": {
        "author_id": 1
    },
    "posts": [
        {
            "wordpress_id": 49916,
            "slug": "seizoen-25-26",
            "title": "Indoor seizoen 25/26",
            "published_at": "2025-09-18T10:15:00Z",
            "category": "announcement",
            "decision": "import"
        }
    ],
    "mappings": {
        "authors": {
            "7": {
                "user_id": 2
            }
        }
    }
}
```

An explicit `mappings.authors.{wordpress_author_id}.user_id` takes precedence over `defaults.author_id`; both must reference an existing Laravel user and never create a WordPress login account. Featured media must resolve through `mappings.media` before its post can be imported. Source category and tag IDs remain temporary classification hints in `mappings.posts`, while the reviewed manifest category becomes the permanent `ArticleCategory`. Existing post mappings are reused without overwriting later editorial changes. A slug owned by an unmapped article is reported as a conflict.

The post phase initially stores the REST API's rendered WordPress body unchanged. The cleanup phase then verifies that body against its import checksum before replacing it, so a rehearsal cannot silently overwrite later editorial changes.

The page phase fetches the complete published REST page inventory in one request and requires an explicit manifest decision for every returned page. It writes `mappings.pages.{wordpress_id}` and a replaceable Markdown review report; it never creates a generic page record. Repeated runs preserve review notes and approved states while resetting changed rewrite sources to `pending`.

The published page inventory was reconfirmed through REST on 2026-07-29:

| WordPress page          | Decision   | Explicit target                                                                                       |
| ----------------------- | ---------- | ----------------------------------------------------------------------------------------------------- |
| `49764` · `media`       | `rewrite`  | Manual code-owned `media-overview` at `/media`; preserve the nine mentions for review.                |
| `49747` · `oosterhout`  | `rewrite`  | Approved `Location` with slug `sporthal-oosterhout`; create it when missing and manually compare useful facts.                   |
| `49706` · `huisregels`  | `rewrite`  | Named route `house_rules`; keep the content code-owned.                                               |
| `49704` · `trainingen`  | `rewrite`  | Named route `events.index` with `type=training`; manually compare current schedule and guidance.      |
| `49498` · `sportpaleis` | `rewrite`  | Approved `Location` with slug `sportpaleis-alkmaar`; create it when missing and manually compare facts and selected photography. |
| `49486` · `koggenhal`   | `rewrite`  | Approved `Location` with slug `sporthal-koggenhal`; create it when missing and manually compare useful facts.                    |
| `2039` · `nieuws`       | `redirect` | Named route `news.index`.                                                                             |
| `319` · `contact`       | `rewrite`  | Named route `contact`; retain the implemented form and workflow.                                      |
| `318` · `stories`       | `gone`     | Explicit `410 Gone`; unused template page.                                                            |
| `317` · `our-work`      | `gone`     | Explicit `410 Gone`; unused template page.                                                            |
| `316` · `about-us`      | `redirect` | Named route `about`.                                                                                  |
| `315` · `home`          | `rewrite`  | Named route `home`; retain the implemented homepage.                                                  |

There is no separate published partner page in this inventory. Verified partner names, links, and logo assets therefore remain in the code-owned partner catalogue instead of entering the page importer.

Page selections use one of three constrained target shapes:

```json
{
    "source": {
        "pages_endpoint": "https://dutchdronesquad.nl/wp-json/wp/v2/pages"
    },
    "pages": [
        {
            "wordpress_id": 49498,
            "slug": "sportpaleis",
            "title": "Sportpaleis Alkmaar (indoor)",
            "decision": "rewrite",
            "target": {
                "type": "location",
                "location_slug": "sportpaleis-alkmaar"
            }
        },
        {
            "wordpress_id": 49704,
            "slug": "trainingen",
            "title": "Trainingsdagen",
            "decision": "rewrite",
            "target": {
                "type": "route",
                "route_name": "events.index",
                "query": {
                    "type": "training"
                }
            }
        },
        {
            "wordpress_id": 49764,
            "slug": "media",
            "title": "In de media",
            "decision": "rewrite",
            "target": {
                "type": "manual",
                "key": "media-overview",
                "path": "/media"
            }
        }
    ]
}
```

`gone` and `skip` decisions omit the target and require a reason. Only approved public named routes, existing Location slugs, and the constrained manual targets are accepted.

### Content Cleanup Representation

The cleanup phase stores imported `Article.content` as UTF-8 plain text. This matches the current public Article renderer, which deliberately renders content as text with preserved line breaks instead of injecting arbitrary HTML. Links, imported images, and approved YouTube embeds remain readable as labelled URLs. This avoids carrying WordPress theme markup into the public application or introducing an unreviewed rich-text renderer during migration.

For every mapped post, cleanup:

- removes shortcodes, scripts, forms, theme wrappers, social widgets, inline event handlers, and styling;
- flattens all heading levels into consistently separated plain-text headings;
- rewrites known post and structured-page links to their Laravel paths;
- rewrites original and WordPress-sized image URLs to imported `MediaAsset` URLs;
- preserves YouTube embeds as labelled links and removes unsupported iframes;
- records every transformation plus unresolved internal links, missing imported media, and suspicious markup in `mappings.posts.{wordpress_id}.cleanup` and the replaceable Markdown review report.

The cleanup metadata includes source and output SHA-256 checksums. A repeated run reuses an unchanged cleaned result. If Article content differs from both checksums, cleanup fails that item and preserves the manual edit. A dry-run computes the same diagnostics without changing Articles, the manifest, or the report.

Mapped WordPress pages do not have generic imported page bodies by design. Their useful content remains visible in the DDS-018 manual rewrite report and is normalized when editors move approved facts into the existing structured destination.

### Redirect Import And Review

The redirect phase derives stable candidates from `mappings.posts` and `mappings.pages`:

- post source URLs target `/news/{slug}` for the mapped Article;
- route, Location, and approved manual page mappings target their resolved local path;
- `gone` and `skip` page decisions remain visible as skips and do not create redirect responses;
- matching existing `Redirect` records are reused without changing manually maintained notes or active state.

Additional aliases collected from the WordPress XML export, historical inventories, or a usable sitemap can be added to the temporary manifest. These entries default to `pending` review and are created inactive; an explicit `approved` status activates them immediately:

```json
{
    "redirects": [
        {
            "source_url": "https://dutchdronesquad.nl/agenda/",
            "target_url": "/events",
            "status_code": 301,
            "provenance": "WordPress XML export and sitemap",
            "review": {
                "status": "pending",
                "notes": "Confirm that no competing calendar destination existed."
            }
        },
        {
            "source_path": "/oude-partners/",
            "target_url": "/partners",
            "decision": "import",
            "review": {
                "status": "approved"
            }
        }
    ]
}
```

Only local absolute targets are accepted. Source query strings are deliberately ignored because the public redirect middleware matches paths. Candidates that normalize to the same source and different targets, self-redirects, or proposals that disagree with an existing database target are blocked and reported without overwriting the existing record. Matching duplicates collapse into one candidate. Successful mappings are stored under `mappings.redirects` using a hash of the normalized source path.

The replaceable redirect review report lists provenance, status code, active state, pending decisions, and blocking conflicts. A dry-run produces the same console diagnostics without changing redirects, the manifest, or the report. Repeated imports reuse records through the unique normalized source path and do not create duplicates.

### Consolidated Staging Review

Every completed non-dry-run phase records its latest counters, diagnostics, item outcomes, and completion timestamp under `runs.{phase}` in the temporary manifest. This run history is migration evidence only; it does not add WordPress state to permanent models or dashboard resources.

The `report` phase combines that run history with the current mappings into `import-review.md`. Its command table and Markdown summary use the same selected, imported, reused, skipped, and failed totals. The report includes:

- completion status and timestamp for media, posts, pages, cleanup, and redirects;
- failed downloads/imports, explicit skips, redirect conflicts, and phases that have not run;
- missing media alt text, unresolved internal links, missing inline media, suspicious removed markup, and pending page or redirect reviews;
- traceability tables from WordPress sources to `MediaAsset`, `Article`, structured page targets, and durable `Redirect` records;
- a `READY` or `BLOCKED` launch-review outcome.

Skips remain visible but do not block launch. Missing phases, failed records, broken references, unresolved content diagnostics, and pending reviews produce a blocking command exit so staging automation cannot mistake an incomplete rehearsal for approval. `--dry-run` prints the same current-state assessment without writing the consolidated report.

The report is a temporary staging artifact rather than an application feature. Remove source snapshots, the XML archive, selection manifest, run history, and generated reports only after production cutover has been verified, the agreed rollback window has closed, and an archival backup is retained. Removing these artifacts must never delete normalized Articles, MediaAssets, Locations, Events, or public Redirects; those durable records do not depend on the importer at runtime.

### Two-Pass Staging Rehearsal

`wordpress:rehearse` runs `media`, `posts`, `pages`, `cleanup`, and `redirects` twice against the same staging manifest, then generates the consolidated import report. It captures each phase exit code and latest run counters after both passes. Idempotency requires every second-pass phase to report zero newly imported records and the global Article, MediaAsset, Redirect, Location, and Event counts to remain unchanged between passes.

After pass two, the command performs bounded HTTP smoke checks against the explicit staging base URL. It samples mapped Articles, MediaAssets, redirect sources, Location targets, and static/manual page targets. Content targets must return HTTP 200; redirect sources must return 301/302 with the mapped `Location` path. The checks use short connection and response timeouts and do not follow redirects while validating redirect targets.

The runner also requires `page-review.md`, `cleanup-review.md`, `redirect-review.md`, and `import-review.md`. It writes `rehearsal-review.md` plus a temporary `rehearsal` section in the manifest with:

- both pass results and persistent model counts;
- public sample outcomes;
- review-artifact presence;
- environment and staging URL;
- manual review approval;
- stable blocker IDs such as `DDS-022-B001`.

Automated HTTP checks do not replace visual or editorial judgment. Run the command without `--approve-manual-review` first, inspect representative public pages in a real browser and review the generated reports with an authorized admin/editor, convert every reported blocker into a concrete backlog item, and rerun after fixes with `--approve-manual-review`. The command returns success only when both passes, consolidated review, sample checks, artifacts, and manual approval are complete.

The rehearsal command refuses to run in `production` unless `--force` is supplied. Use that override only for an explicitly approved production cutover rehearsal; normal DDS-022 work belongs in staging.

Importer requirements:

- dry-run mode;
- idempotent imports;
- an explicit selection list or skip decision for source content;
- clear logs;
- stores source IDs and target mappings only in a temporary import manifest;
- does not overwrite manually edited content unless explicitly allowed;
- reports skipped records;
- reports broken media downloads;
- can run safely in staging more than once.

The importer is launch tooling, not a permanent application subsystem. Import-only manifests, diagnostics, and review reports should be removable after cutover; durable runtime state is limited to normalized domain records, stored media, and redirects that must continue serving legacy public URLs.

## Data Cleanup Rules

During import, normalize:

- mixed Dutch and English UI labels;
- WordPress shortcodes;
- embedded YouTube links;
- old button markup;
- absolute internal links;
- HTML entities;
- image captions;
- inconsistent heading levels.

Do not preserve theme-specific markup. Imported Article bodies use the documented plain-text representation; structured page content is rewritten into its first-class destination.

## Known Content To Exclude

- post comments;
- social share widgets;
- post view counters;
- generic donation/non-profit template sections;
- duplicated header, menu, sidebar, and footer content;
- theme-specific buttons and layout wrappers;
- old search/sidebar widgets unless recreated deliberately.

## DDS-015 Risks And Follow-Up Checks

- compare the administrator-provided XML export with the REST inventory before implementation;
- explain or safely tolerate the REST media count discrepancy without importing duplicates;
- verify every selected media URL and report missing files;
- review alt text in its actual rendering context because WordPress provides no reusable alt text for the inventoried media;
- map hidden WordPress author data without creating login accounts;
- normalize Gutenberg classes, malformed legacy links, headings, tables, galleries, HTML entities, and absolute internal URLs;
- review the nine external media-mention links for dead or missing destinations during the manual rewrite;
- derive redirect sources from REST links, the XML archive, and the approved matrix because the public sitemap did not provide a usable inventory during discovery.
