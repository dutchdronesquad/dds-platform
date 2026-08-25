# WordPress Importer Operator Runbook

## Purpose

This is the operational guide for preparing, running, reviewing, repeating, and eventually removing the temporary WordPress importer. The design decisions and source inventory remain in [WordPress Migration](./wordpress-migration.md); this document is the procedure an operator and reviewer should follow.

The importer is temporary launch tooling. It writes durable `Article`, `MediaAsset`, and `Redirect` records, but keeps WordPress IDs, run evidence, review state, and source-to-target mappings in a temporary JSON manifest.

## Current Operational Status

The importer, offline source capture, and two-pass rehearsal runner are implemented and automatically tested. DDS-022A and DDS-022B are not complete until this procedure has been executed against the approved WordPress inventory in a real staging environment.

The local ignored workspace contains the 2026-08-08 XML export, approved selection manifest, and a verified offline source bundle. These temporary files are deliberately not committed. On 2026-08-16 a controlled local writing run and approved two-pass rehearsal reached `READY`: all five phases were idempotent, persistent counts stayed stable, all 15 public samples passed, selected media had reviewed alt text, and the page/cleanup diagnostics had explicit dispositions. This is diagnostic evidence, not DDS-022A staging evidence. Content cleanup remains a review gate whenever its dry-run reports unresolved links, missing media, or suspicious markup: inspect and explicitly accept the possible information loss before running the writing cleanup phase. Staging still needs a secure copy of that exact bundle, its own valid author and destination IDs, a public URL, and access credentials. Do not substitute test fixtures or the `legacy.example` manifests under `storage/app` for those inputs.

## Responsibilities

Assign these responsibilities before starting:

| Responsibility     | Required work                                                                                                                  |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------ |
| Import operator    | Prepares staging, validates the manifest, runs commands, preserves evidence, and restores the baseline when necessary.         |
| Content reviewer   | Reviews Articles, dates, authors, categories, media, alt text, page rewrites, skips, and cleanup diagnostics.                  |
| Technical reviewer | Reviews failures, idempotency, redirects, HTTP samples, record counts, storage, logs, and blocker resolution.                  |
| Release owner      | Accepts the final `READY` evidence and decides when temporary artifacts may be archived and removed after the rollback window. |

One person may hold multiple roles, but the ticket evidence must identify who performed the manual visual/admin review.

## Command Reference

All commands use the same temporary manifest:

```bash
php artisan wordpress:snapshot --manifest=storage/app/imports/wordpress/selection.json --xml=storage/app/imports/wordpress/dutchdronesquad.WordPress.2026-08-08.xml --output=storage/app/imports/wordpress/source-inventory
php artisan wordpress:import media --manifest=storage/app/imports/wordpress/selection.json [--dry-run]
php artisan wordpress:import posts --manifest=storage/app/imports/wordpress/selection.json [--dry-run]
php artisan wordpress:import pages --manifest=storage/app/imports/wordpress/selection.json [--report=storage/app/imports/wordpress/page-review.md] [--dry-run]
php artisan wordpress:import cleanup --manifest=storage/app/imports/wordpress/selection.json [--report=storage/app/imports/wordpress/cleanup-review.md] [--dry-run] [--refresh-source]
php artisan wordpress:import redirects --manifest=storage/app/imports/wordpress/selection.json [--report=storage/app/imports/wordpress/redirect-review.md] [--dry-run]
php artisan wordpress:import report --manifest=storage/app/imports/wordpress/selection.json [--report=storage/app/imports/wordpress/import-review.md] [--dry-run]
```

The complete two-pass rehearsal is:

```bash
php artisan wordpress:rehearse \
  --manifest=storage/app/imports/wordpress/selection.json \
  --base-url=https://staging.example.com
```

After all reported blockers are resolved and the browser/admin review is recorded in DDS-022B, repeat it with:

```bash
php artisan wordpress:rehearse \
  --manifest=storage/app/imports/wordpress/selection.json \
  --base-url=https://staging.example.com \
  --approve-manual-review
```

`--approve-manual-review` is an operator assertion, not an automated review. Never pass it before the DDS-022B checklist is complete.

Use `--refresh-source` only with the cleanup phase when the normalized Article still matches its recorded cleanup checksum but must be rebuilt from the verified offline source bundle, for example after fixing a normalizer bug. The command verifies the original source checksum and still refuses to overwrite unrelated manual edits. Recheck and reapprove every changed cleanup output afterwards.

Cleanup output uses safe Markdown. A legacy `plain_text` cleanup is automatically rebuilt from the checksum-verified source, imported inline images remain visible, and the first imported inline image becomes the cover when WordPress did not define featured media. Existing covers and later editorial changes are preserved.

## DDS-022A: Provision And Execute Staging

### 1. Collect The Approved Inputs

The ticket must contain or reference:

- the administrator-provided WordPress XML export and its capture date;
- the frozen offline source bundle containing only selected posts and media, all required page decisions, the WordPress XML cross-check, and SHA-256 checksums;
- the completed selection manifest;
- the public staging base URL;
- staging application, database, and media-storage access;
- the staging `Team DDS` fallback author ID and any explicit WordPress-author mappings;
- confirmation that the expected `Location` records and public routes already exist;
- the reviewer names and the planned rollback window.

Create the source bundle while WordPress is still available. After capture, the active importer reads only the local JSON and selected media files. REST is no longer needed for rehearsal or cutover. XML remains a single completeness cross-check; unselected template and gallery attachments are deliberately not copied into the working bundle.

### 2. Prepare A Recoverable Staging Baseline

Before the first writing command:

1. Deploy the exact importer branch or commit that will be reviewed.
2. Confirm `APP_ENV=staging` and that `APP_URL` points to the public staging host.
3. Apply migrations and seed the required baseline records.
4. Verify the configured media disk is writable and publicly readable through staging.
5. Take a database backup and a media-storage snapshot.
6. Record the baseline Article, MediaAsset, Redirect, Location, and Event counts.
7. Ensure no production database or production media disk is configured.

Do not run the rehearsal directly against production. The command refuses production unless `--force` is supplied; that override belongs only to a separately approved production cutover procedure.

### 3. Create The Import Directory

The directory is temporary and must not be committed:

```bash
mkdir -p storage/app/imports/wordpress
```

Place these files there:

```text
storage/app/imports/wordpress/
├── selection.json
├── dutchdronesquad.WordPress.2026-08-08.xml
└── source-inventory/
    ├── snapshot.json        # counts, provenance, relative paths, and SHA-256 checksums
    ├── posts.json
    ├── pages.json
    ├── media.json
    ├── wordpress-export.xml
    └── media/{wordpress_id}-{filename}  # one flat directory, selected files only
```

After execution, the importer adds the review reports in the same directory.

Build the bundle once while the old site is reachable:

```bash
php artisan wordpress:snapshot \
  --manifest=storage/app/imports/wordpress/selection.json \
  --xml=storage/app/imports/wordpress/dutchdronesquad.WordPress.2026-08-08.xml \
  --output=storage/app/imports/wordpress/source-inventory
```

The command inventories the public REST collections, then stores only posts with `decision: import`, media with `decision: import`, and every page needed for rewrite/redirect/gone handling. It records a checksum for every retained source file, copies the XML export as one cross-check file, and adds `source.snapshot_directory` to `selection.json`. It refuses to overwrite an existing bundle unless `--force` is supplied. Use `--force` only to replace that exact temporary directory with a newly captured bundle.

### 4. Build `selection.json`

Use valid JSON without comments. The operator owns `source`, `defaults`, selection lists, explicit redirects, and initial author mappings. The importer owns generated `mappings`, `runs`, and `rehearsal` data; do not delete or casually edit those sections between repeated runs.

The page phase creates the three code-approved DDS locations when their target slug does not exist yet. Repeated imports preserve later editorial changes. Unknown location slugs remain a blocking error.

A representative manifest shape is:

```json
{
    "source": {
        "snapshot_directory": "storage/app/imports/wordpress/source-inventory",
        "media_endpoint": "https://www.example.com/wp-json/wp/v2/media",
        "posts_endpoint": "https://www.example.com/wp-json/wp/v2/posts",
        "pages_endpoint": "https://www.example.com/wp-json/wp/v2/pages"
    },
    "defaults": {
        "author_id": 1
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
            "reason": "Not referenced by approved content."
        }
    ],
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
        },
        {
            "wordpress_id": 318,
            "slug": "stories",
            "title": "Stories",
            "decision": "gone",
            "reason": "Unused template page; return 410 without a redirect."
        }
    ],
    "redirects": [
        {
            "source_path": "/agenda/",
            "target_url": "/events",
            "status_code": 301,
            "provenance": "WordPress XML export and approved inventory",
            "review": {
                "status": "pending",
                "notes": "Confirm no competing calendar destination existed."
            }
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

Allowed post categories are `news`, `announcement`, `community`, and `race_report`.

Manifest rules:

- every media and post entry needs a unique positive `wordpress_id` and `import` or `skip` decision;
- reviewed reusable media alt text belongs in the optional non-empty `media[].alt_text` field so it remains portable without generated local `mappings.media.*.media_asset_id` values;
- skipped content needs a meaningful `reason`;
- every post needs a slug, title, ISO-8601 publication date, and supported category;
- the published page inventory must be complete: every REST page needs `rewrite`, `redirect`, `gone`, or `skip`;
- `rewrite` and `redirect` pages need an approved `location`, `route`, or constrained `manual` target;
- `gone` and `skip` pages need a reason and do not create Redirect records;
- additional redirects accept only local absolute targets; pending redirects remain inactive;
- author IDs must already exist in staging; the importer never creates login accounts.

The example is a schema illustration, not the approved DDS inventory. Do not run it unchanged.

Keep the three endpoint URLs as source provenance even after `snapshot_directory` is present. The importer always prefers the verified local bundle and will reject changed or missing files by checksum; it does not silently fall back to the network when a configured bundle is damaged.

### 5. Validate In Dependency Order

The phases are dependent. A post dry-run cannot resolve featured media until the media phase has written its mappings. Therefore use this sequence:

```bash
# 1. Validate and import selected media.
php artisan wordpress:import media --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import media --manifest=storage/app/imports/wordpress/selection.json

# 2. Validate and import selected posts using the media mappings.
php artisan wordpress:import posts --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import posts --manifest=storage/app/imports/wordpress/selection.json

# 3. Validate the complete published page inventory and write mappings.
php artisan wordpress:import pages --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import pages --manifest=storage/app/imports/wordpress/selection.json

# 4. Preview and apply content cleanup.
php artisan wordpress:import cleanup --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import cleanup --manifest=storage/app/imports/wordpress/selection.json

# 5. Preview and import redirects.
php artisan wordpress:import redirects --manifest=storage/app/imports/wordpress/selection.json --dry-run
php artisan wordpress:import redirects --manifest=storage/app/imports/wordpress/selection.json

# 6. Generate the consolidated review.
php artisan wordpress:import report --manifest=storage/app/imports/wordpress/selection.json
```

Stop after a failed phase, inspect its console output and report, correct the manifest or staging baseline, and rerun that phase. Do not skip forward to dependent phases merely to obtain more output.

### 6. Reset Before Capturing Final Rehearsal Evidence

The controlled phase-by-phase run is for validating the inputs. Before producing final DDS-022A evidence:

1. Archive its reports for diagnosis if useful.
2. Restore the database and media storage to the recorded staging baseline.
3. Restore a clean approved `selection.json` without generated `mappings`, `runs`, or `rehearsal` sections, while retaining reviewed `media[].alt_text` values.
4. Confirm the baseline model counts again.
5. Keep the same deployed commit and source inventory.

This ensures pass one demonstrates creation from a clean baseline and pass two demonstrates reuse. Running the rehearsal on an already imported staging database can still check stability, but it is not sufficient final evidence for DDS-022A.

### 7. Run The Two-Pass Rehearsal

Run without manual approval first:

```bash
php artisan wordpress:rehearse \
  --manifest=storage/app/imports/wordpress/selection.json \
  --base-url=https://staging.example.com
```

The expected first result is usually `BLOCKED` because the manual DDS-022B review has not yet been approved. DDS-022A succeeds operationally when:

- all five phases complete in both passes;
- pass two imports zero new records in every phase;
- Article, MediaAsset, Redirect, Location, and Event counts remain stable between passes;
- public HTTP and redirect samples pass;
- all five reports are retained;
- every remaining blocker is concrete enough to assign in DDS-022B.

Required evidence:

```text
page-review.md
cleanup-review.md
redirect-review.md
import-review.md
rehearsal-review.md
selection.json              # including generated mappings/runs/rehearsal evidence
```

Also attach the deployed commit, staging URL, XML checksum/capture date, baseline backup reference, and operator name to DDS-022A.

## DDS-022B: Review, Resolve, And Approve

### 1. Triage Generated Blockers

Read reports in this order:

1. `rehearsal-review.md` for phase failures, idempotency, model counts, HTTP samples, missing artifacts, and `DDS-022-B###` blockers;
2. `import-review.md` for the consolidated `READY`/`BLOCKED` assessment and source-to-target traceability;
3. `cleanup-review.md` for unresolved internal links, missing inline media, suspicious markup, and transformations;
4. `redirect-review.md` for conflicts, inactive pending redirects, and provenance;
5. `page-review.md` for structured-page rewrite approvals and explicit `410` decisions.

Create or update one owned backlog item for every generated blocker. Use this minimum format:

```markdown
### DDS-022-B###: Short actionable title

- Evidence: report name, source ID/path, and observed result
- Owner: named person or team
- Decision: fix / accepted manual cleanup / approved skip
- Required change: concrete content, mapping, media, redirect, or code action
- Verification: command or staging URL that proves resolution
- Status: open / resolved
```

Do not close a blocker with only “accepted”. Record why it is safe, who accepted it, and whether work moves to the final content/cutover ticket.

### 2. Public Browser Review Matrix

The rehearsal runner performs HTTP checks, not visual or editorial review. Inspect these samples in a real browser on desktop and mobile:

| Surface        | Minimum sample                                                                                  | Verify                                                                                          |
| -------------- | ----------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Articles       | newest, oldest, each imported category, one with inline media, one with YouTube                 | title, author, date, category, line breaks, links, media, video link, no WordPress/theme markup |
| Media          | landscape image, portrait image, transparent logo, GIF if selected, PDF if selected             | loads publicly, correct file/type, dimensions, caption/alt context, no broken source URL        |
| Redirects      | old post, `/about-us/`, training query target, Location page, news index, approved manual alias | correct 301/302, exact target and query string, no loop or chain                                |
| Gone pages     | `/our-work/` and `/stories/`                                                                    | approved `410 Gone` behavior is present in the launch solution; no misleading redirect          |
| Locations      | Sportpaleis, Koggenhal, Oosterhout                                                              | existing record retained, approved facts/photos merged, no duplicate generic page               |
| Static targets | home, about, contact, house rules, training view, media overview, partners where applicable     | approved content destination, navigation/canonical URL, no stale WordPress layout               |

Record every reviewed URL, reviewer, date, viewport, and result in DDS-022B. Screenshots are useful evidence for failures but are not required for every passing page.

### 3. Admin And Editorial Review Matrix

An authorized admin/editor reviews:

- Articles: count, title, slug, author, original publication date, status, category, cover image, and content;
- MediaAssets: count, filename, MIME type, dimensions, archive state, public preview, and reusable alt text;
- Redirects: normalized source path, exact target/query, status, active state, review note, and conflict status;
- structured pages: Location/training/house-rules/media rewrite decisions and approval notes in `page-review.md`;
- explicit skips and `410` decisions against the approved inventory;
- report totals against the final manifest and REST/XML inventory.

The importer deliberately has no permanent WordPress-import dashboard. Review the source-agnostic admin resources plus the temporary reports.

### 4. Resolve And Rerun

For each blocker:

1. Fix the manifest, source selection, destination content, media, redirect, or application code.
2. Restore or deliberately update the staging baseline if the fix changes durable records.
3. Rerun the affected phase in dry-run mode.
4. Apply the phase and regenerate `import-review.md`.
5. Rerun the complete two-pass rehearsal without manual approval.
6. Confirm the blocker disappeared and update its backlog disposition.

Repeat until the only remaining blocker is the missing manual approval assertion. Then record the reviewer and review date in DDS-022B and run:

```bash
php artisan wordpress:rehearse \
  --manifest=storage/app/imports/wordpress/selection.json \
  --base-url=https://staging.example.com \
  --approve-manual-review
```

DDS-022B is complete only when `rehearsal-review.md` says `READY`, `import-review.md` says `READY`, every `DDS-022-B###` item has an owned resolved disposition, and the manual workload for production cutover is documented.

## Understanding Generated Manifest Data

The importer adds these temporary sections:

| Path                   | Purpose                                                                                 |
| ---------------------- | --------------------------------------------------------------------------------------- |
| `mappings.media.*`     | WordPress attachment to MediaAsset, source metadata, checksum, and import timestamp     |
| `mappings.posts.*`     | WordPress post to Article, author/media references, source checksum, and cleanup result |
| `mappings.pages.*`     | WordPress page decision, resolved structured target, checksum, excerpt, and review      |
| `mappings.redirects.*` | normalized source to durable Redirect and review provenance                             |
| `runs.{phase}`         | latest counters, diagnostics, item outcomes, and completion timestamp                   |
| `rehearsal`            | two-pass evidence, counts, samples, artifacts, blockers, status, and timestamp          |

Keep this data through staging, final import verification, and the agreed rollback window. It is not runtime configuration.

## Common Failures

| Message or symptom                                  | Action                                                                                                     |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| manifest not found                                  | verify the absolute path, deployment host, filesystem permissions, and that the approved file was uploaded |
| source bundle already exists                        | retain it, or use `wordpress:snapshot --force` only when intentionally replacing that exact bundle         |
| REST total exceeds retained bundle count            | expected for curated posts/media; compare `source_count` with `count` in `snapshot.json`                   |
| source bundle checksum fails                        | restore or recapture the frozen bundle; never continue with changed source files                           |
| missing or invalid endpoint                         | correct `source.*_endpoint`; only HTTP(S) WordPress REST URLs are accepted                                 |
| media mapping missing during posts                  | successfully run the media phase first and confirm the featured attachment is selected                     |
| reviewed alt text disappears on a clean database    | store it in `media[].alt_text`; never carry generated local `media_asset_id` mappings into another database |
| author mapping missing                              | map the WordPress author to an existing user or set a valid `defaults.author_id`                           |
| slug conflict                                       | review the existing Article; do not overwrite an unrelated record                                          |
| published page lacks a manifest decision            | update the complete page inventory; never silently create or skip a page                                   |
| manual Article content differs from import checksum | preserve the editorial change; review and resolve instead of forcing cleanup                               |
| unresolved links or missing inline media            | add/fix the destination mapping or create an owned manual-cleanup blocker                                  |
| redirect conflict                                   | choose one reviewed target; the importer deliberately preserves the existing database target               |
| second pass imports records                         | stop cutover; inspect missing mappings/deleted targets and fix idempotency                                 |
| public sample is not HTTP 200                       | inspect deployment, route, publication state, media visibility, and staging logs                           |
| redirect sample has wrong Location                  | correct target/query and check for a route or redirect chain                                               |
| report remains BLOCKED                              | resolve every listed failure/pending review; do not bypass with `--approve-manual-review`                  |

## Production Cutover And Artifact Removal

The final production import belongs to DDS-027, after DDS-022 is `READY` and runtime/deployment work is complete. Use the frozen, approved staging manifest and repeat the dependency order against a recoverable production baseline. Do not reuse staging database IDs blindly; author and destination IDs must be valid in production.

After production verification:

1. retain the XML export, frozen manifest, reports, deployed commit, and backup reference through the agreed rollback window;
2. confirm normalized Articles, MediaAssets, structured content, and Redirects work without the source files;
3. archive the evidence outside the application runtime;
4. remove WordPress source snapshots, manifest, generated reports, and import-only code in a dedicated cleanup change;
5. never delete durable imported records or media merely because the temporary importer is removed.
