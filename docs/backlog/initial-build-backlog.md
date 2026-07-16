# Initial Build Backlog

## Purpose

This backlog translates the preparation docs into the first practical implementation sequence. It assumes:

- Laravel modern monolith;
- React, Inertia, TypeScript, and Tailwind;
- DDEV with PostgreSQL;
- custom admin, no Filament in phase 1;
- English-default bilingual support with `en` required as the default base value for translated fields and `nl` optional, unless domain validation deliberately defines an exception;
- no locale-prefixed URLs in phase 1;
- dated activities use the `Event` domain, while the public navigation can label the overview `Agenda`;
- trainings are `Event` records with `type = training`;
- WordPress import comes after the initial application foundation and target models.
- UI/UX quality is part of the definition of done for every user-facing ticket.

## Current Backlog Position

Current implementation point: `main` contains the Laravel, authentication, locale, public-shell, homepage, SEO, and redirect foundation through DDS-008B. DDS-007D remains partially open for its explicitly listed navigation, footer, keyboard, and screen-reader follow-up work.

The current DDS-009 working tree adds the schema dependency cluster in migration order:

- DDS-014: reusable `MediaAsset` records for the future dashboard media library;
- DDS-012: structured `Location` records;
- DDS-009: minimal `Season` and `Event` models and migrations.

This means 16 items in the recommended sequence are complete on `main`, DDS-007D is partial, and three additional schema tickets are implemented in the current working tree. Public Event pages, Event admin CRUD, the media-library UI, and supporting content models are not implemented yet.

Recommended next tickets:

1. finish and merge the current DDS-014 / DDS-012 / DDS-009 schema cluster;
2. define the explicit DDS-009A season-ticket model before promoting season tickets publicly;
3. add the remaining foundational content schemas before their admin screens: DDS-013, DDS-014A, DDS-014D, and DDS-014F;
4. establish DDS-011A and DDS-011B admin resource patterns, then build DDS-014H media-library selection and DDS-011 Event/Season management;
5. complete DDS-010 and DDS-010A public Event work, then align season presentation through DDS-010B;
6. build the public newcomer journey and all documented entry points in DDS-014L using the constrained managed-page foundation;
7. expand the hub with the DDS-014K curated guide library when the initial content proves that separate guide pages are useful;
8. keep DDS-011F holder and attendance handling deferred until native season-ticket registration and its business rules are approved;
9. finish DDS-007D as an independent public-shell polish task before launch.

Do not jump straight from here to the WordPress importer. Import discovery can happen in parallel, but production-grade import should wait until the public design direction, target content models, admin review flows, media handling, redirects, and user/permission management exist.

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

Status: complete. Pest, Pint, Larastan, frontend formatting, linting, type checking, production builds, and GitHub Actions workflows are in place.

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

Status: complete. Early database-backed services and the later Redis, queue-worker, scheduler, production mail, and storage-backup requirements are recorded in the technical documentation.

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

### DDS-003B: Replace Source-String Assertions With Behavioral Test Coverage

Status: complete. Source-string assertions have been replaced with Inertia feature coverage and Pest browser tests for the public shell, safe external links, visible keyboard focus, mobile navigation and reflow, event filters and states, long event content, and empty results. Chromium runs locally through persistent DDEV configuration and in a dedicated CI matrix entry.

Goal: make the test suite prove public behavior, domain rules, and user-visible interactions instead of mirroring implementation text.

Tasks:

- inventory tests that read application source files and classify the user behavior or architectural contract they were intended to protect;
- replace source-string and Tailwind-class assertions with Laravel feature tests, Inertia response assertions, Pest browser tests, and focused domain tests at the appropriate layer;
- cover public navigation, homepage content, event filtering, event states, external-link safety, empty states, and responsive interactions through rendered output or real browser behavior;
- add representative desktop and mobile browser coverage for the highest-risk public flows, including JavaScript and console-error checks;
- test pure formatting or presentation logic through an appropriate executable frontend test layer when it cannot be covered clearly through feature or browser tests;
- remove obsolete implementation-coupled assertions only after equivalent behavioral coverage exists;
- retain static or architecture assertions only when they protect a genuine architectural boundary that cannot be expressed more directly.

Acceptance criteria:

- `PublicStaticPagesTest` no longer reads `.tsx` or `.ts` files with `file_get_contents()`;
- tests do not assert Tailwind class order, import strings, private component names, or arbitrary JSX fragments;
- key public flows fail when their rendered behavior, navigation, accessibility contract, or domain outcome regresses;
- event date, filtering, registration states, long content, empty states, and mobile presentation have executable coverage;
- browser tests include visible focus, working links or filters, absence of JavaScript errors, and representative mobile reflow checks;
- remaining uses of `toContain()` assert meaningful collection membership or output semantics rather than source-code text;
- the rewritten suite remains deterministic and practical to run locally and in CI.

## Epic 2: Auth, Layouts, And Locale Baseline

### DDS-004: Authentication And Admin Gate

Status: complete and merged. `spatie/laravel-permission` is configured, initial `admin` and `editor` roles are seeded, `/dashboard` is protected by authentication, email verification, and role middleware, concrete admin permissions exist, and `php artisan dds:make-admin` provides repeatable first-admin setup.

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

Status: complete. The scaffold, DDEV setup, documentation, and baseline tooling were verified and committed before domain work started.

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

Status: complete. The admin-access foundation was reviewed and merged through pull request #1 with the project checks in place.

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

Status: complete. Dedicated public and authenticated management shells, DDS navigation, responsive layouts, and the initial dashboard presentation are merged.

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

Status: complete. English and Dutch are configured without locale-prefixed routes, English is the default locale and required base for translated fields by default, and locale-keyed JSON is the selected storage shape for deliberately translated database content. Domain validation can define exceptions where appropriate.

Goal: prepare bilingual content without adding locale-prefixed URLs.

Tasks:

- configure supported locales `nl` and `en`;
- set `en` as default locale;
- define a simple content translation approach for phase 1;
- make admin forms able to expose locale-specific fields later.

Acceptance criteria:

- app default locale is `en`;
- supported locales are explicit in config;
- no `/nl` or `/en` route prefix is required;
- implementation notes describe how translatable content fields should be stored.

### DDS-006A: Runtime Locale And Translation UX

Status: complete for the runtime foundation. Request middleware resolves user, cookie, browser, and default preferences; locale data is shared with Inertia; guests and users can persist a supported locale; and the frontend bundle convention is configured. Actual UI-copy translation remains incremental as pages adopt translation bundles.

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

Status: implemented as a temporary shell. The public URLs render through Inertia and use placeholder page data from `config/public_pages.php`. This is acceptable for the first route skeleton until real models or constrained managed content exist.

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

### DDS-007A: Public Shell Content Registry

Status: implemented. Temporary shell copy lives in `config/public_pages.php`, and `routes/web.php` only maps URLs to the shared Inertia shell component and a page key.

Goal: move temporary public shell page data out of route definitions before the public pages grow.

Tasks:

- decide whether static shell content belongs in a typed PHP config file, enum-backed page registry, dedicated controller data provider, or seed-backed content model;
- move placeholder page metadata, sections, and primary actions out of `routes/web.php`;
- keep routes focused on URL definitions and Inertia component selection;
- keep page data typed and testable;
- preserve the existing public URLs and rendered components;
- document that this registry is still a bridge until real models or managed content exist.

Acceptance criteria:

- `routes/web.php` no longer contains large per-page copy arrays;
- each shell page still renders with title, description, sections, and primary action;
- page data has one clear owner and can be reused by tests;
- the approach does not become a generic CMS by accident;
- the temporary nature is visible in code comments or naming where useful.

### DDS-007B: Public Brand Direction

Status: implemented as an evolutionary refresh of the existing DDS site rather than a rebrand. The direction uses real DDS photography, the existing orange/cyan logo, a deep-signal and light-air editorial palette, purposeful display typography, compact racing details, and restrained interaction states. The homepage now opens with `Where racing brings pilots together.` and prioritizes experienced pilots before giving beginners a clear route into the sport. The desktop homepage header becomes sticky with a translucent background while scrolling, the mobile navigation uses a full-screen menu, and the footer uses a four-column information structure. Upcoming events and latest news use horizontally scrollable mobile rails and three-column desktop layouts. Content is immediately visible; entrance-on-scroll animations were intentionally removed after review, while hover and navigation transitions retain reduced-motion handling. The homepage uses an intentionally art-directed section palette rather than changing its editorial sections with the appearance theme. Social preview metadata remains deferred to DDS-008. Team content belongs on the About page, and project, partner, or location sections should only return to the homepage when they support a clear visitor action.

Goal: establish the first recognizable DDS visual direction before investing in final public pages.

Tasks:

- define the initial DDS color palette, type scale, spacing scale, and interaction style;
- decide how the existing logo should be used in header, footer, favicon, and social previews;
- collect or identify the first usable real DDS photos/videos for public pages;
- define image treatment for hero, cards, event pages, project pages, and news;
- create a small set of reusable public UI patterns such as hero, content band, CTA band, feature cards, media strip, and page intro;
- ensure dark mode is either intentionally supported or intentionally scoped out for public pages;
- align public visuals with FPV racing, indoor training, community, and technical event organization instead of generic club-site styling.

Acceptance criteria:

- public pages have a recognizable DDS look instead of starter-kit styling;
- visual direction uses real DDS activity or credible placeholders, not abstract filler;
- mobile and desktop examples exist for the main public patterns;
- contrast, focus states, and text hierarchy meet the global UX acceptance criteria;
- the palette does not read as a one-note Tailwind default theme;
- future public pages can reuse the patterns without copying large blocks.

### DDS-007C: Homepage Content And Conversion Pass

Status: implemented. The homepage includes the revised hero, experienced-pilot and beginner paths, Sportpaleis proof, upcoming events, About and news previews, a compact partner-logo row near the footer, and a final agenda CTA. Projects are deliberately routed through the primary navigation and footer until real project cases justify a homepage feature. The partner zone shows the verified Droneshop.nl logo from the legacy website, omits its anonymous template placeholders, and does not include a separate sales callout. Temporary event, legacy-news, and partner content is server-backed through `config/homepage.php`; the future Event, Article, Partner, and managed-content domains can replace those props without redesigning the page.

Goal: turn the homepage from a shell into a useful first public entry point.

Tasks:

- rewrite the first viewport around the strongest DDS positioning;
- add clear CTAs for next event/training, joining, and partner contact;
- add upcoming events or a temporary next-event placeholder;
- add a concise "What is DDS?" section;
- add a "Start with training" path for new pilots and parents;
- add project/showcase teaser content;
- add visible partner recognition without a separate homepage sales callout;
- add final CTA and footer flow;
- ensure the homepage can function before the WordPress importer exists.

Acceptance criteria:

- visitors can understand what DDS is within the first viewport;
- new pilots can find the next practical step;
- experienced pilots can find events quickly;
- partners can see that DDS is able to organize demos, workshops, and race formats;
- the page has no generic theme copy;
- mobile layout is deliberately designed, not just stacked desktop content.

### DDS-007D: Public Navigation And Footer Polish

Status: partially implemented as part of DDS-007B and DDS-007C. The public shell now has refreshed desktop and mobile navigation, active-link treatment, the primary hierarchy `Projecten`, `Nieuws`, `Over DDS`, `Locaties`, and `Contact`, a separate agenda CTA, a sticky translucent homepage header on desktop, and a four-column footer with brand context and grouped links. `Huisregels` is intentionally footer-only. Login and admin access are hidden from the public shell while their direct routes remain available. Remaining work is to validate the hierarchy across every public page, add any required privacy, media, results, social, partner, or direct-contact pathways, and complete keyboard and screen-reader verification.

Goal: make the public shell feel coherent across pages before real content models land.

Tasks:

- finalize primary navigation labels and order;
- decide where secondary links such as Locations, House Rules, Partners, In The Media, Results, and Privacy live;
- add active states for section and detail pages;
- improve mobile navigation ergonomics;
- add footer contact pathways and partner/demo cues;
- keep login/admin entry private and available only through its direct route;
- verify navigation with keyboard and screen reader basics.

Acceptance criteria:

- navigation follows the active locale; the current homepage can remain temporarily Dutch-first until the multilingual public content layer is in use, without changing English as the platform default;
- secondary links are discoverable without crowding the header;
- active states work for index and detail routes;
- public header/footer do not feel like starter-kit leftovers;
- mobile navigation fits without awkward wrapping or hidden critical links.

### DDS-007E: Add Sportpaleis Alkmaar As A Homepage Partner

Status: planned.

Goal: recognize Sportpaleis Alkmaar as a DDS partner in the homepage partner section.

Tasks:

- add Sportpaleis Alkmaar to the homepage partner data with `https://sportpaleis-alkmaar.nl/` as its website URL;
- obtain and use an official Sportpaleis Alkmaar logo asset with appropriate usage permission instead of recreating or approximating the logo;
- present the logo alongside the existing homepage partner logos with a consistent visual weight, clear spacing, and preserved aspect ratio;
- make the logo or partner entry link to the Sportpaleis Alkmaar website with an accessible partner name and the existing external-link safety conventions;
- verify the partner row at representative mobile and desktop widths, including wrapping, alignment, contrast, and focus states;
- move the temporary homepage entry to the structured `Partner` domain when DDS-014D is implemented, while retaining its homepage-featured state.

Acceptance criteria:

- Sportpaleis Alkmaar is visibly represented as a partner on the homepage with its official logo;
- the partner entry links to `https://sportpaleis-alkmaar.nl/`;
- the logo is sharp, not distorted, and visually balanced with the other partner logos;
- the partner row remains neatly aligned without overflow or awkward breaks on mobile and desktop;
- the partner link has a meaningful accessible name, visible keyboard focus, and safe external-link behavior;
- the implementation can migrate to the structured `Partner` model without redesigning the homepage section.

### DDS-008: Baseline SEO And Redirect Shape

Status: implemented through DDS-008A and DDS-008B. Public pages now have reusable SEO metadata with stable canonical URLs and Open Graph defaults, while legacy WordPress paths are handled through database-backed redirects before the public fallback.

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

### DDS-008A: Public Metadata Component And Defaults

Status: implemented. A typed SEO metadata shape is shared by Laravel and the public React pages, sensible DDS defaults live in configuration, canonical URLs are based on the application URL, and the root document safely renders title, description, robots, canonical, and Open Graph metadata. Feature tests cover defaults, page overrides, and output escaping.

Goal: make SEO metadata easy to apply consistently across public Inertia pages.

Tasks:

- create a typed metadata shape for title, description, canonical URL, robots, and Open Graph data;
- expose metadata from public route data or public models;
- update the root Blade template to render metadata safely;
- add default metadata for pages that do not provide custom values yet;
- add tests for title and description availability where practical;
- ensure canonical URLs use the application URL and stable public routes.

Acceptance criteria:

- public pages can set title, description, canonical URL, and Open Graph image;
- missing metadata falls back to sensible DDS defaults;
- metadata rendering does not leak unsafe HTML;
- metadata shape can be reused by events, articles, projects, locations, and partners;
- future imported content can feed the same derived metadata shape without model-specific SEO columns.

### DDS-008B: Redirect Model And Admin Review Flow

Status: implemented. Legacy paths are stored in an idempotently seeded `Redirect` model with status, active state, hit count, and review notes. Only unmatched safe requests reach the redirect middleware, exact paths and query-string targets are supported, redirect loops are rejected, and admins and editors can inspect the read-only redirect overview. The initial map covers `/trainingen/`, `/trainingsdagen/`, `/agenda/`, `/nieuws/`, and `/huisregels/`; unused WordPress template pages are intentionally excluded.

Goal: prepare SEO-safe legacy URL handling before WordPress URLs are imported.

Tasks:

- create a `Redirect` model and migration;
- store source path, target URL/path, status code, active flag, hit count, and notes;
- implement middleware that checks active redirects before public route fallback;
- add seed or migration examples for known legacy URLs;
- add a simple admin review screen or defer the UI behind a clear follow-up if needed;
- add tests for exact path redirects and query-string targets.

Acceptance criteria:

- legacy paths can redirect without redeploying code;
- `/trainingen/` redirects to `/events?type=training`;
- `/agenda/` redirects to `/events`;
- inactive redirects are ignored;
- redirect loops are prevented or detected;
- WordPress importer can create or update redirect records later.

## Epic 4: Core Domain Foundations And Events

Implementation order follows schema dependencies instead of ticket numbering. Ticket IDs remain stable: reusable media and locations are created before events so content tables can declare their foreign keys in their original create migrations.

### DDS-014: MediaAsset Model

Status: implemented in the current working tree as a schema dependency of DDS-009. Media assets have stable storage identity, a recognizable original filename, optional locale-aware alt text defaults, and optional image dimensions, without WordPress-specific runtime metadata. Whether an image is informative or decorative belongs to its rendering context. Images and PDFs share the same reusable model. Upload, search, selection, and usage management remain in DDS-014H.

Goal: prepare basic media storage and future WordPress media import before content models reference cover images.

Tasks:

- create `MediaAsset` model, migration, and factory;
- include disk, path, original filename, mime type, byte size, optional image dimensions, and optional locale-aware alt text defaults;
- expose cover-image relationships for locations and events.

Acceptance criteria:

- media can be referenced by events and locations without later foreign-key migrations;
- media can provide an optional alt text default in one or more supported locales;
- the rendering context can use a descriptive alt value or an empty `alt` attribute for a decorative use;
- upload and library management can be added without reshaping the table;
- temporary import tooling can deduplicate source media without persisting WordPress metadata on the asset.

### DDS-012: Location Model

Status: implemented in the current working tree as a schema dependency of DDS-009. Locations have an official name, an English-base translated description, a structured Dutch address, indoor/outdoor environment, optional physical dimensions, fixed facility codes, coordinates, an optional cover image, and an event relationship. They intentionally have no publication state or model-specific SEO fields. Public pages and admin management remain in DDS-014I.

Goal: model recurring DDS locations before events reference them.

Tasks:

- create `Location` model, migration, and factory;
- include street, house number, postal code, city, ISO country code, environment, optional floor size and ceiling height, facilities, website URL, and coordinates;
- store the official name as plain text and the public description as locale-keyed JSONB with English required and Dutch optional;
- reference an optional media cover image;
- expose the event relationship.

Acceptance criteria:

- locations can be linked to events through a database-enforced foreign key;
- Sportpaleis, Koggenhal, and Oosterhout can be represented cleanly;
- public location pages have structured venue data and translated descriptions available;
- facility labels are translated in interface language files while the database stores stable codes;
- later location management does not require reshaping the core table.

### DDS-009: Season And Event Models And Migrations

Status: implemented in the current working tree. Seasons provide optional event grouping, a nullable season price, and a season-ticket limit. Events have a required protected location, an optional protected season, a removable cover-image reference, plain-text content, enum-backed type/publication/registration states, scheduled publication, and one listing-oriented index. Model-specific SEO and WordPress fields are intentionally absent.

Goal: create the first real domain model.

Tasks:

- create `Season` model, migration, and factory before events;
- add season name, nullable price, and nullable season-ticket capacity;
- create `Event` model, migration, and factory;
- add title, slug, nullable content, starts_at, ends_at, published_at, status, and type;
- add a required location, optional season, and optional cover-image reference;
- add registration fields: price, capacity, opening time, deadline, registration status, and registration URL.

Acceptance criteria:

- events can be created through factories/tests;
- event type supports `training`, `race`, `demo`, `workshop`, and `other`;
- status supports draft, published, and cancelled;
- event title and content accept Dutch or English text without duplicated locale fields;
- deleting a cover preserves the event and clears only the cover reference;
- a location or season cannot be deleted while an event still references it.

### DDS-009A: Season Ticket Product And Eligibility Model

Goal: model season tickets explicitly without conflating a season grouping, event capacity, and attendance.

Tasks:

- define enum-backed season-ticket sales states for not offered, coming soon, available, sold out, and closed;
- add an optional sales opening time, closing time, registration URL, explanatory copy, price, and season-ticket capacity;
- model the events included in a season ticket explicitly instead of inferring eligibility from `season_id` alone;
- keep season-ticket capacity separate from each included event's capacity;
- expose the derived season date range, eligible-event count, and current sales state through a focused service or public view model;
- document how cancellation affects presentation without encoding unstated refund or replacement rules;
- add factories and model tests for seasons with and without a ticket offer, mixed eligible events, future years, and each sales state.

Acceptance criteria:

- a season can group events without offering a season ticket;
- a season ticket covers only explicitly eligible events;
- event capacity and season-ticket capacity cannot be mistaken for the same value in application data;
- sales state and sales window produce one reliable public availability state;
- current and future season date ranges derive correctly from included events;
- the model can later add holders and attendance without reshaping the season/event relationship.

### DDS-010: Public Events Pages

Goal: show published events publicly.

Tasks:

- build event index page;
- add type filter support;
- build event detail page;
- show training-specific details when `type = training`;
- hide drafts and keep cancelled events visible with a clear state.

Acceptance criteria:

- `/events` lists published upcoming events;
- `/events?type=training` filters training events;
- `/events/{slug}` shows a published event;
- unpublished events are not public;
- event cards are scannable and communicate date, type, location, and registration state clearly;
- empty states are useful when no events match a filter.

### DDS-010A: Realistic Event Fixtures And Frontend Validation

Goal: validate the public event experience with representative data before the Event admin workflow becomes the primary way to create content.

Tasks:

- add a deterministic, development-only event dataset using the existing factories and seeder conventions;
- include races and training events with varied dates, locations, prices, cover-image availability, and registration states;
- represent open, nearly full, full, closed, and cancelled scenarios where the domain model supports them;
- review the homepage event selection, event index, type filters, no-result recovery, and event detail pages with the dataset loaded;
- check event cards and detail content on representative mobile and desktop viewports;
- cover edge cases such as long titles, missing optional content, and events without a cover image;
- keep demo records out of production and make the dataset safe to recreate or remove locally.

Acceptance criteria:

- a documented local seeding command creates the same representative event dataset on repeated runs without introducing duplicates;
- the homepage shows the correct upcoming events and never renders more than its intended event-card limit;
- event type filters and their empty-state recovery work without an avoidable full-page jump or reload;
- event cards communicate date, type, location, price, availability, and cancellation state where applicable;
- long or optional content does not break card or detail layouts on mobile or desktop;
- automated tests cover public visibility, ordering, filtering, and the representative registration states;
- demo data can be reset without affecting production seeding or real content.

### DDS-010B: Public Season And Season Ticket Presentation

Goal: make season context and ticket choices understandable across public event experiences.

Tasks:

- show a localized `Season` label and season name consistently on event cards, event list rows, and event details;
- keep event type, season, event registration state, and cancellation state visually distinct;
- add a compact current-season summary where it supports filtered training or race lists;
- compare single-event registration and season-ticket options without implying that every season offers a ticket;
- show the season-ticket sales state, covered events, price, and registration action from backend data;
- include the calendar year wherever a future date could otherwise be ambiguous;
- hide the sales module when no season ticket is offered while retaining useful season context;
- verify long season names, mixed eligible events, sold-out states, and mobile layouts.

Acceptance criteria:

- visitors can distinguish an event from its season and understand which action buys which access;
- season naming and labels are aligned across homepage cards, `/events`, and `/events/{slug}`;
- an event linked to a season is not presented as season-ticket eligible unless it is explicitly included;
- dates crossing calendar years remain unambiguous;
- unavailable, sold-out, and closed ticket states do not show a misleading registration action;
- focused frontend and feature tests cover the meaningful states instead of relying on broad markup fragments.

### DDS-011: Admin Event CRUD

Goal: manage events from the `/dashboard` management area.

Tasks:

- create `/dashboard/events` event index;
- create event create/edit forms;
- manage seasons as a small supporting resource within the Event workflow;
- add server-side validation through Form Requests;
- add event policies;
- add publish status controls.

Acceptance criteria:

- admins can create, edit, publish, cancel, and remove events;
- admins can manage season names, optional prices, and ticket limits;
- editors can create and update events according to their seeded permissions;
- validation errors are shown clearly;
- event type and registration status are editable;
- public visibility follows status;
- forms are structured for efficient repeated admin use;
- destructive or publishing actions require clear confirmation/feedback.

### DDS-011A: Management Dashboard Information Architecture

Goal: turn `/dashboard` into a practical management home instead of a placeholder.

Tasks:

- define the first dashboard sections and resource navigation;
- add quick links for events, projects, articles, locations, partners, media, users, and redirects;
- show useful operational cards such as drafts, upcoming events, and recent submissions;
- define empty states for a fresh installation;
- keep the dashboard dense and task-oriented rather than marketing-like;
- ensure admin and editor roles see appropriate navigation.

Acceptance criteria:

- `/dashboard` clearly explains what can be managed;
- primary admin tasks are reachable in one click;
- empty installation state is useful;
- dashboard layout works on mobile and desktop;
- navigation is permission-aware enough to avoid dead-end links;
- the page no longer reads as starter-kit content.

### DDS-011B: Admin Resource Shell And Shared CRUD Patterns

Goal: establish repeatable admin patterns before building many resource screens.

Tasks:

- define list page layout conventions for filters, search, pagination, bulk actions, and row actions;
- define create/edit form layout conventions for tabs or sections, save actions, validation, and destructive actions;
- add shared admin components only where repetition is already visible;
- define status badge patterns for draft, published, archived, active, and inactive states;
- define confirmation dialog patterns for archive, delete, publish, and unpublish;
- ensure all patterns work with Inertia forms and server-side validation.

Acceptance criteria:

- event, article, project, location, partner, media, user, and redirect screens can share consistent patterns;
- admin tables support scanning and repeated work;
- form actions are predictable across resources;
- validation and success/error feedback are consistent;
- abstractions remain small and do not block resource-specific UX.

### DDS-011C: User Management

Goal: manage platform users from the dashboard without relying on direct database access.

Tasks:

- create `/dashboard/users` user index;
- add search and filters for role, verification state, and recent activity where available;
- create user detail or edit screen;
- allow admins to update name, email, roles, locale preference, and account state;
- define whether admins can create users directly or invite/reset password only;
- prevent admins from accidentally removing their own last admin access;
- add policies and tests for admin-only user management.

Acceptance criteria:

- admins can view users from the dashboard;
- admins can update roles safely;
- editors cannot manage users unless explicitly permitted later;
- last-admin lockout is prevented;
- role changes use Spatie Permission;
- user management forms provide clear validation and feedback.

### DDS-011D: Role And Permission Review UI

Goal: make the seeded role and permission model visible and reviewable.

Tasks:

- create a read-only or limited-edit screen for roles and permissions;
- show which permissions belong to admin and editor roles;
- decide whether permissions are code-owned only or editable in admin;
- expose role assignment through the user edit screen;
- add tests for permission visibility and restricted access.

Acceptance criteria:

- admins can inspect the active role/permission setup;
- editors cannot change role configuration;
- permissions stay aligned with seeded enum values;
- the UI avoids implying arbitrary permission creation if the system remains code-owned;
- future resource permissions can be reviewed before launch.

### DDS-011E: Admin Activity, Audit, And Safety Feedback

Goal: add lightweight operational confidence for content changes.

Tasks:

- decide whether phase 1 needs audit logging or only timestamp/user metadata;
- add created_by and updated_by fields to content models where useful;
- show last updated information on admin edit screens;
- add clear publish/unpublish/archive feedback;
- define how failed imports and validation errors appear to admins;
- defer full audit logs if they are not needed for launch.

Acceptance criteria:

- admins can see whether content is draft, published, archived, or imported;
- admins can see recent update metadata on managed content where available;
- destructive changes require confirmation;
- the app avoids silent publish-state changes;
- full audit logging is either implemented or explicitly deferred.

### DDS-011F: Season Ticket Holder And Attendance Workflow

Goal: support the operational season-ticket workflow if DDS chooses native registration after the public information model is established.

Tasks:

- define season-ticket holder, payment, cancellation, and refund states with the organization before implementation;
- record which season-ticket product a holder purchased;
- support per-event attendance confirmation or opt-out for eligible events;
- make released places usable by the event registration or waitlist workflow without overselling capacity;
- provide administrators with holder, payment, and per-event attendance overviews;
- give authenticated holders one clear overview of their included events and required actions;
- log important operational changes and protect personal data through policies and retention rules.

Acceptance criteria:

- holder access is derived from the purchased season-ticket product and its explicit eligible events;
- administrators can distinguish season-ticket allocation from actual attendance for each event;
- opt-outs and cancellations cannot silently corrupt event capacity;
- holders can see which events require confirmation and which actions are complete;
- payment and refund behavior is implemented only after the business rules are approved;
- authorization and focused workflow tests cover administrator and holder behavior.

## Epic 5: Supporting Content Models

### DDS-013: Article Model

Status: implemented in the current working tree. Articles have source-agnostic content fields, optional author and cover-image relationships, enum-backed semantic categories and draft/published/archived states, and a publication-date-aware public scope. Excerpts are a frontend presentation concern derived from content, not a persisted field or `Article` model attribute. WordPress identifiers, legacy terms, and import bookkeeping remain outside the permanent article table.

Goal: prepare for news and WordPress post import.

Tasks:

- create `Article` model and migration;
- include the content fields needed for manually created and selectively imported articles;
- include published_at, status, and category;
- prepare author handling.

Acceptance criteria:

- news articles can be represented before import;
- articles remain source-agnostic whether created manually or migrated selectively;
- only published articles are public.

### DDS-014A: Project Showcase Model

Goal: model public showcase projects for DDS-built tooling, software, plugins, apps, integrations, and selected community builds.

Tasks:

- create `Project` model and migration;
- include title, slug, excerpt, content, status, project_type, visibility, featured flag, and sort order;
- support project types such as `plugin`, `app`, `integration`, `event_tooling`, `community_build`, and `other`;
- add optional links for GitHub, live demo, documentation, download, and contact;
- add optional ownership/credit fields for maintainers or contributors;
- add media references and only translate content fields where the editing workflow benefits from it.

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

### DDS-014D: Partner Model And Public Partner Pages

Goal: represent DDS partners and sponsors as structured content.

Tasks:

- create `Partner` model and migration;
- include name, slug, description, logo media reference, website URL, partner type, status, featured flag, and sort order;
- include optional contact notes for internal admin use;
- add public partner listing or section support;
- add factories and tests;
- include locale-aware public fields where useful.

Acceptance criteria:

- partners and sponsors can be represented without hardcoded page copy;
- partner logos can be associated with media assets;
- only active public partners are visible publicly;
- featured partners can be highlighted on home or partner pages;
- internal notes do not leak to public pages.

### DDS-014E: Admin Partner CRUD

Goal: manage partner content from the dashboard.

Tasks:

- create `/dashboard/partners` partner index;
- create partner create/edit forms;
- add validation, policies, status controls, featured controls, and ordering;
- support logo selection once media library exists;
- show useful empty states before partners are imported or entered manually.

Acceptance criteria:

- admins can create, edit, publish, feature, archive, and reorder partners;
- editors can manage partners according to their permissions;
- validation errors are clear;
- public visibility follows status;
- logo/media selection has a fallback state.

### DDS-014F: Managed Static Content For Known Public Pages

Goal: avoid a generic page builder while still allowing known public pages to be edited.

Tasks:

- decide which known pages need managed content: about, house rules, contact intro, homepage sections, partner intro, and location intro;
- create a constrained `StaticPage` or `PublicPage` model if hardcoded content becomes too limiting;
- support fixed page keys instead of arbitrary user-created routes in phase 1;
- include the required title, intro, body sections, status, and updated_by metadata, translating only fields that need parallel public variants;
- add admin edit screens for fixed pages;
- migrate temporary shell copy into the managed approach when ready.

Acceptance criteria:

- known static pages can be updated without code changes;
- arbitrary CMS route creation is not introduced in phase 1;
- page keys are stable and testable;
- pages still use designed React templates;
- WordPress page content has a clear target when imported or manually rewritten.

### DDS-014G: Contact Submission Model And Form

Goal: make `/contact` useful before external CRM or email automation exists.

Tasks:

- create `ContactSubmission` model and migration;
- add public contact form with name, email, topic, message, consent/anti-spam field, and optional source context;
- validate and store submissions;
- send notification email if mail configuration is ready, otherwise record a clear follow-up;
- create `/dashboard/contact-submissions` index and detail view;
- add spam and rate-limit protections.

Acceptance criteria:

- public visitors can submit contact requests;
- submissions are stored and visible to admins;
- validation and success/error states are clear;
- spam protection exists without harming accessibility;
- no submitted message is silently lost if email delivery fails.

### DDS-014H: Media Library Admin

Goal: manage reusable media assets before importing WordPress media at scale.

Tasks:

- build `/dashboard/media` media index;
- support upload, edit metadata, delete/archive, and optional alt text default updates;
- make assets searchable and recognizable by their original filename;
- add a reusable asset picker so events, locations, and later content models can select existing media instead of uploading duplicates;
- show where an asset is used before it is removed;
- show previews for images and useful fallbacks for non-images;
- support filtering by mime type, usage state, and import source;
- define storage path conventions for uploaded and imported files;
- add validation for file size, mime type, and image dimensions where needed.

Acceptance criteria:

- admins can upload and manage media assets;
- existing assets can be selected and reused from Event and Location forms;
- optional alt text defaults are editable in supported locales;
- media records can be attached to events, articles, projects, partners, and locations later;
- imported WordPress media can coexist with manually uploaded media;
- unused media can be reviewed without deleting it automatically.

### DDS-014I: Public Location Pages And Admin Location CRUD

Goal: turn locations into useful public and admin-managed content.

Tasks:

- build `/locations` public index;
- build `/locations/{slug}` public detail page;
- create `/dashboard/locations` admin index;
- create location create/edit forms;
- connect locations to events;
- show structured address, environment, optional floor size and height, facilities, website, and map coordinates.

Acceptance criteria:

- public location pages are useful for visitors attending events;
- recurring DDS locations can be managed without code changes;
- events can show linked location details;
- location pages use the translated description when available and fall back to another non-empty translation;
- coordinates and external website URLs are validated.

### DDS-014J: Public News Pages And Admin Article CRUD

Goal: make the article model useful before WordPress import work begins.

Tasks:

- build `/news` article index;
- build `/news/{slug}` article detail page;
- create `/dashboard/articles` article index;
- create article create/edit forms;
- support published/draft/archive states;
- support cover media, author display, and category.

Acceptance criteria:

- published articles render publicly;
- draft and archived articles are hidden from public pages;
- admins can create and edit articles manually before import;
- imported articles can be reviewed through the same admin UI later;
- empty news states are useful before content exists.

### DDS-014K: Curated Guide Library And Admin Workflow

Goal: manage a curated library of newcomer guides without introducing a generic page builder.

Tasks:

- create a `Guide` model with title, stable English slug, summary, sanitized or structured body content, category, manual order, status, and publication timestamps;
- support optional cover media, editorial owner, last reviewed date, and update metadata;
- decide deliberately which guide fields support English and Dutch variants and require an English base where translations are enabled;
- create guide factories, policies, validation, and focused model tests;
- build `/dashboard/guides` index and create/edit forms using the shared admin resource patterns;
- support draft, published, and archived states plus preview and review-due visibility;
- keep dates, prices, capacities, locations, and sales states out of saved guide prose when a domain model owns them.

Acceptance criteria:

- editors can maintain guides without changing React code;
- published guides have stable English-based URLs and unpublished guides are not public;
- stale safety or equipment guidance can be identified through ownership and review metadata;
- the content structure supports accessible headings, links, lists, and media without arbitrary page layouts;
- guide content cannot become a second source of truth for event, location, or season-ticket availability;
- authorization, validation, publication, and locale fallback behavior are covered by focused tests.

### DDS-014L: Public Getting Started Hub And Entry Points

Goal: give new pilots one coherent path from first interest to a suitable DDS event.

Tasks:

- build `/getting-started` as a designed knowledge-hub landing page and support `/getting-started/{guide:slug}` when guide records are introduced;
- implement the initial structure defined in [Getting Started Knowledge Hub](../product/getting-started-knowledge-hub.md);
- render suitable upcoming events, current season context, and season-ticket availability from backend data;
- add entry points from the main navigation, homepage newcomer section, event overview, training event detail, relevant location details, contact page, and footer;
- use localized labels while keeping stable English routes without locale prefixes;
- preserve `/trainingen/ -> /events?type=training` for visitors looking for dated training events;
- add useful empty states when no beginner-suitable event or season-ticket offer is active;
- verify navigation capacity, long-form readability, contextual links, focus order, and layouts on representative mobile and desktop viewports;
- track entry source so DDS can learn which paths actually help newcomers.

Acceptance criteria:

- a new visitor can understand DDS participation and reach a suitable event or contact action without relying on the legacy site;
- every documented primary and contextual entry point links to the relevant hub section or guide;
- event, location, and season data is current because it comes from its source model;
- the hub clearly distinguishes events, seasons, single-event tickets, and season tickets;
- adding the navigation item does not create broken or crowded mobile navigation;
- accessibility, public visibility, dynamic module, and entry-source behavior are covered by focused tests.

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
- store source-to-target mappings in the temporary import manifest;
- map published dates, slugs, categories, tags, and featured image references;
- report skipped records.

Acceptance criteria:

- import can run twice without duplicates;
- imported posts are traceable through the temporary import manifest during rehearsal and cutover;
- dry-run output is useful;
- no manual copy-paste is required for posts.

### DDS-017: WordPress Media Import Prototype

Goal: prove repeatable media import before importing article and page bodies that reference media.

Tasks:

- create dry-run capable media import command;
- fetch media through REST API or XML attachment data;
- download files to the configured storage disk;
- create or reuse normalized `MediaAsset` records using the temporary import manifest;
- preserve alt text, captions where useful, mime type, file size, and original URL;
- report failed downloads and unsupported file types;
- avoid duplicate files on repeated runs.

Acceptance criteria:

- media import can run twice without duplicate media records;
- imported media can be matched to WordPress attachments through the temporary import manifest;
- failed downloads are reported clearly;
- imported images are previewable in the media library;
- article importer can resolve imported media through the temporary import manifest.

### DDS-018: WordPress Pages Mapping Prototype

Goal: map valuable WordPress pages into first-class DDS targets instead of generic pages.

Tasks:

- inspect the current WordPress pages and identify target models or static page keys;
- map training pages into event guidance or future event records;
- map location pages into `Location` records;
- map house rules into managed static content;
- map partners into `Partner` records where possible;
- map media mentions into articles or a deferred media-mention model;
- produce a report for pages that require manual rewriting.

Acceptance criteria:

- every valuable WordPress page has a target or an explicit skip reason;
- known routes from the information architecture have a redirect target;
- importer does not create arbitrary public pages by default;
- manual rewrite work is visible before launch;
- page mapping can be rerun in staging.

### DDS-019: Imported Content Cleanup Pipeline

Goal: normalize imported WordPress HTML into clean public content.

Tasks:

- strip WordPress shortcodes, theme wrappers, social widgets, and duplicated layout markup;
- normalize heading levels;
- rewrite internal links to new Laravel routes where mappings exist;
- rewrite media URLs to imported media asset URLs;
- preserve useful embeds such as YouTube where safe;
- report unresolved links, missing media, and suspicious markup;
- decide whether cleaned content is stored as HTML, markdown-like content, or structured rich text.

Acceptance criteria:

- imported article/page content does not carry WordPress theme markup;
- broken internal links are reported;
- media references are rewritten where possible;
- cleanup can run in dry-run mode;
- risky transformations are visible in import logs.

### DDS-020: WordPress Redirect Import And Review

Goal: generate a launch-ready redirect map from legacy WordPress URLs.

Tasks:

- collect URLs from WordPress exports, known page mappings, and current sitemap if available;
- generate `Redirect` records for posts, pages, locations, training URLs, and news URLs;
- detect duplicate sources and target conflicts;
- add admin review state or notes for uncertain redirects;
- add tests for common legacy redirects;
- prepare a report for redirects that need manual decision.

Acceptance criteria:

- old post URLs redirect to new article URLs;
- known page URLs redirect to event, location, partner, house rules, or contact targets;
- duplicate or ambiguous redirects are flagged;
- redirects can be reviewed before launch;
- redirect import can run repeatedly without creating duplicates.

### DDS-021: Temporary Import Review Report

Goal: make staging import results understandable without turning one-time migration state into a permanent dashboard feature.

Tasks:

- generate a staging-only report for imported counts, skipped records, failed media, unresolved links, and redirect conflicts;
- include temporary source-to-target mappings from the import manifest;
- record review decisions in the report or manifest during rehearsal;
- keep permanent admin resource lists source-agnostic;
- define when import artifacts can be removed after launch verification.

Acceptance criteria:

- admins can see what the importer did;
- failed or skipped records are visible after the command exits;
- imported records are traceable to WordPress source data during rehearsal and the rollback window;
- review status is clear enough for launch preparation;
- command-line logs and the generated report tell the same story;
- removing import artifacts does not affect normalized content or public redirects.

### DDS-022: Staging Import Rehearsal

Goal: run the full import sequence in staging and identify launch blockers.

Tasks:

- run media import;
- run posts/articles import;
- run pages mapping import;
- run redirect import;
- review sample public pages after import;
- review admin import reports;
- document blockers, manual cleanup, and content gaps;
- rerun import after fixes to prove idempotency.

Acceptance criteria:

- full import can be run in a staging environment;
- repeated import does not duplicate records;
- sample imported articles, media, redirects, locations, and static pages render correctly;
- launch blockers are documented as concrete backlog tickets;
- manual cleanup workload is understood before production cutover.

## Epic 7: Launch Readiness

### DDS-023: Production Runtime Configuration

Goal: prepare the Laravel app for production hosting.

Tasks:

- define cache, session, queue, mail, filesystem, and scheduler requirements;
- decide whether Redis is required for launch or deferred;
- configure queue worker and scheduler process expectations;
- configure production mail provider and from-addresses;
- define backup expectations for database and media storage;
- document environment variables needed for deployment.

Acceptance criteria:

- production runtime requirements are explicit;
- required environment variables are known;
- queue and scheduler expectations are not forgotten;
- mail delivery path is tested or explicitly deferred;
- backups are planned before production launch.

### DDS-024: CI And Deployment Pipeline

Goal: make quality checks and deployment repeatable.

Tasks:

- add or finalize GitHub Actions for PHP tests, Pint, Larastan, frontend lint/typecheck/build, and dependency validation;
- decide deployment target and deployment trigger;
- ensure build artifacts are generated in deployment, not committed;
- add smoke check expectations for public URLs after deploy;
- document rollback expectations.

Acceptance criteria:

- pull requests run the baseline checks;
- deployment process is documented and repeatable;
- failed checks block unsafe merges;
- public smoke checks cover home, events, projects, news, about, contact, login, and dashboard;
- rollback approach is understood.

### DDS-025: Public Accessibility And Responsive Audit

Goal: verify public pages against the UX quality bar before launch.

Tasks:

- audit home, events, event detail, projects, project detail, news, article detail, locations, about, house rules, partners, and contact;
- review every public frontend section and repeated item as its own mobile QA checkpoint, including headers, content bands, cards, lists, carousels, filters, forms, CTAs, and footers;
- check keyboard navigation, focus order, labels, contrast, landmark structure, and heading hierarchy;
- test common mobile viewport widths and both short and representative long or optional content;
- fix overlap, horizontal page overflow, awkward wrapping, inconsistent alignment, broken rhythm, cropped media, and unreadable text issues;
- verify that carousel and overflow affordances remain understandable without instructional labels that add visual noise;
- check empty states and error states.

Acceptance criteria:

- critical public pages are usable on mobile and desktop;
- keyboard focus is visible and logical;
- forms have accessible labels and errors;
- headings follow a reasonable hierarchy;
- every audited section has an explicit mobile result or tracked follow-up;
- repeated items remain consistently aligned when their content lengths differ;
- no critical text overlap, unexplained interruption, horizontal page overflow, or layout breakage remains.

### DDS-026: Admin Usability Audit

Goal: verify the management area is efficient enough for repeated real use.

Tasks:

- audit dashboard, event CRUD, article CRUD, project CRUD, location CRUD, partner CRUD, media library, user management, contact submissions, redirects, and import review;
- test common admin workflows from empty state to published content;
- check validation, save feedback, destructive confirmations, and permission behavior;
- test mobile/tablet fallback for urgent admin tasks;
- reduce unnecessary clicks in high-frequency workflows.

Acceptance criteria:

- admins can complete common tasks without direct database access;
- editors are constrained by permissions;
- repeated content editing is fast and predictable;
- validation recovery is clear;
- destructive actions are protected.

### DDS-027: Launch Content Freeze And Cutover Checklist

Goal: coordinate final content, redirects, and production switch-over.

Tasks:

- define content freeze timing for WordPress;
- run final production or staging import;
- review critical pages and redirects;
- verify DNS/deployment steps;
- verify analytics, robots, sitemap, and canonical behavior;
- verify contact form and mail delivery;
- prepare post-launch monitoring checklist.

Acceptance criteria:

- final import and redirect state are reviewed;
- WordPress freeze/cutover process is clear;
- critical public pages work after deployment;
- contact path works;
- SEO basics are active;
- post-launch issues can be triaged quickly.

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
11. DDS-007A
12. DDS-007B
13. DDS-007C
14. DDS-007D
15. DDS-008
16. DDS-008A
17. DDS-008B
18. DDS-014
19. DDS-012
20. DDS-009
21. DDS-009A
22. DDS-013
23. DDS-014A
24. DDS-014D
25. DDS-014F
26. DDS-011A
27. DDS-011B
28. DDS-011C
29. DDS-011D
30. DDS-014H
31. DDS-010
32. DDS-010A
33. DDS-010B
34. DDS-011
35. DDS-014B
36. DDS-014C
37. DDS-014E
38. DDS-014I
39. DDS-014J
40. DDS-014L
41. DDS-014K
42. DDS-014G
43. DDS-011E
44. DDS-011F
45. DDS-015
46. DDS-017
47. DDS-016
48. DDS-018
49. DDS-019
50. DDS-020
51. DDS-021
52. DDS-022
53. DDS-023
54. DDS-024
55. DDS-025
56. DDS-026
57. DDS-027

The WordPress importer should not be treated as the next major build step after static routes. The platform needs public branding, target content models, admin review flows, media handling, redirects, and user/permission management first. Import work can start as discovery earlier, but production-grade import should wait until the target models and admin review screens exist.
