import { Play } from 'lucide-react';
import PublicExternalLink from '@/components/public/public-external-link';
import {
    CtaBand,
    Eyebrow,
    PublicHero,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { contact } from '@/routes';
import type { SeoMetadata } from '@/types';

type Props = {
    seo: SeoMetadata;
};

type Mention = {
    date: string;
    dateTime: string;
    image?: {
        alt: string;
        src: string;
    };
    kind?: string;
    outlet: string;
    title: string;
    url?: string;
};

const mentionGroups: { mentions: Mention[]; year: string }[] = [
    {
        year: '2023',
        mentions: [
            {
                date: '11 april',
                dateTime: '2023-04-11',
                kind: 'Cluboverzicht',
                outlet: 'Drone Optiek',
                title: 'Vermelding in het overzicht van droneclubs',
                url: 'https://drone-optiek.nl/drone-clubs/',
            },
        ],
    },
    {
        year: '2019',
        mentions: [
            {
                date: '25 november',
                dateTime: '2019-11-25',
                kind: 'Artikel',
                outlet: 'Alkmaar Centraal',
                title: 'Voorronde voor NK indoor drone racen 2020 in Sportpaleis Alkmaar',
                url: 'https://alkmaarcentraal.nl/nieuws/60048952-voorronde-voor-nk-indoor-drone-racen-2020-in-sportpaleis-alkmaar-video',
            },
            {
                date: '25 november',
                dateTime: '2019-11-25',
                kind: 'Artikel',
                outlet: 'DroneRacers.nl',
                title: 'Dennis Mennema wint eerste race van indoor NK',
                url: 'https://www.droneracers.nl/nieuws/dennis-mennema-dronedfpv-wint-eerste-race-van-indoor-nk/',
            },
            {
                date: '17 november',
                dateTime: '2019-11-17',
                outlet: 'Zondagskrant Alkmaar',
                title: 'Vermelding in de zondagskrant',
                image: {
                    src: '/images/dds/media/zondagskrant-alkmaar-2019.png',
                    alt: 'Krantenartikel Nederlands Kampioenschap Indoor Drone Racen in Zondagskrant Alkmaar',
                },
            },
            {
                date: '29 april',
                dateTime: '2019-04-29',
                kind: 'Artikel',
                outlet: 'STAD Alkmaar',
                title: 'Dutch Drone Squad gaat super hard',
                url: 'https://stad-alkmaar.com/dutch-drone-squad-gaat-super-hard/',
            },
        ],
    },
    {
        year: '2018',
        mentions: [
            {
                date: '16 juli',
                dateTime: '2018-07-16',
                outlet: 'QuadInsider Magazine',
                title: 'Vermelding in QuadInsider Magazine',
                image: {
                    src: '/images/dds/media/quadinsider-magazine-2018.jpg',
                    alt: 'Magazinepagina Vliegspot van de maand over Dutch Drone Squad in het Sportpaleis Alkmaar',
                },
            },
            {
                date: '27 juni',
                dateTime: '2018-06-27',
                outlet: 'Noordhollands Dagblad',
                title: 'Vermelding in het Noordhollands Dagblad',
                image: {
                    src: '/images/dds/media/noordhollands-dagblad-2018.jpg',
                    alt: 'Krantenartikel Het ultieme racegevoel met de drone in het Noordhollands Dagblad',
                },
            },
        ],
    },
    {
        year: '2017',
        mentions: [
            {
                date: '30 november',
                dateTime: '2017-11-30',
                kind: 'Artikel',
                outlet: 'STAD Alkmaar',
                title: 'Dutch Drone Squad',
                url: 'http://stad-alkmaar.com/dutch-drone-squad/',
            },
        ],
    },
];

type VideoMention = {
    href: string;
    label: string;
    poster: string;
    title: string;
};

const featuredVideo: VideoMention = {
    href: 'https://www.youtube.com/watch?v=MXn3xfgL9Bw&list=PLsxzBVnCYZqdqi3RiUgZUFG8n8_rfvDMa',
    label: 'RC Playground Network · 2023',
    poster: '/images/dds/media/youtube-rc-playground-2023.jpg',
    title: 'Reportage over Dutch Drone Squad',
};

const videoMentions: VideoMention[] = [
    {
        href: 'https://www.youtube.com/watch?v=nxodyWZ3Nok',
        label: 'Pilootvideo · februari 2024',
        poster: '/images/dds/media/youtube-dds-february-2024.jpg',
        title: 'Dutch Drone Squad · februari 2024',
    },
    {
        href: 'https://streekstadcentraal.nl/images/video/60048952voorronde-voor-nk-indoor-drone-racen-2020-in-sportpaleis-alkmaar-video.mp4',
        label: 'Streekstad Centraal · november 2019',
        poster: '/images/dds/media/video-streekstad-centraal-2019.jpg',
        title: 'Voorronde NK indoor drone racen 2020',
    },
    {
        href: 'https://www.youtube.com/watch?v=-yCxYnKsWrc',
        label: 'NPO Zapp · november 2019 · finale gefilmd bij DDS',
        poster: '/images/dds/media/youtube-zapplive-drone-racer.jpg',
        title: 'Hoe word je professioneel drone racer?',
    },
    {
        href: 'https://www.youtube.com/watch?v=b7SPYTpBAD4',
        label: 'Aftermovie · 2019',
        poster: '/images/dds/media/youtube-fmf-alkmaar-2019.jpg',
        title: 'FMF Alkmaar 2019',
    },
];

type ScanMention = Omit<Mention, 'image'> & {
    image: NonNullable<Mention['image']>;
    year: string;
};

const scanMentions: ScanMention[] = mentionGroups.flatMap(
    ({ mentions, year }) =>
        mentions
            .filter(
                (
                    mention,
                ): mention is Mention & {
                    image: NonNullable<Mention['image']>;
                } => Boolean(mention.image),
            )
            .map((mention) => ({ ...mention, year })),
);

const landscapeScan = scanMentions.find(
    ({ outlet }) => outlet === 'Noordhollands Dagblad',
);
const portraitScans = scanMentions.filter(
    ({ outlet }) => outlet !== 'Noordhollands Dagblad',
);

const onlineMentionGroups = mentionGroups
    .map((group) => ({
        ...group,
        mentions: group.mentions.filter((mention) => mention.url),
    }))
    .filter(({ mentions }) => mentions.length > 0);

export default function Media({ seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                size="compact"
                kicker="Pers & publicaties"
                title="In de media"
                description="Reportages, interviews en vermeldingen over onze vliegavonden, wedstrijden en de ontwikkeling van de dronesport."
                actions={[{ label: 'Perscontact', href: contact().url }]}
                media={{
                    src: '/images/dds/racing/race-control.jpg',
                    alt: 'Race control tijdens een indoor FPV-event van Dutch Drone Squad',
                    position: 'center 48%',
                }}
                separatorTone="paper"
            />

            <MediaLead />
            <PressScans />
            <OnlineArchive />

            <CtaBand
                eyebrow="Pers en samenwerkingen"
                title="Een verhaal maken over FPV-racing?"
                description="Voor interviews, beeldmateriaal en achtergrondinformatie brengen we je graag in contact met de juiste mensen binnen Dutch Drone Squad."
                action={{ label: 'Neem contact op', href: contact().url }}
            />
        </>
    );
}

function MediaLead() {
    return (
        <section
            aria-labelledby="mentions-heading"
            className="bg-paper py-public-section text-deep-signal dark:bg-night-950 dark:text-white"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <header className="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow line={false}>In beeld</Eyebrow>
                        <h2
                            id="mentions-heading"
                            className="mt-5 max-w-2xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl lg:text-6xl"
                        >
                            Reportages en racebeelden.
                        </h2>
                    </div>
                    <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8 dark:text-night-400">
                        Van externe reportages tot eigen parcoursvideo’s. Bekijk
                        Dutch Drone Squad, onze locaties en events door de jaren
                        heen.
                    </p>
                </header>

                <article
                    data-testid="featured-media-mention"
                    className="group mt-12 grid overflow-hidden bg-deep-signal text-white shadow-sm lg:grid-cols-[1.12fr_0.88fr]"
                >
                    <PublicExternalLink
                        href={featuredVideo.href}
                        showIcon={false}
                        className="relative block min-h-72 overflow-hidden rounded-none lg:min-h-112"
                        aria-label={`${featuredVideo.title} bekijken`}
                    >
                        <img
                            src={featuredVideo.poster}
                            alt="Interview tijdens een reportage over Dutch Drone Squad"
                            className="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none"
                        />
                        <div className="absolute inset-0 bg-linear-to-t from-deep-signal/75 via-transparent to-transparent" />
                        <span className="absolute bottom-6 left-6 flex size-12 items-center justify-center bg-dds-orange text-deep-signal sm:bottom-8 sm:left-8">
                            <Play
                                aria-hidden="true"
                                className="size-5 fill-current"
                            />
                        </span>
                    </PublicExternalLink>
                    <div className="flex flex-col justify-center p-7 sm:p-10 lg:p-12">
                        <Eyebrow inverse line={false}>
                            Uitgelichte reportage
                        </Eyebrow>
                        <p className="mt-6 font-mono text-xs tracking-[0.12em] text-night-400 uppercase">
                            {featuredVideo.label}
                        </p>
                        <h3 className="mt-3 font-public-display text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance sm:text-4xl">
                            {featuredVideo.title}
                        </h3>
                        <PublicExternalLink
                            href={featuredVideo.href}
                            className="mt-8 inline-flex min-h-11 w-fit items-center gap-2 border border-white/18 px-5 py-3 text-sm font-semibold text-white transition hover:border-dds-cyan hover:text-dds-cyan"
                        >
                            Bekijk op YouTube
                        </PublicExternalLink>
                    </div>
                </article>

                <div className="mt-12 border-t border-deep-signal/15 pt-8 dark:border-white/12">
                    <h3 className="font-public-display text-2xl font-semibold tracking-[-0.035em] sm:text-3xl">
                        Verder kijken
                    </h3>
                    <div className="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {videoMentions.map((video) => (
                            <VideoCard key={video.href} video={video} />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

function VideoCard({ video }: { video: VideoMention }) {
    return (
        <article className="h-full">
            <PublicExternalLink
                href={video.href}
                showIcon={false}
                className="group h-full w-full flex-col items-stretch overflow-hidden rounded-sm border border-paddock-rule bg-white text-deep-signal shadow-sm dark:bg-night-900 dark:text-white"
                aria-label={`${video.title} bekijken`}
            >
                <span className="relative aspect-video overflow-hidden bg-deep-signal">
                    <img
                        src={video.poster}
                        alt=""
                        loading="lazy"
                        className="size-full object-cover transition duration-300 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none"
                    />
                    <span className="absolute bottom-3 left-3 flex size-9 items-center justify-center bg-dds-orange text-deep-signal">
                        <Play
                            aria-hidden="true"
                            className="size-4 fill-current"
                        />
                    </span>
                </span>
                <span className="flex flex-1 flex-col p-5">
                    <span className="block font-mono text-[0.65rem] tracking-[0.1em] text-dds-blue uppercase dark:text-dds-cyan">
                        {video.label}
                    </span>
                    <span className="mt-2 block font-public-display text-xl leading-tight font-semibold tracking-[-0.03em]">
                        {video.title}
                    </span>
                </span>
            </PublicExternalLink>
        </article>
    );
}

function PressScans() {
    return (
        <section
            aria-labelledby="press-scans-heading"
            data-testid="media-scans"
            className="bg-air py-16 text-deep-signal sm:py-20 lg:py-24 dark:bg-night-900 dark:text-white"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <header className="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow line={false}>Uit het archief</Eyebrow>
                        <h2
                            id="press-scans-heading"
                            className="mt-5 max-w-xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                        >
                            Op papier gevangen.
                        </h2>
                    </div>
                    <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8 dark:text-night-400">
                        Deze kranten- en magazinepagina’s zijn bewaard als scan.
                        Open een publicatie om het originele beeld op volledig
                        formaat te bekijken.
                    </p>
                </header>

                <div className="mt-12 grid gap-6 lg:grid-cols-12 lg:items-start">
                    {landscapeScan && (
                        <div className="lg:col-span-7">
                            <ScanCard mention={landscapeScan} landscape />
                        </div>
                    )}
                    <div className="grid gap-6 sm:grid-cols-2 lg:col-span-5">
                        {portraitScans.map((mention) => (
                            <ScanCard
                                key={`${mention.year}-${mention.outlet}`}
                                mention={mention}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

function ScanCard({
    landscape = false,
    mention,
}: {
    landscape?: boolean;
    mention: ScanMention;
}) {
    return (
        <article className="group">
            <PublicExternalLink
                href={mention.image.src}
                showIcon={false}
                className={`flex h-72 w-full overflow-hidden rounded-sm border border-paddock-rule bg-white p-4 shadow-sm sm:h-80 sm:p-6 lg:h-96 ${landscape ? 'lg:p-7' : ''}`}
                aria-label={`Bekijk de scan uit ${mention.outlet} op volledig formaat`}
            >
                <img
                    src={mention.image.src}
                    alt={mention.image.alt}
                    loading="lazy"
                    className="m-auto max-h-full max-w-full object-contain shadow-md transition duration-300 group-hover:scale-[1.01] motion-reduce:transform-none motion-reduce:transition-none"
                />
            </PublicExternalLink>
            <div className="border-t border-deep-signal/15 pt-5 dark:border-white/12">
                <p className="font-mono text-xs tracking-[0.1em] text-dds-blue uppercase dark:text-dds-cyan">
                    <time dateTime={mention.dateTime}>
                        {mention.date} {mention.year}
                    </time>
                </p>
                <h3 className="mt-2 font-public-display text-2xl leading-tight font-semibold tracking-[-0.035em]">
                    {mention.outlet}
                </h3>
                <PublicExternalLink
                    href={mention.image.src}
                    className="mt-3 text-sm font-semibold text-signal-muted transition hover:text-dds-blue dark:text-night-400 dark:hover:text-dds-cyan"
                >
                    Open originele scan
                </PublicExternalLink>
            </div>
        </article>
    );
}

function OnlineArchive() {
    return (
        <section
            aria-labelledby="online-archive-heading"
            data-testid="media-archive"
            className="bg-paper py-16 text-deep-signal sm:py-20 lg:py-24 dark:bg-night-950 dark:text-white"
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <header className="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-end lg:gap-20">
                    <div>
                        <Eyebrow line={false}>Online publicaties</Eyebrow>
                        <h2
                            id="online-archive-heading"
                            className="mt-5 max-w-xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                        >
                            Verder lezen en kijken.
                        </h2>
                    </div>
                    <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8 dark:text-night-400">
                        Externe reportages en artikelen, chronologisch
                        gerangschikt. Links openen rechtstreeks bij de
                        oorspronkelijke publicatie.
                    </p>
                </header>

                <div className="mt-12 border-t border-deep-signal/15 dark:border-white/12">
                    {onlineMentionGroups.map(({ year, mentions }) => (
                        <section
                            key={year}
                            aria-labelledby={`online-year-${year}`}
                            className="grid border-b border-deep-signal/15 py-8 md:grid-cols-[10rem_1fr] md:gap-10 lg:py-10 dark:border-white/12"
                        >
                            <h3
                                id={`online-year-${year}`}
                                className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                            >
                                {year}
                            </h3>
                            <ul className="mt-5 divide-y divide-deep-signal/12 md:mt-0 dark:divide-white/10">
                                {mentions.map((mention) => (
                                    <li
                                        key={`${mention.date}-${mention.outlet}`}
                                        className="grid min-w-0 gap-3 py-5 sm:py-6 lg:grid-cols-[7rem_10rem_minmax(0,1fr)] lg:items-start lg:gap-8"
                                    >
                                        <div className="flex items-center gap-3 lg:block">
                                            <time
                                                dateTime={mention.dateTime}
                                                className="text-xs font-semibold tracking-[0.08em] text-signal-muted uppercase dark:text-night-400"
                                            >
                                                {mention.date}
                                            </time>
                                            {mention.kind && (
                                                <span className="font-mono text-[0.65rem] tracking-[0.1em] text-dds-blue uppercase lg:mt-2 lg:block dark:text-dds-cyan">
                                                    {mention.kind}
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-sm font-semibold text-deep-signal lg:pt-2 dark:text-white">
                                            {mention.outlet}
                                        </p>
                                        {mention.url && (
                                            <PublicExternalLink
                                                href={mention.url}
                                                className="inline-flex min-h-11 min-w-0 items-start gap-2 py-1 font-public-display text-lg font-semibold tracking-[-0.025em] text-deep-signal transition hover:text-dds-blue sm:text-xl dark:text-white dark:hover:text-dds-cyan"
                                            >
                                                {mention.title}
                                            </PublicExternalLink>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
            </div>
        </section>
    );
}
