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

| Option | Strong At                                                                 | Weaker At                                          | Recommendation                 |
| ------ | ------------------------------------------------------------------------- | -------------------------------------------------- | ------------------------------ |
| DDEV   | multiple projects, reproducible services, local domains, team consistency | requires Docker                                    | Preferred                      |
| Valet  | fast, simple, Mac-native                                                  | global PHP/database differences, less reproducible | Good for quick solo prototypes |
| Sail   | Laravel-native Docker, starter-kit friendly                               | heavier, less convenient across many projects      | Good alternative               |

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
- Run PHP, Artisan, Composer, and Node commands through DDEV on the development MacBook, for example `ddev artisan test`, `ddev composer install`, and `ddev npm run build`.
- Run repository tooling such as `git` and `gh` directly on the host because it operates on the local checkout rather than inside the application container.
- Keep migrations and models small per domain.
- Add tests for domain logic, policies, form requests, and public visibility rules.
- Render public pages through Inertia, not a loose Blade/React mix unless deliberately chosen.
- Admin components can be more abstract than public components because tables and forms repeat.

## Testing

The test suite uses Pest and is divided by responsibility:

- `tests/Unit` contains isolated tests for small pieces of logic. These tests do not boot Laravel or use the database.
- `tests/Feature` contains domain, database, authorization, command, middleware, and HTTP/Inertia behavior. Laravel is booted and the database is refreshed for every test.
- `tests/Browser` contains behavior that requires a real Chromium browser, such as navigation, filtering, responsive layout, accessibility state, and JavaScript errors.

Choose the lowest test layer that proves the behavior with sufficient confidence. Every test should protect an application contract or a concrete regression risk. Do not add tests that only mirror configuration arrays, factory defaults, source code strings, CSS classes, framework behavior, or implementation details.

Factories may prepare fixtures, but should not be used as the expected result. Prefer explicit inputs and assertions against observable output, persisted state, emitted events, redirects, or response contracts. Avoid covering the same contract at multiple layers unless each layer protects a different risk.

Run all Unit and Feature tests:

```bash
ddev artisan test --compact
```

Run one test file or a filtered test:

```bash
ddev artisan test --compact tests/Feature/PublicEventPagesTest.php
ddev artisan test --compact --filter="unpublished events are not public"
```

Run the Chromium browser suite:

```bash
ddev composer test:browser
```

Install Chromium after the first checkout or a Playwright version change:

```bash
ddev npm run browser:install
```

The required Linux browser dependencies and persistent Playwright cache are configured through DDEV, so normal restarts and rebuilds do not require manual system-package installation.

CI is the source of truth for coverage. It runs Unit and Feature tests on PHP 8.4 and PHP 8.5, enforces at least 95% application coverage, and runs the Pest Browser suite separately on PHP 8.4. Developers normally only need to run the relevant local test command; enabling Xdebug and collecting coverage locally is optional and only useful when investigating a coverage regression.

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

## Representative Event Data

Create or refresh the deterministic public event dataset locally with:

```bash
ddev artisan dds:seed-demo-events
```

The command updates the same reserved demo records on every run. It includes public races and trainings with open, waitlist, full, closed, and cancelled states, plus varied pricing, cover images, and optional content.

Remove only these demo events and their now-unused supporting records with:

```bash
ddev artisan dds:seed-demo-events --reset
```

Both operations refuse to run in production. Production database seeding does not call the demo-event seeder.

## Open Spike Questions

Before the application is built:

- Validate DDEV Laravel configuration.
- Install and review the starter kit.
- Test one public Inertia page and one admin Inertia page.
- Lock down auth flow and admin middleware.
