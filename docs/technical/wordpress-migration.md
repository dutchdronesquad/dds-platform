# WordPress Migration

## Goal

The new Laravel platform should make it easy to migrate valuable content from the current WordPress website without carrying over WordPress as a runtime dependency.

The migration should be repeatable, testable, and selective. Not everything from WordPress needs to move one-to-one. The goal is to preserve useful content, SEO value, media, and historical news while mapping it into stronger Laravel domain models.

## Recommended Strategy

Use a staged import:

1. Export WordPress content from the current site.
2. Store raw export files outside the application runtime, for example in `storage/app/imports/wordpress` during local development.
3. Build Laravel import commands for each content type.
4. Normalize imported data into first-class models such as `Article`, `Location`, `Event`, `Partner`, and `MediaAsset`.
5. Generate a redirect map from old WordPress URLs to new Laravel routes.
6. Run the importer repeatedly in a staging environment until the output is clean.

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

## Export Options

### WordPress REST API

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

Useful as a full backup-style import source.

Pros:

- available from WordPress admin;
- captures posts, pages, authors, terms, and attachments in one export;
- good as an archival source.

Cons:

- more parsing work;
- media download still needs care;
- repeated imports need stable deduplication logic.

### Direct Database Export

Only use if REST/XML is insufficient.

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

| WordPress                | Laravel                                 |
| ------------------------ | --------------------------------------- |
| `post_title`             | `title`                                 |
| `post_name`              | `slug`                                  |
| `post_excerpt`           | `excerpt`                               |
| `post_content`           | `content`                               |
| `post_date_gmt`          | `published_at`                          |
| `post_status`            | `status`                                |
| featured image           | `cover_image_id`                        |
| author                   | `author_id` or imported author metadata |
| selected categories/tags | `category` or future taxonomy tables    |

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
| partners                    | `Partner` records                                                                         |
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

Imported locale-keyed content must not pretend Dutch copy is English. The import review should flag translated fields that lack the required English base so they can be translated, rewritten, or deliberately skipped before publication.

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

Initial fixed mappings are provided by `RedirectSeeder`. Post, media, location, and other content-specific redirects remain the responsibility of the later WordPress importer because their final targets depend on imported records. Unused WordPress template pages such as `/about-us/`, `/our-work/`, and `/stories/` are not part of the migration map.

## Import Commands

Recommended command shape:

```txt
php artisan wordpress:import posts --source=rest
php artisan wordpress:import pages --source=rest
php artisan wordpress:import media --source=rest
php artisan wordpress:build-redirects
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

- empty excerpts;
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

## Open Questions

- Which WordPress posts should be migrated and which should be archived?
- Should old authors become real users or static author names?
- Do categories and tags matter in the new site?
- Should house rules be a static markdown-like page or admin-managed content?
- Do all media assets need to be migrated, or only media referenced by migrated content?
- Should redirects be stored in the database or webserver config?
