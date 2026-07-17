import { Head, Link } from '@inertiajs/react';
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
    Users,
} from 'lucide-react';
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

type Props = {
    resources: Record<ResourceId, boolean>;
    stats: {
        drafts: number;
        upcomingEvents: number;
        recentActivity: number;
    };
    isEmpty: boolean;
};

const managementAreas = [
    {
        id: 'events',
        title: 'Events',
        description: 'Plan events, bewaak inschrijvingen en publicatiestatus.',
        icon: CalendarDays,
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
    {
        id: 'redirects',
        title: 'Redirects',
        description: "Controleer oude URL's, doelen en gebruik.",
        icon: RouteIcon,
    },
] satisfies Array<{
    id: ResourceId;
    title: string;
    description: string;
    icon: typeof CalendarDays;
}>;

const statCards = [
    {
        id: 'drafts',
        label: 'Concepten',
        description: 'Events en artikelen die nog niet gepubliceerd zijn.',
        icon: FilePenLine,
    },
    {
        id: 'upcomingEvents',
        label: 'Komende events',
        description: 'Niet-geannuleerde events die nog moeten plaatsvinden.',
        icon: CalendarDays,
    },
    {
        id: 'recentActivity',
        label: 'Recent toegevoegd',
        description:
            'Beheerrecords die in de afgelopen zeven dagen zijn aangemaakt.',
        icon: Clock3,
    },
] satisfies Array<{
    id: keyof Props['stats'];
    label: string;
    description: string;
    icon: typeof CalendarDays;
}>;

export default function Dashboard({ resources, stats, isEmpty }: Props) {
    const visibleAreas = managementAreas.filter((area) => resources[area.id]);

    return (
        <>
            <Head title="Beheer" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
                <section className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs sm:p-6 dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-3xl">
                            <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                                DDS beheer
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-neutral-950 sm:text-3xl dark:text-white">
                                Managementoverzicht
                            </h1>
                            <p className="mt-3 text-sm leading-6 text-neutral-600 sm:text-base dark:text-neutral-400">
                                Ga direct naar een beheergebied of controleer
                                wat vandaag aandacht nodig heeft. Je ziet alleen
                                onderdelen waarvoor je toegang hebt.
                            </p>
                        </div>

                        <Button asChild variant="outline">
                            <Link href={home()} prefetch>
                                Bekijk publieke site
                                <ArrowRight data-icon="inline-end" />
                            </Link>
                        </Button>
                    </div>
                </section>

                <section
                    aria-label="Operationeel overzicht"
                    className="grid gap-4 sm:grid-cols-3"
                >
                    {statCards.map((card) => (
                        <article
                            key={card.id}
                            className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                        {card.label}
                                    </p>
                                    <p className="mt-2 text-3xl font-semibold text-neutral-950 tabular-nums dark:text-white">
                                        {stats[card.id]}
                                    </p>
                                </div>
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    <card.icon className="size-5" />
                                </span>
                            </div>
                            <p className="mt-4 text-xs leading-5 text-neutral-500 dark:text-neutral-500">
                                {card.description}
                            </p>
                        </article>
                    ))}
                </section>

                {isEmpty && (
                    <section className="rounded-lg border border-dashed border-red-200 bg-red-50/60 p-5 sm:p-6 dark:border-red-500/30 dark:bg-red-500/5">
                        <div className="max-w-2xl">
                            <h2 className="font-semibold text-neutral-950 dark:text-white">
                                Klaar voor de eerste content
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Er staat nog niets in de beheergebieden die jij
                                kunt bekijken. Gebruik de beschikbare onderdelen
                                hieronder; nieuwe beheerschermen verschijnen
                                hier zodra ze gereed zijn.
                            </p>
                        </div>
                    </section>
                )}

                <section aria-labelledby="management-areas-title">
                    <div className="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <h2
                                id="management-areas-title"
                                className="text-lg font-semibold text-neutral-950 dark:text-white"
                            >
                                Beheergebieden
                            </h2>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                Kies het onderdeel waarin je wilt werken.
                            </p>
                        </div>
                        <span className="text-xs font-medium text-neutral-500 tabular-nums dark:text-neutral-500">
                            {visibleAreas.length} zichtbaar
                        </span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {visibleAreas.map((area) => (
                            <ManagementAreaCard key={area.id} area={area} />
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

function ManagementAreaCard({
    area,
}: {
    area: (typeof managementAreas)[number];
}) {
    const content = (
        <>
            <div className="flex items-center justify-between gap-3">
                <span className="flex size-10 items-center justify-center rounded-md bg-neutral-100 text-neutral-800 dark:bg-neutral-900 dark:text-neutral-100">
                    <area.icon className="size-5" />
                </span>
                <span className="rounded-full border border-neutral-200 px-2.5 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-800 dark:text-neutral-400">
                    {area.id === 'redirects'
                        ? 'Beschikbaar'
                        : 'In voorbereiding'}
                </span>
            </div>
            <h3 className="mt-4 font-semibold text-neutral-950 dark:text-white">
                {area.title}
            </h3>
            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                {area.description}
            </p>
            <div className="mt-5 flex items-center gap-1 text-xs font-semibold text-red-700 dark:text-red-300">
                {area.id === 'redirects' ? (
                    <>
                        Open beheer
                        <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                    </>
                ) : (
                    'Nog niet beschikbaar'
                )}
            </div>
        </>
    );

    if (area.id === 'redirects') {
        return (
            <Link
                href={redirectsIndex()}
                prefetch
                className="group rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs transition-colors hover:border-red-200 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:outline-none dark:border-sidebar-border dark:bg-neutral-950 dark:hover:border-red-500/40"
            >
                {content}
            </Link>
        );
    }

    return (
        <article className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
            {content}
        </article>
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
