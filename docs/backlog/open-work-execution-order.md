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

DDS-017, DDS-016, DDS-018, DDS-019, DDS-020, and DDS-021 are implemented together in the current stacked WordPress-import branch at the user's direction. DDS-022 is now active: the approved manifest and curated offline bundle are available locally, all 29 selected images have reviewed alt text, all internal-link/media diagnostics are resolved, the eight page rewrites are approved, and the consolidated local review is `READY`. A fresh approved two-pass local rehearsal imported zero records in both passes, kept persistent counts stable, and passed all 15 public samples. The only remaining operational blocker is a recoverable real staging environment with a public URL/access; the browser/admin review must be repeated there for DDS-022B evidence. Do not start DDS-023 until DDS-022A and DDS-022B produce real staging `READY` evidence.

| Order | Ticket   | Deliverable                                       | Dependency or completion rule                                                                                                                            |
| ----: | -------- | ------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
|     1 | DDS-017  | Prototype repeatable WordPress media import.      | Implemented and tested on the current branch; remains incomplete until merged.                                                                           |
|     2 | DDS-016  | Prototype WordPress posts-to-Article import.      | Implemented on top of DDS-017 mappings and tested on the current branch; remains incomplete until merged.                                                |
|     3 | DDS-018  | Prototype deliberate WordPress page mappings.     | Implemented with the complete 12-page REST inventory and review report; remains incomplete until the branch is merged.                                   |
|     4 | DDS-019  | Build the imported-content cleanup pipeline.      | Implemented with plain-text normalization, checksum protection, diagnostics, and a review report; remains incomplete until merged.                       |
|     5 | DDS-020  | Import and review legacy redirects.               | Implemented with derived and explicit candidates, pending review, conflict protection, and idempotent reuse; remains incomplete until merged.            |
|     6 | DDS-021  | Produce the temporary import review report.       | Implemented with per-phase run history, consolidated diagnostics, traceability, launch status, and artifact policy; remains incomplete until merged.     |
|     7 | DDS-022  | Rehearse the complete import on staging.          | Runner implemented and tested; complete DDS-022A with real staging inputs, then DDS-022B blocker resolution and manual approval before marking complete. |
|     8 | DDS-022A | Provision and execute the real staging rehearsal. | Approved inputs are ready locally; active blocker is a recoverable staging environment with a public URL/access and valid destination records.           |
|     9 | DDS-022B | Resolve rehearsal blockers and approve samples.   | Local review is `READY`; repeat browser/admin approval against real staging after DDS-022A and resolve any environment-specific findings.               |

## Ordered Open Work

| Order | Ticket   | Deliverable                                                                                   | Why this position                                                                                                                                              |
| ----: | -------- | --------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
|    10 | DDS-023  | Finalize production runtime configuration, mail, queues, scheduler, storage, and backups.     | Starts only after DDS-022A and DDS-022B produce final `READY` evidence; runtime must be explicit before automated deployment and cutover.                      |
|    11 | DDS-024  | Finalize CI and the deployment pipeline.                                                      | Uses the approved production runtime and provides repeatable deployment and rollback.                                                                          |
|    12 | DDS-025  | Complete the public accessibility and responsive audit.                                       | Runs against the complete public surface and fixes launch-blocking issues.                                                                                     |
|    13 | DDS-026  | Complete the admin usability audit.                                                           | Runs against the complete set of actual dashboard resources; there is no project, partner, fixed-page, or guide CRUD in phase 1.                               |
|    14 | DDS-027  | Complete the content freeze, final import, redirect review, DNS switch, and launch checklist. | Final step after staging rehearsal, deployment, and audits pass.                                                                                               |
|    15 | DDS-014M | Add a reply composer so admins can answer a contact submission directly from the dashboard.   | Optional enhancement to the merged DDS-014G review flow; not required by any other queued ticket, so it sits last rather than displacing launch-critical work. |

## Deferred Decision Gates

These tickets remain documented but are not part of the active numbered queue.

| Ticket   | Reopen only when                                                                                                                                                                                                    |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| DDS-014E | Observed partner maintenance requires non-technical editing, frequent lifecycle changes, operational sponsor state, multiple non-code consumers, or pull-request maintenance causes measurable friction.            |
| DDS-014F | Fixed-page copy needs independent non-technical editing, changes create measurable pull-request delay, bilingual maintenance needs an editorial workflow, or migration discovery proves code ownership impractical. |
| DDS-011F | DDS approves fully mixed heats and native registration after the DDS-011H reopening conditions are met, then explicitly chooses to build native holder, allocation, payment, opt-out, and attendance workflows.     |

If a gate is reached, record the evidence and insert the ticket into the active order at the earliest point where all dependencies are satisfied.
