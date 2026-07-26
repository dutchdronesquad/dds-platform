import { Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarX2,
    CheckCircle2,
    Layers,
    Maximize,
    Ruler,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import PublicEventCard from '@/components/public/public-event-card';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import { index as locationsIndex } from '@/routes/locations';
import type {
    PublicEventSummary,
    PublicLocationDetail,
    SeoMetadata,
} from '@/types';

type Props = {
    location: PublicLocationDetail;
    seo: SeoMetadata;
    upcomingEvents: PublicEventSummary[];
};

const environmentLabels: Record<PublicLocationDetail['environment'], string> = {
    indoor: 'Indoor',
    outdoor: 'Outdoor',
};

const facilityLabels: Record<string, string> = {
    parking: 'Parkeren',
    power: 'Stroomvoorziening',
    toilets: 'Toiletten',
    tables_and_chairs: 'Tafels en stoelen',
    catering: 'Catering',
    wifi: 'Wifi',
};

export default function LocationShow({ location, seo, upcomingEvents }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={environmentLabels[location.environment]}
                title={location.name}
                actions={[
                    {
                        label: 'Alle locaties',
                        href: locationsIndex.url(),
                    },
                ]}
                media={location.image}
                separatorTone="paper"
                showSeparator={false}
                size="compact"
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-label="Locatie in het kort"
                    className="border-y border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900"
                >
                    <div className="mx-auto w-full max-w-7xl px-public-gutter">
                        <dl className="grid gap-px bg-paddock-rule sm:grid-cols-2 lg:grid-cols-3 dark:bg-white/12">
                            <LocationQuickFact
                                icon={Layers}
                                label="Vloeroppervlak"
                                value={
                                    location.floorSizeSquareMetres === null
                                        ? 'Niet opgegeven'
                                        : `${location.floorSizeSquareMetres} m²`
                                }
                            />
                            <LocationQuickFact
                                icon={Ruler}
                                label="Plafondhoogte"
                                value={
                                    location.ceilingHeightMetres === null
                                        ? 'Niet opgegeven'
                                        : `${location.ceilingHeightMetres} m`
                                }
                            />
                            <LocationQuickFact
                                icon={Maximize}
                                label="Omgeving"
                                value={environmentLabels[location.environment]}
                            />
                        </dl>
                    </div>
                </section>

                <section
                    aria-labelledby="briefing-heading"
                    className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:grid-cols-[minmax(0,1.08fr)_minmax(20rem,0.62fr)] lg:items-start lg:gap-20 lg:py-28"
                >
                    <div className="lg:pt-4">
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                            Over deze locatie
                        </p>
                        <h2
                            id="briefing-heading"
                            className="mt-4 font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Praktische informatie.
                        </h2>
                        <div className="dark:text-night-300 mt-7 max-w-3xl text-base leading-8 whitespace-pre-line text-signal-muted sm:text-lg">
                            {location.description ??
                                'De uitgebreide omschrijving van deze locatie volgt binnenkort. De adresgegevens en faciliteiten vind je hiernaast.'}
                        </div>

                        {location.facilities.length > 0 && (
                            <div className="mt-10">
                                <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                    Faciliteiten
                                </p>
                                <ul className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    {location.facilities.map((facility) => (
                                        <li
                                            key={facility}
                                            className="flex items-center gap-2 text-sm leading-5 text-signal-muted dark:text-night-400"
                                        >
                                            <CheckCircle2 className="size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                                            {facilityLabels[facility] ??
                                                facility}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>

                    <div className="min-w-0 lg:sticky lg:top-28">
                        <section className="relative overflow-hidden rounded-sm border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900">
                            <span
                                aria-hidden="true"
                                className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
                            />
                            <div className="p-6 sm:p-8">
                                <p className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                    Adres
                                </p>
                                <address className="dark:text-night-300 mt-3 text-base leading-7 text-signal-muted not-italic">
                                    {location.street} {location.houseNumber}
                                    <br />
                                    {location.postalCode} {location.city}
                                </address>

                                {location.websiteUrl && (
                                    <a
                                        href={location.websiteUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="mt-5 inline-flex min-h-10 w-fit items-center gap-2 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                                    >
                                        Bezoek de website
                                        <ArrowUpRight
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                        <span className="sr-only">
                                            (opent in een nieuw tabblad)
                                        </span>
                                    </a>
                                )}

                                <a
                                    href={location.mapUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-6 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-sm border border-deep-signal/20 px-4 py-2.5 text-sm font-semibold text-deep-signal transition-colors hover:border-dds-blue hover:text-dds-blue focus-visible:ring-2 focus-visible:ring-dds-blue focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/20 dark:text-white dark:hover:border-dds-cyan dark:hover:text-dds-cyan dark:focus-visible:ring-dds-cyan dark:focus-visible:ring-offset-night-900"
                                >
                                    Open in Google Maps
                                    <ArrowUpRight
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    <span className="sr-only">
                                        (opent in een nieuw tabblad)
                                    </span>
                                </a>
                            </div>

                            <div className="relative min-h-64 overflow-hidden border-t border-paddock-rule dark:border-white/12">
                                <iframe
                                    src={location.mapEmbedUrl}
                                    title={`Google Maps-kaart van ${location.name}`}
                                    loading="lazy"
                                    referrerPolicy="strict-origin-when-cross-origin"
                                    allowFullScreen
                                    className="absolute inset-0 size-full border-0"
                                />
                            </div>
                        </section>
                    </div>
                </section>

                <section className="border-t border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900">
                    <div className="mx-auto w-full max-w-7xl px-public-gutter py-14 lg:py-20">
                        <div className="flex flex-col gap-4 border-b border-paddock-rule pb-8 sm:flex-row sm:items-end sm:justify-between dark:border-white/12">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                    Op deze locatie
                                </p>
                                <h2 className="mt-3 font-public-display text-3xl font-semibold tracking-[-0.045em]">
                                    Aankomende events
                                </h2>
                            </div>
                            <Link
                                href={eventsIndex()}
                                prefetch
                                className="inline-flex min-h-10 w-fit items-center gap-1 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                            >
                                Bekijk alle events
                                <ArrowUpRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                        </div>

                        {upcomingEvents.length > 0 ? (
                            <ul
                                aria-label="Aankomende events op deze locatie"
                                className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {upcomingEvents.map((event) => (
                                    <li key={event.id}>
                                        <PublicEventCard event={event} />
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="mt-8 flex min-h-48 flex-col items-center justify-center rounded-sm border border-dashed border-paddock-rule bg-white px-6 py-10 text-center dark:border-white/15 dark:bg-night-950">
                                <span className="flex size-12 items-center justify-center rounded-full bg-paddock text-dds-blue dark:bg-white/8 dark:text-dds-cyan">
                                    <CalendarX2 className="size-5" />
                                </span>
                                <h3 className="mt-4 font-public-display text-xl font-semibold tracking-[-0.03em]">
                                    Nog geen aankomende events
                                </h3>
                                <p className="mt-2 max-w-sm text-sm leading-6 text-signal-muted dark:text-night-400">
                                    Er staan momenteel geen events gepland op
                                    deze locatie. Bekijk de volledige agenda
                                    voor andere activiteiten.
                                </p>
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <CtaBand
                eyebrow="Alle locaties"
                title="Ontdek waar Dutch Drone Squad nog meer vliegt."
                description="Bekijk het volledige overzicht van vlieg- en eventlocaties met adres en praktische informatie."
                action={{
                    label: 'Alle locaties',
                    href: locationsIndex.url(),
                }}
            />
        </>
    );
}

type LocationQuickFactProps = {
    icon: LucideIcon;
    label: string;
    value: string;
};

function LocationQuickFact({
    icon: Icon,
    label,
    value,
}: LocationQuickFactProps) {
    return (
        <div className="flex min-h-20 min-w-0 items-center gap-4 bg-paddock px-5 py-4 text-deep-signal sm:min-h-24 sm:px-6 sm:py-5 dark:bg-night-900 dark:text-white">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-sm border border-deep-signal/10 bg-white/70 text-dds-blue dark:border-white/12 dark:bg-white/6 dark:text-dds-cyan">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <div className="min-w-0">
                <dt className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </dt>
                <dd className="mt-1.5 text-sm leading-5 font-semibold text-deep-signal sm:text-[0.94rem] dark:text-white">
                    {value}
                </dd>
            </div>
        </div>
    );
}
