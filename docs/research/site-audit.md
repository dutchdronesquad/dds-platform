# Research and Site Audit

## Sources

- Dutch Drone Squad: <https://dutchdronesquad.nl>
- Current website research: [current-website-research.md](current-website-research.md)
- Provided brainstorm summary: `/Users/klaas/.codex/attachments/7b3d465d-6970-4748-8a18-67a0d0d472b4/pasted-text.txt`

Reviewed on: 2026-07-04.

## Current Dutch Drone Squad Website

### Strengths

- The core proposition is clear: indoor FPV drone racing training around Alkmaar.
- Important community content already exists: trainings, locations, news, team, partners, rules, and contact.
- There is useful photography that can carry a stronger modern design.
- The news archive has existing SEO and historical value.
- Practical details are available, including training price, locations, timing, and rules.
- The trainings page already contains enough structure to become a full registration workflow later, using events with `type = training`.
- Location pages contain reusable structured data such as address, floor size, height, facilities, and suitability.
- Contact content already separates multiple intents such as training, media, collaboration, sponsorship, and community.

### Weaknesses

- The current website still feels like a filled-in WordPress theme.
- Dutch and English copy are mixed inconsistently; the new platform should make locale ownership explicit.
- Some sections use generic non-profit or donation-oriented language that does not fit DDS.
- Calls to action are not always specific to the user journey.
- Trainings, events, and news behave mostly as pages/posts, not as first-class domain objects.
- Registration still happens outside the platform, mostly through email or external communication.
- The homepage contains generic template sections that should not be migrated.
- Training, location, and rule information is useful but presented as long page content instead of scannable structured data.
- News comments, post view counters, social widgets, and theme markup should be excluded from migration.

### Content To Preserve Or Migrate

- Homepage introduction and core positioning.
- News archive.
- Training days and agenda.
- Location pages:
  - Sportpaleis Alkmaar;
  - Sporthal Koggenhal;
  - Sporthal Oosterhout.
- House rules.
- Team members.
- Partners and sponsors.
- Contact details and social links.
- Existing WordPress media assets, after selection and cleanup.
- Current training schedule and registration requirements.
- News categories and tags as legacy metadata.
- Media mentions from `/media/`.

## Design And Content Direction

- Use strong event and training cards.
- Use clear calls to action such as `View events`, `Register`, and `Contact`.
- Let photography and video carry the visual identity instead of generic decoration.
- Keep navigation compact and focused on participation.
- Give sponsors and partners a clear, professional presentation.
- Make event and training detail pages practical, specific, and persuasive.
- Support Dutch and English content, with Dutch as the default and primary public locale.
- Focus the first release on training, getting started, locations, events, and contact rather than shop, cart, or league functionality.
- Explain how someone can start, what to bring, and what a training evening looks like.
- Surface the next training date and registration state more clearly on the homepage.
- Replace generic impact copy with DDS-specific proof such as years active, training evenings, pilots welcomed, timing support, and supported video systems.

## Conclusion

DDS does not need a generic CMS or a standalone marketing website. It needs a Laravel platform where events, locations, news, partners, and eventually registrations are first-class domain objects. Trainings should be represented as events with `type = training`. Start by replacing the current website, but design the underlying model as a foundation for future platform features.
