# Open Work Execution Order

## Purpose

This is the single authoritative order for unfinished DDS Platform work. Ticket definitions, tasks, and acceptance criteria remain in [Initial Build Backlog](./initial-build-backlog.md); this file answers only what should be picked up next.

Update this list in the same pull request whenever work is merged, split, absorbed, blocked, deferred, or reordered. Do not add another numbered execution list elsewhere.

## Working Rules

- Start the first unfinished row whose dependencies are satisfied.
- Treat a ticket as complete only after its pull request is merged into `main`.
- Keep at most one primary build ticket in progress unless a clearly independent discovery task is explicitly run in parallel.
- Update the ticket status in the main backlog and remove its row here when it is merged.
- Record a reason when the order changes; do not silently move work around.
- Deferred decision-gate tickets are not actionable work until their trigger is documented and approved.

## Current Focus

| Order | Ticket                       | Deliverable                                                                                                                                               | Dependency or completion rule                                                                      |
| ----: | ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
|     1 | DDS-014J | Build public News pages and Article management using the existing Article and MediaAsset foundations. | DDS-014I is complete and merged (pull request #28); the news pages, admin CRUD, and checks are implemented and awaiting review and merge. |

## Ordered Open Work

| Order | Ticket   | Deliverable                                                                                                                                | Why this position                                                                                                                                                  |
| ----: | -------- | ------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
|     2 | DDS-014L | Build the Getting Started hub and all documented entry points using current Event, Season, Location, contact, and code-owned page content. | Gives new pilots one coherent path once its dynamic and contact destinations exist.                                                                                |
|     3 | DDS-014K | Decide from real Getting Started content whether separate managed guides are needed; build the guide library only if the decision is yes.  | Prevents creating a second content system before the initial hub proves the need. Mark the ticket deliberately skipped if the hub remains maintainable without it. |
|     4 | DDS-007D | Complete the cross-page navigation, footer, keyboard, and screen-reader review.                                                            | Runs after the planned public destinations exist, so the final hierarchy is tested once rather than repeatedly.                                                    |
|     5 | DDS-011H | Complete native training-registration and capacity discovery and obtain explicit product approval.                                         | Native registration must not start until compatibility, confirmation, payment, cancellation, and privacy rules are agreed.                                         |
|     6 | DDS-015  | Inventory and approve the WordPress migration scope and source.                                                                            | DDS-014H is complete; DDS-014J and the other public destinations above make imported content reviewable.                                                           |
|     7 | DDS-017  | Prototype repeatable WordPress media import.                                                                                               | Media must exist before article and page bodies can resolve their attachments.                                                                                     |
|     8 | DDS-016  | Prototype WordPress posts-to-Article import.                                                                                               | Depends on DDS-014J and imported media mappings from DDS-017.                                                                                                      |
|     9 | DDS-018  | Prototype deliberate WordPress page mapping.                                                                                               | Uses the completed Location, code-owned fixed-page, partner-catalogue, and media destinations.                                                                     |
|    10 | DDS-019  | Build the imported-content cleanup pipeline.                                                                                               | Runs against representative imported articles and mapped pages.                                                                                                    |
|    11 | DDS-020  | Import and review legacy redirects.                                                                                                        | Requires stable destination routes and source-to-target mappings.                                                                                                  |
|    12 | DDS-021  | Produce the temporary import review report.                                                                                                | Consolidates unresolved content, media, mapping, and redirect decisions.                                                                                           |
|    13 | DDS-022  | Rehearse the complete import on staging.                                                                                                   | Validates idempotency and review workflows before production cutover.                                                                                              |
|    14 | DDS-023  | Finalize production runtime configuration, mail, queues, scheduler, storage, and backups.                                                  | Must be explicit before automated deployment and cutover.                                                                                                          |
|    15 | DDS-024  | Finalize CI and the deployment pipeline.                                                                                                   | Uses the approved production runtime and provides repeatable deployment and rollback.                                                                              |
|    16 | DDS-025  | Complete the public accessibility and responsive audit.                                                                                    | Runs against the complete public surface and fixes launch-blocking issues.                                                                                         |
|    17 | DDS-026  | Complete the admin usability audit.                                                                                                        | Runs against the complete set of actual dashboard resources; there is no project, partner, or fixed-page CRUD in phase 1.                                          |
|    18 | DDS-027  | Complete the content freeze, final import, redirect review, DNS switch, and launch checklist.                                              | Final step after staging rehearsal, deployment, and audits pass.                                                                                                   |
|    19 | DDS-014M | Add a reply composer so admins can answer a contact submission directly from the dashboard.                                                | Optional enhancement to the merged DDS-014G review flow; not required by any other queued ticket, so it sits last rather than displacing launch-critical work.     |

## Deferred Decision Gates

These tickets remain documented but are not part of the active numbered queue.

| Ticket   | Reopen only when                                                                                                                                                                                                    |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| DDS-014E | Observed partner maintenance requires non-technical editing, frequent lifecycle changes, operational sponsor state, multiple non-code consumers, or pull-request maintenance causes measurable friction.            |
| DDS-014F | Fixed-page copy needs independent non-technical editing, changes create measurable pull-request delay, bilingual maintenance needs an editorial workflow, or migration discovery proves code ownership impractical. |
| DDS-011F | DDS-011H is approved and DDS explicitly chooses to build native season-ticket holder, allocation, payment, opt-out, and attendance workflows.                                                                       |

If a gate is reached, record the evidence and insert the ticket into the active order at the earliest point where all dependencies are satisfied.
