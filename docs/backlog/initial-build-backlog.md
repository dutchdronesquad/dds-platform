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

`main` contains the Laravel foundation, public shell and homepage, Event and Season domains, public Event experience, Event and Season management, user and permission management, operational dashboard, DDS authentication branding, reusable media-library management through DDS-014H, the public contact form and dashboard review flow, and public Location pages with admin Location management.

DDS-014A and DDS-014B are complete and merged in pull request #24. DDS-014C is complete and merged in pull request #25 with its maintenance workflow and CMS decision gate. DDS-014D, absorbing DDS-007E, is complete and merged in pull request #26 with the code-owned partner catalogue and public presentation. DDS-014G is complete and merged in pull request #27 with the public contact form, stored submissions, and dashboard review flow. DDS-014J and DDS-014L are complete and merged in pull requests #29 and #30. DDS-014K and DDS-011H are complete as deliberate no-build decisions. DDS-007D and DDS-014N are complete and merged in pull requests #31 and #33. DDS-015 is complete with an approved selective migration matrix and REST as the primary source. DDS-017 is the current media-import prototype.

Projects, partners, and fixed informational pages are deliberately code-owned in phase 1. They do not get database models, permissions, or dashboard CRUD unless observed maintenance needs pass their documented CMS decision gates.

The authoritative order for all unfinished work is [Open Work Execution Order](./open-work-execution-order.md). Keep ticket definitions and acceptance criteria in this document, but do not maintain a second numbered execution list here.

Do not jump straight to the WordPress importer. Import discovery can happen earlier, but production-grade import waits until the public destinations and admin review flows for the selected content exist.

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

Status: implemented. The homepage includes the revised hero, experienced-pilot and beginner paths, Sportpaleis proof, upcoming events, About and news previews, a compact partner-logo row near the footer, and a final agenda CTA. Projects are deliberately routed through the primary navigation and footer until real project cases justify a homepage feature. The partner zone shows the verified Droneshop.nl logo from the legacy website, omits its anonymous template placeholders, and does not include a separate sales callout. Temporary event and legacy-news content can move to their domain models without redesigning the page; partner content remains supplied by a typed code-owned catalogue in phase 1.

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

Status: implemented and awaiting review. The final public hierarchy keeps `Starten met FPV`, `Locaties`, `Nieuws`, and `Contact` directly visible, groups `Over DDS`, `Projecten`, and `Partners` under a compact Information menu on desktop, exposes that group inline on mobile, and retains a separate agenda action. Section and detail routes now expose active states in the header, agenda action, mobile menu, and footer. Contact remains directly available in the primary and mobile navigation; the footer does not duplicate the general email address or a demo/workshop/collaboration contact path. Media coverage remains part of News and race results remain part of event or race-report content until either justifies a real standalone destination; Privacy is reserved as a footer destination once approved policy copy exists rather than publishing a legal placeholder.

Keyboard and screen-reader verification now covers a visible-on-focus skip link, one labelled primary navigation landmark, menu focus containment, Escape close with focus restoration, background scroll and assistive-technology isolation while the mobile menu is open, meaningful current-page states, and safe external-link announcements. Representative public routes pass the Pest browser accessibility scan, including contrast corrections found on the homepage and shared fixed-page cards.

Goal: make the public shell feel coherent across pages before real content models land.

Tasks:

- finalize primary navigation labels and order;
- decide where secondary links such as Locations, House Rules, Partners, In The Media, Results, and Privacy live;
- add active states for section and detail pages;
- improve mobile navigation ergonomics;
- keep contact available through the public navigation without duplicate footer contact pathways;
- keep login/admin entry private and available only through its direct route;
- verify navigation with keyboard and screen reader basics.

Acceptance criteria:

- navigation follows the active locale; the current homepage can remain temporarily Dutch-first until the multilingual public content layer is in use, without changing English as the platform default;
- secondary links are discoverable without crowding the header;
- active states work for index and detail routes;
- public header/footer do not feel like starter-kit leftovers;
- mobile navigation fits without awkward wrapping or hidden critical links.

### DDS-007E: Add Sportpaleis Alkmaar As A Homepage Partner

Status: absorbed into DDS-014D so partner selection, validation, imagery, and homepage presentation are implemented through one code-owned catalogue instead of a one-off homepage entry.

Goal: recognize Sportpaleis Alkmaar as a DDS partner in the homepage partner section.

Tasks:

- add Sportpaleis Alkmaar to the homepage partner data with `https://sportpaleis-alkmaar.nl/` as its website URL;
- obtain and use an official Sportpaleis Alkmaar logo asset with appropriate usage permission instead of recreating or approximating the logo;
- present the logo alongside the existing homepage partner logos with a consistent visual weight, clear spacing, and preserved aspect ratio;
- make the logo or partner entry link to the Sportpaleis Alkmaar website with an accessible partner name and the existing external-link safety conventions;
- verify the partner row at representative mobile and desktop widths, including wrapping, alignment, contrast, and focus states;
- move the temporary homepage entry into the typed code-owned partner catalogue from DDS-014D while retaining its homepage-featured state.

Acceptance criteria:

- Sportpaleis Alkmaar is visibly represented as a partner on the homepage with its official logo;
- the partner entry links to `https://sportpaleis-alkmaar.nl/`;
- the logo is sharp, not distorted, and visually balanced with the other partner logos;
- the partner row remains neatly aligned without overflow or awkward breaks on mobile and desktop;
- the partner link has a meaningful accessible name, visible keyboard focus, and safe external-link behavior;
- the implementation can migrate to a future structured model without redesigning the homepage section if the DDS-014E decision gate is ever reached.

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

Status: complete and merged as a schema dependency of DDS-009. Media assets have stable storage identity, a recognizable original filename, optional locale-aware alt text defaults, and optional image dimensions, without WordPress-specific runtime metadata. Whether an image is informative or decorative belongs to its rendering context. Images and PDFs share the same reusable model. Upload, search, selection, and usage management are implemented through DDS-014H.

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

Status: complete and merged as a schema dependency of DDS-009. Locations have an official name, an English-base translated description, a structured Dutch address, indoor/outdoor environment, optional physical dimensions, fixed facility codes, coordinates, an optional cover image, and an event relationship. They intentionally have no publication state or model-specific SEO fields. Public pages and admin management remain in DDS-014I.

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

Status: complete and merged in pull request #8. Seasons provide optional event grouping, a nullable season price, and a season-ticket limit. Events have a required protected location, an optional protected season, a removable cover-image reference, plain-text content, enum-backed type/publication/registration states, scheduled publication, and one listing-oriented index. Model-specific SEO and WordPress fields are intentionally absent.

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

Status: complete and merged in pull request #13. `Season` remains a generic event grouping for training series, competitions, rankings, or other programmes. A separate optional `SeasonTicket` product owns sales details and an explicit eligible-event selection. Public summaries derive the season date range from all grouped events and ticket eligibility from only the selected events. Cancelled events remain visible in their season context and, when eligible, remain counted as ticket events with their cancelled state; no refund or replacement policy is inferred.

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

Status: complete and merged in pull request #10.

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

Status: complete and merged in pull request #11.

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

Status: complete and merged in pull request #14.

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

Status: complete and merged. Event and Season management use the shared dashboard resource patterns.

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

Status: complete and merged in pull request #15.

Goal: turn `/dashboard` into a practical management home instead of a placeholder.

Tasks:

- define the first dashboard sections and resource navigation;
- add quick links for actual dashboard resources such as events, articles, locations, media, users, and redirects, without dead-end project or partner actions;
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

Status: complete and merged in pull request #16.

Goal: establish repeatable admin patterns before building many resource screens.

Tasks:

- define list page layout conventions for filters, search, pagination, bulk actions, and row actions;
- define create/edit form layout conventions for tabs or sections, save actions, validation, and destructive actions;
- add shared admin components only where repetition is already visible;
- define status badge patterns for draft, published, archived, active, and inactive states;
- define confirmation dialog patterns for archive, delete, publish, and unpublish;
- ensure all patterns work with Inertia forms and server-side validation.

Acceptance criteria:

- model-backed event, article, location, media, user, redirect, and later contact screens can share consistent patterns;
- admin tables support scanning and repeated work;
- form actions are predictable across resources;
- validation and success/error feedback are consistent;
- abstractions remain small and do not block resource-specific UX.

### DDS-011C: User Management

Status: complete and merged in pull request #18.

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

Status: complete and merged in pull request #19.

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

Status: complete and merged in pull request #20.

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

Product rules and implementation gates are maintained in [Training Registration And Capacity](../product/training-registration-and-capacity.md). This ticket must implement that approved policy without becoming a second source of truth.

Tasks:

- keep initial registration calls to action email-based and avoid presenting unverified live availability or waitlist behavior;
- implement approved holder, allocation, payment, cancellation, refund, and attendance states;
- derive holder access from the purchased product and its explicit eligible events;
- reserve eligible events by default and support per-event opt-out without requiring attendance confirmation or issuing an automatic refund;
- keep payment records provider-agnostic while supporting manual external-payment registration in the initial workflow;
- provide administrators with holder, payment, allocation, opt-out, and per-event attendance overviews plus an export for manual heat planning;
- give authenticated holders one clear overview of their included events and required actions;
- log important operational changes, administrator exceptions, and refund reasons, and protect personal data through policies and retention rules.

Acceptance criteria:

- the initial public registration action opens or clearly explains the email process without implying that registration is completed in the platform;
- administrators can distinguish season-ticket allocation from actual attendance for each event;
- only an explicit holder opt-out releases a season-ticket allocation, and opt-outs and cancellations cannot silently corrupt event capacity;
- holders can see their reserved events and completed opt-outs without being asked to confirm every event;
- cancellations, refunds, organizer cancellations, and capacity exceptions follow the product document and require explicit administrator handling where no automatic rule is approved;
- no native registration, live waitlist, online payment, or automatic heat planning is introduced through this ticket;
- authorization and focused workflow tests cover administrator and holder behavior.

### DDS-011G: Dashboard And Authentication Branding

Status: complete and merged in pull request #21.

Goal: make the authenticated part of the platform recognizably DDS by consistently applying the official logo to the dashboard and authentication pages.

Tasks:

- confirm the canonical full and compact DDS logo assets, including suitable variants for light and dark backgrounds;
- replace the generic application mark in the dashboard shell and shared authentication layouts with DDS branding;
- centralize the logo treatment in one reusable component instead of duplicating assets or markup;
- preserve the logo's aspect ratio, clear space, and legibility across desktop and mobile layouts;
- give meaningful logo links an accessible name and hide purely decorative duplicates from assistive technology;
- verify the login, password reset, email verification, two-factor authentication, and dashboard entry screens in light and dark mode.

Acceptance criteria:

- the dashboard and authentication pages are immediately recognizable as part of DDS;
- all authenticated and authentication layouts use one shared source of truth for the DDS logo;
- full and compact variants remain sharp, undistorted, and readable in light and dark mode;
- the logo does not introduce overflow, layout shift, or cramped headings on small screens;
- screen-reader behavior correctly distinguishes linked branding from decorative imagery;
- focused component or browser tests cover the shared branding on the key dashboard and authentication layouts.

### DDS-011H: Native Training Registration And Capacity Discovery

Status: complete as a deliberate no-build decision. DDS keeps the current split-heat workflow email-based and manually administered. Native registration is deferred until DDS has tested and approved fully mixed heats and selected an intended implementation season.

Decision: current registrations are manually confirmed and administered broadly first-in-first-out. A full compatible group produces a manually managed waiting list; administrators may exercise judgment without a required exception taxonomy or audit reason. Payment remains outside the platform. The candidate fully mixed profile set is Analog, HDZero, DJI O4 in race mode, and Walksnail in race mode, but DJI O4 and Walksnail remain unapproved until DDS completes field testing after the summer stop. The effective native-registration season remains deliberately undecided.

Goal: establish a safe and fair capacity model before DDS replaces email registration with native training registration, waitlists, or online payment.

The confirmed current workflow, proposed training formats, capacity examples, vendor references, and implementation gates are maintained in [Training Registration And Capacity](../product/training-registration-and-capacity.md).

Discovery outcomes:

- the current split-heat workflow remains email-based, manually confirmed, and manually administered using first-in-first-out as its guiding principle;
- waiting pilots are managed manually, with administrator judgment and no required exception taxonomy or audit reason;
- payment remains outside the platform;
- native registration is not built for the split-heat format;
- Analog, HDZero, DJI O4 race mode, and Walksnail race mode form the candidate fully mixed profile set;
- DJI O4 and Walksnail race mode remain unapproved until DDS has field-tested them in its actual venue and channel plan;
- the product document owns the detailed policy and records the gates for reopening discovery after the summer stop.

Acceptance criteria:

- the current email workflow, manual confirmation, first-in-first-out principle, waiting-list handling, and external payment choice are documented without implying native platform behavior;
- DDS introduces no native split-format registration, live availability, waiting list, or checkout;
- no untested digital race-mode profile is described as approved for fully mixed heats;
- field testing, mixed-format approval, and selection of an intended season are explicit conditions for reopening native-registration discovery;
- privacy, retention, detailed workflow states, and any future payment timing remain implementation gates rather than invented decisions.

## Epic 5: Supporting Content Models

### DDS-013: Article Model

Status: complete and merged in pull request #9. Articles have source-agnostic content fields, optional author and cover-image relationships, enum-backed semantic categories and draft/published/archived states, and a publication-date-aware public scope. Excerpts are a frontend presentation concern derived from content, not a persisted field or `Article` model attribute. WordPress identifiers, legacy terms, and import bookkeeping remain outside the permanent article table.

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

### DDS-014A: Code-Owned Project Catalogue And Selection

Status: complete and merged in pull request #24.

Decision: phase 1 keeps the project showcase deliberately code-owned. DDS does not introduce a `Project` model, project tables, project permissions, or dashboard CRUD until the maintenance burden proves that a CMS is needed.

Goal: define a small, curated source of truth for DDS-built tooling, software, plugins, apps, integrations, and selected community builds without constraining the public presentation to a generic content model.

Tasks:

- select the initial projects that have a clear public audience, credible outcome, and suitable supporting material;
- create a typed code-owned catalogue, preferably in a dedicated configuration file, with stable slugs, titles, concise summaries, project-type labels, primary links, optional supporting links, credits, and static media paths;
- include only projects intended for public presentation instead of storing internal or private work alongside public entries;
- define which projects justify a dedicated case page and which belong only in the overview;
- keep project imagery in versioned static assets while the catalogue remains code-owned;
- validate unique slugs, required fields, safe external links, and referenced catalogue entries through focused automated tests;
- record that catalogue changes are reviewed and deployed through the normal pull-request workflow.

Acceptance criteria:

- the initial RotorHazard plugins, TrackDraw-style apps, race tooling, livestream overlays, or community utilities can be described without a database model;
- every catalogue entry has a stable slug, clear purpose, primary destination, credit where relevant, and an explicit public audience;
- internal planning data, private projects, task status, and operational project management do not enter the public catalogue;
- catalogue mistakes such as duplicate slugs, missing required fields, and unsafe links are caught automatically;
- adding or changing a project requires a reviewed code change and does not imply that dashboard management already exists.

### DDS-014B: Art-Directed Public Project Showcase

Status: complete and merged in pull request #24.

Goal: replace the temporary `/projects` shell with a distinctive public showcase that presents a small number of DDS projects as credible cases rather than generic CMS records.

Tasks:

- build a dedicated `/projects` Inertia page from the code-owned catalogue;
- give the overview a strong responsive composition with clear project purpose, outcome, credits, media, and primary action;
- add `/projects/{slug}` only for projects with enough context, visuals, or documentation to justify a dedicated case page;
- allow dedicated case components or explicit presentation variants when projects need different storytelling instead of forcing every case into one template;
- add project-type grouping or filtering only when the catalogue becomes large enough for it to help visitors;
- provide useful external links to GitHub, a live demo, documentation, downloads, or contact where they exist;
- add project-specific SEO metadata, safe external-link behavior, useful not-found handling, and representative mobile, keyboard, and accessibility coverage.

Acceptance criteria:

- `/projects` presents the curated public catalogue with a clearly designed DDS identity;
- entries with dedicated cases have stable `/projects/{slug}` URLs, while smaller projects can remain overview-only;
- each visible project communicates its purpose, value, ownership or credits, current public relevance, and one clear next action;
- layouts can vary deliberately for flagship cases without losing navigation, accessibility, or responsive consistency;
- visitors never see internal/private projects or an empty generic project-management state;
- project content can be changed safely through code review without requiring a database or dashboard workflow.

### DDS-014C: Project Showcase Maintenance Workflow And CMS Decision Gate

Status: complete and merged in pull request #25. The code-owned editing workflow, asset and preview conventions, focused checks, reviewer expectations, maintenance evidence, measurable CMS triggers, and future migration contract are documented. The dead-end project resource has been removed from the dashboard response, planned-resource list, and admin navigation.

Decision: there is no `/dashboard/projects` resource in phase 1. This ticket protects that choice and defines when it should be reconsidered instead of pre-emptively building CRUD.

Goal: keep project-showcase maintenance predictable and lightweight while preserving a clear path to structured management if real editorial needs emerge.

Tasks:

- document the code-owned editing workflow, catalogue location, static-asset conventions, preview steps, required tests, and reviewer expectations;
- remove or avoid dashboard navigation and placeholder actions that imply project CRUD is available or planned for immediate delivery;
- review the catalogue after real use and record how often projects, ordering, links, credits, and case content change;
- reconsider a `Project` model and dashboard workflow only when non-technical editors need independent access, catalogue changes become frequent, the catalogue grows enough to need operational filtering or archival state, or the same data needs multiple non-code consumers;
- if the decision gate is reached, design a later migration that preserves public slugs, URLs, credits, media, and the art-directed presentation rather than replacing it with a generic page builder;
- keep media-library attachment, permissions, publication states, and reordering out of scope until that later decision is approved.

Acceptance criteria:

- maintainers have one documented and tested way to add or change a project through a pull request;
- the dashboard does not contain a dead-end project-management action;
- the concrete CMS triggers are documented and can be evaluated using observed maintenance needs;
- no project model, migration, policy, permission set, Form Request, or admin form is introduced in phase 1;
- a future CMS migration can preserve existing public URLs and custom presentation if the decision changes.

### DDS-014D: Code-Owned Partner Catalogue And Public Presentation

Status: implemented and ready for review. Droneshop.nl and Sportpaleis Alkmaar now live in one validated code-owned catalogue that supplies both the homepage partner row and the dedicated `/partners` presentation. Public data is deliberately limited, partner links and logo assets are validated, the dead-end dashboard placeholder has been removed, and DDS-007E is fully absorbed. The ticket remains open until its pull request is reviewed and merged.

Decision: phase 1 keeps the small and slowly changing partner list in code. DDS does not introduce a `Partner` model, partner tables, permissions, or dashboard CRUD merely to replace a short verified list that developers already update safely through pull requests.

Goal: give verified partners and sponsors a consistent public presentation from one typed, code-owned source of truth.

Tasks:

- create a dedicated typed partner catalogue in configuration or another code-owned data file;
- include stable keys, names, website URLs, versioned logo paths, accessible logo text, optional public descriptions, manual order, and explicit homepage visibility;
- migrate the existing Droneshop.nl entry and DDS-007E Sportpaleis Alkmaar entry into that catalogue;
- use the same catalogue for the homepage partner row and `/partners` when a separate page adds visitor value;
- validate unique keys, safe external URLs, required fields, referenced assets, and deterministic ordering through focused automated tests;
- document the asset, preview, test, and pull-request workflow for adding or changing a partner;
- keep private contact notes, agreements, invoices, and sponsor administration outside the public catalogue.

Acceptance criteria:

- homepage and optional partner-page presentation use one verified catalogue instead of duplicated page copy;
- each visible partner has a recognizable logo, safe destination, meaningful accessible name, and deliberate position;
- partner changes are reviewable code changes and do not require a database or dashboard resource;
- private or unverified partner information cannot leak through the public catalogue;
- the presentation can preserve its routes and visual design if a future CMS decision later changes the storage mechanism.

### DDS-014E: Partner CMS Decision Gate

Status: deferred and not part of the active execution order. Reopen only when the decision gate below is reached.

Goal: prevent speculative partner CRUD while retaining an explicit route to managed partner content if real maintenance needs emerge.

Reopen this ticket only when at least one of these conditions is observed:

- non-technical editors need to add or update partners independently;
- partner visibility, ordering, tiers, or campaign periods change frequently;
- contracts or sponsor lifecycle state need structured operational handling;
- partner data needs multiple non-code consumers;
- pull-request maintenance creates measurable delay or repeated errors.

If reopened:

- define the required model, migration, validation, policies, permissions, media relationships, publication states, and dashboard workflow from observed needs;
- preserve existing public keys, URLs, logo assets, ordering, SEO behavior, and designed presentation;
- define a migration from the code-owned catalogue without running both sources in parallel.

Acceptance criteria:

- no `/dashboard/partners` placeholder or unused partner permission exists before the gate is reached;
- the decision to introduce partner management is supported by recorded maintenance evidence;
- any later CMS migration preserves the public experience and has one clear source of truth.

### DDS-014F: Managed Static Content For Known Public Pages

Status: deferred and not part of the active execution order. The July 2026 review found no demonstrated need for non-technical editors to maintain these pages independently.

Decision:

- keep about, house rules, the contact intro, homepage sections, partner intro, and location intro in code-owned configuration or their designed React templates;
- do not add a `StaticPage` or `PublicPage` model, database migration, page permissions, or `/dashboard/pages` screens in phase 1;
- map useful WordPress page content deliberately into these known templates during migration rather than preserving a generic CMS page model.

Reopen this ticket only when at least one of these conditions is observed:

- non-technical editors need to change fixed-page copy independently;
- fixed-page changes become frequent or urgent enough that pull-request maintenance causes measurable delay;
- parallel-language maintenance needs a dedicated editorial workflow;
- migration discovery identifies approved legacy page content that cannot be maintained reasonably in the existing templates.

If reopened:

- decide exactly which known pages need managed content;
- support fixed page keys only, without arbitrary user-created routes;
- define required translated fields, publication state, update metadata, validation, policies, permissions, and admin edit screens;
- preserve the designed React templates and establish one source of truth for migrated content.

Acceptance criteria:

- no unused page model, migration, permission, or dashboard placeholder exists before the gate is reached;
- the decision to introduce managed page content is supported by recorded maintenance evidence;
- any later managed-content implementation keeps routes stable and does not introduce a generic page builder.

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

Replying to a submission directly from the dashboard is tracked separately as DDS-014M and is not part of the DDS-014G definition of done.

### DDS-014H: Media Library Admin

Status: complete and merged in pull request #22.

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
- media records can be attached to model-backed events, articles, and locations, while code-owned partner and project catalogues continue to use versioned static assets;
- imported WordPress media can coexist with manually uploaded media;
- unused media can be reviewed without deleting it automatically.

Optional follow-up tasks — AI-assisted alt text defaults (deferred and not part of the DDS-014H definition of done):

- select an AI provider and model, with an explicit quality, privacy, and cost assessment;
- generate alt text asynchronously through a queued job with retries, rate limits, and cost limits;
- present generated text as an editable suggestion that never silently replaces editorial alt text;
- support the configured locales and distinguish a reusable asset default from context-specific alt text;
- add production configuration, privacy safeguards, monitoring, and failure reporting for the generation workflow.

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

Status: complete and merged in pull request #29.

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

Status: complete as a deliberate no-build decision. After reviewing the real Getting Started content delivered in DDS-014L, no managed guide library or admin workflow is needed in phase 1.

Decision:

- keep the three published guides code-owned through `config/getting_started_guides.php` and their dedicated React page templates;
- retain the existing stable slugs, manual ordering, editorial owner, and review date metadata, which are already shared and covered by focused public feature tests;
- do not add a `Guide` model, database table, policy, permissions, rich-text body, publication workflow, or `/dashboard/guides` resource merely to move a small, designed guide set into a second content system;
- treat the entries in the guide catalogue as the explicitly published set; a guide that is not in that catalogue has no public route;
- continue sourcing changing event, season, ticket, and location facts from their domain models rather than guide prose.

The implemented evidence does not justify the original build scope: the catalogue contains three guides, each guide has a deliberately different long-form presentation, and no independent non-technical editing need or repeated pull-request maintenance problem has been observed. A constrained body editor would not replace those templates, while a flexible editor would become the generic page builder this ticket was intended to avoid.

Reopen this ticket only when at least one of these conditions is observed:

- non-technical editors need to publish or revise guides independently;
- guide updates become frequent or urgent enough that pull-request maintenance creates measurable delay or errors;
- the guide set grows enough to need operational draft, archive, category, ordering, ownership, or review-due workflows;
- actively maintained English and Dutch variants require coordinated editorial fallback and review;
- guide content needs structured reuse by another non-code consumer;
- the current owner and review-date checks prove insufficient to keep safety or equipment guidance current.

If reopened, preserve the public slugs, designed page templates, metadata, source-of-truth boundaries, and existing URLs while migrating to one managed source. Do not run code-owned and database-owned guide bodies in parallel.

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

Status: complete and merged in pull request #30.

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

### DDS-014M: Reply To Contact Submissions From The Dashboard

Status: optional follow-up to DDS-014G, deferred and not required before other queued work.

Goal: let admins answer a visitor directly from the contact submission detail view instead of only reading it and switching to an external mailbox.

Tasks:

- add a reply composer to the contact submission detail view;
- store sent replies against the submission (e.g. a `contact_replies` table or a lightweight thread), including sender, body, and delivery status;
- send replies through the configured mailer with the visitor as recipient, keeping the existing reply-to behavior reviewable;
- track a submission's conversation status (e.g. open, replied, closed) separately from the existing delivery status;
- add authorization, audit logging, and abuse safeguards for outbound replies sent by admins.

Acceptance criteria:

- an admin with contact permissions can send a reply to a visitor without leaving the dashboard;
- sent replies are stored and visible on the submission's detail view;
- reply delivery failures are visible and do not silently lose the reply;
- only authorized users can send replies, and sends are attributable to the sending admin.

### DDS-014N: Art-Directed Over DDS Page

Status: complete and merged in pull request #33.

Goal: replace the generic `/about` public shell with a distinctive, code-owned page that explains who Dutch Drone Squad is, what the group organizes, and why pilots, visitors, and partners should get involved.

Tasks:

- replace the shared `public/shell` presentation for `/about` with a dedicated Inertia React page while keeping the stable route and SEO contract;
- turn the existing temporary copy into a clear Dutch-first narrative covering the DDS story, FPV racing, community, knowledge sharing, events, and collaborations;
- move the team and community context reserved by DDS-007B onto this page without publishing private member information or unsupported claims;
- use real, versioned DDS photography and the established public visual language instead of generic cards or placeholder sections;
- provide deliberate next actions to the Agenda, Getting Started hub, Projects, and Contact without duplicating their content;
- keep stable editorial copy and presentation code-owned in accordance with the DDS-014F decision, while sourcing changing event or location facts from their domain models when shown;
- verify heading hierarchy, landmarks, alternative text, keyboard navigation, contrast, and representative mobile and desktop layouts;
- add focused feature and browser coverage for content, routes, links, responsive behavior, and accessibility.

Acceptance criteria:

- `/about` no longer renders as a generic fixed-page shell;
- visitors can understand DDS's identity, activities, community role, and relevant ways to participate;
- team and community content has a deliberate home without bloating the homepage;
- content uses verified DDS facts and imagery and avoids invented history, statistics, or member details;
- the page remains code-owned and does not introduce a page model, dashboard CRUD, or generic page builder;
- existing `/about` navigation, canonical URL, and SEO behavior remain stable;
- the page passes focused feature, browser, responsive, and accessibility checks.

## Epic 6: WordPress Import Spike

### DDS-015: WordPress Export Discovery

Status: complete. The public REST API is the primary structured capture source, while rehearsal and cutover use a checksummed, curated offline JSON/media bundle so they do not depend on a live WordPress site. The administrator XML export remains one archive and completeness-check file. All 21 published posts start selectable through a file-based manifest; the approved working bundle currently retains 20 posts, all 12 required page records, and 29 selected media files in one flat directory. Unselected template, gallery, and SponsorKliks media are not retained. Static pages are mapped to explicit rewrite, redirect, or removal outcomes in [WordPress Migration](../technical/wordpress-migration.md).

Implementation constraint: keep the one-time importer small, direct, and removable. XML remains a cross-check rather than a second importer. Do not build a generic import framework, synchronization layer, database-backed import administration, or permanent WordPress schema unless a rehearsal proves a specific approved-content gap.

Goal: define the approved migration scope and verify the best import source for the current site after the target content workflows are ready for review.

Start conditions:

- DDS-014H provides a working media library with upload, metadata editing, previews, reuse, and asset selection;
- DDS-014J provides public news pages and Article admin CRUD so sample and imported articles can be reviewed in their real destination;
- every additional WordPress page type included in the discovery has an implemented target model or an explicit manual-rewrite destination;
- the existing redirect foundation is available for legacy URLs that will not be imported directly.

#### DDS-015 discovery subtask: Content Selection And Migration Policy

Before comparing REST and XML exports, hold a content workshop and produce an explicit `import`, `rewrite`, `redirect`, or `skip` decision for each legacy content group.

Questions to resolve:

- whether to preserve all historical news posts, use a publication-date cutoff, or curate a valuable selection;
- which race reports, results, training updates, and other historical records remain useful as a public archive;
- whether About DDS, house rules, locations, training information, and other static pages are copied selectively or rewritten into the new designed pages;
- whether media import is limited to assets referenced by approved content plus selected brand and partner assets;
- which WordPress users, comments, drafts, private content, plugin data, theme markup, categories, and tags are intentionally excluded or normalized;
- which skipped public URLs redirect to a new destination and which should deliberately return `410 Gone`.

Subtask output:

- a reviewed content inventory grouped by WordPress content type and publication period;
- a source-to-target decision matrix with an owner or reason for manual review;
- a provisional media scope and legacy URL policy;
- explicit confirmation that the import is a curated migration rather than a full WordPress clone.

Tasks:

- complete and approve the content-selection subtask;
- test WordPress REST API endpoints;
- compare with XML export availability;
- inspect posts, pages, media, categories, tags, and featured images;
- list fields that need cleanup.

Acceptance criteria:

- preferred import source is selected;
- the content-selection matrix defines what is imported, rewritten, redirected, or skipped;
- risks and missing fields are documented;
- sample records are mapped to implemented target models and can be reviewed through their real admin and public presentation;
- DDS-016 through DDS-020 do not start until their relevant target workflows and the content-selection subtask are complete.

### DDS-016: Posts To Articles Import Prototype

Status: in progress. Implemented in the current stacked WordPress-import branch with dry-run support, idempotent manifest mappings, author fallback, category normalization, source tag hints, and featured-media resolution. It remains open until the branch is merged.

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

#### DDS-016A: Markdown Article Authoring And Safe Rendering

Status: proposed follow-up; it is not a blocker for the WordPress importer or DDS-022 rehearsal because imported plain text is valid Markdown-compatible source.

Goal: let editors author structured Article content with Markdown while keeping public rendering safe and consistent.

Tasks:

- retain Markdown source in the existing `articles.content` column without introducing a second content representation;
- render Markdown server-side through one reusable application boundary;
- strip raw HTML and reject unsafe links when converting Markdown to public HTML;
- derive SEO descriptions from rendered plain text rather than visible Markdown syntax;
- explain supported Markdown in the admin Article form and provide a useful preview workflow;
- style headings, paragraphs, lists, links, quotes, and code consistently on the public Article page;
- keep existing imported plain-text Articles readable without a data migration.

Acceptance criteria:

- editors can use common Markdown structure in Article content;
- public Article pages render that structure as accessible HTML;
- raw scripts, raw HTML, and unsafe link protocols cannot reach the public response;
- imported plain-text Articles render unchanged in meaning;
- feature tests cover rendering, escaping, unsafe links, and SEO excerpts.

### DDS-017: WordPress Media Import Prototype

Status: in progress. Implemented in the current stacked WordPress-import branch with dry-run support, idempotent manifest mappings, missing-alt diagnostics, guarded downloads, and reusable `MediaAsset` records. It remains open until the branch is merged.

Goal: prove repeatable media import before importing article and page bodies that reference media.

Tasks:

- create dry-run capable media import command;
- capture selected media through REST into the verified local source bundle;
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

Status: in progress. Implemented in the current stacked WordPress-import branch with complete REST-inventory enforcement, constrained Location/route/manual targets, explicit `410` decisions, preserved review state, and a repeatable Markdown review report. It remains open until the branch is merged.

Goal: map valuable WordPress pages into first-class DDS targets instead of generic pages.

Tasks:

- inspect the current WordPress pages and identify target models or static page keys;
- map training pages into event guidance or future event records;
- map location pages into `Location` records;
- map house rules into managed static content;
- review partner pages manually and move only verified names, links, and selected logo assets into the code-owned partner catalogue;
- map media mentions into articles or a deferred media-mention model;
- produce a report for pages that require manual rewriting.

Acceptance criteria:

- every valuable WordPress page has a target or an explicit skip reason;
- known routes from the information architecture have a redirect target;
- importer does not create arbitrary public pages by default;
- manual rewrite work is visible before launch;
- page mapping can be rerun in staging.

### DDS-019: Imported Content Cleanup Pipeline

Status: in progress. Implemented in the current stacked WordPress-import branch as an idempotent plain-text cleanup phase with checksum protection, route and media rewriting, safe YouTube preservation, dry-run diagnostics, and a replaceable Markdown review report. It remains open until the branch is merged.

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

Status: in progress. Implemented in the current stacked WordPress-import branch with derived post/page redirects, optional XML/sitemap aliases, inactive pending-review records, conflict protection, idempotent reuse, and a replaceable Markdown review report. It remains open until the branch is merged.

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
- known page URLs redirect to event, location, code-owned partner, house rules, or contact targets;
- duplicate or ambiguous redirects are flagged;
- redirects can be reviewed before launch;
- redirect import can run repeatedly without creating duplicates.

### DDS-021: Temporary Import Review Report

Status: in progress. Implemented in the current stacked WordPress-import branch with per-phase run history in the temporary manifest, a consolidated staging Markdown report, source-to-target traceability, explicit launch blockers and skips, matching command/report totals, and an artifact-removal policy. It remains open until the branch is merged.

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

Status: in progress and operationally blocked on real staging only. The current branch implements and tests a two-pass `wordpress:rehearse` runner with persistent-count idempotency checks, public Article/media/redirect/Location/static-page HTTP samples, review-artifact checks, manual approval gating, manifest evidence, and a Markdown rehearsal report. The local workspace has the approved `storage/app/imports/wordpress/selection.json`, the 2026-08-08 XML export, and a verified offline bundle. After the DDEV snapshot `before-wordpress-import-2026-08-16`, a controlled local run created 29 media mappings, 20 post mappings, all 12 page decisions, and 27 redirect mappings. The 29 selected images now have reviewed alt text; malformed WordPress attributes and five dead legacy links were resolved; 21 lossy markup removals have explicit reviewed dispositions; and all eight structured-page rewrites are approved. `/house-rules` contains the reviewed Dutch rules and `/media` contains all nine historical mentions. On 2026-08-16 the consolidated local import review and approved two-pass rehearsal both reached `READY`: both passes imported zero new records, persistent counts remained stable, all 15 HTTP samples passed, and the eight structured destinations were checked locally in a real browser, with the new pages also checked at a 390-pixel viewport. The actual staging rehearsal has not run because no staging base URL or staging access is available. DDS-022 remains open until DDS-022A and DDS-022B repeat this evidence in real staging.

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

#### DDS-022A: Provision And Execute The Real Staging Rehearsal

Goal: transfer the approved local inputs to a recoverable staging environment and execute the implemented runner.

Procedure: follow [WordPress Importer Operator Runbook](../technical/wordpress-importer-runbook.md), especially “DDS-022A: Provision And Execute Staging”.

Tasks:

- securely transfer the approved selection manifest, offline source bundle, XML archive, author mappings, and selected media to staging; REST access to the old site is no longer required;
- provide the public staging base URL and authorized application/database/storage access;
- run `php artisan wordpress:rehearse --manifest=storage/app/imports/wordpress/selection.json --base-url=<staging-url>`;
- retain `page-review.md`, `cleanup-review.md`, `redirect-review.md`, `import-review.md`, and `rehearsal-review.md` as ticket evidence;
- confirm pass two imports zero new records and persistent model counts remain stable.

Acceptance criteria:

- the rehearsal evidence comes from the real staging environment and approved WordPress inventory;
- all five phases complete twice;
- idempotency checks pass or produce concrete blockers;
- generated artifacts are attached to the staging review.

#### DDS-022B: Resolve Rehearsal Blockers And Approve Samples

Goal: turn staging findings into owned launch work and finish the required human review.

Procedure: follow [WordPress Importer Operator Runbook](../technical/wordpress-importer-runbook.md), especially “DDS-022B: Review, Resolve, And Approve”.

Tasks:

- inspect representative imported Articles, media, redirects, Locations, and static destinations in a real browser;
- review all phase and consolidated reports through the authorized admin/editor workflow;
- convert every generated `DDS-022-B###` blocker into a concrete backlog ticket with owner and disposition;
- document skipped content and remaining manual cleanup workload;
- fix or explicitly accept every blocker, rerun the rehearsal, then pass `--approve-manual-review`.

Acceptance criteria:

- no generated blocker lacks an owned backlog disposition;
- visual and admin review is explicitly approved;
- the final rehearsal status is `READY`;
- production cutover has an understood, reviewed manual workload.

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

- audit dashboard, event CRUD, article CRUD, location CRUD, media library, user management, contact submissions, redirects, and import review;
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

## Open Work Execution Order

[Open Work Execution Order](./open-work-execution-order.md) is the single authoritative numbered list of unfinished tickets. This backlog retains the complete ticket definitions and acceptance criteria; it must not contain a second sequence that can drift out of date.

The execution-order document distinguishes work that is ready, blocked, a decision point, or deliberately deferred. Update it whenever a ticket is merged, split, absorbed, newly blocked, or moved behind an approved dependency.
