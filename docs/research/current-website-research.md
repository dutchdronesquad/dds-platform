# Current Website Research

## Scope

Reviewed source: <https://dutchdronesquad.nl>

Pages reviewed on 2026-07-04:

- `/`
- `/trainingen/`
- `/nieuws/`
- `/sportpaleis/`
- `/koggenhal/`
- `/oosterhout/`
- `/huisregels/`
- `/contact/`
- `/media/`

## Executive Summary

The current website contains more useful product structure than it first appears. Several WordPress pages already behave like domain objects:

- `/trainingen/` is effectively a season schedule, ticketing explanation, registration process, and timing guide.
- location pages contain structured venue data, facilities, address information, and suitability notes.
- news posts preserve season history, race results, announcements, categories, tags, and SEO value.
- `/media/` is a lightweight media mention archive.
- `/huisregels/` is operational policy content that should stay visible and versioned.

The new platform should not treat the old site as generic page content. It should extract the useful structure and model it directly.

## Navigation Findings

Current primary navigation:

- Home
- News
- In the media
- Locations
- Trainingsdagen
- House rules
- Contact
- Agenda CTA

Issues:

- The information architecture mixes pages, post archives, and calls to action.
- `Trainingsdagen` links to `/trainingen/`, while the footer labels it as `Vliegagenda`.
- The `Agenda` CTA appears prominently but currently overlaps conceptually with trainings.
- Locations are nested under information, but they are important enough to have a clearer top-level or secondary route.
- The public labels mix Dutch and English without an explicit locale strategy.

Recommendations:

- Use `Events` as the primary dated-activity section.
- Treat regular flight evenings as event records with `type = training`.
- Keep `Locaties` as a clear secondary navigation item.
- Support Dutch and English content explicitly, with Dutch as the default public locale and English as an optional translation layer.

## Homepage Findings

What works:

- The hero proposition is direct: become a drone racer.
- The page already communicates price, recurring trainings, and lap timing.
- DDS history and PDRNL involvement are valuable credibility signals.
- The homepage has strong raw material: community, training, demos, news, locations, team, partners, and media.

Problems:

- Several sections still contain generic template copy that does not fit DDS.
- Generic CTA labels such as `See Proof`, `View Financials`, and donation-style copy should be removed.
- The page tries to cover too many unrelated sections with weak hierarchy.
- The news and locations sections are useful, but they need stronger cards and clearer actions.
- The team section is valuable, but should not interrupt the core training conversion path too early.

Recommendations:

- Make the first screen about the next concrete action: view upcoming events or register interest.
- Promote the next training date directly on the homepage.
- Replace generic impact/donation sections with DDS-specific proof:
  - years active;
  - number of training evenings;
  - pilots welcomed;
  - supported video systems;
  - timing and livestream capability.
- Use team, history, and partners as credibility sections after the main training and location content.

## Trainings Page Findings

The `/trainingen/` page is the strongest candidate for the first real platform workflow.

Current content includes:

- season schedule;
- location per date;
- start and end times;
- last updated date;
- capacity range of 12 to 16 pilots;
- single evening ticket price;
- season pass price;
- registration deadline;
- season card priority logic;
- waitlist-like behavior when full;
- required registration fields;
- payment confirmation flow;
- reminder email flow;
- timing setup instructions;
- FPV Scores UUID requirement;
- VTX type and level information.

Implications for the new platform:

- Trainings should be first-class records, but they can be modeled as `Event` records with `type = training` instead of a separate `Training` model.
- Registration should become a real workflow later, but phase 1 can still use email/external payment while storing the structure.
- Training dates need capacity, registration deadline, registration status, and possibly check-in state.
- Required registration data should be modeled early even if the first version only sends email.

Recommended `EventRegistration` fields for later:

- event_id;
- full_name;
- callsign;
- email;
- fpv_scores_uuid;
- skill_level;
- video_system;
- can_help_build;
- has_season_pass;
- payment_status;
- registration_status;
- notes;
- confirmed_at;
- cancelled_at.

## Location Page Findings

Current location pages contain structured and reusable data.

### Sportpaleis Alkmaar

Useful data:

- indoor training location;
- address: Terborchlaan 200, 1816 LE Alkmaar;
- floor area over 2000m2;
- maximum height 11m;
- timing through race timers and FPV Scores;
- regular flight time 18:00 to 21:00 on Sunday evenings;
- facilities: power, tables, chairs, toilets, safety nets, free parking;
- suitable for all levels, with a preference that pilots can fly a track.

### Sporthal Koggenhal

Useful data:

- address: Dwingel 4, 1648 JM De Goorn;
- floor area 45 x 30m, 1350m2;
- height 7 to 9m;
- fixed blacklight installation;
- specifically useful for beginners or slower obstacle practice;
- only used when there is enough interest;
- facilities: power, benches, toilets, free parking.

### Sporthal Oosterhout

Useful data:

- address: Vondelstraat 35, 1813 AA Alkmaar;
- floor area 24 x 42m, 1000m2;
- height 7 to 9m;
- fallback location when Sportpaleis is unavailable;
- timing through race timers and FPV Scores;
- facilities: power, toilets, free parking, but no tables/chairs.

Implications:

- `Location` should include size, height, facilities, skill suitability, parking notes, and operational notes.
- A location can have a role: primary, beginner, fallback, event-only.
- Public location pages should be more structured and scannable than long prose.

## News Findings

The news archive has real historical value.

Observed categories:

- Geen categorie;
- Klassement;
- Nieuws;
- Race Track;
- Sportpaleis;
- Wedstrijden.

Observed tags include:

- Alkmaar;
- bbq;
- corona;
- dds;
- drones;
- fly to meat you;
- gezelligheid;
- indoor;
- jubileum;
- prijzen;
- race;
- seizoen;
- Sportpaleis;
- time;
- update;
- wedstrijd.

Content types inside posts:

- season announcements;
- training updates;
- BBQ/community events;
- anniversary updates;
- race result posts;
- race announcements;
- pandemic/status updates;
- track-related posts.

Recommendations:

- Import posts as `Article`.
- Preserve categories and tags at least as imported metadata, even if the first UI only exposes category.
- Consider a `type` or `category` distinction between:
  - news;
  - race report;
  - announcement;
  - community;
  - media mention.
- Disable public comments in the new platform unless there is a clear moderation plan.
- Keep old post URLs redirecting to the new article URLs.

## House Rules Findings

The house rules page is operationally important and should remain easy to find from:

- training detail pages;
- registration flow;
- footer;
- confirmation emails.

The current page contains Dutch and English versions. The new platform should support both locales explicitly because international pilots visit DDS, while Dutch remains the default public language.

Recommendations:

- Treat house rules as managed policy content with a `last_updated_at` field.
- Link to house rules during registration and require explicit acceptance later.
- Keep a changelog or version marker once online registrations are introduced.

## Contact Page Findings

The contact page already separates several intents:

- general questions;
- media and press;
- collaboration and sponsoring;
- WhatsApp;
- Discord;
- social channels.

Recommendations:

- The new contact form should include a `topic` field:
  - training;
  - registration;
  - collaboration;
  - sponsorship;
  - media;
  - general.
- Contact submissions should store the source page and topic.
- The admin should support statuses such as `new`, `read`, `replied`, and `archived`.
- Social/community links should be available in the footer and contact page, but registration should not depend only on WhatsApp or Discord.

## Media Mentions Findings

The `/media/` page is a simple chronological archive of press and external mentions from 2017 onward.

Recommendations:

- Keep this content, but model it separately from regular news if it grows.
- Minimal first version: import as articles with a `media` category or create a static `In The Media` page.
- Better later version: `MediaMention` model with date, title, publisher, URL, media type, and optional image.

## Migration Lessons

The current site suggests these import priorities:

1. Posts and their featured images.
2. Location pages.
3. Training page content and current season schedule.
4. House rules.
5. Media mentions.
6. Partners and sponsors.
7. Team content.

The importer should avoid blindly carrying over:

- generic template sections;
- post comments;
- theme-specific button markup;
- duplicated navigation/footer content;
- inconsistent English/Dutch labels;
- social sharing widgets;
- post view counters.

## Platform Opportunities

The new platform can improve the current site most by making these concepts explicit:

- a training season with multiple training dates;
- individual training registrations;
- locations with facilities and suitability;
- articles with clear types and categories;
- media mentions;
- partner/sponsor tiers;
- project showcases;
- contact topics and admin follow-up.

## Open Questions

- Which event filters should be visible from day one: training, race, demo, workshop, community?
- Should the first release already support season passes as a concept?
- Should FPV Scores UUID be required for all registrations or optional until timing is used?
- Should house rules be bilingual in the first release?
- Should old comments be ignored completely or archived privately?
- Should media mentions become their own model or remain a page/category initially?
