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

Status: started with this documentation set.

Detailed implementation tickets are tracked in [Initial Build Backlog](../backlog/initial-build-backlog.md).

## Phase 1: Technical Foundation

Goal: turn the empty repository into a working Laravel foundation.

Tasks:

- scaffold Laravel;
- install Laravel starter kit with React, Inertia, TypeScript, and Tailwind;
- configure DDEV;
- set up authentication;
- configure supported locales `nl` and `en`, with `nl` as default;
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

Status: in progress. DDS-007B establishes the public brand direction and implements the redesigned homepage, responsive public header and footer, experienced-pilot and beginner paths, About teaser, upcoming-events preview, and news preview. The event cards currently use a mock fallback and news can use legacy fallback content until the corresponding domain models exist. Remaining Phase 2 work includes the final navigation review, secondary public pages, projects and partner positioning, contact flow, SEO metadata, and migration inventory.

Tasks:

- build homepage;
- build header and footer;
- add upcoming events section with training events highlighted;
- add about/community section;
- add locations section;
- add news preview;
- add partners section;
- add contact page;
- add baseline locale-aware SEO metadata;
- inventory WordPress posts, pages, media, and URLs for migration.

Acceptance criteria:

- key DDS content from the current site exists in the new app;
- mobile and desktop layouts are usable;
- all primary CTAs work;
- no generic placeholder copy remains;
- old WordPress content is inventoried and categorized for migration.

## Phase 3: Admin Foundation

Goal: create a custom management environment as the foundation for all domains.

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

Tasks:

- Event model and migration;
- event type/category support;
- locale-aware event content fields;
- training-specific fields on events;
- public event index and detail page;
- public event filters for training, race, demo, workshop, and community events;
- admin event CRUD;
- form requests;
- policies;
- draft/published/archived status;
- cover image;
- SEO fields;
- tests.

Acceptance criteria:

- admins can manage events;
- only published events are publicly visible;
- drafts are protected;
- validation is reliable server-side;
- events have clean slugs and metadata;
- events support Dutch content and optional English translations;
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

- imported posts are idempotent and traceable through legacy IDs;
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
- locale-aware content fields and SEO metadata;
- sitemap.

Acceptance criteria:

- news articles are manageable after import;
- locations have their own detail pages;
- partners are manageable and sortable;
- Dutch is the default content locale and English translations can be added;
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

- Project model;
- public project index and detail page;
- admin CRUD;
- gallery/media support;
- CTA to contact or request form.

Acceptance criteria:

- DDS can present completed projects;
- cases include visuals, context, and outcome;
- potential clients are guided toward contact.

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
