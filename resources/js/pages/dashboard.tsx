import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    Clock3,
    FilePenLine,
    Handshake,
    Images,
    MapPin,
    Newspaper,
    Tags,
    UserRound,
    Users,
} from 'lucide-react';
import {
    create as createEvent,
    edit as editEvent,
    index as eventsIndex,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    create as createSeason,
    edit as editSeason,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import type { AdminStatus } from '@/components/admin/admin-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';

type ResourceId =
    | 'events'
    | 'articles'
    | 'locations'
    | 'partners'
    | 'media'
    | 'users'
    | 'roles'
    | 'redirects';

type Props = {
    openPoints: {
        closedRegistrationEvents: number;
        draftEvents: number;
        expiredRegistrationEvents: number;
        missingContentEvents: number;
        missingCoverEvents: number;
        unassignedUpcomingEvents: number;
    };
    capabilities: {
        createEvents: boolean;
        createSeasons: boolean;
        viewUsers: boolean;
    };
    isEmpty: boolean;
    nextEvent: {
        id: number;
        location: {
            city: string;
            name: string;
        };
        registrationStatus: RegistrationStatus;
        startsAt: string;
        status: Extract<AdminStatus, 'cancelled' | 'draft' | 'published'>;
        title: string;
    } | null;
    recentChanges: RecentChange[];
    resources: Record<ResourceId, boolean>;
    stats: {
        draftEvents: number;
        recentChanges: number;
        upcomingEvents: number;
    };
};

type RegistrationStatus = 'closed' | 'full' | 'open' | 'waitlist';

type RecentChangeBase = {
    id: number;
    title: string;
    updatedAt: string;
    updatedBy: {
        id: number;
        name: string;
    } | null;
};

type RecentChange = RecentChangeBase &
    ({ kind: 'event'; slug: null } | { kind: 'season'; slug: string });

type DashboardLink = NonNullable<InertiaLinkProps['href']>;

type DashboardAction = {
    description: string;
    href: DashboardLink;
    icon: typeof CalendarDays;
    title: string;
};

const plannedAreas = [
    { id: 'articles', title: 'Artikelen', icon: Newspaper },
    { id: 'locations', title: 'Locaties', icon: MapPin },
    { id: 'partners', title: 'Partners', icon: Handshake },
    { id: 'media', title: 'Media', icon: Images },
] satisfies Array<{
    id: ResourceId;
    title: string;
    icon: typeof CalendarDays;
}>;

const registrationLabels: Record<RegistrationStatus, string> = {
    closed: 'Registratie gesloten',
    full: 'Registratie vol',
    open: 'Registratie open',
    waitlist: 'Wachtlijst actief',
};

const dateTimeFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

export default function Dashboard({
    capabilities,
    isEmpty,
    nextEvent,
    openPoints,
    recentChanges,
    resources,
    stats,
}: Props) {
    const openPointItems = [
        {
            count: openPoints.draftEvents,
            description:
                'Controleer de inhoud en publiceer wanneer alles klaarstaat.',
            href: eventsIndex({ query: { status: ['draft'] } }),
            icon: FilePenLine,
            testId: 'open-point-draft-events',
            title: 'Conceptevents',
        },
        {
            count: openPoints.expiredRegistrationEvents,
            description:
                'Sluit de inschrijving of pas de inschrijfdeadline aan.',
            href: eventsIndex({
                query: { situation: ['expired_registration'] },
            }),
            icon: Clock3,
            testId: 'open-point-expired-registration',
            title: 'Inschrijving open na de deadline',
        },
        {
            count: openPoints.closedRegistrationEvents,
            description:
                'Controleer of de inschrijving bewust gesloten blijft.',
            href: eventsIndex({
                query: { situation: ['closed_registration'] },
            }),
            icon: CircleAlert,
            testId: 'open-point-closed-registration',
            title: 'Komende events met gesloten registratie',
        },
        {
            count: openPoints.unassignedUpcomingEvents,
            description:
                'Koppel een seizoen wanneer het event bij de kalender hoort.',
            href: eventsIndex({ query: { situation: ['without_season'] } }),
            icon: Tags,
            testId: 'open-point-without-season',
            title: 'Komende events zonder seizoen',
        },
        {
            count: openPoints.missingContentEvents,
            description:
                'Voeg een omschrijving toe voordat het event wordt gepubliceerd.',
            href: eventsIndex({ query: { situation: ['without_content'] } }),
            icon: Newspaper,
            testId: 'open-point-without-content',
            title: 'Komende events zonder inhoud',
        },
        {
            count: openPoints.missingCoverEvents,
            description:
                'Voeg een omslagafbeelding toe voor een compleet event.',
            href: eventsIndex({ query: { situation: ['without_cover'] } }),
            icon: Images,
            testId: 'open-point-without-cover',
            title: 'Komende events zonder omslagafbeelding',
        },
    ].filter((item) => item.count > 0);

    const quickActions: DashboardAction[] = [
        ...(capabilities.createEvents
            ? [
                  {
                      title: 'Nieuw event',
                      description: 'Plan datum, locatie en inschrijving.',
                      href: createEvent(),
                      icon: CalendarDays,
                  },
              ]
            : []),
        ...(capabilities.createSeasons
            ? [
                  {
                      title: 'Nieuw seizoen',
                      description: 'Bundel events in een kalenderperiode.',
                      href: createSeason(),
                      icon: Tags,
                  },
              ]
            : []),
        ...(capabilities.viewUsers
            ? [
                  {
                      title: 'Gebruikers beheren',
                      description: 'Controleer accounts, rollen en toegang.',
                      href: usersIndex(),
                      icon: Users,
                  },
              ]
            : []),
    ];

    const visiblePlannedAreas = plannedAreas.filter(
        (area) => resources[area.id],
    );

    return (
        <>
            <Head title="Beheer" />

            <div className="h-full flex-1 overflow-x-hidden px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
                <div className="mx-auto flex w-full max-w-7xl flex-col gap-8">
                    <section className="overflow-hidden rounded-xl border border-neutral-800 bg-neutral-950 text-white shadow-sm dark:border-neutral-700">
                        <div className="flex flex-col gap-6 px-5 py-6 sm:px-7 sm:py-8 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <p className="text-xs font-semibold tracking-[0.18em] text-signal-300 uppercase">
                                    Dutch Drone Squad
                                </p>
                                <h1 className="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                                    Beheeroverzicht
                                </h1>
                                <p className="mt-3 max-w-2xl text-sm leading-6 text-neutral-400 sm:text-base">
                                    Zie welke punten nog openstaan, pak
                                    veelgebruikte acties op en ga verder waar
                                    het team stopte.
                                </p>
                            </div>

                            <Button
                                asChild
                                variant="outline"
                                className="w-full border-white/20 bg-white/5 text-white shadow-none hover:bg-white/10 hover:text-white focus-visible:ring-signal-400/60 sm:w-fit"
                            >
                                <Link href={home()} prefetch>
                                    Publieke site
                                    <ArrowRight data-icon="inline-end" />
                                </Link>
                            </Button>
                        </div>

                        <div
                            aria-label="Operationeel overzicht"
                            className="grid border-t border-white/10 sm:grid-cols-3 sm:divide-x sm:divide-white/10"
                        >
                            <StatLink
                                testId="dashboard-stat-drafts"
                                href={eventsIndex({
                                    query: { status: ['draft'] },
                                })}
                                icon={FilePenLine}
                                label="Conceptevents"
                                value={stats.draftEvents}
                                description="Wachten op controle of publicatie"
                            />
                            <StatLink
                                testId="dashboard-stat-upcoming"
                                href={eventsIndex()}
                                icon={CalendarDays}
                                label="Komende events"
                                value={stats.upcomingEvents}
                                description={
                                    nextEvent
                                        ? `${dateTimeFormatter.format(new Date(nextEvent.startsAt))} · ${nextEvent.title}`
                                        : 'Nog geen event gepland'
                                }
                            />
                            <a
                                href="#recent-changes"
                                data-testid="dashboard-stat-recent"
                                className="group flex items-center gap-4 border-b border-white/10 px-5 py-4 transition-colors last:border-b-0 hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-signal-400 focus-visible:outline-none focus-visible:ring-inset sm:border-b-0 sm:px-7 sm:py-5"
                            >
                                <StatContent
                                    icon={Clock3}
                                    label="Recent gewijzigd"
                                    value={stats.recentChanges}
                                    description="Events en seizoenen in de laatste 7 dagen"
                                />
                            </a>
                        </div>
                    </section>

                    <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.8fr)]">
                        <section aria-labelledby="open-points-title">
                            <SectionHeading
                                id="open-points-title"
                                title="Open punten"
                                description="Punten die je vanuit het dashboard kunt oppakken."
                            />

                            <div className="mt-4 overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
                                {openPointItems.length > 0 ? (
                                    openPointItems.map((item) => (
                                        <Link
                                            key={item.title}
                                            href={item.href}
                                            prefetch
                                            data-testid={item.testId}
                                            className="group grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-neutral-200 p-4 transition-colors last:border-b-0 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none focus-visible:ring-inset sm:gap-4 sm:p-5 dark:border-neutral-800 dark:hover:bg-neutral-900/70 dark:focus-visible:bg-neutral-900/70"
                                        >
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                                <item.icon className="size-4.5" />
                                            </span>
                                            <span className="min-w-0">
                                                <span className="block font-semibold text-neutral-950 dark:text-white">
                                                    {item.title}
                                                </span>
                                                <span className="mt-1 block text-sm leading-5 text-neutral-500 dark:text-neutral-400">
                                                    {item.description}
                                                </span>
                                            </span>
                                            <span className="flex items-center gap-2">
                                                <Badge
                                                    variant="secondary"
                                                    className="min-w-7 tabular-nums"
                                                >
                                                    {item.count}
                                                </Badge>
                                                <ArrowRight className="hidden size-4 text-neutral-400 transition-transform group-hover:translate-x-0.5 sm:block" />
                                            </span>
                                        </Link>
                                    ))
                                ) : (
                                    <div className="flex items-start gap-3 p-5 sm:p-6">
                                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            <CheckCircle2 className="size-4.5" />
                                        </span>
                                        <div>
                                            <p className="font-semibold text-neutral-950 dark:text-white">
                                                Geen open punten
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                                {isEmpty
                                                    ? 'Maak een eerste event aan om de planning te starten.'
                                                    : 'Concepten, registraties, inhoud, afbeeldingen en seizoenskoppelingen zijn op orde.'}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </section>

                        <aside className="grid gap-6">
                            <section aria-labelledby="quick-actions-title">
                                <SectionHeading
                                    id="quick-actions-title"
                                    title="Snel aan de slag"
                                    description="Veelgebruikte beheeracties."
                                />
                                <div className="mt-4 grid gap-2">
                                    {quickActions.map((action) => (
                                        <Link
                                            key={action.title}
                                            href={action.href}
                                            prefetch
                                            data-testid="quick-action"
                                            className="group flex items-center gap-3 rounded-lg border border-neutral-200 bg-white p-3.5 shadow-xs transition-colors hover:border-signal-300 hover:bg-signal-50/40 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-signal-700 dark:hover:bg-signal-500/5"
                                        >
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-signal-50 text-signal-700 dark:bg-signal-500/10 dark:text-signal-300">
                                                <action.icon className="size-4.5" />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block text-sm font-semibold text-neutral-950 dark:text-white">
                                                    {action.title}
                                                </span>
                                                <span className="mt-0.5 block truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                    {action.description}
                                                </span>
                                            </span>
                                            <ArrowRight className="size-4 text-neutral-400 transition-transform group-hover:translate-x-0.5" />
                                        </Link>
                                    ))}
                                </div>
                            </section>

                            {nextEvent && (
                                <section
                                    aria-labelledby="next-event-title"
                                    data-testid="next-event"
                                    className="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs sm:p-5 dark:border-neutral-800 dark:bg-neutral-950"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase">
                                            Eerstvolgend event
                                        </p>
                                        <CalendarClock className="size-4 text-signal-600 dark:text-signal-300" />
                                    </div>
                                    <Link
                                        href={editEvent(nextEvent.id)}
                                        id="next-event-title"
                                        className="mt-3 block text-base font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                                    >
                                        {nextEvent.title}
                                    </Link>
                                    <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                        {dateTimeFormatter.format(
                                            new Date(nextEvent.startsAt),
                                        )}
                                    </p>
                                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        {nextEvent.location.name} ·{' '}
                                        {nextEvent.location.city}
                                    </p>
                                    <div className="mt-4 flex flex-wrap items-center gap-2">
                                        <AdminStatusBadge
                                            status={nextEvent.status}
                                        />
                                        <Badge variant="outline">
                                            {
                                                registrationLabels[
                                                    nextEvent.registrationStatus
                                                ]
                                            }
                                        </Badge>
                                    </div>
                                </section>
                            )}
                        </aside>
                    </div>

                    <section
                        id="recent-changes"
                        aria-labelledby="recent-changes-title"
                        className="scroll-mt-6"
                    >
                        <SectionHeading
                            id="recent-changes-title"
                            title="Recente wijzigingen"
                            description="De laatste aanpassingen aan events en seizoenen, inclusief de verantwoordelijke beheerder."
                        />

                        <div className="mt-4 overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
                            {recentChanges.length > 0 ? (
                                recentChanges.map((change) => (
                                    <RecentChangeRow
                                        key={`${change.kind}-${change.id}`}
                                        change={change}
                                    />
                                ))
                            ) : (
                                <div className="flex items-start gap-3 p-5 sm:p-6">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                        <Clock3 className="size-4.5" />
                                    </span>
                                    <div>
                                        <p className="font-semibold text-neutral-950 dark:text-white">
                                            Nog geen wijzigingen
                                        </p>
                                        <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                            Nieuwe en bijgewerkte events en
                                            seizoenen verschijnen hier.
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </section>

                    {visiblePlannedAreas.length > 0 && (
                        <aside className="flex flex-col gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                            <div>
                                <p className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                    In voorbereiding
                                </p>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    Deze beheeronderdelen worden later actief.
                                </p>
                            </div>
                            <ul className="flex flex-wrap gap-2">
                                {visiblePlannedAreas.map((area) => (
                                    <li
                                        key={area.id}
                                        className="flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400"
                                    >
                                        <area.icon className="size-3.5" />
                                        {area.title}
                                    </li>
                                ))}
                            </ul>
                        </aside>
                    )}
                </div>
            </div>
        </>
    );
}

function StatLink({
    description,
    href,
    icon,
    label,
    testId,
    value,
}: {
    description: string;
    href: DashboardLink;
    icon: typeof CalendarDays;
    label: string;
    testId: string;
    value: number;
}) {
    return (
        <Link
            href={href}
            prefetch
            data-testid={testId}
            className="group flex items-center gap-4 border-b border-white/10 px-5 py-4 transition-colors last:border-b-0 hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-signal-400 focus-visible:outline-none focus-visible:ring-inset sm:border-b-0 sm:px-7 sm:py-5"
        >
            <StatContent
                description={description}
                icon={icon}
                label={label}
                value={value}
            />
        </Link>
    );
}

function StatContent({
    description,
    icon: Icon,
    label,
    value,
}: {
    description: string;
    icon: typeof CalendarDays;
    label: string;
    value: number;
}) {
    return (
        <>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-signal-500/15 text-signal-300 transition-colors group-hover:bg-signal-500/20">
                <Icon className="size-4.5" />
            </span>
            <span className="min-w-0">
                <span className="flex items-baseline gap-2">
                    <span className="text-2xl font-semibold tabular-nums">
                        {value}
                    </span>
                    <span className="truncate text-sm font-medium text-neutral-200">
                        {label}
                    </span>
                </span>
                <span className="block truncate text-xs text-neutral-500">
                    {description}
                </span>
            </span>
        </>
    );
}

function SectionHeading({
    description,
    id,
    title,
}: {
    description: string;
    id: string;
    title: string;
}) {
    return (
        <div>
            <h2
                id={id}
                className="text-xl font-semibold tracking-tight text-neutral-950 dark:text-white"
            >
                {title}
            </h2>
            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                {description}
            </p>
        </div>
    );
}

function RecentChangeRow({ change }: { change: RecentChange }) {
    const href =
        change.kind === 'event'
            ? editEvent(change.id)
            : editSeason(change.slug);
    const label = change.kind === 'event' ? 'Event' : 'Seizoen';

    return (
        <Link
            href={href}
            prefetch
            data-testid="recent-change"
            className="group grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-2 border-b border-neutral-200 p-4 transition-colors last:border-b-0 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none focus-visible:ring-inset sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:gap-4 sm:p-5 dark:border-neutral-800 dark:hover:bg-neutral-900/70 dark:focus-visible:bg-neutral-900/70"
        >
            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                {change.kind === 'event' ? (
                    <CalendarDays className="size-4.5" />
                ) : (
                    <Tags className="size-4.5" />
                )}
            </span>
            <span className="min-w-0">
                <span className="flex min-w-0 flex-wrap items-center gap-2">
                    <span className="truncate font-semibold text-neutral-950 group-hover:text-signal-700 dark:text-white dark:group-hover:text-signal-300">
                        {change.title}
                    </span>
                    <Badge variant="secondary">{label}</Badge>
                </span>
                <span className="mt-1 flex items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                    <UserRound className="size-3.5 shrink-0" />
                    <span className="truncate">
                        {change.updatedBy?.name ?? 'Systeem / import'}
                    </span>
                </span>
            </span>
            <span className="col-start-2 flex items-center gap-1.5 text-xs text-neutral-500 tabular-nums sm:col-start-auto">
                <time dateTime={change.updatedAt}>
                    {dateTimeFormatter.format(new Date(change.updatedAt))}
                </time>
                <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
            </span>
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Beheer',
            href: dashboard(),
        },
    ],
};
