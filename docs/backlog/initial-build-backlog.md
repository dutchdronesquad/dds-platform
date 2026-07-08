# Initial Build Backlog

## Purpose

This backlog translates the preparation docs into the first practical implementation sequence. It assumes:

- Laravel modern monolith;
- React, Inertia, TypeScript, and Tailwind;
- DDEV with PostgreSQL;
- custom admin, no Filament in phase 1;
- Dutch-first bilingual content with `nl` and `en`;
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

Goal: enable login and protect `/admin`.

Tasks:

- use starter kit authentication;
- install and configure `spatie/laravel-permission`;
- create initial `admin` and `editor` roles;
- create `/admin` route;
- protect `/admin` with auth middleware;
- protect admin access through roles/permissions;
- add a first admin user seeding path.

Acceptance criteria:

- unauthenticated users cannot access `/admin`;
- authenticated admin user can access `/admin`;
- non-admin behavior is defined;
- role checks use Spatie Permission instead of a custom boolean-only approach;
- first admin account can be created repeatably for local/staging setup.

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

### DDS-005: Public And Admin Layout Shells

Goal: create the first layout boundaries.

Tasks:

- create `PublicLayout`;
- create `AdminLayout`;
- add basic public header/footer structure;
- add basic admin sidebar/topbar structure;
- keep styling minimal but coherent;
- establish initial spacing, typography, navigation, and interaction conventions.

Acceptance criteria:

- public pages render in `PublicLayout`;
- admin pages render in `AdminLayout`;
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

- app default locale is `nl`;
- supported locales are explicit in config;
- no `/nl` or `/en` route prefix is required;
- implementation notes describe how translatable content fields should be stored.

## Epic 3: Core Public Structure

### DDS-007: Public Static Shell Pages

Goal: create the first public route structure.

Tasks:

- create home page;
- create `/events`;
- create `/events/{slug}` placeholder;
- create `/projects`;
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

Goal: manage events from the custom admin.

Tasks:

- create admin event index;
- create event create/edit forms;
- add server-side validation through Form Requests;
- add event policies;
- add publish status controls.

Acceptance criteria:

- admin can create, edit, and archive events;
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
3. DDS-003
4. DDS-004
5. DDS-005
6. DDS-006
7. DDS-007
8. DDS-009
9. DDS-010
10. DDS-011
11. DDS-012
12. DDS-013
13. DDS-014
14. DDS-015
15. DDS-016

The public website rebuild can begin once DDS-007 and DDS-010 exist, while admin and import work continue behind it.
