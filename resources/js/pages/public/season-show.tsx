import { Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarRange,
    Clock3,
    MapPin,
    Ticket,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PublicEventRegistrationStatus } from '@/components/public/public-event-card';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    eventTypeLabels,
    formatEventDate,
    formatEventLocation,
    formatEventPrice,
    formatEventShortDate,
    formatEventTimeRange,
    formatSeasonDateRange,
    getEventRegistrationDetail,
    seasonTicketSalesLabels,
} from '@/lib/event-formatting';
import { cn } from '@/lib/utils';
import { index as eventsIndex, show as eventShow } from '@/routes/events';
import type {
    PublicSeasonDetail,
    PublicSeasonEvent,
    PublicSeasonTicket,
    SeoMetadata,
} from '@/types';

type Props = {
    season: PublicSeasonDetail;
    seo: SeoMetadata;
};

export default function SeasonShow({ season, seo }: Props) {
    const eventCountLabel =
        season.eventCount === 1
            ? '1 publiek event'
            : `${season.eventCount} publieke events`;

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker="Seizoen"
                title={season.name}
                description={`${formatSeasonDateRange(season.startsAt, season.endsAt)} · ${eventCountLabel}`}
                actions={[
                    {
                        label: 'Bekijk de events',
                        href: '#season-events',
                    },
                    {
                        label: 'Terug naar de agenda',
                        href: eventsIndex.url(),
                    },
                ]}
                media={{
                    src: '/images/dds/racing/indoor-track.jpg',
                    alt: 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
                    position: '56% center',
                }}
                separatorTone="paper"
                size="compact"
            />

            <main className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <div className="mx-auto grid w-full max-w-[86rem] gap-10 px-public-gutter py-16 sm:py-20 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-14 lg:py-24">
                    <section
                        id="season-events"
                        aria-labelledby="season-events-heading"
                        className="min-w-0 scroll-mt-24 lg:col-start-1 lg:row-start-1"
                    >
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                            Seizoensprogramma
                        </p>
                        <h2
                            id="season-events-heading"
                            className="mt-3 font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Events in dit seizoen.
                        </h2>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-signal-muted dark:text-night-400">
                            {season.ticket === null
                                ? 'Voor dit seizoen meld je je per event aan. Prijs en beschikbaarheid staan bij ieder event.'
                                : 'Het seizoensticket geeft toegang tot alle events hieronder. Je kunt iedere avond ook los boeken; de losse prijs en actuele aanmeldstatus staan per event vermeld.'}
                        </p>

                        <ul
                            aria-label={`Events in ${season.name}`}
                            className="mt-8 divide-y divide-paddock-rule border-y border-paddock-rule dark:divide-white/12 dark:border-white/12"
                        >
                            {season.events.map((event) => (
                                <li key={event.id}>
                                    <SeasonEventRow event={event} />
                                </li>
                            ))}
                        </ul>
                    </section>

                    <div className="lg:sticky lg:top-28 lg:col-start-2 lg:row-start-1">
                        {season.ticket === null ? (
                            <NoSeasonTicketCard />
                        ) : (
                            <SeasonTicketCard
                                eventCount={season.eventCount}
                                ticket={season.ticket}
                            />
                        )}
                    </div>
                </div>
            </main>
        </>
    );
}

function SeasonEventRow({ event }: { event: PublicSeasonEvent }) {
    const date = formatEventShortDate(event.startsAt);
    const registrationDetail = getEventRegistrationDetail(event);
    const registrationTooltipId = `season-event-registration-${event.id}`;
    const showsEventPrice = event.priceCents !== null;

    return (
        <Link
            href={eventShow(event.slug)}
            prefetch
            aria-describedby={
                event.status === 'cancelled' ? undefined : registrationTooltipId
            }
            className="group/row grid gap-5 py-6 transition-colors hover:bg-white/65 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset sm:grid-cols-[6rem_minmax(0,1fr)] sm:px-4 lg:grid-cols-[6rem_minmax(0,1fr)_9.5rem] lg:items-center dark:hover:bg-white/4"
        >
            <time
                dateTime={event.startsAt}
                aria-label={formatEventDate(event.startsAt)}
                className="flex items-baseline gap-2 sm:flex-col sm:items-center sm:gap-0 sm:border-r sm:border-paddock-rule sm:py-2 dark:sm:border-white/12"
            >
                <span className="font-public-display text-4xl leading-none font-semibold tracking-[-0.055em] text-dds-orange">
                    {date.day}
                </span>
                <span className="font-mono text-[0.68rem] font-semibold tracking-[0.09em] text-signal-muted uppercase dark:text-night-400">
                    {date.month} {date.year}
                </span>
                <span className="text-xs font-medium text-signal-muted capitalize dark:text-night-400">
                    {date.weekday}
                </span>
            </time>

            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span className="rounded-sm bg-dds-blue/8 px-2 py-1 font-mono tracking-[0.08em] text-dds-blue uppercase dark:bg-dds-cyan/10 dark:text-dds-cyan">
                        {eventTypeLabels[event.type]}
                    </span>
                </div>

                <h3 className="mt-3 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal transition-colors group-hover/row:text-dds-blue dark:text-white dark:group-hover/row:text-dds-cyan">
                    {event.title}
                </h3>
                <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-signal-muted dark:text-night-400">
                    <span className="inline-flex items-center gap-2">
                        <MapPin
                            aria-hidden="true"
                            className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan"
                        />
                        {formatEventLocation(
                            event.location.name,
                            event.location.city,
                        )}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <Clock3
                            aria-hidden="true"
                            className="size-4 text-dds-blue dark:text-dds-cyan"
                        />
                        {formatEventTimeRange(event.startsAt, event.endsAt)}
                    </span>
                    {showsEventPrice && (
                        <span className="inline-flex items-center gap-1.5">
                            <Ticket
                                aria-hidden="true"
                                className="size-4 text-dds-blue dark:text-dds-cyan"
                            />
                            {formatEventPrice(event.priceCents)}
                        </span>
                    )}
                </div>
            </div>

            <div className="flex items-center justify-between gap-4 border-t border-paddock-rule pt-4 sm:col-start-2 lg:col-start-auto lg:flex-col lg:items-stretch lg:justify-center lg:border-t-0 lg:border-l lg:px-4 lg:pt-0 lg:text-center dark:border-white/12">
                <div
                    data-testid="season-event-registration-status"
                    className="group/status relative w-full"
                >
                    <PublicEventRegistrationStatus
                        event={event}
                        className="lg:w-full"
                    />
                    {event.status !== 'cancelled' && (
                        <span
                            id={registrationTooltipId}
                            role="tooltip"
                            data-testid="season-event-registration-tooltip"
                            className="pointer-events-none invisible absolute right-0 bottom-[calc(100%+0.625rem)] z-30 w-max max-w-64 translate-y-1 rounded-sm bg-deep-signal px-3 py-2 text-left text-xs leading-5 font-medium text-white opacity-0 shadow-lg transition-[opacity,transform,visibility] duration-150 group-hover/status:visible group-hover/status:translate-y-0 group-hover/status:opacity-100 group-focus-visible/row:visible group-focus-visible/row:translate-y-0 group-focus-visible/row:opacity-100 motion-reduce:transition-none dark:bg-white dark:text-deep-signal"
                        >
                            {registrationDetail.note}
                        </span>
                    )}
                </div>
                <span className="inline-flex items-center gap-2 text-sm font-semibold text-dds-blue lg:justify-center dark:text-dds-cyan">
                    Bekijk event
                    <ArrowUpRight
                        aria-hidden="true"
                        className="size-4 transition-transform group-hover/row:translate-x-0.5 group-hover/row:-translate-y-0.5 motion-reduce:transition-none"
                    />
                </span>
            </div>
        </Link>
    );
}

function SeasonTicketCard({
    eventCount,
    ticket,
}: {
    eventCount: number;
    ticket: PublicSeasonTicket;
}) {
    const salesDate =
        ticket.salesState === 'coming_soon'
            ? ticket.salesOpensAt
            : ticket.salesClosesAt;
    const salesDateLabel =
        ticket.salesState === 'coming_soon' ? 'Verkoop opent' : 'Verkoop sluit';

    return (
        <aside
            aria-labelledby="season-ticket-heading"
            className="relative overflow-hidden rounded-sm border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900"
        >
            <span
                aria-hidden="true"
                className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
            />
            <div className="p-6 sm:p-7">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                            Seizoensticket
                        </p>
                        <h2
                            id="season-ticket-heading"
                            className="mt-3 font-public-display text-3xl font-semibold tracking-[-0.045em]"
                        >
                            {formatEventPrice(ticket.priceCents)}
                        </h2>
                    </div>
                    <SeasonTicketStatus salesState={ticket.salesState} />
                </div>

                <p className="mt-4 text-sm leading-6 text-signal-muted dark:text-night-400">
                    {ticket.copy ??
                        'Eén ticket voor alle events in dit seizoen.'}
                </p>

                {ticket.registrationUrl !== null ? (
                    <a
                        href={ticket.registrationUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-6 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-blue focus-visible:ring-offset-3 focus-visible:outline-none dark:focus-visible:ring-dds-cyan dark:focus-visible:ring-offset-night-900"
                    >
                        Koop seizoensticket
                        <ArrowUpRight aria-hidden="true" className="size-4" />
                        <span className="sr-only">
                            (opent in een nieuw tabblad)
                        </span>
                    </a>
                ) : ticket.salesState === 'available' ? (
                    <p className="mt-6 text-sm font-semibold text-signal-muted dark:text-night-400">
                        De verkooplink volgt.
                    </p>
                ) : null}
            </div>

            <dl className="divide-y divide-paddock-rule border-t border-paddock-rule bg-paddock px-5 dark:divide-white/12 dark:border-white/12 dark:bg-night-950">
                <SeasonTicketFact
                    icon={Ticket}
                    label="Inbegrepen"
                    value={`${eventCount} ${eventCount === 1 ? 'event' : 'events'}`}
                />
                <SeasonTicketFact
                    icon={Clock3}
                    label={salesDateLabel}
                    value={
                        salesDate === null
                            ? 'Niet vermeld'
                            : formatEventDate(salesDate)
                    }
                />
                <SeasonTicketFact
                    icon={Users}
                    label="Beschikbaar"
                    value={
                        ticket.capacity === null
                            ? 'Aantal volgt'
                            : `${ticket.capacity} tickets`
                    }
                />
            </dl>
        </aside>
    );
}

function NoSeasonTicketCard() {
    return (
        <aside
            aria-labelledby="season-registration-heading"
            className="rounded-sm border border-paddock-rule bg-white p-6 shadow-sm sm:p-7 dark:border-white/12 dark:bg-night-900"
        >
            <span className="flex size-10 items-center justify-center rounded-sm bg-dds-blue/8 text-dds-blue dark:bg-dds-cyan/10 dark:text-dds-cyan">
                <CalendarRange aria-hidden="true" className="size-5" />
            </span>
            <p className="mt-5 font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                Aanmelden
            </p>
            <h2
                id="season-registration-heading"
                className="mt-3 font-public-display text-2xl font-semibold tracking-[-0.035em]"
            >
                Per event aanmelden.
            </h2>
            <p className="mt-4 text-sm leading-6 text-signal-muted dark:text-night-400">
                Voor dit seizoen is geen seizoensticket beschikbaar. Open een
                event om de actuele aanmeldmogelijkheid te bekijken.
            </p>
        </aside>
    );
}

function SeasonTicketStatus({
    salesState,
}: Pick<PublicSeasonTicket, 'salesState'>) {
    return (
        <span
            className={cn(
                'inline-flex min-h-8 w-fit shrink-0 items-center rounded-md border px-3 py-1.5 text-xs leading-4 font-semibold',
                salesState === 'available' &&
                    'border-emerald-200 bg-emerald-50/70 text-emerald-700 dark:border-emerald-400/25 dark:bg-emerald-500/10 dark:text-emerald-300',
                salesState === 'coming_soon' &&
                    'border-sky-200 bg-sky-50/70 text-sky-800 dark:border-sky-400/25 dark:bg-sky-500/10 dark:text-sky-200',
                salesState === 'sold_out' &&
                    'border-orange-200 bg-orange-50/70 text-orange-800 dark:border-orange-400/25 dark:bg-orange-500/10 dark:text-orange-200',
                salesState === 'closed' &&
                    'dark:text-night-300 border-slate-200 bg-slate-50/80 text-slate-700 dark:border-white/15 dark:bg-white/6',
            )}
        >
            {seasonTicketSalesLabels[salesState]}
        </span>
    );
}

function SeasonTicketFact({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-start gap-3 py-4">
            <Icon
                aria-hidden="true"
                className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan"
            />
            <div className="min-w-0">
                <dt className="font-mono text-[0.62rem] font-semibold tracking-[0.1em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </dt>
                <dd className="mt-1 text-sm font-semibold break-words">
                    {value}
                </dd>
            </div>
        </div>
    );
}
