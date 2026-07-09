# Initial Build Backlog

## Purpose

This backlog translates the preparation docs into the first practical implementation sequence. It assumes:

- Laravel modern monolith;
- React, Inertia, TypeScript, and Tailwind;
- DDEV with PostgreSQL;
- custom admin, no Filament in phase 1;
- English-default bilingual content with `en` and `nl`;
- no locale-prefixed URLs in phase 1;
- public dated activities are called `Events`;
- trainings are `Event` records with `type = training`;
- WordPress import comes after the initial application foundation and target models.
- UI/UX quality is part of the definition of done for every user-facing ticket.

## Global UX Acceptance Criteria

These criteria apply to public and admin UI tickets unless explicitly scoped out:

- user intent is clear on each page;
- primary actions are easy to find and visually distinct;
- responsive layout works on mobile and desktop;
- text hierarchy supports scanning;
- forms include labels, validation, helper text where needed, and feedback states;
- loading, empty, error, and success states are considered;
- keyboard focus states and accessible contrast are present;
- UI follows established project patterns instead of one-off styling.

## Epic 1: Laravel Foundation

### DDS-001: Scaffold Laravel Application

Status: complete. Laravel is scaffolded, the React/Inertia/TypeScript/Tailwind starter is present, the local DDEV URL shows the Laravel start screen, migrations run, tests pass, and the production asset build succeeds.

Goal: create the base Laravel application in this repository.

Tasks:

- scaffold a fresh Laravel app;
- install the Laravel starter kit with React, Inertia, TypeScript, and Tailwind;
- keep default app structure close to Laravel conventions;
- verify the default app runs locally.

Acceptance criteria:

- Laravel app exists in the repository;
- Vite builds frontend assets;
- Inertia page rendering works;
- no domain-specific code is introduced yet.

### DDS-002: Configure DDEV With PostgreSQL

Status: complete. DDEV is configured, PostgreSQL works, migrations run successfully, and the local app URL works.

Goal: make local development reproducible.

Tasks:

- configure DDEV for Laravel;
- use PostgreSQL as the database service;
- configure Node/Vite usage through DDEV workflow;
- add Mailpit if supported cleanly at this stage;
- document the local startup commands.

Acceptance criteria:

- app runs through DDEV;
- database connection uses PostgreSQL;
- migrations can run locally;
- local URL works over HTTPS or DDEV default routing;
- setup steps are documented.

### DDS-002A: Configure DDEV Vite Dev Server

Status: complete. Vite is exposed through DDEV on `https://dds-platform.ddev.site:5173`, and `vite.config.ts` is configured to use the DDEV host for dev server origin and HMR.

Goal: make Vite dev mode work through the DDEV URL without distorted or missing assets.

Tasks:

- expose Vite port `5173` through DDEV;
- configure Vite to listen on `0.0.0.0`;
- configure DDEV-aware Vite origin and HMR settings;
- document when to use `ddev npm run dev` versus `ddev npm run build`;
- document the role of `public/hot`.

Acceptance criteria:

- app is opened through `https://dds-platform.ddev.site`;
- `ddev npm run dev` serves hot assets through `https://dds-platform.ddev.site:5173`;
- `public/hot` points to the DDEV Vite URL while dev mode is running;
- production-style local checks can use built assets after removing `public/hot`.

### DDS-003: Baseline Quality Tooling

Status: baseline complete. `ddev artisan test` and `ddev npm run build` pass. CI can still be added as a follow-up once the initial codebase is committed.

Goal: establish basic project checks early.

Tasks:

- configure Pest;
- configure Pint;
- configure TypeScript checking;
- configure frontend linting if provided by starter kit;
- add initial CI workflow.

Acceptance criteria:

- backend tests run;
- formatting command runs;
- frontend typecheck runs;
- CI runs the same baseline checks.

### DDS-003A: Document Runtime Follow-Ups

Goal: track infrastructure items that are intentionally deferred from the first local scaffold.

Tasks:

- document that early local development uses database-backed cache, sessions, and queues;
- note Redis as a later production/runtime requirement;
- note future queue worker and scheduler process requirements;
- note future production mail provider and storage backup requirements.

Acceptance criteria:

- deferred runtime items are visible in docs;
- Redis is not forgotten;
- first local setup stays simple.

## Epic 2: Auth, Layouts, And Locale Baseline

### DDS-004: Authentication And Admin Gate

Status: implemented locally. `spatie/laravel-permission` is installed and configured, the package migration/config are published, initial `admin` and `editor` roles are seeded, `/dashboard` is protected by auth, email verification, and role middleware, CRUD permissions are available for concrete admin actions, and a repeatable first-admin command exists through `php artisan dds:make-admin`. Backend tests and TypeScript checks pass locally. Final DDEV migration, seeding, build, and browser verification are still pending.

Goal: enable login and protect the starter `/dashboard` route as the first management entrypoint.

Tasks:

- use starter kit authentication;
- install and configure `spatie/laravel-permission`;
- create initial `admin` and `editor` roles;
- use the existing `/dashboard` route from the starter kit;
- protect `/dashboard` with auth middleware;
- protect admin shell access through roles;
- protect concrete admin actions through permissions;
- add a first admin user creation command.

Acceptance criteria:

- unauthenticated users cannot access `/dashboard`;
- authenticated admin user can access `/dashboard`;
- non-admin behavior is defined;
- role and permission checks use Spatie Permission instead of a custom boolean-only approach;
- first admin account can be created repeatably for local/staging setup.

Local verification commands:

- `ddev artisan migrate`;
- `ddev artisan db:seed`;
- `ddev artisan dds:make-admin`;
- `ddev npm run types:check`;
- `ddev npm run lint:check`;
- `ddev npm run build`;
- `ddev artisan test --compact`.

### DDS-004A: Initial Commit Checkpoint

Goal: define when the first GitHub commit should happen.

Tasks:

- review scaffolded Laravel/DDEV files;
- verify `.env` is ignored and `.env.example` is safe;
- ensure `composer.json` metadata matches DDS Platform;
- ensure migrations, tests, and production build pass;
- commit the validated scaffold and docs before domain work starts.

Acceptance criteria:

- `ddev artisan migrate` passes;
- `ddev artisan test` passes;
- `ddev npm run build` passes;
- initial commit contains docs, scaffold, DDEV config, and baseline tooling;
- no local secrets are committed.

### DDS-004B: DDS-004 Pull Request Verification

Goal: finish the first admin-access slice through GitHub review.

Tasks:

- push the `codex/dds-004-admin-gate` branch;
- open a ready pull request against `main`;
- verify GitHub Actions for backend tests, frontend typecheck/build, and formatting;
- record any CI-specific follow-ups separately.

Acceptance criteria:

- PR is open and not marked as draft;
- CI passes or failures are triaged into concrete follow-up tasks;
- branch contains the DDS-004 admin access foundation only;
- local verification commands are noted in the PR description.

### DDS-005: Public And Admin Layout Shells

Goal: harden the existing starter layouts into intentional DDS public and management shells.

Tasks:

- turn the existing authenticated app layout into the first management layout for `/dashboard`;
- decide whether the public site needs a separate `PublicLayout` immediately or can start from dedicated public page components;
- replace generic starter navigation labels, logo treatment, and dashboard placeholders with DDS-oriented structure;
- add basic public header/footer structure for the first public pages;
- add basic management sidebar/topbar structure using the existing starter layout patterns;
- keep styling minimal but coherent;
- establish initial spacing, typography, navigation, and interaction conventions;
- preserve the starter authentication/settings UX unless there is a clear DDS reason to change it.

Acceptance criteria:

- public pages have a clear layout direction and do not rely on generic starter visuals;
- management pages render through the existing authenticated app layout;
- `/dashboard` feels like a DDS management entrypoint rather than an untouched starter dashboard;
- navigation placeholders exist;
- mobile and desktop layout shells are usable;
- focus, hover, and active states are visible;
- no final design polish is required yet, but the UX baseline must be intentional.

### DDS-006: Locale Configuration

Goal: prepare bilingual content without adding locale-prefixed URLs.

Tasks:

- configure supported locales `nl` and `en`;
- set `nl` as default locale;
- define a simple content translation approach for phase 1;
- make admin forms able to expose locale-specific fields later.

Acceptance criteria:

- app default locale is `en`;
- supported locales are explicit in config;
- no `/nl` or `/en` route prefix is required;
- implementation notes describe how translatable content fields should be stored.

### DDS-006A: Runtime Locale And Translation UX

Goal: turn the locale configuration into a runtime language experience, using useful patterns from NIPKaart without introducing locale-prefixed URLs.

Tasks:

- add request locale middleware that chooses the locale from an authenticated user preference, a guest cookie, browser preference, and finally the configured default;
- keep supported locale validation driven by locale config instead of hardcoded locale lists;
- share the active locale and supported locales with Inertia;
- decide whether users need a persisted `locale` preference in phase 1 or whether guest-cookie behavior is enough until account settings mature;
- add a small language switcher only when there are translated UI strings to switch between;
- define the frontend translation bundle shape for React, likely JSON namespaces grouped by domain such as `frontend`, `backend`, and `global`;
- avoid adding locale route prefixes, redirects, or duplicate page URLs.

Acceptance criteria:

- the active locale can be resolved per request without changing the URL shape;
- unsupported locale values are rejected or ignored consistently;
- Inertia pages can read the active locale from shared props;
- the selected approach works for both guests and authenticated users;
- frontend translation files have a predictable namespace convention;
- the implementation remains optional for pages that still use plain placeholder copy.

## Epic 3: Core Public Structure

### DDS-007: Public Static Shell Pages

Goal: create the first public route structure.

Tasks:

- create home page;
- create `/events`;
- create `/events/{slug}` placeholder;
- create `/projects`;
- position `/projects` as a showcase for DDS-built tooling, software, plugins, apps, integrations, and selected community builds;
- create `/news`;
- create `/locations`;
- create `/about`;
- create `/house-rules`;
- create `/partners`;
- create `/contact`.

Acceptance criteria:

- all public routes render;
- navigation uses `Events`, `Projects`, `News`, `About`, and `Contact`;
- pages can use placeholder content, but no generic theme copy;
- `/projects` placeholder copy clearly frames projects as a public showcase rather than internal project management;
- each page has a clear purpose, heading hierarchy, and primary next action.

### DDS-008: Baseline SEO And Redirect Shape

Goal: prepare SEO before content import.

Tasks:

- define page title and description handling;
- define Open Graph image handling;
- prepare canonical URL handling;
- define redirect middleware or model approach;
- add initial legacy redirect examples from WordPress.

Acceptance criteria:

- public pages can set SEO metadata;
- redirect strategy is implemented or documented in code;
- old `/trainingen/` can redirect to `/events?type=training`;
- old `/agenda/` can redirect to `/events`.

## Epic 4: Event Domain

### DDS-009: Event Model And Migration

Goal: create the first real domain model.

Tasks:

- create `Event` model and migration;
- add title, slug, excerpt, content, starts_at, ends_at, status, type, category;
- add location reference or temporary location fields;
- add registration fields: price, capacity, deadline, registration_status, registration_url;
- add SEO fields;
- include locale-aware content fields.

Acceptance criteria:

- events can be created through factories/tests;
- event type supports `training`, `race`, `demo`, `workshop`, `community`, `other`;
- status supports draft/published/archived;
- model supports Dutch content and optional English translations.

### DDS-010: Public Events Pages

Goal: show published events publicly.

Tasks:

- build event index page;
- add type filter support;
- build event detail page;
- show training-specific details when `type = training`;
- hide drafts and archived events.

Acceptance criteria:

- `/events` lists published upcoming events;
- `/events?type=training` filters training events;
- `/events/{slug}` shows a published event;
- unpublished events are not public;
- event cards are scannable and communicate date, type, location, and registration state clearly;
- empty states are useful when no events match a filter.

### DDS-011: Admin Event CRUD

Goal: manage events from the `/dashboard` management area.

Tasks:

- create `/dashboard/events` event index;
- create event create/edit forms;
- add server-side validation through Form Requests;
- add event policies;
- add publish status controls.

Acceptance criteria:

- admins can create, edit, and archive events;
- editors can create and update events according to their seeded permissions;
- validation errors are shown clearly;
- event type and registration status are editable;
- public visibility follows status;
- forms are structured for efficient repeated admin use;
- destructive or publishing actions require clear confirmation/feedback.

## Epic 5: Supporting Content Models

### DDS-012: Location Model

Goal: model recurring DDS locations.

Tasks:

- create `Location` model and migration;
- include address, city, floor size, height, facilities, suitability, role, parking, and map URL;
- create basic admin CRUD or seed-based management.

Acceptance criteria:

- locations can be linked to events;
- Sportpaleis, Koggenhal, and Oosterhout can be represented cleanly;
- public location pages have structured fields available.

### DDS-013: Article Model

Goal: prepare for news and WordPress post import.

Tasks:

- create `Article` model and migration;
- include locale-aware title, excerpt, content, and SEO fields;
- include published_at, status, category, and legacy source fields;
- prepare author handling.

Acceptance criteria:

- news articles can be represented before import;
- legacy WordPress IDs can be stored;
- only published articles are public.

### DDS-014: MediaAsset Model

Goal: prepare basic media storage and future WordPress media import.

Tasks:

- create `MediaAsset` model and migration;
- include disk, path, original filename, mime type, size, alt text, and legacy source fields;
- add basic upload or seed/import-friendly storage path.

Acceptance criteria:

- media can be referenced by events/articles/partners later;
- imported WordPress media can be deduplicated by legacy ID;
- alt text is supported.

### DDS-014A: Project Showcase Model

Goal: model public showcase projects for DDS-built tooling, software, plugins, apps, integrations, and selected community builds.

Tasks:

- create `Project` model and migration;
- include title, slug, excerpt, content, status, project_type, visibility, featured flag, and sort order;
- support project types such as `plugin`, `app`, `integration`, `event_tooling`, `community_build`, and `other`;
- add optional links for GitHub, live demo, documentation, download, and contact;
- add optional ownership/credit fields for maintainers or contributors;
- add SEO fields and media references;
- include locale-aware content fields.

Acceptance criteria:

- RotorHazard plugins, TrackDraw-style apps, race tooling, livestream overlays, and community utilities can be represented cleanly;
- projects can distinguish open source, public platform, internal/private, and archived work;
- only published and public projects are visible on the public site;
- the model does not imply internal task or project-management workflows;
- project records can be created through factories/tests.

### DDS-014B: Public Project Showcase Pages

Goal: show DDS projects publicly as a credible development and tooling showcase.

Tasks:

- build `/projects` project index page;
- build `/projects/{slug}` project detail page;
- add filters or grouping by project type when useful;
- show project links, status, media, and concise value proposition;
- add useful empty states.

Acceptance criteria:

- `/projects` lists published public projects;
- `/projects/{slug}` shows a published public project;
- project cards communicate type, purpose, status, and primary link clearly;
- archived or internal-only projects are not publicly listed unless explicitly marked visible;
- pages can support both software/tooling projects and selected community builds without separate content types.

### DDS-014C: Admin Project CRUD

Goal: manage project showcase content from the `/dashboard` management area.

Tasks:

- create `/dashboard/projects` project index;
- create project create/edit forms;
- add server-side validation through Form Requests;
- add project policies;
- add publish status and featured controls;
- make link fields easy to add, remove, and validate.

Acceptance criteria:

- admins can create, edit, publish, feature, archive, and reorder projects;
- editors can create and update projects according to their seeded permissions;
- validation errors are shown clearly;
- public visibility follows status and visibility fields;
- forms are structured for concise project showcase editing, not task tracking.

## Epic 6: WordPress Import Spike

### DDS-015: WordPress Export Discovery

Goal: verify the best import source for the current site.

Tasks:

- test WordPress REST API endpoints;
- compare with XML export availability;
- inspect posts, pages, media, categories, tags, and featured images;
- list fields that need cleanup.

Acceptance criteria:

- preferred import source is selected;
- risks and missing fields are documented;
- sample records are mapped to target models.

### DDS-016: Posts To Articles Import Prototype

Goal: prove repeatable post import.

Tasks:

- create dry-run capable import command;
- import posts into `Article`;
- store legacy source IDs;
- map published dates, slugs, categories, tags, and featured image references;
- report skipped records.

Acceptance criteria:

- import can run twice without duplicates;
- imported posts are traceable to WordPress IDs;
- dry-run output is useful;
- no manual copy-paste is required for posts.

## Recommended Sequence

1. DDS-001
2. DDS-002
3. DDS-002A
4. DDS-003
5. DDS-004
6. DDS-004B
7. DDS-005
8. DDS-006
9. DDS-006A
10. DDS-007
11. DDS-009
12. DDS-010
13. DDS-011
14. DDS-012
15. DDS-013
16. DDS-014
17. DDS-014A
18. DDS-014B
19. DDS-014C
20. DDS-015
21. DDS-016

The public website rebuild can begin once DDS-007 and DDS-010 exist, while admin and import work continue behind it.
