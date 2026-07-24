# Roadmap

## Phase 0: Preparation

Goal: define direction and scope before writing application code.

Deliverables:

- project brief;
- research and site audit;
- product vision;
- information architecture;
- technical architecture;
- local development recommendation;
- initial decision log.

Status: complete. Direction, scope, architecture, local-development guidance, decisions, roadmap, and the implementation backlog are established and maintained as the platform evolves.

Detailed implementation tickets are tracked in [Initial Build Backlog](../backlog/initial-build-backlog.md).

## Phase 1: Technical Foundation

Goal: turn the empty repository into a working Laravel foundation.

Status: complete. The DDEV/Laravel foundation, authentication, roles and permissions, locale runtime, layouts, quality tooling, and GitHub workflows are merged on `main`.

Tasks:

- scaffold Laravel;
- install Laravel starter kit with React, Inertia, TypeScript, and Tailwind;
- configure DDEV;
- set up authentication;
- configure supported locales `en` and `nl`, with `en` as default;
- configure Pest, Pint, type checking, and linting;
- add baseline CI;
- create empty PublicLayout and AdminLayout shells.

Acceptance criteria:

- app runs locally through DDEV;
- login works;
- `/` renders through Inertia;
- `/dashboard` is protected;
- locale configuration is in place;
- tests and linting run locally and in CI.

## Phase 2: Public Website Rebuild

Goal: replace the current WordPress site visually and functionally with static or seeded content.

Status: in progress. The public shell, brand direction, homepage conversion, baseline SEO, and legacy redirects are merged. Temporary homepage content remains server-backed until the corresponding domain models are wired in. Remaining Phase 2 work includes the DDS-007D navigation/accessibility review, secondary public pages, contact flow, and selective migration inventory.

Tasks:

- build homepage;
- build header and footer;
- add upcoming events section with training events highlighted;
- add about/community section;
- add locations section;
- add news preview;
- add partners section;
- add contact page;
- add centrally derived baseline SEO metadata;
- inventory WordPress posts, pages, media, and URLs for migration.

Acceptance criteria:

- key DDS content from the current site exists in the new app;
- mobile and desktop layouts are usable;
- all primary CTAs work;
- no generic placeholder copy remains;
- old WordPress content is inventoried and categorized for migration.

## Phase 3: Admin Foundation

Goal: create a custom management environment as the foundation for all domains.

Status: partially implemented. Authentication, role-gated access, the management shell, dashboard placeholders, flash handling, permissions, and redirect review exist. DDS-011A and DDS-011B still need to turn these into reusable resource-management patterns.

Tasks:

- admin dashboard;
- admin navigation;
- admin page header;
- empty states;
- flash messages;
- confirmation dialogs;
- baseline table/list patterns;
- admin middleware and policies.

Acceptance criteria:

- admin feels like a coherent part of the app;
- management patterns are reusable;
- non-admin users cannot access `/dashboard`;
- CRUD patterns can be applied to the first domain.

## Phase 4: Events As First Domain

Goal: build one domain properly and use it as the pattern for later domains. Regular trainings are modeled as events with `type = training`.

Status: in progress. The `MediaAsset`, `Location`, `Season`, and `Event` schema foundation is implemented in the current working tree. Public Event pages, media selection, validation, policies, and Event/Season admin management remain open.

Tasks:

- Event model and migration;
- database-enforced event type and status support;
- a small `Season` model for optional event grouping and season tickets;
- plain-text event content in the editor's chosen language;
- shared scheduling, pricing, capacity, and registration fields on events;
- public event index and detail page;
- public event filters for training, race, demo, workshop, and other events;
- admin event CRUD;
- form requests;
- policies;
- draft/published/cancelled status;
- cover image;
- centrally derived SEO metadata;
- tests.

Acceptance criteria:

- admins can manage events;
- only published events are publicly visible;
- drafts are protected;
- validation is reliable server-side;
- events have clean slugs and metadata;
- events can contain Dutch or English content without duplicated locale fields;
- training evenings can be managed through event CRUD.

## Phase 5: Event Registrations

Goal: prepare the registration workflow for training events without introducing a separate training model.

Tasks:

- EventRegistration model and migration;
- registration fields for name, callsign, email, FPV Scores UUID, skill level, and video system;
- event fields for price, capacity, deadline, and registration status;
- temporary external or mail-based registration flow;
- tests.

Acceptance criteria:

- upcoming events are visible on the homepage;
- training-type event details answer practical questions;
- registration has a clear workflow, even before payments exist.

## Phase 6: WordPress Content Migration

Goal: migrate valuable WordPress content into the new Laravel domain models.

Note: this phase starts after the Laravel foundation and core target models exist. Import tooling should map into real `Article`, `Event`, `Location`, `Partner`, and `MediaAsset` models instead of shaping those models around raw WordPress exports.

Tasks:

- choose REST API or XML export as the primary import source;
- create import commands for posts, pages, and media;
- map WordPress posts to `Article`;
- map selected pages to `Location`, `Event`, `Partner`, or static content;
- download and register referenced media as `MediaAsset`;
- rewrite internal links and media URLs;
- generate redirect map from old WordPress URLs to new routes;
- run dry-runs in local and staging environments.

Acceptance criteria:

- imported posts are idempotent and traceable through a temporary import manifest during rehearsal and cutover;
- media referenced by migrated content is available in Laravel;
- old important URLs redirect to their new destinations;
- skipped or manually reviewed records are reported clearly;
- import can be repeated without duplicating content.

## Phase 7: News, Locations, And Partners

Goal: make the migrated and newly created content manageable through the custom admin.

Tasks:

- Article CRUD;
- Location CRUD;
- Partner CRUD;
- selectively translated content fields and centrally derived SEO metadata;
- sitemap.

Acceptance criteria:

- news articles are manageable after import;
- locations have their own detail pages;
- partners are manageable and sortable;
- interface strings support English and Dutch, while each content model only translates fields that need parallel variants;
- SEO loss during launch is minimized.

## Phase 8: Contact And Mail

Goal: store contact requests and send application mail reliably.

Tasks:

- contact form;
- ContactSubmission model;
- notification to DDS inbox;
- confirmation email to visitor;
- Mailpit locally;
- Plunk or alternative in production;
- admin overview and status handling.

Acceptance criteria:

- requests are stored;
- visitor receives confirmation;
- admin receives notification;
- spam and privacy risks are addressed.

## Phase 9: Project Showcases

Goal: position DDS better toward partners, demos, and external assignments.

Tasks:

- curated, typed project catalogue maintained in code;
- art-directed public project index and selected detail pages;
- versioned static project media and safe external project links;
- documented pull-request maintenance workflow;
- explicit CMS decision gate based on observed editing frequency, catalogue scale, and non-technical editor needs;
- CTA to contact or request form.

Acceptance criteria:

- DDS can present completed projects;
- cases include visuals, context, and outcome;
- potential clients are guided toward contact.
- the showcase retains custom presentation without requiring a database or dashboard CRUD in phase 1;
- a later CMS is introduced only when concrete maintenance needs justify it.

## Later

- real training registration with capacity;
- payments;
- waitlists;
- automatic reminders;
- newsletter;
- pilot profiles;
- timing/results integrations;

## Recommended First Build Ticket

> Scaffold Laravel with React/Inertia/TypeScript/Tailwind, configure DDEV, create public and admin layout shells, set up authentication and admin middleware, and add baseline CI.
