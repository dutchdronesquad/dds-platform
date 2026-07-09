import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, MapPin } from 'lucide-react';
import { index as eventsIndex } from '@/routes/events';

type Props = {
    slug: string;
};

export default function EventShow({ slug }: Props) {
    const readableTitle = slug
        .split('-')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

    return (
        <>
            <Head title={readableTitle || 'Event'} />

            <section className="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">
                <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:py-16">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                            Event placeholder
                        </p>
                        <h1 className="mt-4 text-4xl font-semibold text-neutral-950 sm:text-5xl dark:text-white">
                            {readableTitle || 'Event'}
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-neutral-600 sm:text-lg dark:text-neutral-400">
                            Deze detailpagina reserveert de publieke URL voor
                            een DDS-event. Het echte eventmodel kan later titel,
                            datum, locatie, programma en deelnamegegevens
                            leveren.
                        </p>
                        <div className="mt-8">
                            <Link
                                href={eventsIndex()}
                                className="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 focus-visible:outline-none dark:bg-red-500 dark:text-neutral-950 dark:hover:bg-red-400 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-neutral-950"
                            >
                                <ArrowLeft className="size-4" />
                                Terug naar events
                            </Link>
                        </div>
                    </div>

                    <div className="grid gap-4">
                        <article className="rounded-lg border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-900">
                            <div className="flex items-start gap-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    <CalendarDays className="size-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-semibold text-neutral-950 dark:text-white">
                                        Eventinformatie
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                        Datum, type activiteit en registratie
                                        worden hier zichtbaar zodra DDS-009 het
                                        eventdomein toevoegt.
                                    </p>
                                </div>
                            </div>
                        </article>

                        <article className="rounded-lg border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-900">
                            <div className="flex items-start gap-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    <MapPin className="size-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-semibold text-neutral-950 dark:text-white">
                                        Locatie en regels
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                        De pagina houdt ruimte voor locatie,
                                        huisregels en praktische aanwijzingen
                                        voor veilig deelnemen.
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </>
    );
}
