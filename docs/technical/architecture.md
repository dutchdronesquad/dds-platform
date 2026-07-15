# Technical Architecture

## Architecture Decision

Recommended architecture: Laravel modern monolith.

```txt
Laravel app
├─ public website
├─ custom admin
├─ Eloquent domain models
├─ queues, mail, and scheduler
├─ React/Inertia views
└─ Vite asset build
```

This fits DDS because the first need is a maintainable content and event platform, while the future roadmap can grow into registrations, reminders, showcases, payments, and integrations.

## Why Not A Separate Frontend

A separate Next.js frontend with a Laravel API would add complexity before DDS needs it:

- two deployments;
- duplicated routing concepts;
- more auth complexity;
- more overhead for admin forms;
- more maintenance for a small team.

Inertia provides a modern React experience while keeping routing, policies, validation, sessions, and redirects close to Laravel.

## Why No Filament In Phase 1

Filament is strong for fast admin panels, but a custom React/Inertia admin is a better fit if:

- the public and admin interfaces should feel related;
- forms and workflows will become DDS-specific;
- event and training registrations will need custom interactions;
- the project should avoid being shaped too much by resource abstractions;
- React/Inertia is already the frontend direction.

This costs more work initially, but gives more product freedom.

## Stack

Backend:

- Laravel;
- Eloquent;
- Form Requests;
- Policies;
- Jobs;
- Queues;
- Notifications and Mail;
- Scheduler;
- Pest;
- Spatie Permission once roles become more than a single admin check.

Frontend:

- React;
- TypeScript;
- Inertia;
- Tailwind;
- shadcn/ui selectively for admin and form components;
- TanStack Table once admin tables need filtering and sorting;
- Inertia forms or React Hook Form depending on form complexity.

Database:

- PostgreSQL.
- deliberately translated content uses locale-keyed JSONB with English required as the base value and Dutch optional by default;
- media alt text is an optional domain-specific exception that may use any supported locale;
- ordinary editor-authored fields, such as Event title and content, remain plain text.

Cache and queues:

- Redis for production;
- database-backed cache, sessions, and queues are acceptable for early local development;
- Redis should be introduced before production or once background jobs/reminders become important.

Mail:

- existing mailboxes remain in place;
- application mail should use a dedicated subdomain such as `mail.dutchdronesquad.nl`;
- Plunk is suitable for transactional mail and possible future newsletter flows.

## Suggested Code Organization

```txt
app/
├─ Http/
│  ├─ Controllers/
│  │  ├─ Public/
│  │  └─ Admin/
│  ├─ Requests/
│  └─ Middleware/
├─ Models/
├─ Policies/
├─ Actions/
├─ Mail/
├─ Notifications/
└─ Jobs/

resources/js/
├─ pages/
│  ├─ public/
│  ├─ admin/
│  └─ auth/
├─ layouts/
├─ components/
│  ├─ public/
│  ├─ admin/
│  ├─ cards/
│  ├─ forms/
│  └─ ui/
└─ types/
```

## Admin Authorization

Start simple:

- `User` model with Laravel starter kit authentication;
- role middleware for the starter `/dashboard` route;
- policies per model.

Later:

- Spatie Permission;
- roles `admin` and `editor`;
- optional granular permissions per domain.

## Media

For phase 1, a simple custom media approach is enough:

- uploads through the admin;
- `MediaAsset` model;
- disk, path, original filename, mime type, byte size, optional image dimensions, and optional locale-aware alt text defaults;
- reusable records for images and PDFs without WordPress-specific runtime fields;
- conversions or thumbnails later through Spatie Media Library or custom queued jobs.

Important: every rendered image receives an `alt` attribute. The rendering context chooses a descriptive value, optionally starting from the asset default, or `alt=""` when that specific use is decorative.

## SEO

Minimum SEO baseline:

- clean slugs;
- unique title and description;
- Open Graph image;
- canonical URL;
- sitemap;
- robots.txt;
- redirects from old WordPress URLs;
- a repeatable WordPress import path for posts, pages, media, and redirects;
- structured data for events once event details are stable.

## Deployment

Target shape:

```txt
app container / PHP runtime
webserver
PostgreSQL
Redis
queue worker
scheduler
storage volume
Vite build artifacts
```

Candidates:

- Laravel Cloud;
- Vito v4;
- Dokploy;
- self-managed VPS with Docker Compose.

For DDS, predictable operations matter more than maximum flexibility. Choose hosting after a small technical spike, with PostgreSQL support as a baseline requirement.

## Later Infrastructure Follow-Ups

- Add Redis and decide which stores use it: cache, sessions, queues, or all three.
- Add a queue worker process to the deployment target.
- Add Laravel scheduler execution to the deployment target.
- Decide whether Horizon is worth adding once Redis queues are used.
- Configure production mail provider and DNS records for the application mail subdomain.
- Define storage backup and retention strategy for uploads and imported WordPress media.

## Main Technical Risks

- Building a generic page builder too early.
- Underestimating training registrations, especially capacity, cancellation, mail, and privacy.
- Migrating WordPress media without cleanup.
- Repeating admin form logic instead of creating shared patterns.
- Forgetting SEO redirects during launch.
- Treating WordPress import as a one-off manual copy instead of a repeatable migration.
- Trying to ship too many platform features in the first release.
