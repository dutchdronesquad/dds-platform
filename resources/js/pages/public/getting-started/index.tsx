import { Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    Compass,
    Gamepad2,
    HelpCircle,
    Route,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    CtaBand,
    Eyebrow,
    PublicHero,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { contact, house_rules as houseRules } from '@/routes';
import { show as gettingStartedShow } from '@/routes/getting_started';
import { index as locationsIndex } from '@/routes/locations';
import type {
    GettingStartedEntrySource,
    GettingStartedGuideSummary,
    SeoMetadata,
} from '@/types';

type Props = {
    entrySource: GettingStartedEntrySource | null;
    guides: GettingStartedGuideSummary[];
    seo: SeoMetadata;
};

const quickAnswers: {
    body: ReactNode;
    icon: LucideIcon;
    title: string;
}[] = [
    {
        title: 'Kan ik zonder ervaring aansluiten?',
        body: 'Reguliere Sportpaleis-trainingen zijn voor piloten die al zelfstandig een parcours vliegen. Ben je nieuw? Begin in een simulator en vraag naar een beginnersmoment in De Goorn.',
        icon: Gamepad2,
    },
    {
        title: 'Moet ik meteen een drone kopen?',
        body: (
            <>
                Nee. Met een zender en simulator kun je eerst leren sturen.
                Vraag vóór een complete of tweedehands set advies in onze{' '}
                <a
                    href="https://chat.whatsapp.com/HInatYEIAAPEhtj3WNJy9V"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="font-semibold text-dds-blue underline decoration-dds-blue/30 underline-offset-3 hover:text-deep-signal dark:text-dds-cyan dark:hover:text-white"
                >
                    WhatsApp-community
                </a>
                .
            </>
        ),
        icon: HelpCircle,
    },
    {
        title: 'Wanneer ben ik klaar voor een training?',
        body: 'Als je zelfstandig een compleet parcours kunt vliegen, basisreparaties kunt uitvoeren en begrijpt hoe video-kanalen en veilig laden werken.',
        icon: ShieldCheck,
    },
];

export default function GettingStartedIndex({
    entrySource,
    guides,
    seo,
}: Props) {
    const guideEntrySource = entrySource ?? 'navigation';

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker="Beginnen met FPV"
                title="Van nieuwsgierig naar goed voorbereid."
                description="Een inhoudelijke route door FPV-vliegen: leer de basis, oefen veilig, kies passende uitrusting en begrijp wat DDS van je verwacht voordat je naar een training komt."
                actions={[
                    {
                        label: 'Start bij de basis',
                        href: gettingStartedShow.url('first-fpv-flight', {
                            query: { source: guideEntrySource },
                        }),
                    },
                    {
                        label: 'Stel een gerichte vraag',
                        href: contact.url({
                            query: { source: 'getting-started' },
                        }),
                    },
                ]}
                media={{
                    src: '/images/dds/racing/pilot-at-training.jpg',
                    alt: 'Piloot tijdens een indoor training van Dutch Drone Squad',
                    position: '62% center',
                }}
                separatorTone="muted"
            />

            <div
                data-entry-source={entrySource ?? undefined}
                className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white"
            >
                <section
                    aria-labelledby="quick-answers-heading"
                    className="border-b border-paddock-rule bg-night-50 dark:border-white/12 dark:bg-night-900"
                >
                    <div className="mx-auto w-full max-w-[86rem] px-public-gutter py-14 sm:py-16">
                        <div className="max-w-3xl">
                            <Eyebrow line={false}>Eerst dit</Eyebrow>
                            <h2
                                id="quick-answers-heading"
                                className="mt-4 font-public-display text-3xl font-semibold tracking-[-0.04em] sm:text-4xl"
                            >
                                Goed om te weten voordat je begint.
                            </h2>
                        </div>
                        <div className="mt-8 grid gap-5 lg:grid-cols-3">
                            {quickAnswers.map((answer) => (
                                <article
                                    key={answer.title}
                                    className="rounded-sm border border-paddock-rule bg-white p-6 dark:border-white/10 dark:bg-night-950"
                                >
                                    <answer.icon
                                        aria-hidden="true"
                                        className="size-5 text-dds-blue dark:text-dds-cyan"
                                    />
                                    <h3 className="mt-4 text-lg font-semibold tracking-[-0.02em]">
                                        {answer.title}
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-signal-muted dark:text-night-400">
                                        {answer.body}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="guides-heading"
                    className="mx-auto w-full max-w-[86rem] px-public-gutter py-16 sm:py-20"
                >
                    <div className="border-b border-paddock-rule pb-8 dark:border-white/12">
                        <Eyebrow line={false}>Kennisroute</Eyebrow>
                        <h2
                            id="guides-heading"
                            className="mt-5 max-w-4xl font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                        >
                            Drie gidsen, in een logische volgorde.
                        </h2>
                        <p className="mt-5 max-w-3xl text-base leading-7 text-signal-muted dark:text-night-400">
                            Begin bij de basis als FPV helemaal nieuw voor je
                            is. Heb je al simulator- of vliegervaring, spring
                            dan direct naar materiaal of de voorbereiding op een
                            trainingsavond.
                        </p>
                    </div>

                    <ol
                        aria-label="Gidsen om te beginnen met FPV"
                        className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        {guides.map((guide, index) => (
                            <li key={guide.slug}>
                                <Link
                                    href={gettingStartedShow(guide.slug, {
                                        query: {
                                            source: guideEntrySource,
                                        },
                                    })}
                                    prefetch
                                    className="group flex h-full flex-col overflow-hidden rounded-sm border border-paddock-rule bg-white transition-colors hover:border-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
                                >
                                    <div className="relative aspect-[16/9] overflow-hidden bg-deep-signal">
                                        <img
                                            src={guide.heroImage.src}
                                            alt={guide.heroImage.alt}
                                            loading="lazy"
                                            style={{
                                                objectPosition:
                                                    guide.heroImage.position,
                                            }}
                                            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
                                        />
                                        <span className="absolute top-3 left-3 flex size-8 items-center justify-center rounded-full bg-white/90 font-mono text-xs font-semibold text-deep-signal dark:bg-night-950/90 dark:text-white">
                                            {index + 1}
                                        </span>
                                    </div>
                                    <div className="flex flex-1 flex-col p-5 sm:p-6">
                                        <p className="font-mono text-[0.68rem] font-semibold tracking-[0.09em] text-dds-blue uppercase dark:text-dds-cyan">
                                            <span className="rounded-sm bg-dds-blue/8 px-2 py-1 dark:bg-dds-cyan/10">
                                                {guide.eyebrow}
                                            </span>
                                        </p>
                                        <h3 className="mt-3 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal transition-colors group-hover:text-dds-blue sm:text-[1.7rem] dark:text-white dark:group-hover:text-dds-cyan">
                                            {guide.title}
                                        </h3>
                                        <p className="mt-4 text-sm leading-6 text-signal-muted dark:text-night-400">
                                            {guide.summary}
                                        </p>
                                        <div className="mt-auto flex items-center justify-end gap-2 pt-6 text-sm font-semibold text-dds-blue dark:text-dds-cyan">
                                            Lees de gids
                                            <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ol>
                </section>

                <section
                    aria-labelledby="elsewhere-heading"
                    className="mx-auto w-full max-w-7xl px-public-gutter py-14 lg:py-20"
                >
                    <div className="flex items-center gap-3">
                        <Route
                            aria-hidden="true"
                            className="size-5 text-dds-blue dark:text-dds-cyan"
                        />
                        <h2
                            id="elsewhere-heading"
                            className="font-public-display text-2xl font-semibold tracking-[-0.03em]"
                        >
                            Praktische informatie
                        </h2>
                    </div>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        <ResourceLink
                            href={locationsIndex()}
                            icon={Compass}
                            title="Bekijk de actuele locaties"
                            body="Adres, faciliteiten, bereikbaarheid en komende activiteiten staan op de locatiepagina’s."
                        />
                        <ResourceLink
                            href={houseRules()}
                            icon={ShieldCheck}
                            title="Lees de huisregels"
                            body="Bindende regels en event-specifieke vereisten gaan altijd vóór de samenvatting in deze gidsen."
                        />
                    </div>
                </section>
            </div>

            <CtaBand
                eyebrow="Nog onzeker?"
                title="Waar kunnen we je mee helpen?"
                description="Vertel kort waar je vraag over gaat en hoeveel ervaring je al hebt. Gaat het om materiaal, een event, een locatie of toegankelijkheid? Laat het gerust weten."
                action={{
                    label: 'Neem contact op',
                    href: contact.url({
                        query: { source: 'getting-started' },
                    }),
                }}
            />
        </>
    );
}

function ResourceLink({
    body,
    href,
    icon: Icon,
    title,
}: {
    body: string;
    href: ReturnType<typeof locationsIndex>;
    icon: LucideIcon;
    title: string;
}) {
    return (
        <Link
            href={href}
            prefetch
            className="group flex items-center justify-between gap-4 rounded-sm border border-paddock-rule bg-white p-5 transition-colors hover:border-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
        >
            <span>
                <span className="flex items-center gap-2 font-semibold text-deep-signal dark:text-white">
                    <Icon className="size-4 text-dds-blue dark:text-dds-cyan" />
                    {title}
                </span>
                <span className="mt-1 block text-sm leading-6 text-signal-muted dark:text-night-400">
                    {body}
                </span>
            </span>
            <ArrowUpRight className="size-4 shrink-0 text-dds-blue transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 dark:text-dds-cyan" />
        </Link>
    );
}
