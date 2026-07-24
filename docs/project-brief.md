# Project Brief

## Context

Dutch Drone Squad currently runs on WordPress. The existing website contains the most important information, but the platform is limited for future ambitions such as showcasing projects, managing training registrations, publishing richer event content, and creating a more polished public presence.

The new website should remain clearly recognizable as Dutch Drone Squad: local, community-oriented, practical for pilots, and focused on training, participation, and FPV drone racing around Alkmaar.

## Goal

Rebuild dutchdronesquad.nl as a modern Laravel platform that:

- keeps the current DDS identity recognizable;
- improves the public website and mobile experience;
- provides a custom admin instead of relying on WordPress;
- makes events, locations, news, and contact requests manageable, keeps small project and partner catalogues safely maintainable through code review, and represents trainings as event records;
- creates a technical foundation for registrations, reminders, payments, and integrations later.

## Non-Goals For This Phase

- Do not build application code yet.
- Do not scaffold Laravel yet.
- Do not create final UI designs.
- Do not implement database migrations, React components, or controllers.
- Do not migrate WordPress content yet.

## Recommended Product Path

1. Rebuild the public website.
2. Add authentication and a custom admin area.
3. Build the event domain properly first, with trainings as the first important event type.
4. Repeat the model-backed pattern for news, locations, and contact submissions, while partners remain a typed code-owned catalogue until their CMS decision gate is reached.
5. Add registrations, reminders, project showcases, and payments in later phases.
