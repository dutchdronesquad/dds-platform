import { Head } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    MapPin,
    Newspaper,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';

const contentAreas = [
    {
        id: 'events',
        title: 'Events',
        description: 'Publiceer clubdagen, demo-vluchten en trainingsmomenten.',
        status: 'Binnenkort uitbreidbaar',
        icon: CalendarDays,
    },
    {
        id: 'projects',
        title: 'Projecten',
        description: 'Bundel bouwverslagen, missies en community-initiatieven.',
        status: 'Klaar voor structuur',
        icon: ClipboardList,
    },
    {
        id: 'news',
        title: 'Nieuws',
        description: 'Maak updates klaar voor publicatie op de publieke site.',
        status: 'Publicatie voorbereid',
        icon: Newspaper,
    },
    {
        id: 'locations',
        title: 'Locaties',
        description:
            'Bereid vliegplekken, partners en praktische bezoekersinformatie voor.',
        status: 'Locaties voorbereid',
        icon: MapPin,
    },
];

const operations = [
    {
        title: 'Toegang',
        description: 'Admins en editors beheren de eerste platformcontent.',
        icon: ShieldCheck,
    },
    {
        title: 'Community',
        description:
            'Leden, partners en organisatoren krijgen later eigen workflows.',
        icon: Users,
    },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Beheer" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
                <section className="rounded-lg border border-sidebar-border/70 bg-white p-6 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-3xl">
                            <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                                DDS beheer
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-neutral-950 sm:text-3xl dark:text-white">
                                Platformbasis voor Dutch Drone Squad
                            </h1>
                            <p className="mt-3 text-sm leading-6 text-neutral-600 sm:text-base dark:text-neutral-400">
                                Gebruik dit startpunt om publieke content,
                                community-informatie en beheerprocessen verder
                                op te bouwen. De navigatie toont alvast de
                                hoofdgebieden van het platform.
                            </p>
                        </div>
                        <div className="rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <span className="font-medium text-neutral-950 dark:text-white">
                                Fase:
                            </span>{' '}
                            <span className="text-neutral-600 dark:text-neutral-400">
                                basis ingericht
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    id="operations"
                    className="grid gap-4 md:grid-cols-2"
                    aria-label="Beheerstatus"
                >
                    {operations.map((item) => (
                        <article
                            key={item.title}
                            className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950"
                        >
                            <div className="flex items-start gap-3">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    <item.icon className="size-5" />
                                </span>
                                <div>
                                    <h2 className="font-semibold text-neutral-950 dark:text-white">
                                        {item.title}
                                    </h2>
                                    <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                        {item.description}
                                    </p>
                                </div>
                            </div>
                        </article>
                    ))}
                </section>

                <section
                    className="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                    aria-label="Contentgebieden"
                >
                    {contentAreas.map((area) => (
                        <article
                            id={area.id}
                            key={area.id}
                            className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs transition-colors hover:border-red-200 dark:border-sidebar-border dark:bg-neutral-950 dark:hover:border-red-500/40"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="flex size-10 items-center justify-center rounded-md bg-neutral-100 text-neutral-800 dark:bg-neutral-900 dark:text-neutral-100">
                                    <area.icon className="size-5" />
                                </span>
                                <span className="rounded-full border border-neutral-200 px-2.5 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-800 dark:text-neutral-400">
                                    Binnenkort
                                </span>
                            </div>
                            <h2 className="mt-4 font-semibold text-neutral-950 dark:text-white">
                                {area.title}
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                {area.description}
                            </p>
                            <p className="mt-4 text-xs font-medium text-red-700 dark:text-red-300">
                                {area.status}
                            </p>
                        </article>
                    ))}
                </section>

                <section
                    id="house-rules"
                    className="rounded-lg border border-sidebar-border/70 bg-white p-6 shadow-xs dark:border-sidebar-border dark:bg-neutral-950"
                >
                    <h2 className="text-lg font-semibold text-neutral-950 dark:text-white">
                        Eerste beheerconventies
                    </h2>
                    <div className="mt-4 grid gap-3 text-sm text-neutral-600 md:grid-cols-3 dark:text-neutral-400">
                        <p>
                            Publieke content krijgt duidelijke titels,
                            introteksten en vervolgstappen.
                        </p>
                        <p>
                            Beheerpagina's blijven compact, scanbaar en gericht
                            op herhaald gebruik.
                        </p>
                        <p>
                            Rollen en toegang blijven gekoppeld aan de
                            admin/editor basis.
                        </p>
                    </div>
                </section>
            </div>
        </>
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
