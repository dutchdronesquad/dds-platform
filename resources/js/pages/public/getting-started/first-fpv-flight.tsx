import { Link } from '@inertiajs/react';
import { ArrowUpRight, Flag, Gauge, Signal, Sparkles } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import {
    ContentBand,
    CtaBand,
    FeatureCard,
    PublicHero,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { contact } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import {
    index as gettingStartedIndex,
    show as gettingStartedShow,
} from '@/routes/getting_started';
import type { GettingStartedGuideSummary, SeoMetadata } from '@/types';

type Props = {
    guide: GettingStartedGuideSummary;
    seo: SeoMetadata;
};

const activities: { body: string; icon: LucideIcon; title: string }[] = [
    {
        title: 'Training',
        body: 'Een gezamenlijke oefenavond voor piloten die al zelfstandig een volledig parcours kunnen vliegen.',
        icon: Gauge,
    },
    {
        title: 'Race',
        body: 'Heats op tijd, met gates en laps; de organisatoren bewaken de veiligheid.',
        icon: Flag,
    },
    {
        title: 'Demo',
        body: 'DDS laat FPV-racing aan bezoekers, organisaties of publiek zien; zelf vliegen is niet automatisch onderdeel van een demo.',
        icon: Sparkles,
    },
    {
        title: 'Workshop',
        body: 'Verdieping op een specifiek onderwerp, zoals afstelling, onderhoud of racelijnen.',
        icon: Signal,
    },
];

const visitorPaths: {
    action: { href: ReturnType<typeof eventsIndex.url>; label: string };
    body: string;
    title: string;
}[] = [
    {
        title: 'Nog nooit gevlogen',
        body: 'Begin in een simulator. Vraag DDS daarna naar een beginnersmoment in De Goorn; reguliere trainingen zijn geen eerste vlieglessen.',
        action: {
            label: 'Vraag naar een beginnersmoment',
            href: contact.url({
                query: { source: 'getting-started-no-experience' },
            }),
        },
    },
    {
        title: 'Ik kan al vliegen',
        body: 'Kun je een compleet parcours zelfstandig vliegen en basisreparaties doen? Lees dan de eventvereisten en bekijk de trainingen.',
        action: {
            label: 'Bekijk trainingen',
            href: eventsIndex.url({ query: { type: 'training' } }),
        },
    },
    {
        title: 'Ervaren racer',
        body: 'Meld je aan voor een race en vergelijk rondetijden met andere piloten.',
        action: {
            label: 'Bekijk races',
            href: eventsIndex.url({ query: { type: 'race' } }),
        },
    },
];

const simulators = [
    {
        name: 'VelociDrone',
        description:
            'De DDS-aanrader voor racing, met veel parcoursen en volop mogelijkheden om rondetijden te trainen.',
        href: 'https://www.velocidrone.com/',
        recommended: true,
    },
    {
        name: 'Liftoff',
        description:
            'Een toegankelijke allround simulator met tutorials en ruimte voor zowel racing als freestyle.',
        href: 'https://www.liftoff-game.com/liftoff-fpv-drone-racing',
        recommended: false,
    },
    {
        name: 'DCL – The Game',
        description:
            'Een meer spelgerichte simulator met verschillende vliegmodi en parcoursen van de Drone Champions League.',
        href: 'https://dronechampionsleague.com/game/',
        recommended: false,
    },
    {
        name: 'DRL Simulator',
        description:
            'Een racegerichte simulator met een ingebouwde vliegschool en banen van de Drone Racing League.',
        href: 'https://store.steampowered.com/app/641780/The_Drone_Racing_League_Simulator/',
        recommended: false,
    },
];

export default function GettingStartedFirstFpvFlight({ guide, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={guide.eyebrow}
                title={guide.title}
                description="FPV laat je vliegen vanuit het perspectief van de drone. Lees hoe de besturing werkt en waarom je begint in een simulator."
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
                <ContentBand
                    eyebrow="Wat is FPV?"
                    title="Je kijkt door de camera van de drone."
                    description="FPV staat voor 'first person view'. Bij gewoon vliegen op zicht (line of sight) kijk je naar de drone vanaf de grond. Bij FPV zet je een videobril op en zie je precies wat de camera van de drone ziet, alsof je zelf in de cockpit zit. Dat verschil in perspectief is waarom FPV zo'n andere, intensere manier van vliegen is en waarom het een eigen leercurve heeft."
                >
                    <div className="grid gap-8 md:grid-cols-2">
                        <div className="border-t-2 border-dds-cyan pt-4">
                            <h3 className="font-semibold">
                                Het beeld komt naar jou
                            </h3>
                            <p className="mt-3 text-sm leading-7 text-signal-muted dark:text-night-400">
                                Een camera op de drone stuurt live beeld via de
                                videozender, vaak VTX genoemd, naar je goggles.
                                Daardoor kijk je niet meer naar waar de drone
                                hangt, maar vooruit door het parcours. Je leert
                                snelheid, hoogte en afstand dus uit het
                                camerabeeld aflezen.
                            </p>
                        </div>
                        <div className="border-t-2 border-dds-cyan pt-4">
                            <h3 className="font-semibold">
                                Jij stuurt de drone
                            </h3>
                            <p className="mt-3 text-sm leading-7 text-signal-muted dark:text-night-400">
                                Met de radiozender stuur je opdrachten naar de
                                ontvanger op de drone. De flight controller
                                verwerkt je stickbewegingen en bepaalt hoe de
                                motoren reageren. De verbinding voor besturing
                                staat los van het videosignaal; voor beide
                                systemen moeten zender en ontvanger wel bij
                                elkaar passen.
                            </p>
                        </div>
                    </div>
                    <p className="mt-7 rounded-sm bg-night-50 px-5 py-4 text-sm leading-7 text-night-500 dark:bg-night-900 dark:text-night-400">
                        Voor videobeeld kies je tussen analoog en digitaal. Die
                        systemen verschillen in beeldkwaliteit, vertraging en
                        apparatuur en zijn meestal niet met elkaar te
                        combineren. Controleer daarom of camera, videozender en
                        goggles hetzelfde systeem gebruiken en welke regels op
                        het event gelden.
                    </p>
                </ContentBand>

                <ContentBand
                    tone="air"
                    eyebrow="Besturing"
                    title="Vier bewegingen, tegelijk gecombineerd."
                    description="Bij de gebruikelijke Mode 2-indeling bedient de linker stick gas en draaien; de rechter stick kantelen. Je vliegt vrijwel nooit met maar één beweging: een vloeiende bocht combineert meerdere assen."
                >
                    <dl className="grid gap-4 sm:grid-cols-2">
                        {[
                            {
                                term: 'Throttle',
                                description:
                                    'Regelt hoeveel stuwkracht de motoren leveren en daarmee vooral stijgen, dalen en snelheid door de lucht.',
                            },
                            {
                                term: 'Yaw',
                                description:
                                    'Draait de neus van de drone naar links of rechts om de kijkrichting te veranderen.',
                            },
                            {
                                term: 'Pitch',
                                description:
                                    'Kantelt de drone voor- of achterover; voorover kantelen bouwt voorwaartse snelheid op.',
                            },
                            {
                                term: 'Roll',
                                description:
                                    'Kantelt de drone zijwaarts en helpt samen met yaw om een gecontroleerde bocht te vliegen.',
                            },
                        ].map((item) => (
                            <div
                                key={item.term}
                                className="rounded-sm border border-paddock-rule bg-white p-5 dark:border-white/10 dark:bg-night-900"
                            >
                                <dt className="font-semibold">{item.term}</dt>
                                <dd className="mt-2 text-sm leading-6 text-signal-muted dark:text-night-400">
                                    {item.description}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </ContentBand>

                <ContentBand
                    layout="stacked"
                    eyebrow="Leren vliegen"
                    title="De simulator is je eerste veilige oefenplek."
                    description="Een FPV-drone remt niet vanzelf wanneer je de sticks loslaat. In acro-modus bepaal je voortdurend de hoek en draairichting. Een simulator geeft je de herhaling om die besturing automatisch te maken, zonder dat iedere fout reparatiewerk oplevert."
                    wideContent={
                        <div>
                            <h3 className="text-lg font-semibold tracking-[-0.02em] text-deep-signal dark:text-white">
                                Welke simulator kun je gebruiken?
                            </h3>
                            <p className="mt-2 max-w-3xl text-sm leading-7 text-signal-muted dark:text-night-400">
                                De precieze keuze is minder belangrijk dan
                                regelmatig oefenen met je eigen zender. Dit zijn
                                vier bekende opties:
                            </p>
                            <div
                                data-testid="simulators-grid"
                                className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {simulators.map((simulator) => (
                                    <a
                                        key={simulator.name}
                                        href={simulator.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group rounded-sm border border-paddock-rule bg-white p-5 text-sm leading-7 text-signal-muted transition-colors hover:border-dds-cyan hover:bg-night-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dds-blue dark:border-white/10 dark:bg-night-900 dark:text-night-400 dark:hover:border-dds-cyan/70 dark:hover:bg-night-800"
                                    >
                                        <span className="flex items-start justify-between gap-4">
                                            <span className="font-semibold text-deep-signal dark:text-white">
                                                {simulator.name}
                                            </span>
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="mt-0.5 size-4 shrink-0 text-dds-blue transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 dark:text-dds-cyan"
                                            />
                                        </span>
                                        {simulator.recommended && (
                                            <span className="mt-2 inline-flex rounded-full bg-dds-orange/12 px-2.5 py-1 text-xs font-semibold text-deep-signal dark:text-dds-orange">
                                                DDS-aanrader
                                            </span>
                                        )}
                                        <span className="mt-3 block leading-6">
                                            {simulator.description}
                                        </span>
                                        <span className="sr-only">
                                            Opent in een nieuw tabblad.
                                        </span>
                                    </a>
                                ))}
                            </div>
                        </div>
                    }
                >
                    <div className="space-y-5 text-sm leading-7 text-signal-muted dark:text-night-400">
                        <p>
                            Oefen niet alleen op je snelste ronde. Rustig
                            opstijgen, hoogte vasthouden, bochten in beide
                            richtingen vliegen en na een fout herstellen zeggen
                            meer over je controle. Een echte radiozender die via
                            USB werkt benadert het gevoel beter dan een
                            gamecontroller en kun je later blijven gebruiken.
                        </p>
                        <p>
                            Reguliere trainingen in het Sportpaleis zijn geen
                            eerste vlieglessen. Daar wordt verwacht dat je
                            zelfstandig een compleet parcours vliegt en je drone
                            na een crash weer vliegklaar kunt maken. Bij
                            voldoende interesse kan DDS een apart
                            beginnersmoment in De Goorn organiseren.
                        </p>
                        <div className="rounded-sm border-l-2 border-dds-orange bg-dds-orange/7 px-5 py-4">
                            <p className="font-semibold text-deep-signal dark:text-white">
                                Wanneer ben je klaar voor de volgende stap?
                            </p>
                            <p className="mt-2">
                                Niet wanneer je één snelle ronde hebt gereden,
                                maar wanneer je meerdere rondes rustig afmaakt,
                                een gemiste gate gecontroleerd herstelt en nog
                                aandacht overhoudt voor wat er om je heen
                                gebeurt.
                            </p>
                        </div>
                    </div>
                </ContentBand>

                <ContentBand
                    tone="paddock"
                    eyebrow="DDS-activiteiten"
                    title="Wat je bij DDS kunt doen."
                    description="DDS organiseert verschillende activiteiten rond FPV. Het type vertelt wat er die dag gebeurt, maar niet automatisch welk ervaringsniveau past. Lees daarom altijd ook de eventbeschrijving en deelnamevereisten."
                >
                    <div className="grid gap-6 sm:grid-cols-2">
                        {activities.map((item) => (
                            <FeatureCard
                                key={item.title}
                                icon={item.icon}
                                title={item.title}
                                body={item.body}
                            />
                        ))}
                    </div>
                    <p className="mt-6 text-sm leading-7 text-night-500 dark:text-night-400">
                        Op een parcours vlieg je door gates, de poortjes van de
                        baan. Eén volledige ronde heet een lap. Een heat is het
                        vliegblok van een kleine groep piloten. De race director
                        bewaakt daarbij de planning, timing, kanaalindeling en
                        het veilige verloop.
                    </p>
                </ContentBand>

                <ContentBand
                    layout="stacked"
                    tone="warmup"
                    eyebrow="Welke route past bij jou?"
                    title="Kies je startpunt."
                    description="Deze indeling is een richting, geen toelatingstest. Twijfel je, beschrijf dan eerst je simulatorervaring, vliegervaring en materiaal in een bericht aan DDS."
                >
                    <div className="grid gap-6 md:grid-cols-3">
                        {visitorPaths.map((path) => (
                            <div
                                key={path.title}
                                className="flex flex-col gap-4 rounded-sm border border-paddock-rule bg-white p-6 dark:border-white/10 dark:bg-night-800"
                            >
                                <h3 className="text-lg font-semibold tracking-[-0.02em] text-night-950 dark:text-white">
                                    {path.title}
                                </h3>
                                <p className="text-sm leading-6 text-night-500 dark:text-night-400">
                                    {path.body}
                                </p>
                                <Link
                                    href={path.action.href}
                                    prefetch
                                    className="mt-auto inline-flex min-h-9 items-center gap-1 text-sm font-semibold text-dds-blue hover:text-deep-signal dark:text-dds-cyan dark:hover:text-white"
                                >
                                    {path.action.label}
                                </Link>
                            </div>
                        ))}
                    </div>
                </ContentBand>
            </div>

            <CtaBand
                eyebrow="Volgende stap"
                title="Kies daarna materiaal dat bij elkaar past."
                description="Lees hoe radio, ontvanger, videosysteem, goggles, accu’s en lader samen één veilige en bruikbare set vormen."
                action={{
                    label: 'Uitrusting kiezen',
                    href: gettingStartedShow.url('choosing-equipment'),
                }}
            />
        </>
    );
}
