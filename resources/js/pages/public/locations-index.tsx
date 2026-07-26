import { Link } from '@inertiajs/react';
import { ArrowUpRight, MapPin, MapPinX } from 'lucide-react';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import { show as locationShow } from '@/routes/locations';
import type { PublicLocationSummary, SeoMetadata } from '@/types';

type Props = {
    locations: PublicLocationSummary[];
    seo: SeoMetadata;
};

const environmentLabels: Record<PublicLocationSummary['environment'], string> =
    {
        indoor: 'Indoor',
        outdoor: 'Outdoor',
    };

export default function LocationsIndex({ locations, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="Waar Dutch Drone Squad vliegt."
                description="Bekijk onze vlieg- en eventlocaties met adres, faciliteiten en praktische informatie voor bezoekers."
                actions={[
                    { label: 'Bekijk de agenda', href: eventsIndex.url() },
                ]}
                media={{
                    src: '/images/dds/racing/indoor-track.jpg',
                    alt: 'Indoor FPV-raceparcours van Dutch Drone Squad in Alkmaar',
                    position: '56% center',
                }}
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-labelledby="locations-heading"
                    className="mx-auto w-full max-w-[86rem] px-public-gutter py-16 sm:py-20"
                >
                    <div className="border-b border-paddock-rule pb-8 dark:border-white/12">
                        <h2
                            id="locations-heading"
                            className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Onze locaties
                        </h2>
                        <p className="mt-4 max-w-2xl text-base leading-7 text-signal-muted dark:text-night-400">
                            {locations.length === 1
                                ? '1 locatie beschikbaar.'
                                : `${locations.length} locaties beschikbaar.`}
                        </p>
                    </div>

                    {locations.length > 0 ? (
                        <ul
                            aria-label="Locaties"
                            className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            {locations.map((location) => (
                                <li key={location.id}>
                                    <Link
                                        href={locationShow(location.slug)}
                                        prefetch
                                        className="group flex h-full flex-col overflow-hidden rounded-sm border border-paddock-rule bg-white transition-colors hover:border-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
                                    >
                                        <div className="relative aspect-[16/9] overflow-hidden bg-deep-signal">
                                            <img
                                                src={location.image.src}
                                                alt={location.image.alt}
                                                loading="lazy"
                                                className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
                                            />
                                        </div>
                                        <div className="flex flex-1 flex-col p-5 sm:p-6">
                                            <p className="flex flex-wrap items-center gap-2 font-mono text-[0.68rem] font-semibold tracking-[0.09em] text-dds-blue uppercase dark:text-dds-cyan">
                                                <span className="rounded-sm bg-dds-blue/8 px-2 py-1 text-dds-blue dark:bg-dds-cyan/10 dark:text-dds-cyan">
                                                    {
                                                        environmentLabels[
                                                            location.environment
                                                        ]
                                                    }
                                                </span>
                                            </p>
                                            <h3 className="mt-3 line-clamp-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal transition-colors group-hover:text-dds-blue sm:text-[1.7rem] dark:text-white dark:group-hover:text-dds-cyan">
                                                {location.name}
                                            </h3>
                                            <p className="mt-3 flex items-start gap-2 text-sm leading-5 text-signal-muted dark:text-night-400">
                                                <MapPin className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                                                <span>{location.city}</span>
                                            </p>
                                            {location.excerpt && (
                                                <p className="mt-4 line-clamp-3 text-sm leading-6 text-signal-muted dark:text-night-400">
                                                    {location.excerpt}
                                                </p>
                                            )}
                                            <div className="mt-auto flex items-center justify-end gap-2 pt-6 text-sm font-semibold text-dds-blue dark:text-dds-cyan">
                                                Bekijk locatie
                                                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                                            </div>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="mt-10 flex min-h-80 flex-col items-center justify-center rounded-sm border border-dashed border-paddock-rule bg-paddock px-6 py-14 text-center dark:border-white/15 dark:bg-night-900">
                            <span className="flex size-14 items-center justify-center rounded-full bg-white text-dds-blue shadow-sm dark:bg-white/8 dark:text-dds-cyan">
                                <MapPinX className="size-6" />
                            </span>
                            <h2 className="mt-6 font-public-display text-2xl font-semibold tracking-[-0.035em]">
                                Geen locaties gevonden
                            </h2>
                            <p className="mt-3 max-w-md text-sm leading-6 text-signal-muted dark:text-night-400">
                                Er zijn nog geen locaties gepubliceerd. Kijk
                                later opnieuw of bekijk de agenda voor
                                aankomende events.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
