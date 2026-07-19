import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    Clock3,
    FilePenLine,
    FolderKanban,
    Handshake,
    Images,
    MapPin,
    Newspaper,
    Route as RouteIcon,
    Tags,
    Users,
} from 'lucide-react';
import { index as eventsIndex } from '@/actions/App/Http/Controllers/Admin/EventController';
import { index as seasonsIndex } from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';
import { index as redirectsIndex } from '@/routes/redirects';

type ResourceId =
    | 'events'
    | 'projects'
    | 'articles'
    | 'locations'
    | 'partners'
    | 'media'
    | 'users'
    | 'redirects';

type ManagementAreaId = ResourceId | 'seasons';

type Props = {
    resources: Record<ResourceId, boolean>;
    stats: {
        drafts: number;
        upcomingEvents: number;
        recentActivity: number;
    };
    isEmpty: boolean;
    management?: {
        canManageSeasons: boolean;
    };
};

type ManagementArea = {
    id: ManagementAreaId;
    title: string;
    description: string;
    icon: typeof CalendarDays;
    href?: NonNullable<InertiaLinkProps['href']>;
};

const managementAreas: ManagementArea[] = [
    {
        id: 'events',
        title: 'Events',
        description: 'Plan events, bewaak inschrijvingen en publicatiestatus.',
        icon: CalendarDays,
        href: eventsIndex(),
    },
    {
        id: 'seasons',
        title: 'Seizoenen',
        description: 'Bundel events in een heldere kalenderperiode.',
        icon: Tags,
        href: seasonsIndex(),
    },
    {
        id: 'redirects',
        title: 'Redirects',
        description: "Controleer oude URL's, doelen en gebruik.",
        icon: RouteIcon,
        href: redirectsIndex(),
    },
    {
        id: 'projects',
        title: 'Projecten',
        description: 'Beheer bouwverslagen, missies en communityprojecten.',
        icon: FolderKanban,
    },
    {
        id: 'articles',
        title: 'Artikelen',
        description: 'Schrijf nieuws en raceverslagen voor de publieke site.',
        icon: Newspaper,
    },
    {
        id: 'locations',
        title: 'Locaties',
        description: 'Houd vliegplekken en praktische locatiegegevens actueel.',
        icon: MapPin,
    },
    {
        id: 'partners',
        title: 'Partners',
        description: 'Onderhoud partnerinformatie en zichtbaarheid.',
        icon: Handshake,
    },
    {
        id: 'media',
        title: 'Media',
        description: 'Vind en hergebruik afbeeldingen en documenten.',
        icon: Images,
    },
    {
        id: 'users',
        title: 'Gebruikers',
        description: 'Beheer accounts, rollen en toegang tot het platform.',
        icon: Users,
    },
];

const statItems = [
    {
        id: 'drafts',
        label: 'Concepten',
        description: 'Nog niet gepubliceerd',
        icon: FilePenLine,
    },
    {
        id: 'upcomingEvents',
        label: 'Komende events',
        description: 'Nog te organiseren',
        icon: CalendarDays,
    },
    {
        id: 'recentActivity',
        label: 'Recent toegevoegd',
        description: 'In de laatste 7 dagen',
        icon: Clock3,
    },
] satisfies Array<{
    id: keyof Props['stats'];
    label: string;
    description: string;
    icon: typeof CalendarDays;
}>;

export default function Dashboard({
    resources,
    stats,
    isEmpty,
    management,
}: Props) {
    const visibleAreas = managementAreas.filter((area) =>
        area.id === 'seasons'
            ? management?.canManageSeasons
            : resources[area.id],
    );
    const availableAreas = visibleAreas.filter((area) => area.href);
    const plannedAreas = visibleAreas.filter((area) => !area.href);

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
                                    Bekijk wat aandacht vraagt en open direct
                                    het onderdeel waarin je wilt werken.
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

                        <dl
                            aria-label="Operationeel overzicht"
                            className="grid border-t border-white/10 sm:grid-cols-3 sm:divide-x sm:divide-white/10"
                        >
                            {statItems.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center gap-4 border-b border-white/10 px-5 py-4 last:border-b-0 sm:border-b-0 sm:px-7 sm:py-5"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="flex size-9 shrink-0 items-center justify-center rounded-md bg-signal-500/15 text-signal-300"
                                    >
                                        <item.icon className="size-4.5" />
                                    </span>
                                    <div className="min-w-0">
                                        <div className="flex items-baseline gap-2">
                                            <dt className="order-2 truncate text-sm font-medium text-neutral-200">
                                                {item.label}
                                            </dt>
                                            <dd className="order-1 text-2xl font-semibold tabular-nums">
                                                {stats[item.id]}
                                            </dd>
                                        </div>
                                        <p className="truncate text-xs text-neutral-500">
                                            {item.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </dl>
                    </section>

                    {isEmpty && (
                        <section className="border-l-2 border-flight-500 bg-flight-50 px-4 py-3 dark:bg-flight-500/5">
                            <h2 className="text-sm font-semibold text-neutral-950 dark:text-white">
                                Klaar voor de eerste content
                            </h2>
                            <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Begin bij een beschikbaar onderdeel hieronder;
                                nieuwe beheerschermen verschijnen hier zodra ze
                                klaar zijn.
                            </p>
                        </section>
                    )}

                    <section aria-labelledby="management-areas-title">
                        <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2
                                    id="management-areas-title"
                                    className="text-xl font-semibold tracking-tight text-neutral-950 dark:text-white"
                                >
                                    Beheeromgeving
                                </h2>
                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                    Alleen de onderdelen waarvoor je toegang
                                    hebt worden getoond.
                                </p>
                            </div>
                            <span className="text-xs font-medium text-neutral-500 tabular-nums">
                                {availableAreas.length} beschikbaar
                            </span>
                        </div>

                        <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(17rem,0.85fr)]">
                            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-950">
                                {availableAreas.map((area) => (
                                    <AvailableAreaRow
                                        key={area.id}
                                        area={area}
                                    />
                                ))}
                            </div>

                            {plannedAreas.length > 0 && (
                                <aside className="rounded-xl border border-neutral-200/80 bg-neutral-50/70 p-4 sm:p-5 dark:border-neutral-800 dark:bg-neutral-900/40">
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            In voorbereiding
                                        </h3>
                                        <span className="rounded-full bg-neutral-200/70 px-2 py-0.5 text-xs font-medium text-neutral-600 tabular-nums dark:bg-neutral-800 dark:text-neutral-400">
                                            {plannedAreas.length}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-500">
                                        Deze onderdelen worden later actief.
                                    </p>

                                    <ul className="mt-4 divide-y divide-neutral-200/80 dark:divide-neutral-800">
                                        {plannedAreas.map((area) => (
                                            <li
                                                key={area.id}
                                                className="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
                                            >
                                                <area.icon className="size-4 shrink-0 text-neutral-400 dark:text-neutral-500" />
                                                <span className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                    {area.title}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </aside>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

function AvailableAreaRow({ area }: { area: ManagementArea }) {
    if (!area.href) {
        return null;
    }

    return (
        <Link
            href={area.href}
            prefetch
            className="group grid grid-cols-[auto_minmax(0,1fr)] items-start gap-x-3 gap-y-2 border-b border-neutral-200 p-4 transition-colors last:border-b-0 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-signal-500 focus-visible:outline-none focus-visible:ring-inset active:bg-neutral-100 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center sm:gap-4 sm:p-5 dark:border-neutral-800 dark:hover:bg-neutral-900/70 dark:focus-visible:bg-neutral-900/70 dark:active:bg-neutral-900"
        >
            <span className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-signal-50 text-signal-700 transition-colors group-hover:bg-signal-100 dark:bg-signal-500/10 dark:text-signal-300 dark:group-hover:bg-signal-500/15">
                <area.icon className="size-5" />
            </span>
            <div className="min-w-0 flex-1">
                <h3 className="font-semibold text-neutral-950 dark:text-white">
                    {area.title}
                </h3>
                <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                    {area.description}
                </p>
            </div>
            <span className="col-start-2 flex shrink-0 items-center gap-1 text-sm font-semibold text-signal-700 sm:col-start-auto dark:text-signal-300">
                Open beheer
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
