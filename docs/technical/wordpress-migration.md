# WordPress Migration

## Goal

The new Laravel platform should make it easy to migrate valuable content from the current WordPress website without carrying over WordPress as a runtime dependency.

The migration should be repeatable, testable, and selective. Not everything from WordPress needs to move one-to-one. The goal is to preserve useful content, SEO value, media, and historical news while mapping it into stronger Laravel domain models.

## Recommended Strategy

Use a staged import:

1. Capture the public WordPress REST responses used by the migration.
2. Store those source snapshots and the WordPress XML export outside the application runtime, for example in `storage/app/imports/wordpress` during local development.
3. Generate a small, editable selection manifest whose published posts default to `import` but can be changed individually to `skip`.
4. Use a few explicit Laravel import phases for selected media, posts, and redirects.
5. Normalize imported data into first-class models such as `Article`, `Location`, `Event`, and `MediaAsset`, while manually approved partner data moves into the code-owned partner catalogue.
6. Run dry-runs and repeat the import in staging until the output is clean.
7. Remove the temporary source snapshots, manifest, diagnostics, and import-only code after cutover verification and the rollback window.

## Keep The Importer Small

This is a one-time migration, not a permanent integration. Prefer direct, readable migration code over a generalized import framework.

- use the REST API as the only active import source;
- retain the WordPress XML export as an archive and cross-check, but do not build a second XML adapter unless REST proves incomplete for approved content;
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
| House Rules page                                        | Rewrite        | Manually maintain the code-owned `/house-rules` destination.                                                          |
| In the media page                                       | Rewrite        | Create a small code-owned overview from the nine reviewed external mentions; do not create thin `Article` records.    |
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
| Page `49764`, `media`, containing nine external mentions from 2017 through 2023 | Manual rewrite into a code-owned media overview; unresolved or dead external links remain visible in the review report.                    |

## Export Options

### WordPress REST API

Decision: primary and only active import source.

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

Decision: retain as an administrator-provided archive and completeness check. Do not implement an XML importer unless the comparison reveals approved content that REST cannot provide.

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

Initial fixed mappings are provided by `RedirectSeeder`. Post, media, location, and other content-specific redirects remain the responsibility of the later WordPress importer because their final targets depend on imported records. The approved page policy redirects `/about-us/` to `/about`; the unused `/our-work/` and `/stories/` template pages should return `410 Gone` without replacement redirects.

## Import Commands

Keep the command surface explicit and temporary. A suitable shape is:

```txt
php artisan wordpress:import media --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import posts --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import redirects --manifest=storage/app/imports/wordpress/selection.json --dry-run
```

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

Do not blindly preserve theme-specific markup. Convert content to clean HTML or a controlled rich text format.

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
