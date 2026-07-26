import { Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type { ReactNode } from 'react';
import PublicExternalLink from '@/components/public/public-external-link';
import {
    ContentBand,
    CtaBand,
    Eyebrow,
    PublicHero,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { house_rules as houseRules } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as gettingStartedIndex } from '@/routes/getting_started';
import type { GettingStartedGuideSummary, SeoMetadata } from '@/types';

type Props = {
    guide: GettingStartedGuideSummary;
    seo: SeoMetadata;
};

const journeySteps: { body: string; title: string }[] = [
    {
        title: 'Meld je aan',
        body: 'Gebruik de aanmeldroute op de eventpagina en vul de gevraagde informatie over je ervaring en videosysteem in.',
    },
    {
        title: 'Betaal je deelname',
        body: 'Heb je geen seizoensticket, betaal dan na je aanmelding voor de training. Met een geldig seizoensticket hoef je niet opnieuw te betalen.',
    },
    {
        title: 'Pak je materiaal in',
        body: 'Controleer de instellingen voor de training, laad veilig en neem je gereedschap en belangrijkste reserveonderdelen mee.',
    },
    {
        title: 'Help de baan opbouwen',
        body: 'Meld je bij aankomst bij de organisatie en help samen met de andere piloten de baan opbouwen. Zet je drone en videozender nog niet aan.',
    },
    {
        title: 'Loop de trackwalk',
        body: 'Na de opbouw loop je samen het parcours. Zo leer je de vliegrichting, de volgorde van de gates en de lastige stukken van de baan kennen.',
    },
    {
        title: 'Vlieg en laad volgens de regels',
        body: 'Je vliegt in je toegewezen heat. Buiten je heat wacht je met inschakelen, laad je op de aangewezen plek en houd je de voortgang van de avond in de gaten.',
    },
    {
        title: 'Pak in en ruim samen op',
        body: 'Controleer je materiaal, breng accu’s naar een veilige opslagspanning wanneer dat nodig is en help de track en werkplekken netjes achter te laten.',
    },
];

const technicalChecklist: { content: ReactNode; id: string }[] = [
    {
        id: 'fpv-scores',
        content: (
            <>
                <PublicExternalLink
                    href="https://fpvscores.com"
                    showIcon={false}
                    className="font-semibold text-dds-blue hover:text-deep-signal dark:text-dds-cyan dark:hover:text-white"
                >
                    FPV Scores
                </PublicExternalLink>{' '}
                UUID, indien vereist voor het event.
            </>
        ),
    },
    {
        id: 'video',
        content:
            'Videosysteem gecontroleerd; de VTX-tabel bevat de kanaal- en vermogensinstellingen die je flight controller mag gebruiken.',
    },
    {
        id: 'power',
        content:
            'Toegestaan zendvermogen, doorgaans 25 mW waar dat verplicht is.',
    },
    {
        id: 'bitrate',
        content:
            'Bitrate en videomodus gecontroleerd volgens de eventinformatie.',
    },
    {
        id: 'channel',
        content: 'Volg de procedure voor kanaaltoewijzing op locatie.',
    },
];

const packingChecklist = [
    'Opgeladen accu’s, balanceerstekkers in goede staat en een veilige opbergmethode.',
    'Propellers, basisgereedschap, soldeer- of reparatiemateriaal dat je zelf kunt gebruiken en gangbare reserveonderdelen.',
    'Radio, goggles, antennes, benodigde kabels en eventueel een stoel of persoonlijke bescherming als het event dat adviseert.',
    'De praktische eventinformatie en een concrete vraag voor iedere vereiste die nog onduidelijk is.',
];

const trainingTerms = [
    {
        term: 'Race director',
        description:
            'De persoon die de planning, timing, kanaalindeling en het veilige verloop coördineert.',
    },
    {
        term: 'Baanopbouw',
        description:
            'Samen de gates, obstakels en veiligheidsvoorzieningen plaatsen voordat de trackwalk begint.',
    },
    {
        term: 'Trackwalk',
        description:
            'Samen het parcours nalopen zodat gates, vliegrichting, lastige lijnen en veilige zones duidelijk zijn.',
    },
    {
        term: 'Heat',
        description:
            'Een vliegblok voor een kleine, vooraf ingedeelde groep piloten met compatibele video-kanalen.',
    },
];

export default function GettingStartedFirstDdsEvent({ guide, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={guide.eyebrow}
                title={guide.title}
                description="Tijdens een reguliere training vlieg je zelfstandig op een vast parcours. Na de opbouw loop je samen de trackwalk; daarna vlieg je volgens de heatindeling."
                actions={[
                    {
                        label: 'Terug naar de gids',
                        href: gettingStartedIndex.url(),
                    },
                ]}
                media={guide.heroImage}
                separatorTone="paper"
                size="compact"
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-labelledby="journey-heading"
                    className="py-public-section"
                >
                    <div className="mx-auto w-full max-w-7xl px-public-gutter">
                        <div className="max-w-2xl">
                            <Eyebrow line={false}>Stap voor stap</Eyebrow>
                            <h2
                                id="journey-heading"
                                className="mt-4 font-public-display text-3xl font-bold tracking-[-0.035em] text-balance text-night-950 sm:text-4xl dark:text-white"
                            >
                                De trainingsavond in zeven stappen.
                            </h2>
                            <p className="mt-5 text-base leading-7 text-night-500 dark:text-night-400">
                                De exacte tijden en indeling verschillen per
                                event, maar de volgorde blijft herkenbaar:
                                aanmelden, zo nodig betalen, samen opbouwen, de
                                trackwalk lopen, in heats vliegen en samen
                                afronden.
                            </p>
                        </div>

                        <ol className="mt-12 max-w-2xl space-y-10">
                            {journeySteps.map((step, index) => (
                                <li key={step.title} className="relative pl-14">
                                    {index < journeySteps.length - 1 && (
                                        <span
                                            aria-hidden="true"
                                            className="absolute top-9 -bottom-10 left-4 w-px -translate-x-1/2 bg-night-200 dark:bg-white/10"
                                        />
                                    )}
                                    <span
                                        aria-hidden="true"
                                        className="absolute top-0 left-0 flex size-9 shrink-0 items-center justify-center rounded-full bg-flight-50 text-sm font-semibold text-flight-700 dark:bg-flight-500/10 dark:text-flight-300"
                                    >
                                        {index + 1}
                                    </span>
                                    <p className="font-semibold text-night-950 dark:text-white">
                                        {step.title}
                                    </p>
                                    <p className="mt-1 text-sm leading-6 text-night-500 dark:text-night-400">
                                        {step.body}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>

                <ContentBand
                    tone="muted"
                    eyebrow="Wie en wat"
                    title="Begrijp de woorden op de avond."
                    description="Met deze vier begrippen begrijp je hoe de trainingsavond vanaf de baanopbouw tot de heats verloopt."
                >
                    <dl className="grid gap-4 sm:grid-cols-2">
                        {trainingTerms.map((item) => (
                            <div
                                key={item.term}
                                className="rounded-sm border border-night-200 bg-white p-5 dark:border-white/10 dark:bg-night-800"
                            >
                                <dt className="text-sm font-semibold text-night-950 dark:text-white">
                                    {item.term}
                                </dt>
                                <dd className="mt-2 text-sm leading-6 text-night-500 dark:text-night-400">
                                    {item.description}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </ContentBand>

                <ContentBand
                    size="compact"
                    eyebrow="Voor je vertrekt"
                    title="Materiaal en tas: laatste check."
                    description="Iedereen vliegt op hetzelfde parcours, op videokanalen die elkaar kunnen storen. Bij een training is dit je eigen verantwoordelijkheid: controleer zelf of je zender en videosysteem binnen de regels vallen, zodat jouw signaal andere piloten niet stoort en andersom."
                >
                    <div className="grid gap-10 sm:grid-cols-2">
                        <div>
                            <h3 className="text-xs font-semibold tracking-[0.08em] text-night-950 uppercase dark:text-white">
                                Controleer je materiaal
                            </h3>
                            <ul className="mt-4 space-y-3">
                                {technicalChecklist.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex items-start gap-3 text-sm leading-6 text-night-500 dark:text-night-400"
                                    >
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                                        {item.content}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-xs font-semibold tracking-[0.08em] text-night-950 uppercase dark:text-white">
                                Inpakken
                            </h3>
                            <ul className="mt-4 space-y-3">
                                {packingChecklist.map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-start gap-3 text-sm leading-6 text-night-500 dark:text-night-400"
                                    >
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <div className="mt-10 border-t border-paddock-rule pt-7 dark:border-white/12">
                        <h3 className="font-public-display text-2xl font-semibold tracking-[-0.03em]">
                            Veilig vliegen is iets wat je samen doet.
                        </h3>
                        <div className="mt-4 max-w-3xl space-y-4 text-sm leading-7 text-signal-muted dark:text-night-400">
                            <p>
                                Wacht met inschakelen tot de race director dat
                                toestaat, laad alleen op de aangewezen manier en
                                stop wanneer je twijfelt over een accu,
                                instelling of beschadiging. De{' '}
                                <Link
                                    href={houseRules()}
                                    className="font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                                >
                                    huisregels
                                </Link>{' '}
                                en aanwijzingen van de organisatie gaan altijd
                                vóór deze algemene samenvatting. Ervaren piloten
                                helpen graag met een gerichte vraag, maar
                                iedereen blijft verantwoordelijk voor zijn eigen
                                basisgereedschap en materiaal.
                            </p>
                        </div>
                    </div>
                </ContentBand>
            </div>

            <CtaBand
                eyebrow="Klaar voor de agenda?"
                title="Kies een training die bij je niveau past."
                description="Controleer op de eventpagina altijd de actuele datum, locatie, deelnamevereisten en aanmeldroute."
                action={{
                    label: 'Bekijk trainingen',
                    href: eventsIndex.url({
                        query: { type: 'training' },
                    }),
                }}
            />
        </>
    );
}
