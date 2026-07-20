# Training Registration And Capacity

## Decision

DDS keeps training registration email-based in the first platform version. The public website may explain the process and open an email registration action, but it must not imply that registration, live availability, a waitlist, or payment is handled inside the platform.

Before native registration is introduced, DDS must approve a training policy that describes admission, capacity, VTX compatibility, season-ticket priority, payment timing, and waitlist behavior. The policy defaults at season level and may be explicitly overridden for an individual training.

This document is the product source of truth for those rules. Backlog tickets should describe implementation scope and link here instead of duplicating the full decision set.

## Scope

This document covers:

- email-based and future native training registration;
- single-event tickets and season-ticket allocations;
- attendance, opt-out, cancellation, and refund behavior;
- training capacity and heat-planning constraints;
- VTX profiles, admission policy, and the `25 mW` house rule;
- pilot skill levels used for heat balancing;
- current and proposed future training formats;
- implementation gates for native registration, waitlists, and online payment.

It does not define race registration rules. A race can use a straightforward numeric capacity when its regulations already define one compatible VTX set.

## Terminology

| Term                     | Meaning                                                                                                |
| ------------------------ | ------------------------------------------------------------------------------------------------------ |
| Training format          | The capacity and VTX policy used to compose the three heats for a training.                            |
| VTX profile              | The small, policy-relevant description of the video system and mode a pilot normally uses.             |
| Season-ticket allocation | The place reserved for an active holder at an explicitly eligible event.                               |
| Attendance               | Whether an allocated or registered pilot is expected and ultimately attends.                           |
| Opt-out                  | A season-ticket holder explicitly releasing one event allocation without cancelling the season ticket. |
| Loose registration       | A registration or ticket for one event rather than the full season.                                    |
| Race-compatible          | A VTX profile that DDS has validated for use in a mixed RaceBand-oriented heat.                        |
| Digital-only             | A profile that uses the dedicated digital heat in the current training format.                         |

## Sources Of Truth

| Information                                     | Source of truth                                   | Notes                                                             |
| ----------------------------------------------- | ------------------------------------------------- | ----------------------------------------------------------------- |
| Maximum pilot count and default training format | Season training policy                            | Can be overridden explicitly per event.                           |
| Effective format for one training               | Event override, otherwise season default          | Historical registrations retain the effective policy they used.   |
| Eligible season-ticket events                   | Season Ticket product                             | Never inferred only from the event's season.                      |
| Holder allocation and opt-out                   | Season-ticket attendance workflow                 | Allocation and actual attendance remain distinct.                 |
| Pilot VTX and skill level                       | Pilot profile with an event registration snapshot | Later profile changes do not rewrite history.                     |
| VTX admission and heat compatibility            | Versioned training policy                         | Must be changeable for a future season without a code deployment. |
| Transmitter power and binding safety behavior   | House Rules                                       | The current requirement is `25 mW`.                               |
| Single-ticket refund deadline                   | House Rules                                       | At least 24 hours before the event start.                         |

## First Platform Version

### Registration Channel

- Pilots register by email.
- The public event action opens or clearly explains that email process.
- DDS confirms the registration manually.
- Availability, compatibility decisions, and waiting pilots remain operationally managed outside the platform.
- The website does not show an exact remaining-place count for training when DDS has not confirmed that number.
- There is no native registration form, live waitlist, automated seat assignment, or online checkout.

This preserves the current process while the public events, media, content, and administration foundations are completed.

### Payments

- Current payments are handled externally and recorded manually where the platform needs an operational payment state.
- The future data model should remain provider-agnostic so online payment can be added later.
- Native payment must not be introduced before DDS has decided when a capacity-dependent request becomes payable.

## Season Tickets

### Allocation And Attendance

- A season ticket covers only the events explicitly selected for that product.
- An active season ticket reserves one place at every eligible event by default.
- Holders do not confirm every event; they are treated as attending unless they opt out.
- Only an explicit opt-out releases a holder's allocation.
- Holders can opt out until the event, although interface copy and reminders should strongly encourage early notice so DDS can offer the place to someone else.
- An opt-out releases capacity but does not create an automatic refund.
- Administrators must be able to distinguish the reserved allocation, expected attendance, opt-out, actual attendance, and no-show.

### Product And Identity

- A holder record references the season-ticket product that was purchased.
- Season tickets are personal and non-transferable.
- An administrator may record an exceptional transfer or correction with a reason.
- The application must protect holder data through authorization and an approved retention policy.

### VTX And Guaranteed Places

- A season-ticket application includes the pilot's VTX profile because the holder consumes compatible capacity for every eligible training.
- The VTX mix must be reviewed when season tickets are sold; total ticket count alone is insufficient under the current split-heat format.
- A paid holder keeps their guaranteed place if they later change VTX profile.
- A conflicting change produces an administrator warning and requires manual rebalancing; it never silently revokes the paid allocation.
- Historical registrations keep the VTX profile and effective training policy used for that event.

## Single-Event Tickets

- Loose training registrations are handled by email in the first version.
- First-in-first-out is the default fairness principle.
- A single-event ticket cancelled at least 24 hours before the event starts is eligible for a refund under the current House Rules.
- A later cancellation is normally not refunded.
- This 24-hour financial rule does not prevent a season-ticket holder from opting out closer to the event, because a season-ticket opt-out has no automatic refund.

## Cancellation And Refund Exceptions

- DDS currently has no standard cancellation or refund policy for a complete season ticket.
- A no-show normally forfeits the amount paid.
- DDS has previously made a full refund as a one-off exception for a holder who did not attend during the season; this does not establish an automatic entitlement.
- Any exceptional holder cancellation or refund requires a deliberate administrator action and a recorded reason.
- Organizer cancellation is rare because season-ticket income helps fund the complete planned season.
- If DDS cancels an event, there is no automatic refund, credit, or replacement outcome yet; an administrator records the case-specific decision.
- Event-capacity reduction has not occurred in the current process. If it occurs later, the system must not automatically remove paid season-ticket holders.

## Heat Planning Inputs

### Capacity And Layout

- A training has a hard maximum of 15 pilots.
- DDS normally composes three heats of five shortly before the event.
- The current, proven layout uses two heats for the majority Analog/HDZero group and one dedicated digital heat.
- Under this layout, ten places are available to the race-compatible group and five to the dedicated digital group.
- One group can therefore be full before the overall count reaches 15. For example, an eleventh Analog/HDZero pilot has no compatible place when only four digital pilots are registered.
- DDS composes the actual heats manually from registrations, opt-outs, VTX information, experience, and prior observed lap times.
- Automatic heat generation and persisted heat management are outside the first implementation.

### Pilot Skill

- Pilots self-identify as `beginner`, `advanced`, or `pro`.
- Skill level never determines admission and never consumes a different capacity class.
- Organizers use the selected level, experience with the regular group, and previously measured times only to balance the heats.

### VTX Information

- VTX information is required for trainings, not universally for every event type.
- Pilots normally use one stable VTX configuration rather than changing systems to make an event fit.
- DDS currently needs only a concise VTX profile, not model numbers, channel lists, or user-entered transmit power unless a future policy proves otherwise.
- Transmit power is always `25 mW` under the House Rules and should be acknowledged as a rule instead of presented as a selectable registration field.

## Training Policy Profiles

### Season Default And Event Override

- A season selects the default training policy before season-ticket sales open.
- An event inherits that policy unless an administrator deliberately selects an override.
- An override can support a test evening or exceptional format without silently changing the rest of the season.
- Registrations resolve one effective policy and retain that policy version for later audit and historical meaning.

### Current: Dedicated Digital Heat

| Property                   | Rule                                                                |
| -------------------------- | ------------------------------------------------------------------- |
| Total capacity             | 15 pilots                                                           |
| Heat layout                | Three heats of five                                                 |
| Race-compatible capacity   | 10 places across two Analog/HDZero-oriented heats                   |
| Dedicated digital capacity | 5 places in one digital heat                                        |
| Current digital admission  | DJI Vista, O3, O4, and Walksnail can use the dedicated digital heat |
| Availability               | Depends on the compatible group as well as the total count          |
| Registration decision      | Email-based and manually confirmed in the first version             |

This format continues to support older digital systems because they have a separate heat. Native availability could eventually count compatible groups, but DDS should not expose that automation until the profiles and exceptional cases have been validated.

### Future: Fully Mixed Race-Compatible Heats

| Property           | Rule                                                                             |
| ------------------ | -------------------------------------------------------------------------------- |
| Total capacity     | One shared pool of 15 pilots                                                     |
| Heat layout        | Three interchangeable mixed heats of five                                        |
| Admission          | Only VTX profiles approved by DDS for mixed race-mode operation                  |
| Availability       | Simple confirmed count up to 15 after profile eligibility is checked             |
| Registration order | Confirm eligible pilots first-in-first-out; use a conventional waitlist after 15 |

The proposed direction is to exclude DJI Vista, O3, and older DJI systems when the dedicated digital heat is removed, while allowing DJI O4 only in an approved race-mode configuration. Walksnail race mode is a candidate for admission but still needs DDS field validation. These are future policy decisions, not current restrictions.

## VTX Profiles And Policy

The future implementation should separate a profile from the rule applied to it. A concise profile may need to distinguish:

- Analog;
- HDZero;
- DJI O4 in race mode;
- DJI O4 without the approved race-mode setup;
- DJI Vista, O3, or older;
- Walksnail in race mode;
- Walksnail without race mode;
- another or unknown configuration requiring review.

A versioned training policy can classify a profile as:

- allowed;
- requiring administrator review;
- not allowed.

The exact values and wording remain part of DDS-011H discovery. They must not become a rigid brand-only enum when operating mode determines compatibility.

### Vendor Evidence To Validate In Practice

- [DJI's O4 FAQ](https://www.dji.com/o4-air-unit/faq) states that O4 race mode uses analog RaceBand frequency points and supports simultaneous use with analog racing drones. Enabling it requires DJI Goggles 3 or Goggles N3.
- [Official Walksnail firmware notes](https://cdn.shopify.com/s/files/1/0036/3921/4169/files/Avatar_system_firmware_updates_records_38.43.4_stable.pdf?v=1722940508) document a race mode with analog channels.

Vendor capability does not by itself approve a profile for DDS. Walksnail race mode and DJI O4 race mode must be tested with the actual DDS venue, equipment, channel plan, and `25 mW` rule before the fully mixed policy is adopted.

## Future Native Registration

### Dedicated-Digital-Heat Format

Native registration under the current format cannot rely only on a total count. It must account for the ten race-compatible and five dedicated-digital allocations, including season-ticket holders. Profiles that can fit more than one group may require an administrator decision. DDS may therefore keep manual confirmation for capacity-sensitive requests even after the form moves into the platform.

### Fully Mixed Format

Once DDS has approved only mutually compatible profiles, native registration becomes substantially simpler:

1. Validate that the submitted VTX profile is allowed by the effective policy.
2. Count active season-ticket allocations and confirmed loose registrations.
3. Confirm eligible requests first-in-first-out until the shared capacity reaches 15.
4. Put later eligible requests on a conventional waitlist.
5. Release a season-ticket allocation only after its holder explicitly opts out.

### Public Presentation

- The email version shows the registration route without live availability claims.
- A future split-heat native version should avoid a generic remaining-place count when availability depends on VTX profile.
- A future fully mixed native version may show a conventional capacity state after eligibility is known.
- Public labels must distinguish `registration received`, `confirmed`, `awaiting review`, `waitlisted`, `cancelled`, and `opted out` if those states are approved for implementation.

## Administrative Needs

The eventual administration workflow should provide:

- season-ticket holders with product, payment, VTX, and eligibility context;
- event allocations separated from expected and actual attendance;
- opt-outs, no-shows, cancellations, and released allocations;
- loose requests in first-in-first-out order;
- VTX grouping or filtering and warnings for incompatible capacity;
- self-reported skill levels for manual heat balancing;
- manual payment status and later provider-backed payment status;
- recorded reasons for queue exceptions, transfers, refunds, and policy overrides;
- an exportable operational overview for composing heats outside the platform.

Important operational changes must be authorized, timestamped, and attributable to an administrator.

## Worked Scenarios

### Split Format Reaches A Group Limit

Ten Analog/HDZero pilots and four dedicated-digital pilots are confirmed. The total is 14, but another Analog/HDZero pilot cannot be confirmed because the race-compatible group is already full. The empty digital place does not imply availability for that profile.

### Fully Mixed Format Reaches Capacity

Fifteen pilots with approved mixed-heat profiles are confirmed. The sixteenth eligible request joins a conventional first-in-first-out waitlist regardless of brand because every admitted profile can use every heat.

### Holder Opts Out

A season-ticket holder explicitly opts out for one training. Their allocation becomes available, but the holder receives no automatic refund. Under the email process, DDS handles the replacement manually. A future native workflow applies the effective training policy before confirming a waiting pilot.

### Holder Changes VTX

A paid holder changes from a race-compatible profile to a digital-only profile after season-ticket sale. Their place remains guaranteed. The platform flags the capacity conflict for manual resolution and retains both the original registration snapshot and the change history.

## Open Decisions And Implementation Gates

Native training registration, waitlists, and online payment remain blocked until DDS has:

- field-tested DJI O4 race mode and Walksnail race mode in the actual training environment;
- approved the VTX profiles and admission classifications for the intended season;
- decided whether the first native split-format workflow confirms all requests manually or automates safe cases;
- defined response deadlines and reassignment behavior for a future waitlist;
- decided when payment is collected for a request awaiting compatibility review;
- approved holder, allocation, attendance, payment, cancellation, and refund states;
- approved privacy, retention, and administrator-access rules;
- confirmed that the public House Rules contain the binding `25 mW` and 24-hour single-ticket cancellation rules.

Email remains the registration channel until these gates are explicitly approved.

## Non-Goals For The First Implementation

- automatic heat generation;
- timing or lap-result management;
- algorithmic skill evaluation;
- automatic refund decisions;
- automatically revoking a paid season-ticket allocation;
- inferring compatibility from brand alone;
- showing live training availability while registration remains email-based.

## Related Backlog Tickets

- DDS-009A: Season Ticket Product And Eligibility Model;
- DDS-010B: Public Season And Season Ticket Presentation;
- DDS-011F: Season Ticket Holder And Attendance Workflow;
- DDS-011H: Native Training Registration And Capacity Discovery.
