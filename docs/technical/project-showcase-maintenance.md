# Project Showcase Maintenance

## Phase 1 Policy

The public project showcase is maintained in code. There is no `Project` model,
database table, permission set, publication workflow, or `/dashboard/projects`
resource in phase 1.

Every public project change follows the pull-request workflow below. A CMS is
considered only from observed maintenance evidence recorded in this document,
not from anticipated needs.

## Source Map

| Concern                                | Source of truth                                                                         |
| -------------------------------------- | --------------------------------------------------------------------------------------- |
| Project content and display order      | `config/project_catalogue.php`                                                          |
| Allowed project types and Dutch labels | `app/Enums/ProjectType.php`                                                             |
| Catalogue validation                   | `app/Support/ProjectCatalogue.php` and `app/Support/ProjectCatalogueEntry.php`          |
| Public Inertia data shape              | `app/Support/PublicProjectData.php`                                                     |
| Public overview presentation           | `resources/js/pages/public/projects-index.tsx`                                          |
| Project-specific static media          | `public/images/projects/`                                                               |
| Catalogue and route contracts          | `tests/Feature/ProjectCatalogueTest.php` and `tests/Feature/PublicProjectPagesTest.php` |
| Responsive and interactive behavior    | Project tests in `tests/Browser/PublicSiteTest.php`                                     |

The array order in `config/project_catalogue.php` is the public display order.
The `slug` is the stable public identity and must not be changed after
publication merely to improve wording.

## Catalogue Fields

Each project entry uses the validated fields below:

| Field              | Rule                                                                                    |
| ------------------ | --------------------------------------------------------------------------------------- |
| `slug`             | Required, unique, lowercase URL slug. Treat as immutable after publication.             |
| `title`            | Required public title.                                                                  |
| `summary`          | Required concise description of the purpose and outcome.                                |
| `type`             | Required value from `ProjectType`. Add a new enum case only when no existing type fits. |
| `audience`         | Required description of who benefits from the project.                                  |
| `credits`          | Required non-empty list naming owners and relevant contributors.                        |
| `primary_link`     | Optional labelled HTTPS destination. Omit it when no honest public action exists.       |
| `supporting_links` | List of labelled HTTPS documentation, source, download, or context links.               |
| `media`            | List of versioned static image paths with useful Dutch alt text.                        |
| `featured`         | Optional boolean; the current overview has exactly one featured project.                |
| `video_url`        | Optional direct HTTPS URL ending in `.webm` or `.mp4`.                                  |

Only information intended for public presentation belongs in this file.
Internal planning, private projects, delivery status, credentials, contact
notes, commercial agreements, and operational project management stay out of
the catalogue.

## Asset Conventions

- Put project-specific images in `public/images/projects/`.
- Use lowercase kebab-case names starting with the project slug where practical,
  for example `trackdraw-editor.webp`.
- Prefer optimized WebP or AVIF for photographs and screenshots, and SVG for
  suitable marks or diagrams.
- Reuse an existing canonical DDS image path when the same photograph already
  belongs elsewhere under `public/images/`; do not create a duplicate merely to
  place it in the project folder.
- Use `dark_path` only when the regular image does not work in dark mode.
- Keep paths root-relative and free of query strings, fragments, or parent
  traversal.
- Write alt text for what the image contributes in this showcase context. Do
  not repeat the filename or add “image of”.
- Confirm DDS has the right to publish the asset and credit its maker where
  relevant.

The admin media library is not part of this workflow. Project media remains
versioned with the application until the CMS decision gate is approved.

## Pull-Request Workflow

### 1. Establish public value

Before editing, confirm that the project has:

- a clear public audience;
- a credible purpose or outcome;
- verified ownership and credits;
- current supporting material;
- no private or operational information.

Small internal tools without a useful public story should not be added.

### 2. Edit the catalogue and assets

Create a feature branch from `main`, add or update assets, then edit
`config/project_catalogue.php`.

- Append or deliberately reposition the complete entry; order changes are
  editorial changes and must be explained in the pull request.
- Reuse an existing `ProjectType` where it fits.
- Open every external link manually. Automated validation proves the URL shape
  is safe, not that the destination still exists or says the right thing.
- Preserve an existing slug. If a public URL ever depends on it, add a redirect
  as part of an explicitly reviewed route change instead of silently renaming
  it.
- Do not add a generic detail route or a speculative `has_case_page` flag.
  A dedicated case page needs enough content and visual material to justify its
  own art-directed implementation and tests.

### 3. Run the focused checks

Run the application through DDEV:

```bash
ddev artisan test --compact tests/Feature/ProjectCatalogueTest.php
ddev artisan test --compact tests/Feature/PublicProjectPagesTest.php
ddev npm run types:check
```

When presentation, media, filtering, or responsive behavior changes, also run:

```bash
ddev artisan test --compact tests/Browser/PublicSiteTest.php --filter=project
ddev npm run build
```

### 4. Preview the public result

Start the frontend with `ddev npm run dev` and review `/projects` at the normal
DDEV application URL.

Check:

- narrow mobile and wide desktop layouts;
- light and dark appearance;
- project ordering, type filters, and visible-result count;
- image crop, legibility, alt text, and video fallback;
- keyboard focus and activation;
- external destinations and credits;
- the featured project and the balance between software, hardware, and
  community work.

### 5. Open the pull request

The pull-request description must state:

- which catalogue entries, order, links, credits, cases, or assets changed;
- why the content is public and who its audience is;
- whether any existing slug or public destination changed;
- which checks and preview states were completed;
- the maintenance-log row added for the change.

At least one reviewer checks public suitability, factual accuracy, credits,
external destinations, asset rights, responsive presentation, and the focused
test results. A catalogue change is published only after the normal review,
checks, merge, and deployment complete.

## Maintenance Evidence

Every pull request that changes project content or ordering adds one row. A
quarterly review adds a row even when no catalogue change occurred. Count only
showcase maintenance, not refactors that leave the public content unchanged.

| Date       | Pull request | Change kinds                                                        | Requested by    | Lead time        | Time-sensitive delay | Notes                                                               |
| ---------- | ------------ | ------------------------------------------------------------------- | --------------- | ---------------- | -------------------- | ------------------------------------------------------------------- |
| 2026-07-24 | #24          | Initial catalogue, order, links, credits, and overview presentation | DDS maintainers | Initial delivery | No                   | Phase 1 baseline: 9 public entries, maintained through code review. |

Use one or more change kinds from: `entry`, `order`, `link`, `credit`, `case`,
`media`, `lifecycle`, or `review-only`. Lead time runs from a concrete,
ready-to-publish request to the merged pull request. Record whether the
code-owned workflow itself caused a requested publication date to be missed.

Review the evidence quarterly and after every sixth catalogue-changing pull
request in a rolling 90-day period, whichever comes first.

## CMS Decision Gate

Meeting a trigger opens a documented review; it does not automatically approve
a CMS. Reconsider structured project management when at least one of these
signals is observed:

1. A named non-technical editor needs independent access and has submitted at
   least two real catalogue changes that required developer mediation.
2. Six or more catalogue-changing pull requests merge within 90 days.
3. Three time-sensitive changes within 90 days miss their agreed publication
   date solely because the pull-request workflow could not deliver them in
   time.
4. The catalogue reaches at least 20 active entries and maintainers repeatedly
   need draft, scheduled, archived, or operational filtering state to manage
   them.
5. Drafting, scheduling, or archiving is requested for at least two real
   projects; these lifecycle needs do not belong in the public config file.
6. A second production consumer needs independently editable project data and
   cannot reasonably use the existing code-owned public projection.

The review records the evidence in `docs/decisions/README.md` or a dedicated
ADR and chooses one outcome:

- keep the code-owned workflow;
- remove a specific source of pull-request friction without adding a CMS;
- approve a separately scoped model and dashboard migration.

Until that decision is approved, do not add project tables, models, policies,
permissions, Form Requests, media-library attachment, publication state,
reordering UI, or dashboard forms.

## Future Migration Contract

If the decision changes, the later design must:

- import the catalogue through a tested, repeatable migration rather than
  manual re-entry;
- preserve every existing slug and public URL, adding explicit redirects only
  for approved URL changes;
- preserve credits, link labels and destinations, media meaning, alt text, and
  deterministic order;
- keep the existing public Inertia data contract where practical so the
  art-directed overview can evolve independently from storage;
- support dedicated case presentations instead of forcing all projects into one
  generic template;
- define authorization, validation, publication, archival, and media ownership
  from the observed needs that opened the gate;
- avoid dual sources of truth by switching reads only after imported data has
  been verified.

A generic page builder is not the migration target.
