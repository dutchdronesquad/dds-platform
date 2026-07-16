# Getting Started Knowledge Hub

## Decision

DDS should add a curated public knowledge hub at `/getting-started`. Its purpose is to help new and returning pilots understand FPV, prepare for a first DDS event, and find the right next step without having to reconstruct that information from event descriptions or the legacy website.

The public name can be localized:

- English: `Getting Started`;
- Dutch page title: `Beginnen met FPV`;
- Dutch compact navigation label: `Beginnen`.

The route stays English and has no locale prefix, in line with the platform URL policy. Although the hub may be described informally as a wiki, it is curated DDS content rather than an open, community-editable wiki.

The legacy `/trainingen/` route keeps redirecting to `/events?type=training`. Visitors looking for a dated training should reach the agenda; visitors looking for evergreen guidance should reach `/getting-started`.

## Purpose

The hub should answer the questions a new pilot has before they are ready to register:

- What is FPV and what does DDS organize?
- Can I join if I have never flown before?
- Which equipment do I need, and what should I avoid buying too early?
- What happens during a DDS training or race day?
- Which technical and safety requirements apply?
- What is the difference between an event, a season, a single-event ticket, and a season ticket?
- Which event or location is suitable for my experience level?
- Where can I ask for help when I am still unsure?

The desired visitor outcome is a concrete next step: read the relevant guide, choose a suitable event, check a location, or contact DDS with a focused question.

## Sources Of Truth

The hub explains how DDS works, but it must not duplicate operational data that can change. Each domain retains one source of truth.

| Information                                                                  | Source of truth                   | How the hub uses it                                 |
| ---------------------------------------------------------------------------- | --------------------------------- | --------------------------------------------------- |
| Event date, time, event price, capacity, deadline, and status                | Event                             | Link to or render current event data                |
| Season name, included events, season-ticket price, capacity, and sales state | Season and season-ticket workflow | Render current season data and explain the concept  |
| Address, facilities, dimensions, and accessibility                           | Location                          | Link to the current location page                   |
| Binding conduct and safety policies                                          | House Rules                       | Summarize the purpose and link to the full rules    |
| Registration, payment, confirmation, and waitlist state                      | Registration workflow             | Send the visitor into the current registration flow |
| Beginner guidance, equipment advice, preparation, and DDS context            | Getting Started hub               | Own and maintain as evergreen editorial content     |

Event descriptions may contain event-specific instructions. They should not become the only place where general newcomer information is maintained.

## Initial Content Structure

### 1. Start Here

A short orientation for someone who has just discovered FPV or DDS:

- what FPV drone flying is;
- what DDS organizes;
- the difference between training, free practice, racing, demos, and workshops;
- which route suits a complete beginner, a pilot who can already fly, or an experienced racer;
- one clear next action for each visitor type.

### 2. Learn The Basics

Explain the concepts needed to understand the rest of the website:

- drone, radio, goggles, video transmitter, batteries, and charger;
- analog and digital video systems where relevant to DDS events;
- line of sight versus FPV;
- simulator practice;
- basic race terminology such as gates, laps, heat, timing, and race control.

### 3. Choose Equipment

Provide decision support without turning DDS into a shop:

- what a complete setup consists of;
- what can be borrowed or tried first when DDS can offer that;
- minimum useful characteristics rather than fragile product recommendations;
- compatibility warnings;
- battery, charger, and fire-safety basics;
- a checklist to use before buying.

Time-sensitive product advice should include an update date and an editorial owner.

### 4. Your First Time At DDS

Describe the real visitor journey:

- choose a suitable event;
- register and receive confirmation;
- prepare equipment and identifiers;
- arrive, check in, and complete any technical check;
- join the briefing;
- fly, charge safely, and ask for help;
- pack up and choose a next event.

### 5. Pilot Checklist

Keep the practical checklist easy to scan and link to it from training event details:

- FPV Scores UUID when required;
- configured video system and VTX table;
- applicable power limit, currently communicated as `25 mW` where required;
- supported bitrate or video settings;
- assigned channel procedure;
- charged batteries and safe storage;
- spare parts, tools, and personal equipment;
- what to do when a requirement is unclear.

Requirements that vary by event remain on the event detail page.

### 6. Events, Seasons And Tickets

Explain the participation model in plain language:

- an event is one dated activity with its own registration, capacity, and status;
- a season groups related events under one recognizable period or competition context;
- a single-event ticket grants access only to the selected event;
- a season ticket is a separate participation product covering a defined set of events;
- not every season has to offer a season ticket;
- not every event linked to a season has to be included in its season ticket;
- season-ticket capacity and event capacity are separate limits;
- ticket holders may still need to confirm attendance or opt out for individual dates.

The page should compare the available options using current backend data instead of hard-coded dates, prices, or availability.

### 7. Locations And Skill Fit

Help visitors choose the right environment without duplicating location pages:

- indoor and outdoor differences;
- track scale and what that means for skill level;
- practical facilities;
- links to suitable upcoming events at each location;
- a clear warning when a location or event is primarily for experienced pilots.

### 8. Safety, Community And Help

Close the knowledge journey with:

- the most important safety principles;
- expected behavior and a link to House Rules;
- how DDS members help each other;
- the right contact route for beginner, equipment, event, or accessibility questions;
- links to any later glossary or frequently asked questions.

## Public Entry Points

The hub should be discoverable before a visitor gets stuck. These entry points are part of the feature, not optional promotion.

| Entry point               | Placement and intent                                                                    | Priority         |
| ------------------------- | --------------------------------------------------------------------------------------- | ---------------- |
| Main navigation           | `Getting Started` / `Beginnen` for visitors who arrive with a general learning need     | Primary          |
| Homepage newcomer section | Replace the isolated beginner paragraph with a short path and a clear link into the hub | Primary          |
| Homepage hero             | Optional secondary text link when it does not compete with the agenda CTA               | Secondary        |
| Events overview           | Context link near filters or the empty state: unsure which event fits, start here       | Contextual       |
| Training event detail     | Link from preparation, skill-level, or what-to-bring content                            | Contextual       |
| Location detail           | Link where venue scale or required experience needs explanation                         | Contextual       |
| Contact page              | Offer the relevant guide before a visitor submits a broad beginner question             | Contextual       |
| Footer                    | Permanent fallback under a learning or participation group                              | Primary fallback |
| Search and SEO            | Index stable guide pages with descriptive titles and internal links                     | Discovery        |

The desktop and mobile navigation need a capacity review when this item is added. If the main navigation becomes too crowded, move a lower-priority corporate link to the footer rather than hiding the newcomer path.

## Season And Season-Ticket Model

### Concepts

These concepts must remain distinct in content, code, and interface labels:

1. A `Season` is a named grouping, for example `Indoor Season 2026/27`. Its public date range is derived from its included events. A season can exist without a season ticket.
2. A single-event registration belongs to one `Event` and uses that event's price, capacity, deadline, and registration status.
3. A `Season Ticket` is a product for a defined set of eligible events. It has its own price, capacity, sales window, sales state, and registration destination.
4. Season-ticket eligibility must be explicit. It must not be inferred solely from an event having the same `season_id`, because a special event can belong to the season context without being included in the ticket.
5. Buying a season ticket does not automatically describe attendance. A later operational workflow should record whether a ticket holder attends or opts out for each included event.

### Public Sales States

Use clear visitor-facing states:

- `Not offered`: the season is informational only;
- `Coming soon`: a ticket is planned but sales have not opened;
- `Available`: registration is open;
- `Sold out`: the season-ticket allocation is full;
- `Closed`: the sales deadline has passed or sales were closed manually.

A cancelled event remains visibly part of the season history, but the interface must not imply refund, replacement, or ticket terms that are not stored and managed explicitly.

### Required Domain Extension

The current `Season` fields—name, optional price, and ticket capacity—are a useful start but not enough to publish a reliable season-ticket offer. Before season tickets are promoted, the domain should also support:

- explicit season-ticket availability or sales state;
- sales opening and closing times;
- a season-ticket registration URL or native registration route;
- explicit included events;
- public explanatory copy or a managed reference to it;
- later: holders, payment state, and per-event attendance or opt-out state.

## Public Season Presentation

Season context should be recognizable without overpowering the event itself:

- event cards and list rows show a localized `Season` label followed by the season name;
- the event-detail hero presents event type and season as separate concepts;
- a filtered training or race list may show a compact current-season summary above its events;
- the Getting Started ticket guide may show the current season, included events, prices, and sales state from backend data;
- event and season dates always include the year when ambiguity is possible, especially for dates in a future calendar year;
- the season-ticket module is hidden when no ticket is offered, while the season label can still remain visible;
- single-event and season-ticket actions use distinct labels and explain what each option covers.

## Content And Admin Strategy

The public experience should use designed React templates and constrained content fields rather than a generic page builder.

Phase 1 can publish the hub through a managed page with a fixed `getting_started` key. The implementation should keep dynamic event, season, location, and registration data outside the saved rich-text body.

When the guide library grows, introduce curated `Guide` records with:

- title and stable English slug;
- short summary;
- structured or sanitized body content;
- category and manual order;
- publication status and timestamps;
- optional cover media;
- editorial owner and last reviewed date;
- optional English and Dutch variants only for content that DDS will actively maintain in both languages.

Possible later routes include:

```txt
/getting-started
/getting-started/first-fpv-flight
/getting-started/choosing-equipment
/getting-started/first-dds-event
/getting-started/events-seasons-and-tickets
/getting-started/pilot-checklist
```

## Delivery Phases

### Phase 1: Useful Hub

- publish the landing page and core beginner journey;
- add all primary and contextual entry points;
- explain events, seasons, and tickets;
- render suitable upcoming events and current season information from backend data;
- link to existing Event, Location, Contact, and House Rules pages.

### Phase 2: Curated Guide Library

- introduce guide records and admin workflow;
- split long subjects into stable, searchable detail pages;
- add review dates, ownership, and related-content links;
- expand equipment, simulator, safety, and glossary content.

### Phase 3: Registration And Season-Ticket Workflow

- support a native season-ticket product if DDS chooses not to use an external registration service;
- manage holders, payment state, capacity, and deadlines;
- allow attendance confirmation or opt-out per included event;
- show the holder's season access and event actions in a private account area.

## Quality Requirements

- Mobile layouts must keep long-form content readable and prevent awkward card, table, or navigation breaks.
- Every guide needs one obvious next action and relevant contextual links.
- Dynamic dates, prices, capacities, and statuses must come from their domain models.
- Links must use named Laravel routes through Wayfinder in the React frontend.
- Guide headings, landmarks, focus order, link labels, and contrast must meet the platform accessibility standard.
- Empty states must remain honest when there is no suitable upcoming event or active season-ticket offer.
- Analytics should distinguish navigation, homepage, event, location, contact, footer, and search entry points.
- Editorial content must have an owner and a review rhythm so safety and equipment guidance does not become stale.

## Decisions Still Needed

- Confirm the final Dutch label: `Beginnen`, `Starten met FPV`, or `Nieuw bij DDS`.
- Decide whether a season-ticket holder automatically reserves every included event or confirms attendance per event.
- Decide whether season-ticket sales stay external initially or become a native registration product.
- Select the first guide set and assign an editorial owner for each subject.
