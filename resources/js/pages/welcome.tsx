import { Link } from '@inertiajs/react';
import { ArrowUpRight, Maximize2, Timer, UsersRound } from 'lucide-react';
import type { ReactNode } from 'react';
import PublicEventCard from '@/components/public/public-event-card';
import PublicPartnerLogo from '@/components/public/public-partner-logo';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { about, partners as partnersPage } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as gettingStartedIndex } from '@/routes/getting_started';
import { index as locationsIndex } from '@/routes/locations';
import { index as newsIndex } from '@/routes/news';
import type { PublicEventSummary, PublicPartner, SeoMetadata } from '@/types';

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

type WelcomeProps = {
    latestNews: NewsItem[];
    partners: PublicPartner[];
    seo: SeoMetadata;
    upcomingEvents: PublicEventSummary[];
};

const pilotBenefits = [
    {
        icon: Timer,
        title: 'Echte rondetiming',
        description: 'Zie na iedere heat waar je tijd wint.',
    },
    {
        icon: Maximize2,
        title: 'Een volledige track',
        description: '2.000 m² sportvloer met 11 meter vrije hoogte.',
    },
    {
        icon: UsersRound,
        title: 'Hulp in de paddock',
        description: 'Vergelijk racelijnen, afstelling en techniek.',
    },
];

export default function Welcome({
    latestNews,
    partners,
    seo,
    upcomingEvents,
}: WelcomeProps) {
    const visibleEvents = upcomingEvents.slice(0, 3);
    const newsItems = latestNews.slice(0, 3);

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="Where racing brings pilots together."
                description="Race door de gates, jaag op snellere rondetijden en push elkaar tot de laatste accu. Train, race en verbeter jezelf op onze indoorbaan in Alkmaar."
                actions={[
                    { label: 'Bekijk de agenda', href: eventsIndex.url() },
                    {
                        label: 'Bekijk hoe we racen',
                        href: '#ervaren-piloten',
                    },
                ]}
                media={{
                    src: '/images/dds/racing/homepage-hero.jpg',
                    alt: 'Lichtspoor van een FPV-drone boven het indoor raceparcours van Dutch Drone Squad',
                    className: 'object-[42%_center] sm:object-[center_42%]',
                }}
                separatorTone="air"
            />

            <section
                id="ervaren-piloten"
                className="scroll-mt-20 overflow-hidden bg-air text-deep-signal"
            >
                <div className="mx-auto w-full max-w-7xl px-public-gutter pt-12 pb-20 sm:pt-16 sm:pb-28 lg:pt-20 lg:pb-32">
                    <div className="grid gap-8 lg:grid-cols-[1.08fr_0.92fr] lg:items-center lg:gap-20">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase">
                                Voor piloten die verder willen
                            </p>
                            <h2
                                data-testid="pilot-development-heading"
                                className="mt-5 max-w-4xl font-public-display text-5xl leading-[0.95] font-semibold tracking-[-0.06em] text-balance sm:text-6xl lg:text-7xl"
                            >
                                Sneller worden doe je samen.
                            </h2>
                        </div>

                        <p className="max-w-xl text-lg leading-8 text-signal-muted sm:text-xl sm:leading-9 lg:pb-2">
                            Wil je strakkere lijnen vliegen en snellere
                            rondetijden neerzetten? Sluit aan op de volledige
                            track in het Sportpaleis, vergelijk je tijden en
                            leer van andere piloten.
                        </p>
                    </div>

                    <div
                        data-testid="pilot-development-panel"
                        className="mt-14 grid overflow-hidden border border-deep-signal/15 bg-deep-signal shadow-[0_30px_80px_-45px_rgb(18_60_67/0.65)] lg:mt-16 lg:grid-cols-[1.34fr_0.66fr]"
                    >
                        <figure className="relative min-h-[25rem] overflow-hidden bg-deep-signal/10 sm:min-h-[32rem] lg:min-h-[36rem]">
                            <img
                                src="/images/dds/racing/training-community.jpg"
                                alt="Piloten en bezoekers tijdens een event van Dutch Drone Squad"
                                loading="lazy"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-linear-to-t from-deep-signal/85 via-deep-signal/5 to-transparent" />
                            <span className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange" />
                            <figcaption className="absolute right-6 bottom-6 left-6 flex items-end justify-between gap-4 text-white sm:right-8 sm:bottom-8 sm:left-8">
                                <div>
                                    <p className="text-[11px] font-semibold tracking-[0.16em] text-dds-cyan uppercase">
                                        Train · vergelijk · verbeter
                                    </p>
                                    <p className="mt-2 max-w-md font-public-display text-2xl leading-tight font-semibold tracking-[-0.035em] sm:text-3xl">
                                        Iedere ronde geeft je iets om mee verder
                                        te gaan.
                                    </p>
                                </div>
                                <span
                                    aria-hidden="true"
                                    className="hidden h-px flex-1 bg-white/35 sm:block"
                                />
                            </figcaption>
                        </figure>

                        <div className="flex flex-col px-6 py-8 text-white sm:px-8 sm:py-10 lg:px-9 lg:py-12">
                            <p className="text-[11px] font-semibold tracking-[0.16em] text-dds-orange uppercase">
                                Op de baan
                            </p>
                            <h3 className="mt-4 max-w-sm font-public-display text-3xl leading-[1.04] font-semibold tracking-[-0.045em] text-balance sm:text-4xl">
                                Alles voor je volgende persoonlijke record.
                            </h3>

                            <ul className="mt-8 border-t border-white/15">
                                {pilotBenefits.map((benefit, index) => {
                                    const Icon = benefit.icon;

                                    return (
                                        <li
                                            key={benefit.title}
                                            className="grid grid-cols-[2.5rem_1fr] gap-4 border-b border-white/15 py-5"
                                        >
                                            <span
                                                className={
                                                    index === 0
                                                        ? 'flex size-10 items-center justify-center bg-dds-orange text-deep-signal'
                                                        : 'flex size-10 items-center justify-center bg-white/8 text-dds-cyan'
                                                }
                                            >
                                                <Icon
                                                    aria-hidden="true"
                                                    className="size-[1.125rem]"
                                                    strokeWidth={2}
                                                />
                                            </span>
                                            <div>
                                                <h4 className="font-public-display text-xl font-semibold tracking-[-0.03em]">
                                                    {benefit.title}
                                                </h4>
                                                <p className="mt-1.5 text-sm leading-6 text-white/58">
                                                    {benefit.description}
                                                </p>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>

                            <Link
                                href={eventsIndex()}
                                prefetch
                                className="group mt-8 inline-flex items-center gap-2 self-start text-sm font-semibold text-dds-cyan transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-4 focus-visible:ring-offset-deep-signal focus-visible:outline-none"
                            >
                                Bekijk komende trainingen
                                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                            </Link>
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
                                    src="/images/dds/racing/indoor-track.jpg"
                                    alt="Volledig indoor FPV-parcours in het Sportpaleis Alkmaar"
                                    loading="lazy"
                                    className="h-full w-full object-cover"
                                />
                                <span className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange" />
                                <span className="absolute bottom-0 left-0 h-1.5 w-1/4 bg-dds-cyan" />
                            </div>
                        </figure>
                    </div>
                </div>
            </section>

            <UpcomingEventsSection events={visibleEvents} />

            <section className="bg-dds-orange text-deep-signal">
                <div className="mx-auto grid w-full max-w-7xl items-center gap-7 px-public-gutter py-12 sm:py-14 lg:grid-cols-[1fr_auto]">
                    <div>
                        <h2 className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl">
                            Vlieg je de volgende keer mee?
                        </h2>
                        <p className="mt-3 text-sm font-medium text-deep-signal">
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
                            href={gettingStartedIndex({
                                query: { source: 'homepage' },
                            })}
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
                                src="/images/dds/racing/pilot-preparing-drone.jpg"
                                alt="FPV-piloot werkt aan een racedrone tijdens een event van Dutch Drone Squad"
                                loading="lazy"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-x-0 bottom-0 h-1.5 bg-dds-orange" />
                        </figure>
                    </div>
                </div>
            </section>

            {newsItems.length > 0 && (
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

                        <div className="-mx-public-gutter mt-10 md:mx-0 md:mt-12">
                            <ul
                                aria-label="Laatste nieuws"
                                tabIndex={0}
                                className="flex snap-x snap-proximity scroll-px-public-gutter scrollbar-none gap-4 overflow-x-auto overscroll-x-contain px-public-gutter pb-4 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset md:grid md:snap-none md:grid-cols-3 md:gap-7 md:overflow-visible md:px-0 md:pb-0 lg:gap-10"
                            >
                                {newsItems.map((item) => (
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
            )}

            <PartnerLogosSection partners={partners} />
        </>
    );
}

type PartnerLogosSectionProps = {
    partners: PublicPartner[];
};

function PartnerLogosSection({ partners }: PartnerLogosSectionProps) {
    if (partners.length === 0) {
        return null;
    }

    return (
        <section className="border-t border-deep-signal/10 bg-paddock text-deep-signal">
            <div className="mx-auto w-full max-w-7xl px-public-gutter py-12 sm:py-14">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <h2 className="text-xs font-semibold tracking-[0.12em] text-signal-muted uppercase">
                        Partners & sponsors
                    </h2>
                    <Link
                        href={partnersPage()}
                        prefetch
                        className="text-sm font-semibold text-dds-blue transition-colors hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                    >
                        Bekijk alle partners
                    </Link>
                </div>

                <ul className="mt-8 flex flex-wrap items-center gap-x-10 gap-y-8 md:gap-x-12">
                    {partners.map((partner) => (
                        <li
                            key={partner.key}
                            className="flex min-h-14 w-64 max-w-full items-center sm:w-72"
                        >
                            <PublicPartnerLogo
                                partner={partner}
                                className="focus-visible:ring-dds-cyan focus-visible:ring-offset-4 focus-visible:ring-offset-paddock"
                            />
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

type UpcomingEventsSectionProps = {
    events: PublicEventSummary[];
};

function UpcomingEventsSection({ events }: UpcomingEventsSectionProps) {
    return (
        <section id="upcoming-events" className="bg-paper text-deep-signal">
            <div className="mx-auto w-full max-w-7xl px-public-gutter py-20 sm:py-28 lg:py-32">
                <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <h2 className="max-w-3xl font-public-display text-5xl leading-[0.96] font-semibold tracking-[-0.055em] sm:text-6xl">
                            Upcoming events
                        </h2>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-signal-muted">
                            Bekijk wanneer we vliegen, wat de capaciteit is en
                            of aanmelden nog mogelijk is.
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

                {events.length > 0 ? (
                    <div className="-mx-public-gutter mt-10 md:mx-0 md:mt-12">
                        <ul
                            aria-label="Upcoming events"
                            tabIndex={0}
                            className="flex snap-x snap-proximity scroll-px-public-gutter scrollbar-none gap-4 overflow-x-auto overscroll-x-contain px-public-gutter pb-4 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none focus-visible:ring-inset md:grid md:snap-none md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 md:pb-0"
                        >
                            {events.map((event) => (
                                <li
                                    key={event.id}
                                    className="w-[calc(100vw-3.5rem)] max-w-[22rem] shrink-0 snap-start md:w-auto md:max-w-none md:shrink md:snap-none"
                                >
                                    <PublicEventCard event={event} />
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <div className="relative mt-12 min-h-64 overflow-hidden sm:min-h-72 lg:min-h-80">
                        <img
                            src="/images/dds/racing/sportpaleis-empty-leveled.jpg"
                            alt="Lege sportvloer in het Sportpaleis Alkmaar"
                            className="absolute inset-0 size-full object-cover object-center"
                        />
                        <div className="relative flex min-h-64 items-end sm:min-h-72 sm:items-stretch lg:min-h-80">
                            <div className="flex w-full items-center bg-deep-signal/92 px-6 py-7 text-white sm:w-[52%] sm:px-10 sm:py-10 lg:w-[44%] lg:px-12">
                                <div>
                                    <h3 className="font-public-display text-3xl leading-tight font-semibold tracking-[-0.04em] sm:text-4xl">
                                        De baan is even leeg.
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-white/72 sm:text-base">
                                        Zodra de volgende racedag vaststaat,
                                        vind je hem hier.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <span
                            aria-hidden="true"
                            className="absolute bottom-0 left-0 h-1 w-1/3 bg-dds-orange sm:w-[40%]"
                        />
                        <span
                            aria-hidden="true"
                            className="absolute top-0 right-0 h-1 w-1/5 bg-dds-cyan"
                        />
                    </div>
                )}
            </div>
        </section>
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
                <div className="relative mb-6 aspect-[16/9] overflow-hidden bg-deep-signal/8">
                    <img
                        src={
                            item.image?.src ??
                            '/images/dds/racing/pilot-at-training.jpg'
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
