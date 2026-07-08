# Local Development

## Decision

DDEV is the local development environment for this project, especially on a MacBook with multiple Laravel projects.

Why:

- consistent PHP, database, and service versions per project;
- fewer global dependencies;
- easy switching between projects;
- good local domain and HTTPS workflow;
- more reproducible than Valet;
- lighter to use day-to-day than a full Sail workflow for every project.

## Comparison

| Option | Strong At | Weaker At | Recommendation |
| --- | --- | --- | --- |
| DDEV | multiple projects, reproducible services, local domains, team consistency | requires Docker | Preferred |
| Valet | fast, simple, Mac-native | global PHP/database differences, less reproducible | Good for quick solo prototypes |
| Sail | Laravel-native Docker, starter-kit friendly | heavier, less convenient across many projects | Good alternative |

## Suggested Local Services

```txt
PHP 8.4 or the current Laravel-recommended version
Node LTS
PostgreSQL
Mailpit
```

The local database should match production, so DDEV should be configured with PostgreSQL.

For the first local phase, Laravel can use database-backed cache, sessions, and queues. Redis should be added later when queue volume, cache behavior, or deployment shape makes it useful.

## First Setup Once Code Starts

Do not run this during the documentation phase. This is the recommended direction for the first implementation task:

1. Initialize a new Laravel project.
2. Install the Laravel starter kit with React, Inertia, TypeScript, and Tailwind.
3. Configure DDEV for Laravel.
4. Configure PostgreSQL.
5. Add Mailpit for local mail testing.
6. Run Pest as the baseline test setup.
7. Add CI for tests, linting, and type checks.

## Development Conventions

- Use feature branches from `main`.
- Keep migrations and models small per domain.
- Add tests for domain logic, policies, form requests, and public visibility rules.
- Render public pages through Inertia, not a loose Blade/React mix unless deliberately chosen.
- Admin components can be more abstract than public components because tables and forms repeat.

## Vite With DDEV

The project exposes Vite through DDEV on `https://dds-platform.ddev.site:5173`.

For active frontend development:

```bash
ddev npm run dev
```

Keep that command running and open the application through the normal DDEV URL:

```txt
https://dds-platform.ddev.site
```

Do not open the raw Vite URL as the application URL. Laravel should serve the app, while Vite serves hot assets.

For a production-style local check:

```bash
ddev npm run build
```

If `public/hot` exists, Laravel will keep using the dev server even after a build. Stop the dev server cleanly, or remove `public/hot` before checking built assets.

## Open Spike Questions

Before the application is built:

- Validate DDEV Laravel configuration.
- Install and review the starter kit.
- Test one public Inertia page and one admin Inertia page.
- Lock down auth flow and admin middleware.
