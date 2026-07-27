import { Eyebrow, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import { index as gettingStartedIndex } from '@/routes/getting_started';
import type { SeoMetadata } from '@/types';

type Props = {
    seo: SeoMetadata;
};

type TimelineEntry = {
    body: string;
    dateTime: string;
    label: string;
    title: string;
    year: string;
};

const timeline: TimelineEntry[] = [
    {
        year: '2017',
        dateTime: '2017',
        label: 'Juli tot eind 2017',
        title: 'Oprichting en de eerste trainingsavond.',
        body: 'In juli richten Klaas Schoute en Richard de Wit DDS op. In oktober volgt de eerste trainingsavond van het winterseizoen in het Sportpaleis Alkmaar. DDS organiseert dan om de twee weken een vliegavond. Eind 2017 sluit Boudewijn Pilon aan bij het organisatieteam en richt hij zich vooral op de track.',
    },
    {
        year: '2018',
        dateTime: '2018',
        label: '9 september',
        title: 'De eerste Fly to Meat You BBQ.',
        body: 'Bij Sportpark Keep Moving in Oosterblokker vindt de eerste Fly to Meat You BBQ plaats: een vliegmiddag met barbecue.',
    },
    {
        year: '2019',
        dateTime: '2019',
        label: '31 maart en 24 november',
        title: 'De eerste wedstrijden.',
        body: 'Op 31 maart houdt DDS de eerste wedstrijd, met Dennis Mennema als winnaar. Op 24 november volgt een wedstrijd die meetelt voor de ranking van Platform Drone Racing Nederland (PDRNL). Via PDRNL werken vijf Nederlandse drone-raceorganisaties samen aan wedstrijden, een landelijke ranking en kennisdeling.',
    },
    {
        year: '2020–21',
        dateTime: '2021',
        label: 'Pauze en herstart',
        title: 'Na een coronapauze hervat DDS de events.',
        body: 'Door het coronavirus liggen de events vanaf maart 2020 stil. Richard en Boudewijn stoppen in deze periode met organiseren. Op 22 augustus 2021 pakt DDS de draad weer op. Nico Kraakman sluit aan bij het team en tijdens de pauze krijgt de huisstijl een nieuw logo.',
    },
    {
        year: '2022',
        dateTime: '2022',
        label: 'Vijf jaar DDS',
        title: 'Het vijfjarig jubileum.',
        body: 'In juli viert DDS het vijfjarig bestaan met een vliegavond, drinken, snacks en taart. Ter gelegenheid van het jubileum is er ook een prijsvraag: hoeveel propellers zitten er in een vaas?',
    },
    {
        year: '2023',
        dateTime: '2023',
        label: 'Uitbreiding team',
        title: 'Zef en Dennis Molenaar sluiten aan.',
        body: 'Zef en Dennis Molenaar sluiten aan bij het team. Zef neemt het trackdesign op zich; samen worden zij verantwoordelijk voor de trackspullen.',
    },
    {
        year: '2024',
        dateTime: '2024',
        label: 'Nieuw seizoensritme',
        title: 'Zeven vliegavonden en een seizoensticket.',
        body: 'Vanaf september gaat DDS over op maximaal zeven vliegavonden per winterseizoen. De avonden vinden ongeveer maandelijks plaats en piloten kunnen voor het eerst één seizoensticket voor het hele seizoen kopen.',
    },
    {
        year: '2025/26',
        dateTime: '2025',
        label: 'Uitbreiding team',
        title: 'Marijn Koesen sluit aan.',
        body: 'In het winterseizoen 2025/2026 sluit Marijn Koesen aan. Hij neemt het trackdesign over van Zef, maakt deel uit van team track en kan bij afwezigheid van Klaas de tijdregistratie verzorgen.',
    },
];

export default function About({ seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                size="compact"
                kicker="Over Dutch Drone Squad"
                title={
                    <>
                        FPV-trainingen en races
                        <span className="block text-dds-cyan">in Alkmaar.</span>
                    </>
                }
                description="Dutch Drone Squad organiseert sinds 2017 indoor FPV-trainingen, races en demonstraties in Alkmaar en omgeving."
                actions={[
                    {
                        label: 'Bekijk de agenda',
                        href: eventsIndex.url(),
                    },
                    {
                        label: 'Lees hoe je begint',
                        href: gettingStartedIndex.url({
                            query: { source: 'about-hero' },
                        }),
                    },
                ]}
                media={{
                    src: '/images/dds/racing/training-community.jpg',
                    alt: 'Piloten en bezoekers rond het indoor FPV-parcours tijdens een event van Dutch Drone Squad',
                    position: 'center 45%',
                }}
                separatorTone="air"
            />

            <IdentitySection />
            <TimelineSection />
            <PilotsSection />
            <BehindTheRaceSection />
        </>
    );
}

function IdentitySection() {
    return (
        <section
            aria-labelledby="identity-heading"
            className="bg-air py-public-section text-deep-signal"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="grid gap-8 lg:grid-cols-[0.82fr_1.18fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow line={false}>Het begin</Eyebrow>
                        <h2
                            id="identity-heading"
                            className="mt-5 max-w-xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl lg:text-6xl"
                        >
                            Een plek om in de winter te vliegen.
                        </h2>
                    </div>
                    <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8">
                        In 2017 zocht Klaas Schoute een indoorlocatie om ook in
                        de winter te kunnen vliegen. Na contact met
                        verschillende partijen kwam hij uit bij het Sportpaleis
                        in Alkmaar en richtte hij samen met Richard de Wit Dutch
                        Drone Squad op.
                    </p>
                </div>

                <div className="mt-12 grid gap-8 lg:grid-cols-3">
                    <div className="border-t border-deep-signal/18 pt-5">
                        <h3 className="font-public-display text-xl font-semibold tracking-[-0.025em]">
                            Open opgezet
                        </h3>
                        <p className="mt-3 text-base leading-7 text-signal-muted">
                            Vanaf de oprichting koos DDS voor een open opzet.
                            Events worden openbaar aangekondigd, informatie is
                            makkelijk te vinden en de agenda wordt waar mogelijk
                            voor een volledig seizoen gepland.
                        </p>
                    </div>
                    <div className="border-t border-deep-signal/18 pt-5">
                        <h3 className="font-public-display text-xl font-semibold tracking-[-0.025em]">
                            Kennismaken
                        </h3>
                        <p className="mt-3 text-base leading-7 text-signal-muted">
                            Nieuwe piloten kunnen eerst komen kijken, vragen
                            stellen en tijdens een trainingsavond kennismaken
                            met de hobby.
                        </p>
                    </div>
                    <div className="border-t border-deep-signal/18 pt-5">
                        <h3 className="font-public-display text-xl font-semibold tracking-[-0.025em]">
                            Opbrengsten
                        </h3>
                        <p className="mt-3 text-base leading-7 text-signal-muted">
                            We organiseren de events op vrijwillige basis. De
                            opbrengsten gebruiken we voor de huur van de
                            vlieglocatie, reparaties aan het baanmateriaal en
                            investeringen in techniek.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

function TimelineSection() {
    return (
        <section
            aria-labelledby="timeline-heading"
            data-testid="about-timeline"
            className="overflow-hidden bg-night-950 py-public-section text-white"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="grid gap-8 lg:grid-cols-[0.82fr_1.18fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow inverse line={false}>
                            Geschiedenis · sinds 2017
                        </Eyebrow>
                        <h2
                            id="timeline-heading"
                            className="mt-5 max-w-2xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl lg:text-6xl"
                        >
                            Wat er sinds de oprichting is gebeurd.
                        </h2>
                    </div>
                    <p className="max-w-2xl text-base leading-7 text-white/65 sm:text-lg sm:leading-8">
                        Deze tijdlijn bevat de belangrijkste veranderingen in de
                        events, samenwerkingen en samenstelling van het team.
                    </p>
                </div>

                <ol
                    aria-label="Geschiedenis van Dutch Drone Squad"
                    className="relative mt-16 sm:mt-20"
                >
                    <span
                        aria-hidden="true"
                        className="absolute top-3 bottom-3 left-[0.4375rem] w-px bg-white/18 md:left-1/2 md:-translate-x-px"
                    />
                    {timeline.map((entry, index) => (
                        <TimelineItem
                            key={entry.dateTime}
                            entry={entry}
                            index={index}
                        />
                    ))}
                </ol>
            </div>
        </section>
    );
}

function TimelineItem({
    entry,
    index,
}: {
    entry: TimelineEntry;
    index: number;
}) {
    const isEven = index % 2 === 0;

    return (
        <li
            data-testid={`about-timeline-${entry.dateTime}`}
            className="relative grid grid-cols-[2rem_1fr] pb-10 last:pb-0 md:min-h-0 md:grid-cols-[1fr_5rem_1fr] md:items-start md:pb-14"
        >
            <span
                aria-hidden="true"
                className="relative z-10 mt-1 flex size-4 items-center justify-center border border-dds-cyan bg-night-950 md:col-start-2 md:mx-auto"
            >
                <span className="size-1.5 bg-dds-orange" />
            </span>

            <article
                className={
                    isEven
                        ? 'col-start-2 md:col-start-1 md:row-start-1 md:pr-8 md:text-right'
                        : 'col-start-2 md:col-start-3 md:row-start-1 md:pl-8'
                }
            >
                <div
                    className={
                        isEven
                            ? 'flex flex-wrap items-baseline gap-x-4 gap-y-2 md:justify-end'
                            : 'flex flex-wrap items-baseline gap-x-4 gap-y-2'
                    }
                >
                    <time
                        dateTime={entry.dateTime}
                        className="font-public-display text-4xl leading-none font-semibold tracking-[-0.05em] text-dds-cyan sm:text-5xl"
                    >
                        {entry.year}
                    </time>
                    <span className="text-xs font-semibold tracking-[0.1em] text-white/62 uppercase">
                        {entry.label}
                    </span>
                </div>
                <h3 className="mt-5 font-public-display text-2xl leading-[1.1] font-semibold tracking-[-0.035em] text-balance sm:text-3xl">
                    {entry.title}
                </h3>
                <p
                    className={
                        isEven
                            ? 'mt-4 max-w-xl text-sm leading-7 text-white/62 sm:text-base md:ml-auto'
                            : 'mt-4 max-w-xl text-sm leading-7 text-white/62 sm:text-base'
                    }
                >
                    {entry.body}
                </p>
            </article>
        </li>
    );
}

function PilotsSection() {
    return (
        <section
            aria-labelledby="pilots-heading"
            className="bg-deep-signal text-white"
        >
            <div className="mx-auto grid w-full max-w-[100rem] lg:grid-cols-[1.16fr_0.84fr]">
                <div className="relative min-h-[28rem] overflow-hidden bg-night-900 sm:min-h-[36rem] lg:min-h-[46rem]">
                    <img
                        src="/images/dds/racing/pilot-at-training.jpg"
                        alt="FPV-piloot bereidt zich naast het indoor raceparcours voor op een vlucht"
                        loading="lazy"
                        className="absolute inset-0 h-full w-full object-cover"
                        style={{ objectPosition: '54% center' }}
                    />
                    <div className="absolute inset-0 bg-linear-to-t from-deep-signal/45 via-transparent to-transparent lg:bg-linear-to-r lg:from-transparent lg:to-deep-signal/18" />
                </div>

                <div className="flex items-center px-public-gutter py-16 sm:py-20 lg:px-16 lg:py-24 xl:px-20">
                    <div className="max-w-xl">
                        <Eyebrow inverse line={false}>
                            Piloten en bezoekers
                        </Eyebrow>
                        <h2
                            id="pilots-heading"
                            className="mt-5 font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                        >
                            Deelnemen of eerst komen kijken.
                        </h2>
                        <p className="mt-7 text-base leading-8 text-white/68 sm:text-lg">
                            Tijdens een trainingsavond is er ruimte om vragen te
                            stellen over materiaal, instellingen en het verloop
                            van de avond. Piloten helpen elkaar ook met
                            vlieglijnen, reparaties en technische problemen.
                            Door de jaren heen hebben ook veel internationale
                            piloten aan DDS-events deelgenomen.
                        </p>
                        <p className="mt-5 text-base leading-8 text-white/68 sm:text-lg">
                            Je kunt altijd eerst als bezoeker komen kijken. Wil
                            je zelf vliegen, dan vind je op deze website
                            praktische informatie over de voorbereiding en
                            deelname. Bij het opbouwen en opruimen van de baan
                            helpen de aanwezige piloten mee.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

const teamMembers = [
    {
        initials: 'KS',
        name: 'Klaas Schoute',
        note: 'Oprichter · techniek en development',
    },
    {
        initials: 'NK',
        name: 'Nico Kraakman',
        note: 'Financiën · lessen op maat',
    },
    {
        initials: 'ZM',
        name: 'Zef Molenaar',
        note: 'Team track',
    },
    {
        initials: 'DM',
        name: 'Dennis Molenaar',
        note: 'Team track',
    },
    {
        initials: 'MK',
        name: 'Marijn Koesen',
        note: 'Trackdesigner · team track · tijdregistratie',
    },
];

function BehindTheRaceSection() {
    return (
        <section
            aria-labelledby="team-heading"
            data-testid="team-section"
            className="bg-warmup py-public-section text-deep-signal"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow line={false}>Team DDS</Eyebrow>
                        <h2
                            id="team-heading"
                            className="mt-5 max-w-xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                        >
                            Het huidige team.
                        </h2>
                    </div>
                    <p className="mt-7 max-w-xl text-base leading-8 text-signal-muted sm:text-lg">
                        Binnen het team heeft ieder een eigen aandachtsgebied.
                        Tijdens events helpt iedereen waar dat nodig is.
                    </p>
                </div>

                <div className="-mx-public-gutter mt-12 md:mx-0">
                    <ul
                        aria-label="Teamleden van Dutch Drone Squad"
                        data-testid="team-list"
                        tabIndex={0}
                        className="flex snap-x snap-proximity scroll-px-public-gutter scrollbar-none gap-3 overflow-x-auto overscroll-x-contain px-public-gutter pb-4 focus-visible:ring-2 focus-visible:ring-dds-blue focus-visible:outline-none focus-visible:ring-inset md:grid md:snap-none md:grid-cols-3 md:gap-px md:overflow-visible md:bg-deep-signal/18 md:px-0 md:pb-0 xl:grid-cols-5"
                    >
                        {teamMembers.map((member, index) => (
                            <li
                                key={member.name}
                                className="grid w-[82vw] max-w-xs shrink-0 snap-start grid-cols-[5.5rem_1fr] items-center gap-4 bg-warmup p-3 md:block md:w-auto md:max-w-none"
                            >
                                <div
                                    aria-hidden="true"
                                    data-testid="team-portrait-placeholder"
                                    className="relative isolate aspect-[4/5] w-[5.5rem] overflow-hidden bg-deep-signal text-white md:w-auto"
                                >
                                    <span className="absolute top-0 right-0 h-1/2 w-2 bg-dds-orange md:w-3" />
                                    <span className="absolute bottom-0 left-0 h-px w-2/3 bg-dds-cyan/65" />
                                    <span className="absolute top-2 left-2 font-mono text-[0.5rem] font-semibold tracking-[0.12em] text-white/45 md:top-5 md:left-5 md:text-[0.65rem] md:tracking-[0.14em]">
                                        TEAM /{' '}
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <span className="absolute inset-0 grid place-items-center font-public-display text-3xl font-semibold tracking-[-0.08em] text-dds-cyan md:text-7xl xl:text-6xl">
                                        {member.initials}
                                    </span>
                                    <span className="absolute right-4 bottom-4 left-4 hidden border-t border-white/18 pt-3 text-[0.65rem] font-semibold tracking-[0.12em] text-white/58 uppercase md:block">
                                        Portret volgt
                                    </span>
                                </div>
                                <div className="min-w-0 self-center">
                                    <h3 className="font-public-display text-lg font-semibold tracking-[-0.025em] md:mt-5 md:text-xl">
                                        {member.name}
                                    </h3>
                                    <p className="mt-1 text-sm leading-6 text-deep-signal/72 md:min-h-12">
                                        {member.note}
                                    </p>
                                    <span className="mt-2 block text-[0.65rem] font-semibold tracking-[0.12em] text-deep-signal uppercase md:hidden">
                                        Portret volgt
                                    </span>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}
