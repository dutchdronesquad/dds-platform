# DDS Platform

The Dutch Drone Squad website and administration platform, built with Laravel, Inertia, React, TypeScript, and Tailwind CSS.

The application is developed locally with DDEV and PostgreSQL. Public pages, authentication, role-based administration, baseline SEO metadata, and database-backed legacy redirects are in place; the remaining domains are developed incrementally from the product backlog.

## Documentation

- [Project Brief](docs/project-brief.md)
- [Research and Site Audit](docs/research/site-audit.md)
- [Current Website Research](docs/research/current-website-research.md)
- [Product Vision and Scope](docs/product/vision-and-scope.md)
- [Information Architecture](docs/product/information-architecture.md)
- [Technical Architecture](docs/technical/architecture.md)
- [Local Development](docs/technical/local-development.md)
- [WordPress Migration](docs/technical/wordpress-migration.md)
- [Roadmap](docs/roadmap/roadmap.md)
- [Initial Build Backlog](docs/backlog/initial-build-backlog.md)
- [Decision Log](docs/decisions/README.md)

## Direction

Dutch Drone Squad should be rebuilt as a modern Laravel monolith with React, Inertia, TypeScript, and Tailwind. The first release should replace the current WordPress website while laying the foundation for future capabilities such as project showcases, training registrations, event management, news, partners, and eventually payments or member features.

The recommended approach is iterative: first rebuild a strong public website and custom admin, then expand domain by domain.
