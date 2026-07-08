# Decision Log

This file records important project decisions. Later, use separate ADR files if decisions need more detail.

## 2026-07-04: Laravel Modern Monolith

Decision: build DDS Platform as one Laravel application with a React/Inertia frontend.

Reason:

- matches the preferred Laravel direction;
- keeps routing, authentication, policies, validation, and deployment simple;
- fits a small team and incremental growth;
- leaves room for APIs and integrations later.

Alternatives considered:

- separate Next.js frontend with Laravel API;
- keep and extend WordPress;
- headless CMS.

## 2026-07-04: No Filament In Phase 1

Decision: build the admin as a custom React/Inertia interface.

Reason:

- maximum freedom for DDS-specific workflows;
- stronger visual alignment between public and admin areas;
- less dependency on resource abstractions;
- better fit for future registration and custom interaction flows.

Tradeoff:

- this requires more initial implementation work than Filament.

## 2026-07-04: DDEV As Local Environment

Decision: use DDEV as the local development environment.

Reason:

- pleasant workflow for multiple Laravel projects on macOS;
- reproducible services per project;
- local domains and HTTPS are handled well;
- fewer global dependencies than Valet.

Alternatives considered:

- Valet for quick local prototypes;
- Sail as the Laravel-native Docker option.

## 2026-07-04: Concrete Domains Before Page Builder

Decision: start with domain models such as Event, Article, Location, Partner, Project, ContactSubmission, and later EventRegistration.

Reason:

- DDS has clear repeatable content types;
- concrete models enable better validation, admin workflows, and SEO;
- a page builder would increase scope and complexity too early.

## 2026-07-04: Trainings Are Events

Decision: model regular training evenings as `Event` records with `type = training`, not as a separate `Training` aggregate in phase 1. Public dated activities are exposed under `Events`, with training as a filter/type.

Reason:

- trainings, races, demos, and workshops share dates, locations, publication state, SEO, media, and registration concepts;
- the public website exposes events as the main dated-activity section, with trainings as a filter/type;
- one admin CRUD for events is simpler and prevents duplicated form logic;
- registration can later attach to `Event` through `EventRegistration`.

Tradeoff:

- event fields need to support training-specific attributes such as capacity, registration deadline, video system notes, and price.

## 2026-07-04: PostgreSQL As Database

Decision: use PostgreSQL as the project database.

Reason:

- strong default for modern Laravel applications;
- good fit for structured content, JSON metadata where useful, and future reporting;
- works well in DDEV and common production environments.

## 2026-07-04: WordPress Migration Should Be Repeatable

Decision: migrate valuable WordPress content through repeatable import commands instead of manual copy-paste.

Reason:

- posts, pages, media, and redirects need to be tested before launch;
- legacy IDs make imports traceable and idempotent;
- staging imports can be repeated while the old site remains live;
- SEO redirects are easier to verify when generated from explicit mappings.

Preferred approach:

- use the WordPress REST API or XML export as the primary source;
- map posts to `Article`;
- map selected pages to concrete domain models;
- import referenced media into `MediaAsset`;
- maintain a redirect map from old WordPress URLs to new Laravel routes.

## 2026-07-04: English-Default Bilingual Content

Decision: support English and Dutch content, with English as the application default and Dutch as a supported locale from the start.

Reason:

- DDS is Netherlands-oriented and the current public website is primarily Dutch;
- international pilots and partners may still benefit from English content;
- code, routes, database columns, and repository documentation stay English for maintainability;
- content models and SEO should be locale-aware from the start to avoid a painful retrofit later.

Implementation direction:

- default locale `en`;
- supported locales `en` and `nl`;
- public route names remain English-based for now;
- content fields should support translations or locale-specific records;
- full Dutch content parity is supported incrementally and does not require locale-prefixed routes in phase 1.

## 2026-07-07: No Locale Prefixes In Phase 1

Decision: do not introduce locale-prefixed public routes such as `/nl/events` or `/en/events` in the first release.

Reason:

- the platform can support Dutch and English content without complicating routing immediately;
- existing and future URLs stay shorter and easier to redirect from WordPress;
- locale-aware content and SEO can be modeled first, with localized routing added later if needed.

Implementation direction:

- use English route names such as `/events`, `/news`, `/about`, and `/contact`;
- default public content locale is `en`;
- optionally expose language selection through content rendering, not separate route trees, in phase 1;
- keep the architecture open for localized routes later.

## 2026-07-07: Admin UI Starts In English

Decision: the admin interface starts in English.

Reason:

- code, models, routes, and repository documentation are English;
- admin users are expected to be close to the project and can work with English labels;
- this avoids translating internal tooling before the public experience is stable.

Implementation direction:

- admin navigation, forms, validation labels, and empty states can start in English;
- public content remains English-default and translatable to Dutch;
- content locale should be explicit in admin forms.

## 2026-07-07: Scaffold Before WordPress Import

Decision: scaffold the Laravel/DDEV foundation and core domain model before building the WordPress import tooling.

Reason:

- import commands need real target models and fields;
- redirects are easier to verify once public route names exist;
- the first technical risk is application foundation, not content import;
- import can be developed as a controlled spike after `Event`, `Article`, `Location`, `Partner`, and `MediaAsset` shapes are clearer.

Implementation direction:

- build Laravel, DDEV, PostgreSQL, auth, layouts, locale config, and admin shell first;
- create the `Event` domain before importing training/event-like content;
- run a WordPress REST/XML import spike after the target models exist.

## 2026-07-08: Use Spatie Permission For Admin Roles

Decision: use `spatie/laravel-permission` for admin/editor roles and permission checks instead of building a custom role system.

Reason:

- DDS needs a small but real admin authorization model;
- `admin` and `editor` roles are enough for phase 1, but permissions may become more granular later;
- Spatie Permission is the Laravel standard for this use case;
- it avoids a custom boolean-only approach that would likely be replaced later.

Implementation direction:

- install `spatie/laravel-permission` during `DDS-004`;
- create `admin` and `editor` roles;
- use CRUD- and workflow-oriented permissions per domain for concrete actions;
- protect the starter `/dashboard` route through `admin` and `editor` roles, then protect concrete actions through permissions;
- provide a repeatable Artisan command for creating or promoting the first admin user.

## 2026-07-08: Initial Commit After Validated Scaffold

Decision: create the first GitHub commit after the scaffold, DDEV/PostgreSQL setup, migrations, tests, production build, docs, and Composer metadata are validated.

Reason:

- this creates a clean baseline before domain-specific changes start;
- it makes future diffs around auth, admin, events, and imports easier to review;
- it preserves the project planning docs alongside the generated Laravel foundation.

Commit should include:

- Laravel starter scaffold;
- DDEV configuration;
- PostgreSQL-compatible `.env.example`;
- project documentation;
- composer metadata for DDS Platform.

## 2026-07-08: UX Quality Is Definition Of Done

Decision: UI/UX quality is treated as part of the definition of done for user-facing work, not as a final polish pass.

Reason:

- DDS should feel credible, practical, and carefully designed from the first public release;
- the product owner values UX highly and has UX engineering expertise;
- early layout, interaction, and content hierarchy decisions will shape the whole platform;
- custom admin only pays off if it is genuinely ergonomic for repeated use.

Implementation direction:

- public and admin layouts must be designed intentionally from the first shell;
- tickets involving UI should include responsive behavior, states, accessibility, and content hierarchy;
- admin screens should optimize scanning, repeated workflows, and low-friction editing;
- public screens should optimize visitor intent, clarity, trust, and conversion to the next action;
- generic starter-kit visuals and placeholder copy should be replaced deliberately as soon as pages become product-facing.
