# Information Architecture

## Proposed Navigation

The final public navigation should be validated in DDS-007D. The DDS-007B implementation deliberately keeps the desktop header compact: the logo links home, `Nieuws`, `Locaties`, `Huisregels`, and `Contact` are direct links, and `Bekijk agenda` is the primary action. `Over DDS`, `Projecten`, `Partners`, and practical links remain available through the mobile menu or footer while the final hierarchy is reviewed.

Current Dutch homepage navigation language:

- Home
- Agenda
- Projecten
- Nieuws
- Over DDS
- Contact

When the multilingual public content layer is active, labels follow the selected locale. The English-default equivalents are `Home`, `Events`, `Projects`, `News`, `About`, and `Contact`.

Secondary or footer navigation:

- Locations
- House Rules
- Partners
- In The Media
- Results
- Privacy

Recommended public label policy:

- use English as the default public locale and support Dutch at the content-model level;
- keep code identifiers, database columns, route names, and repository documentation in English;
- allow the current landing page to remain temporarily Dutch while no multilingual public content mechanism is in use;
- use `Agenda` for the current Dutch main action and `Events` for the English equivalent, while `event` remains the generic content term;
- allow a concise English brand line such as `Where racing brings pilots together.` inside the temporary Dutch landing page;
- use the Event domain for trainings, races, demos, workshops, and community activities;
- treat trainings as an event type/filter, not as a separate public section.

## Language Strategy

The platform should support English and Dutch content. English remains the application and public-content default; Dutch translations can be added gradually. Until the multilingual public content layer and language switcher are in use, the current landing page is intentionally kept in Dutch as a temporary exception.

Implementation principles:

- code identifiers, model names, route names, database columns, and repository docs use English;
- public content has `en` as the default locale;
- public content should be translatable to `nl`;
- admin UI can start in English, but content fields should make locale explicit;
- SEO metadata should be locale-aware;
- URLs should stay stable and English-based without locale prefixes for the first release.

Suggested locale handling:

```txt
Default locale: en
Supported locales: en, nl
Code and routes: English
Primary content: English
Optional translated content: Dutch
Temporary landing page content: Dutch until multilingual public content is active
Localized URL prefixes: not in phase 1
```

## Public Routes

```txt
/
/events
/events/{slug}
/events?type=training
/events?type=race
/projects
/projects/{slug}
/news
/news/{slug}
/locations
/locations/{slug}
/about
/house-rules
/partners
/contact
```

Legacy URLs that need redirects:

```txt
/trainingen/ -> /events?type=training
/agenda/ -> /events
/sportpaleis/ -> /locations/sportpaleis-alkmaar
/koggenhal/ -> /locations/sporthal-koggenhal
/oosterhout/ -> /locations/sporthal-oosterhout
/huisregels/ -> /house-rules
/media/ -> /media or /news?category=media
/nieuws/ -> /news
```

## Admin Routes

```txt
/dashboard
/dashboard/events
/dashboard/events/create
/dashboard/events/{event}/edit
/dashboard/articles
/dashboard/articles/create
/dashboard/articles/{article}/edit
/dashboard/projects
/dashboard/projects/create
/dashboard/projects/{project}/edit
/dashboard/locations
/dashboard/partners
/dashboard/media
/dashboard/contact-submissions
```

## Homepage Structure

Current DDS-007B order:

1. Hero with DDS positioning, real photography, and an agenda CTA.
2. Experienced-pilot introduction and participation requirements.
3. Sportpaleis proof, track scale, timing context, and season context.
4. Upcoming events, with the next three shown when data is available.
5. Beginner path, clearly separated from the experienced Sportpaleis events.
6. Concise About DDS introduction.
7. Latest news.
8. Final agenda CTA.

The structure should stay modular. The future Event and Article domains replace the current fallbacks without redesigning the page. A featured project or partner/demo cue can be added later when it supports a clear visitor action. Team details belong on the About page, and inactive or fallback locations should not be promoted as equal homepage destinations.

## Event Detail Page

Goal: provide practical information about a training, race, demo, workshop, or special activity.

Implementation note: a training is an `Event` with `type = training`. Public pages should use the event routes and can filter/highlight training events where useful.

Core content:

- title;
- date and time;
- location;
- price;
- skill level;
- available spots, later;
- description;
- what to bring;
- rules and safety;
- registration CTA;
- contact option for questions.

Future registration-specific content:

- registration deadline;
- capacity;
- waitlist state;
- season pass priority;
- required FPV Scores UUID;
- required video system;
- skill level;
- help-with-build preference;
- house rules acceptance.

## Project Detail Page

Goal: show what DDS can organize or has delivered.

Core content:

- client, partner, or context;
- challenge;
- DDS approach;
- race, demo, or workshop format;
- technical setup;
- photos or video;
- result;
- CTA for a similar request.

## Content Models

### Event

For trainings, competitions, demos, special activities, and agenda items.

Fields:

- title;
- slug;
- excerpt;
- content;
- starts_at;
- ends_at;
- location_id or temporary location fields;
- cover_image_id;
- status;
- registration_url;
- type;
- category;
- price_cents;
- capacity;
- registration_deadline_at;
- registration_status;
- skill_level;
- video_system_notes;
- seo_title;
- seo_description.

Suggested `type` values:

- training;
- race;
- demo;
- workshop;
- community;
- other.

Suggested `registration_status` values:

- closed;
- open;
- waitlist;
- full;
- external.

### Article

For news, updates, and media posts.

Fields:

- title;
- slug;
- excerpt;
- content;
- cover_image_id;
- published_at;
- status;
- author_id;
- category;
- seo_title;
- seo_description.

### Project

For showcases, demos, workshops, and external assignments.

Fields:

- title;
- slug;
- client_name;
- excerpt;
- content;
- date;
- cover_image_id;
- gallery_media_ids;
- status;
- sort_order;
- seo_title;
- seo_description.

### Location

For recurring training and event locations.

Fields:

- name;
- slug;
- description;
- address;
- city;
- floor_size;
- height;
- parking_info;
- cover_image_id;
- map_url;
- status.
- role;
- facilities;
- suitability;
- operational_notes.

### EventRegistration

For later online registration workflows.

Fields:

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

### MediaMention

Optional later model for press and external media references.

Fields:

- title;
- slug;
- publisher;
- published_at;
- external_url;
- media_type;
- description;
- cover_image_id;
- sort_order;
- status.

### Partner

For partners and sponsors.

Fields:

- name;
- slug;
- description;
- logo_media_id;
- website_url;
- type;
- tier;
- sort_order;
- is_visible.

### ContactSubmission

For contact requests.

Fields:

- name;
- email;
- phone;
- subject;
- message;
- source_page;
- status;
- handled_at.
