# Product Vision and Scope

## Product Vision

Dutch Drone Squad should become the central platform for FPV drone racing events, community activity, demos, and projects around DDS. The website should help newcomers understand how to join, help existing pilots quickly find event and training information, and show partners what DDS can organize.

## Positioning

DDS stands for:

- indoor drone racing training;
- community and knowledge sharing;
- technical race organization with timing, heats, and livestream experience;
- a local base around Alkmaar with broader FPV racing ambition;
- demos, workshops, and projects for external partners.

## Audiences

Homepage priority is deliberate: experienced pilots should reach practical event information first, followed by a clear and honest starting path for beginners. Parents, partners, schools, and clients remain important audiences, but their deeper proof and contact paths can live on supporting pages instead of competing in the first homepage sections.

### New Pilots

Need to know:

- Can I join without experience?
- What equipment do I need?
- Where and when is the next training?
- What does it cost?
- How do I register?

The Sportpaleis events are not intended as first-flight sessions. Beginners should be directed to De Goorn when a beginner moment is organized based on sufficient interest.

### Experienced Pilots

Need to know:

- When is the next training evening?
- Which location and track?
- Will there be timing, heats, or results?
- Are there updates or changes?
- Am I expected to fly a complete track independently and repair my own drone after a crash?

### Parents And Youth

Need to know:

- Is it safe and organized?
- Which rules apply?
- Is guidance available?
- How accessible is the sport?

### Partners, Schools, And Clients

Need to know:

- Can DDS provide demos, workshops, or race formats?
- What experience does DDS have?
- How professional is the organization?
- How can they get in touch?

## Phase 1 Scope

The first release should replace the current WordPress site with a Laravel app and custom admin.

In scope:

- homepage;
- events, including regular training evenings;
- agenda or events overview;
- news;
- locations;
- about;
- house rules;
- partners;
- contact form;
- custom admin;
- basic media upload;
- SEO baseline;
- responsive layout.

Out of scope:

- online payments;
- shop;
- extensive member portal;
- generic page builder;
- league or ranking system;
- ticketing with QR codes;
- newsletter automation;

Language scope:

- English and Dutch should be supported from the start at the content model level;
- English remains the default and primary public locale;
- Dutch translations can be added gradually and do not need full content parity in the first release;
- the current landing page can remain temporarily Dutch until the multilingual public content layer is in use;
- a concise English slogan or campaign line can be used inside the temporary Dutch landing page when it strengthens the DDS brand.

## Current Operational Context

These facts should guide public copy and future managed content until operations change:

- Sportpaleis Alkmaar is the primary recurring indoor race location;
- the usual indoor season runs from September through May;
- a regular Sportpaleis event has a maximum capacity of 15 pilots;
- Sportpaleis participants are expected to fly a complete track independently and repair their own drone;
- beginner moments can take place in De Goorn based on sufficient interest and do not currently have a fixed schedule;
- Oosterhout was a temporary fallback location and should not be presented as an equal active homepage destination.

## Phase 2 Opportunities

Potential follow-up features:

- online training registrations;
- event registrations for trainings, races, demos, and workshops;
- automatic confirmations and reminders;
- project showcases;
- demo and workshop requests;
- media galleries;
- pilot profiles;
- results or timing integrations;
- basic newsletter flows;
- payments through Mollie or Stripe;
- waitlists for full trainings.

## Product Principles

- Start with concrete DDS domains, not a generic CMS.
- The admin should be fast and practical for a small organization.
- Public pages should be visually strong, but practical information remains primary.
- Photography and video should carry the visual identity.
- Mobile matters, especially for agenda, contact, and training details.
- Every page should have a clear next action.

## UX Quality Bar

UI/UX quality is a first-class project requirement, not a visual polish phase at the end. The product owner has a UX engineering background, so interaction quality, information hierarchy, accessibility, and perceived craft should be treated as part of the definition of done.

Principles:

- prioritize clarity over decorative complexity;
- make primary actions obvious and contextual;
- design dense admin screens for repeated use, scanning, and low friction;
- design public pages around visitor intent, not internal content structure;
- keep mobile layouts as deliberate as desktop layouts;
- use photography/video intentionally to communicate real DDS activity;
- avoid generic template copy, generic dashboard visuals, and filler sections;
- ensure forms have clear labels, validation, helper text, loading states, empty states, and success/error feedback;
- keep visual components consistent across pages, but avoid over-abstracting before patterns are proven;
- treat accessibility, keyboard navigation, focus states, color contrast, and readable spacing as baseline requirements.

Definition of done for user-facing work:

- page purpose is clear within the first viewport;
- primary and secondary actions are visually distinct;
- text hierarchy supports scanning;
- layouts work on mobile and desktop without overlap or awkward wrapping;
- forms provide useful validation and recovery paths;
- loading, empty, error, and success states are handled;
- interaction states are visible for mouse, touch, and keyboard users;
- implementation is reviewed against existing design conventions before merging.
