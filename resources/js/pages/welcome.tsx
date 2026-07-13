import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { PublicHero } from '@/components/public/public-patterns';
import { about, contact } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as locationsIndex } from '@/routes/locations';
import { index as newsIndex } from '@/routes/news';

type NewsItem = {
    dateLabel: string;
    excerpt: string;
    href: string;
    image?: {
        alt: string;
        src: string;
    };
    title: string;
};

type UpcomingEvent = {
    availabilityLabel: string;
    dateLabel: string;
    href: string;
    location: string;
    priceLabel: string;
    timeLabel: string;
    title: string;
    typeLabel: string;
};

type WelcomeProps = {
    latestNews?: NewsItem[];
    upcomingEvent?: UpcomingEvent | null;
    upcomingEvents: UpcomingEvent[];
};

const mockUpcomingEvents: UpcomingEvent[] = [
    {
        availabilityLabel: '12 van 15 plekken vrij',
        dateLabel: '13 SEP',
        href: eventsIndex().url,
        location: 'Sportpaleis Alkmaar',
        priceLabel: '€15',
        timeLabel: '18:00–21:00',
        title: 'Indoor training · Round 01',
        typeLabel: '5-inch FPV racing',
    },
    {
        availabilityLabel: '8 van 15 plekken vrij',
        dateLabel: '11 OKT',
        href: eventsIndex().url,
        location: 'Sportpaleis Alkmaar',
        priceLabel: '€15',
        timeLabel: '18:00–21:00',
        title: 'Indoor training · Round 02',
        typeLabel: '5-inch FPV racing',
    },
    {
        availabilityLabel: 'Aanmelding volgt',
        dateLabel: '15 NOV',
        href: eventsIndex().url,
        location: 'Sportpaleis Alkmaar',
        priceLabel: '€15',
        timeLabel: '18:00–21:00',
        title: 'Indoor training · Round 03',
        typeLabel: '5-inch FPV racing',
    },
];

const pilotBenefits = [
    {
        title: 'Echte rondetiming',
        description: 'Zie na iedere heat waar je tijd wint.',
    },
    {
        title: 'Een volledige track',
        description: '2.000 m² sportvloer met 11 meter vrije hoogte.',
    },
    {
        title: 'Hulp in de paddock',
        description: 'Vergelijk racelijnen, afstelling en techniek.',
    },
];

const legacyNews: NewsItem[] = [
    {
        dateLabel: '9 september 2024',
        excerpt: 'De planning en veranderingen voor het nieuwe indoorseizoen.',
        href: 'https://dutchdronesquad.nl/seizoen-24-25/',
        image: {
            alt: 'FPV-piloot tijdens een indoor event van Dutch Drone Squad',
            src: '/images/dds/pilot-at-training.jpg',
        },
        title: "Let's Get Ready! Indoor seizoen 24/25",
    },
    {
        dateLabel: '4 september 2022',
        excerpt:
            'Een nieuw indoorseizoen in Alkmaar, met een vernieuwde aanpak voor de events.',
        href: 'https://dutchdronesquad.nl/here-we-go-indoor-seizoen-22-23/',
        image: {
            alt: 'Indoor FPV-track in het Sportpaleis in Alkmaar',
            src: '/images/dds/indoor-track.jpg',
        },
        title: 'Here we go! Indoor seizoen 22/23',
    },
    {
        dateLabel: '13 augustus 2022',
        excerpt:
            'Een gezamenlijke vliegmiddag en barbecue als afsluiting van de zomer.',
        href: 'https://dutchdronesquad.nl/bbq-2022/',
        image: {
            alt: 'Piloten bij elkaar tijdens een event van Dutch Drone Squad',
            src: '/images/dds/training-community.jpg',
        },
        title: 'BBQ: Fly to meat you 2022',
    },
];

export default function Welcome({
    latestNews,
    upcomingEvent,
    upcomingEvents,
}: WelcomeProps) {
    const visibleEvents = (
        upcomingEvents.length > 0
            ? upcomingEvents
            : upcomingEvent
              ? [upcomingEvent]
              : mockUpcomingEvents
    ).slice(0, 3);
    const newsItems = latestNews?.length ? latestNews : legacyNews;

    return (
        <>
            <Head title="Home" />

            <PublicHero
                title="Where racing brings pilots together."
                description="Race door de gates, jaag op snellere rondetijden en push elkaar tot de laatste accu. Indoor FPV-racing in Alkmaar."
                actions={[
                    { label: 'Bekijk de agenda', href: eventsIndex().url },
                    {
                        label: 'Voor ervaren piloten',
                        href: '#ervaren-piloten',
                    },
                ]}
                media={{
                    src: '/images/dds/homepage-hero.jpg',
                    alt: 'Lichtspoor van een FPV-drone boven het indoor raceparcours van Dutch Drone Squad',
                    className: 'object-[42%_center] sm:object-[center_42%]',
                }}
            />

            <HeroRaceLine />

            <section
                id="ervaren-piloten"
                className="scroll-mt-20 overflow-hidden bg-air text-deep-signal"
            >
                <div className="mx-auto w-full max-w-7xl px-public-gutter pt-12 pb-20 sm:pt-16 sm:pb-28 lg:pt-20 lg:pb-36">
                    <div className="grid gap-8 lg:grid-cols-[1.12fr_0.88fr] lg:items-end lg:gap-20">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase">
                                Voor ervaren piloten
                            </p>
                            <h2 className="mt-5 max-w-4xl font-public-display text-5xl leading-[0.95] font-semibold tracking-[-0.06em] text-balance sm:text-6xl lg:text-7xl">
                                Sneller worden doe je samen.
                            </h2>
                        </div>

                        <p className="max-w-xl text-lg leading-8 text-signal-muted sm:text-xl sm:leading-9 lg:pb-2">
                            Kun je zelfstandig een parcours vliegen en je drone
                            na een crash repareren? Dan kun je aansluiten op de
                            volledige track in het Sportpaleis.
                        </p>
                    </div>

                    <div className="mt-14 grid gap-10 lg:mt-20 lg:grid-cols-[1.36fr_0.64fr] lg:items-end lg:gap-16">
                        <div>
                            <figure>
                                <div className="relative aspect-[4/3] overflow-hidden bg-deep-signal/10">
                                    <img
                                        src="/images/dds/training-community.jpg"
                                        alt="Piloten en bezoekers tijdens een event van Dutch Drone Squad"
                                        loading="lazy"
                                        className="h-full w-full object-cover"
                                    />
                                    <span className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange" />
                                    <span className="absolute bottom-0 left-0 h-1.5 w-1/4 bg-dds-cyan" />
                                </div>
                                <figcaption className="mt-4 text-xs font-medium text-signal-muted">
                                    Op de baan jaag je op rondetijden. In de
                                    paddock deel je kennis en ervaring.
                                </figcaption>
                            </figure>
                        </div>

                        <div>
                            <dl className="border-t border-deep-signal/18">
                                {pilotBenefits.map((benefit) => (
                                    <div
                                        key={benefit.title}
                                        className="border-b border-deep-signal/18 py-6"
                                    >
                                        <dt className="font-public-display text-2xl font-semibold tracking-[-0.035em]">
                                            {benefit.title}
                                        </dt>
                                        <dd className="mt-2 text-sm leading-6 text-signal-muted">
                                            {benefit.description}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </div>
                </div>
            </section>

            <section className="overflow-hidden bg-deep-signal text-white">
                <div className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-20 sm:py-28 lg:grid-cols-[0.78fr_1.22fr] lg:items-center lg:gap-20 lg:py-36">
                    <div className="max-w-lg">
                        <p className="font-public-display text-6xl leading-none font-semibold tracking-[-0.06em] text-dds-cyan sm:text-7xl">
                            2.000 m²
                        </p>
                        <p className="mt-3 text-sm font-semibold text-white/55">
                            sportvloer · 11 meter hoog
                        </p>
                        <h2 className="mt-10 font-public-display text-4xl leading-[1.02] font-semibold tracking-[-0.05em] text-balance sm:text-5xl">
                            Een echte racebaan. Gewoon in Alkmaar.
                        </h2>
                        <p className="mt-6 text-base leading-7 text-white/60">
                            Voor ieder event leggen we in het Sportpaleis een
                            andere indoor track met live timing. Sinds 2017
                            komen piloten uit heel Nederland hiernaartoe om hun
                            skills te verbeteren, snellere rondetijden neer te
                            zetten en voluit te racen.
                        </p>
                        <Link
                            href={locationsIndex()}
                            prefetch
                            className="group mt-7 inline-flex items-center gap-2 text-sm font-semibold text-dds-cyan transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                        >
                            Bekijk het Sportpaleis
                            <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                        </Link>
                    </div>

                    <div>
                        <figure>
                            <div className="relative aspect-[4/3] overflow-hidden bg-white/8">
                                <img
                                    src="/images/dds/indoor-track.jpg"
                                    alt="Volledig indoor FPV-parcours in het Sportpaleis Alkmaar"
                                    loading="lazy"
                                    className="h-full w-full object-cover"
                                />
                                <span className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange" />
                                <span className="absolute bottom-0 left-0 h-1.5 w-1/4 bg-dds-cyan" />
                            </div>
                            <figcaption className="mt-4 text-xs leading-5 text-white/42">
                                Het gebruikelijke vliegseizoen loopt van
                                september tot en met mei.
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            <UpcomingEventsSection
                events={visibleEvents}
                isMock={upcomingEvents.length === 0 && !upcomingEvent}
            />

            <section className="overflow-hidden bg-warmup text-deep-signal">
                <div className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-20 sm:py-28 lg:grid-cols-[1.08fr_0.92fr] lg:items-center lg:gap-24 lg:py-32">
                    <div className="max-w-3xl">
                        <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase">
                            Nieuw met drone racing?
                        </p>
                        <h2 className="mt-5 font-public-display text-5xl leading-[0.96] font-semibold tracking-[-0.06em] text-balance sm:text-6xl lg:text-7xl">
                            Begin met controle. Snelheid komt later.
                        </h2>
                    </div>

                    <div>
                        <p className="text-lg leading-8 text-signal-muted">
                            Het Sportpaleis is bedoeld voor zelfstandige
                            piloten. Wie nog aan de basis werkt, kan bij
                            voldoende animo starten tijdens een beginnersmoment
                            in De Goorn.
                        </p>
                        <Link
                            href={contact()}
                            prefetch
                            className="group mt-8 inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-deep-signal px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-warmup focus-visible:outline-none"
                        >
                            Ontdek hoe je begint
                            <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                        </Link>
                    </div>
                </div>
            </section>

            <section className="overflow-hidden bg-deep-signal text-white">
                <div className="mx-auto grid w-full max-w-7xl lg:grid-cols-[0.9fr_1.1fr] lg:items-stretch">
                    <div className="flex items-center px-public-gutter py-20 sm:py-24 lg:py-28 lg:pr-20">
                        <div className="max-w-xl">
                            <p className="font-mono text-xs font-semibold tracking-[0.12em] text-dds-orange uppercase">
                                Over Dutch Drone Squad
                            </p>
                            <h2 className="mt-5 font-public-display text-4xl leading-[1.02] font-semibold tracking-[-0.05em] sm:text-5xl">
                                Meer dan een plek om te vliegen.
                            </h2>
                            <p className="mt-6 text-base leading-8 text-white/62">
                                Dutch Drone Squad is een groep enthousiaste
                                drone racers uit Alkmaar en omstreken. We bouwen
                                tracks, organiseren events en ontwikkelen
                                techniek die FPV-racing verder helpt.
                            </p>
                            <Link
                                href={about()}
                                prefetch
                                className="group mt-8 inline-flex items-center gap-2 text-sm font-semibold text-dds-cyan transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                            >
                                Leer DDS kennen
                                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                            </Link>
                        </div>
                    </div>

                    <div>
                        <figure className="relative min-h-[24rem] overflow-hidden bg-white/8 lg:min-h-[34rem]">
                            <img
                                src="/images/dds/race-control.jpg"
                                alt="De timing- en racetechniek van Dutch Drone Squad in het Sportpaleis"
                                loading="lazy"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-x-0 bottom-0 h-1.5 bg-dds-orange" />
                        </figure>
                    </div>
                </div>
            </section>

            <section id="latest-news" className="bg-paper text-ink">
                <div className="mx-auto w-full max-w-7xl px-public-gutter py-20 sm:py-28 lg:py-32">
                    <div className="flex items-end justify-between gap-8">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase">
                                Uit de paddock
                            </p>
                            <h2 className="mt-4 font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl">
                                Laatste nieuws
                            </h2>
                        </div>
                        <Link
                            href={newsIndex()}
                            prefetch
                            className="hidden text-sm font-semibold text-dds-blue transition-colors hover:text-deep-signal sm:inline-flex"
                        >
                            Bekijk alles
                        </Link>
                    </div>

                    <p className="mt-9 flex items-center gap-2 text-xs font-semibold tracking-[0.08em] text-dds-blue uppercase md:hidden">
                        Veeg voor meer
                        <ArrowRight className="size-4" aria-hidden="true" />
                    </p>

                    <div className="-mx-public-gutter mt-4 md:mx-0 md:mt-12">
                        <ul
                            aria-label="Laatste nieuws"
                            tabIndex={0}
                            className="flex snap-x snap-proximity scroll-px-public-gutter scrollbar-none gap-4 overflow-x-auto overscroll-x-contain px-public-gutter pb-4 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset md:grid md:snap-none md:grid-cols-3 md:gap-7 md:overflow-visible md:px-0 md:pb-0 lg:gap-10"
                        >
                            {newsItems.slice(0, 3).map((item) => (
                                <li
                                    key={item.href}
                                    className="w-[calc(100vw-3.5rem)] max-w-[22rem] shrink-0 snap-start md:w-auto md:max-w-none md:shrink md:snap-none"
                                >
                                    <NewsCard item={item} />
                                </li>
                            ))}
                        </ul>
                    </div>

                    <Link
                        href={newsIndex()}
                        prefetch
                        className="mt-10 inline-flex text-sm font-semibold text-dds-blue sm:hidden"
                    >
                        Bekijk al het nieuws
                    </Link>
                </div>
            </section>

            <section className="bg-dds-orange text-deep-signal">
                <div className="mx-auto grid w-full max-w-7xl items-center gap-7 px-public-gutter py-12 sm:py-14 lg:grid-cols-[1fr_auto]">
                    <div>
                        <h2 className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl">
                            Vlieg je de volgende keer mee?
                        </h2>
                        <p className="mt-3 text-sm font-medium text-deep-signal/70">
                            Bekijk de planning en meld je aan voor de volgende
                            event.
                        </p>
                    </div>
                    <Link
                        href={eventsIndex()}
                        prefetch
                        className="group inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-deep-signal px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-deep-signal/90 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-dds-orange focus-visible:outline-none"
                    >
                        Bekijk de agenda
                        <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                    </Link>
                </div>
            </section>
        </>
    );
}

type UpcomingEventsSectionProps = {
    events: UpcomingEvent[];
    isMock: boolean;
};

function UpcomingEventsSection({ events, isMock }: UpcomingEventsSectionProps) {
    return (
        <section id="upcoming-events" className="bg-paper text-deep-signal">
            <div className="mx-auto w-full max-w-7xl px-public-gutter py-20 sm:py-28 lg:py-32">
                <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <h2 className="max-w-3xl font-public-display text-5xl leading-[0.96] font-semibold tracking-[-0.055em] sm:text-6xl">
                            Upcoming events
                        </h2>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-signal-muted">
                            Bekijk wanneer we vliegen, hoeveel plekken nog
                            beschikbaar zijn en meld je op tijd aan.
                        </p>
                    </div>
                    <Link
                        href={eventsIndex()}
                        prefetch
                        className="group inline-flex items-center gap-2 text-sm font-semibold text-dds-blue transition-colors hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                    >
                        Volledige agenda
                        <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                    </Link>
                </div>

                <p className="mt-9 flex items-center gap-2 text-xs font-semibold tracking-[0.08em] text-dds-blue uppercase md:hidden">
                    Veeg voor meer
                    <ArrowRight className="size-4" aria-hidden="true" />
                </p>

                <div className="-mx-public-gutter mt-4 md:mx-0 md:mt-12">
                    <ul
                        aria-label="Upcoming events"
                        tabIndex={0}
                        className="flex snap-x snap-proximity scroll-px-public-gutter scrollbar-none gap-4 overflow-x-auto overscroll-x-contain px-public-gutter pb-4 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset md:grid md:snap-none md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 md:pb-0"
                    >
                        {events.map((event, index) => (
                            <li
                                key={`${event.dateLabel}-${event.title}`}
                                className="w-[calc(100vw-3.5rem)] max-w-[22rem] shrink-0 snap-start md:w-auto md:max-w-none md:shrink md:snap-none"
                            >
                                <EventCard event={event} index={index} />
                            </li>
                        ))}
                    </ul>
                </div>

                {isMock && (
                    <p className="mt-5 font-mono text-[0.68rem] tracking-[0.08em] text-signal-muted uppercase">
                        Voorbeelddata · wordt later gekoppeld aan de eventmodule
                    </p>
                )}
            </div>
        </section>
    );
}

function HeroRaceLine() {
    return (
        <div
            aria-hidden="true"
            className="relative z-10 -mt-10 h-10 overflow-hidden text-air sm:-mt-14 sm:h-14"
        >
            <svg
                viewBox="0 0 390 40"
                preserveAspectRatio="none"
                className="h-full w-full sm:hidden"
            >
                <path
                    fill="currentColor"
                    d="M0 40V27H100L124 8H226L252 27H390V40Z"
                />
                <path
                    d="M0 27H100L124 8H153"
                    fill="none"
                    stroke="var(--color-dds-orange)"
                    strokeWidth="3"
                    vectorEffect="non-scaling-stroke"
                />
                <path
                    d="M226 8L252 27H390"
                    fill="none"
                    stroke="var(--color-dds-cyan)"
                    strokeWidth="3"
                    vectorEffect="non-scaling-stroke"
                />
            </svg>
            <svg
                viewBox="0 0 1440 64"
                preserveAspectRatio="none"
                className="hidden h-full w-full sm:block"
            >
                <path
                    fill="currentColor"
                    d="M0 64V48H360L430 15H755L820 48H1120L1190 29H1440V64Z"
                />
                <path
                    d="M0 48H360L430 15H575"
                    fill="none"
                    stroke="var(--color-dds-orange)"
                    strokeWidth="4"
                    vectorEffect="non-scaling-stroke"
                />
                <path
                    d="M755 15L820 48H1120L1190 29H1440"
                    fill="none"
                    stroke="var(--color-dds-cyan)"
                    strokeWidth="4"
                    vectorEffect="non-scaling-stroke"
                />
            </svg>
        </div>
    );
}

type EventCardProps = {
    event: UpcomingEvent;
    index: number;
};

function EventCard({ event, index }: EventCardProps) {
    return (
        <Link
            href={event.href}
            prefetch
            className="group flex h-full min-h-[25rem] flex-col border-t-4 border-dds-orange bg-deep-signal p-6 text-white transition duration-300 hover:-translate-y-1 hover:bg-[#174a52] focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none sm:p-7"
        >
            <div className="flex items-start justify-between gap-4">
                <p className="font-mono text-xs tracking-[0.12em] text-dds-cyan uppercase">
                    Round {String(index + 1).padStart(2, '0')}
                </p>
                <p className="text-xs font-medium text-white/52">
                    {event.availabilityLabel}
                </p>
            </div>

            <p className="mt-10 font-public-display text-5xl leading-none font-semibold tracking-[-0.06em] text-dds-orange sm:text-6xl">
                {event.dateLabel}
            </p>
            <h3 className="mt-6 font-public-display text-2xl leading-tight font-semibold tracking-[-0.035em]">
                {event.title}
            </h3>
            <p className="mt-2 text-sm text-white/52">{event.typeLabel}</p>

            <dl className="mt-auto grid grid-cols-2 gap-x-6 gap-y-5 border-t border-white/15 pt-6 text-sm">
                <div>
                    <dt className="font-mono text-[0.65rem] tracking-[0.1em] text-white/55 uppercase">
                        Tijd
                    </dt>
                    <dd className="mt-1 font-semibold">{event.timeLabel}</dd>
                </div>
                <div>
                    <dt className="font-mono text-[0.65rem] tracking-[0.1em] text-white/55 uppercase">
                        Ticket
                    </dt>
                    <dd className="mt-1 font-semibold">{event.priceLabel}</dd>
                </div>
                <div className="col-span-2">
                    <dt className="font-mono text-[0.65rem] tracking-[0.1em] text-white/55 uppercase">
                        Locatie
                    </dt>
                    <dd className="mt-1 font-semibold">{event.location}</dd>
                </div>
            </dl>

            <span className="mt-6 inline-flex items-center justify-between gap-3 text-sm font-semibold text-dds-cyan">
                Bekijk event
                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
            </span>
        </Link>
    );
}

type NewsCardProps = {
    item: NewsItem;
};

function NewsCard({ item }: NewsCardProps) {
    return (
        <article className="h-full border-t border-deep-signal/18 pt-6">
            <NewsLink
                href={item.href}
                className="group block h-full rounded-sm focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-4 focus-visible:ring-offset-paper focus-visible:outline-none"
            >
                <div className="relative mb-6 aspect-[16/10] overflow-hidden bg-deep-signal/8">
                    <img
                        src={
                            item.image?.src ??
                            '/images/dds/pilot-at-training.jpg'
                        }
                        alt={
                            item.image?.alt ??
                            'FPV-piloot tijdens een indoor event van Dutch Drone Squad'
                        }
                        loading="lazy"
                        className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.015] motion-reduce:transition-none"
                    />
                    <span className="absolute top-0 right-0 h-1 w-1/3 bg-dds-orange" />
                </div>
                <p className="text-xs text-signal-muted">{item.dateLabel}</p>
                <h3 className="mt-3 text-xl leading-snug font-semibold tracking-[-0.03em] text-deep-signal transition-colors group-hover:text-dds-blue sm:text-2xl">
                    {item.title}
                </h3>
                <p className="mt-3 text-sm leading-6 text-signal-muted">
                    {item.excerpt}
                </p>
            </NewsLink>
        </article>
    );
}

type NewsLinkProps = {
    children: ReactNode;
    className?: string;
    href: string;
};

function NewsLink({ children, className, href }: NewsLinkProps) {
    if (href.startsWith('http://') || href.startsWith('https://')) {
        return (
            <a href={href} className={className}>
                {children}
            </a>
        );
    }

    return (
        <Link href={href} prefetch className={className}>
            {children}
        </Link>
    );
}
