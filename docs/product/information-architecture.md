# Information Architecture

## Proposed Navigation

The DDS-007D public hierarchy keeps the desktop header focused: the logo links home, followed by `Projecten`, `Nieuws`, `Over DDS`, `Locaties`, and `Contact`, with `Bekijk agenda` as the primary action. `Huisregels` is a practical footer-only link, while `Partners` is available in the footer and represented by verified partner logos on the homepage. Login and admin access are private operational entry points and are therefore not linked from the public shell; their direct routes remain available to authorized users.

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

- use English as the default public locale and support Dutch in the interface and selected content fields;
- keep code identifiers, database columns, route names, and repository documentation in English;
- allow the current landing page to remain temporarily Dutch while no multilingual public content mechanism is in use;
- use `Agenda` for the current Dutch main action and `Events` for the English equivalent, while `event` remains the generic content term;
- allow a concise English brand line such as `Where racing brings pilots together.` inside the temporary Dutch landing page;
- use the Event domain for trainings, races, demos, workshops, and other dated activities;
- treat trainings as an event type/filter, not as a separate public section.

## Language Strategy

The platform supports English and Dutch interface strings. Editor-authored content does not have to be duplicated: an event can be written in either language, while stable fields such as a location description or media alt text can use locale-keyed JSONB when parallel translations are useful.

Implementation principles:

- code identifiers, model names, route names, database columns, and repository docs use English;
- the public interface has `en` as its default locale and supports `nl`;
- UI labels live in translation files;
- only deliberately translatable content fields expose locale-specific inputs;
- those locale maps require an English base value by default and may add Dutch as an optional translation;
- media alt text is an optional reusable default in any supported locale; the rendering context decides whether to use descriptive text or an empty `alt` attribute;
- event title and content remain plain text in the language chosen by the editor;
- SEO metadata is derived from model content, routes, publication state, and cover media;
- URLs should stay stable and English-based without locale prefixes for the first release.

Suggested locale handling:

```txt
Default locale: en
Supported locales: en, nl
Code and routes: English
Editor-authored event content: Dutch or English
Selected translated fields: locale-keyed JSONB
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

Current homepage order:

1. Hero with DDS positioning, real photography, and an agenda CTA.
2. Experienced-pilot introduction and participation requirements.
3. Sportpaleis proof, track scale, timing context, and season context.
4. Upcoming events, with the next three shown when data is available.
5. Beginner path, clearly separated from the experienced Sportpaleis events.
6. Concise About DDS introduction.
7. Latest news.
8. Compact row with verified partner logos from backend content.
9. Final agenda CTA.

The structure stays modular. Projects remain directly accessible through the primary navigation and footer; a project only earns homepage space when a real case supports a clear visitor action. Temporary homepage modules are delivered from backend configuration so the future Event, Article, Partner, and managed-content domains can replace them without redesigning the page. Team details belong on the About page, and inactive or fallback locations should not be promoted as equal homepage destinations.

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

### Shared SEO Contract

Public pages derive SEO metadata consistently instead of adding SEO columns to every model:

- the title comes from the public title or name;
- the description is derived from the content or description;
- canonical URLs are derived from the named public route and stable slug rather than stored separately;
- robots directives are derived from publication status or visibility;
- Open Graph images use the content cover or logo media, with the platform default as fallback.

This contract applies to events, articles, projects, locations, partners, and managed public pages. Models do not store `seo_title` or `seo_description` fields unless a concrete future requirement proves that an override is necessary.

### Event

For trainings, competitions, demos, special activities, and agenda items.

Fields:

- title;
- slug;
- content;
- starts_at;
- ends_at;
- location_id;
- season_id;
- cover_image_id;
- published_at;
- status;
- registration_url;
- type;
- price_cents;
- capacity;
- registration_opens_at;
- registration_deadline_at;
- registration_status;

Suggested `type` values:

- training;
- race;
- demo;
- workshop;
- other.

Suggested `registration_status` values:

- closed;
- open;
- waitlist;
- full.

Suggested `status` values:

- draft;
- published;
- cancelled.

### Season

For grouping related events and optionally selling a season ticket.

Fields:

- name;
- price_cents;
- ticket_capacity.

Season dates are derived from the first and last linked events. Event capacity remains independent from the number of season tickets.

### MediaAsset

For reusable images and PDFs.

Fields:

- disk;
- path;
- original_filename;
- mime_type;
- size_bytes;
- width;
- height;
- optional alt_text defaults keyed by locale.

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

### Location

For recurring training and event locations.

Fields:

- name;
- slug;
- description;
- street;
- house_number;
- postal_code;
- city;
- country_code;
- environment;
- floor_size_square_metres;
- ceiling_height_metres;
- cover_image_id;
- facilities;
- website_url;
- latitude;
- longitude.

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
- is_visible;

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
