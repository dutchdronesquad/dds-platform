import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    MapPin,
    Newspaper,
    Users,
} from 'lucide-react';
import { dashboard, login } from '@/routes';

const publicSections = [
    {
        id: 'events',
        title: 'Events',
        description:
            'Clubdagen, demo-vluchten en trainingen krijgen hier straks een vaste plek.',
        icon: CalendarDays,
    },
    {
        id: 'projects',
        title: 'Projecten',
        description:
            'Bouwverslagen, missies en experimenten worden vindbaar voor de community.',
        icon: ClipboardList,
    },
    {
        id: 'news',
        title: 'Nieuws',
        description:
            'Updates, aankondigingen en terugblikken vormen de publieke tijdlijn.',
        icon: Newspaper,
    },
    {
        id: 'about',
        title: 'Community',
        description:
            'DDS brengt dronevliegers, makers en partners samen rond veilig vliegen.',
        icon: Users,
    },
];

const principles = [
    'Veilig en verantwoord vliegen',
    'Kennis delen binnen de community',
    'Zichtbare activiteiten en projecten',
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Home" />

            <section className="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mx-auto grid min-h-[calc(100vh-8rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                            Dutch Drone Squad
                        </p>
                        <h1 className="mt-4 text-4xl font-semibold text-neutral-950 sm:text-5xl dark:text-white">
                            Communityplatform voor dronevliegers, makers en
                            partners.
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-neutral-600 sm:text-lg dark:text-neutral-400">
                            DDS bouwt aan een plek waar events, projecten,
                            nieuws en praktische informatie samenkomen voor
                            iedereen die betrokken is bij de community.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                href="#events"
                                className="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:bg-red-500 dark:text-neutral-950 dark:hover:bg-red-400 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                            >
                                Bekijk richting
                            </a>
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center justify-center rounded-md border border-neutral-300 px-4 py-2.5 text-sm font-semibold text-neutral-900 transition-colors hover:border-neutral-950 hover:bg-neutral-950 hover:text-white focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:border-neutral-700 dark:text-neutral-100 dark:hover:border-white dark:hover:bg-white dark:hover:text-neutral-950 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                                >
                                    Naar beheer
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-flex items-center justify-center rounded-md border border-neutral-300 px-4 py-2.5 text-sm font-semibold text-neutral-900 transition-colors hover:border-neutral-950 hover:bg-neutral-950 hover:text-white focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:border-neutral-700 dark:text-neutral-100 dark:hover:border-white dark:hover:bg-white dark:hover:text-neutral-950 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                                >
                                    Inloggen
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="rounded-md bg-neutral-950 p-5 text-white dark:bg-black">
                            <div className="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                <div>
                                    <p className="text-sm text-white/60">
                                        Flight board
                                    </p>
                                    <h2 className="mt-1 text-xl font-semibold">
                                        DDS community
                                    </h2>
                                </div>
                                <span className="rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-neutral-950">
                                    Actief
                                </span>
                            </div>

                            <div className="mt-5 grid gap-3">
                                {principles.map((principle) => (
                                    <div
                                        key={principle}
                                        className="flex items-center gap-3 rounded-md bg-white/5 p-3"
                                    >
                                        <CheckCircle2 className="size-5 text-red-300" />
                                        <span className="text-sm">
                                            {principle}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <div
                                id="contact"
                                className="mt-5 rounded-md border border-white/10 p-4"
                            >
                                <div className="flex items-start gap-3">
                                    <MapPin className="mt-0.5 size-5 text-red-300" />
                                    <div>
                                        <h3 className="font-medium">
                                            Nederland als werkgebied
                                        </h3>
                                        <p className="mt-1 text-sm leading-6 text-white/65">
                                            Locaties, partners en huisregels
                                            krijgen hier stap voor stap een
                                            duidelijke plek.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="bg-stone-50 py-12 dark:bg-neutral-950">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-2xl">
                        <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                            Publieke structuur
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold text-neutral-950 dark:text-white">
                            Eerste navigatiegebieden
                        </h2>
                    </div>

                    <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {publicSections.map((section) => (
                            <article
                                id={section.id}
                                key={section.id}
                                className="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs transition-colors hover:border-red-200 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-red-500/40"
                            >
                                <span className="flex size-10 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    <section.icon className="size-5" />
                                </span>
                                <h3 className="mt-4 font-semibold text-neutral-950 dark:text-white">
                                    {section.title}
                                </h3>
                                <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                    {section.description}
                                </p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
