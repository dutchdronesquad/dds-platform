import {
    BatteryCharging,
    Glasses,
    Radio,
    ShieldCheck,
    Wrench,
} from 'lucide-react';
import {
    ContentBand,
    CtaBand,
    FeatureCard,
    PublicHero,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import {
    index as gettingStartedIndex,
    show as gettingStartedShow,
} from '@/routes/getting_started';
import type { GettingStartedGuideSummary, SeoMetadata } from '@/types';

type Props = {
    guide: GettingStartedGuideSummary;
    seo: SeoMetadata;
};

const safetyChecklist = [
    'Gebruik alleen een balanceerlader en instellingen die geschikt zijn voor het celtype en aantal cellen.',
    'Controleer spanning, stekker, laadstroom en de balanceeraansluiting vóór iedere laadbeurt.',
    'Laad nooit onbeheerd en volg op locatie de aangewezen laad- en opslagprocedure.',
    'Gebruik een beschadigde, warme of gezwollen accu niet opnieuw en vraag hoe je hem veilig afvoert.',
];

const setupParts = [
    {
        title: 'Drone en ontvanger',
        body: 'De drone bevat flight controller, motoren, ontvanger en videosysteem. Formaat, spanning en onderdelen bepalen waar hij geschikt voor is.',
        icon: Wrench,
    },
    {
        title: 'Radiozender',
        body: 'Je handzender stuurt via een protocol naar de ontvanger. Kies liever één degelijk systeem dat ook via USB met je simulator werkt.',
        icon: Radio,
    },
    {
        title: 'Goggles en video',
        body: 'De videobril moet het signaal van de videozender kunnen ontvangen. Analoog en verschillende digitale systemen zijn niet vanzelf uitwisselbaar.',
        icon: Glasses,
    },
    {
        title: 'Accu’s en lader',
        body: 'Accuspanning, stekker en laadprofiel moeten bij drone en lader passen. Een lader is veiligheidsmateriaal, geen bijzaak.',
        icon: BatteryCharging,
    },
];

export default function GettingStartedChoosingEquipment({ guide, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                kicker={guide.eyebrow}
                title={guide.title}
                description="Een werkende FPV-set is een keten van onderdelen. De beste aankoop is niet het nieuwste product, maar een combinatie die onderling compatibel, veilig te laden, te repareren en geschikt voor jouw activiteit is."
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
                    eyebrow="Voor je koopt"
                    title="Begin klein en koop in de juiste volgorde."
                    description="Je hoeft niet meteen een complete racedrone-set te kopen. Begin met een radiozender die met een simulator werkt. Controleer daarna welke configuraties bij de DDS-activiteiten passen, zeker als je tweedehands koopt of twijfelt tussen videosystemen."
                >
                    <div className="space-y-5 text-sm leading-7 text-night-500 dark:text-night-400">
                        <p>
                            Begin met de vraag wat voor vlieger je wilt worden:
                            wil je racen, freestylen of vooral recreatief
                            vliegen? Die keuze bepaalt welk formaat en welke
                            eigenschappen bij je passen. Kies daarna een
                            videosysteem. Controleer radiozender en ontvanger
                            als één combinatie, en camera, videozender en
                            goggles als een tweede. Onderdelen van hetzelfde
                            merk zijn niet automatisch compatibel.
                        </p>
                        <p>
                            Houd in je budget ruimte voor een geschikte
                            balanceerlader, meerdere accu’s, propellers,
                            gereedschap en slijtdelen. Een goedkope drone zonder
                            veilige lader of beschikbare reserveonderdelen is
                            geen goedkope vliegklare set.
                        </p>
                    </div>
                </ContentBand>

                <ContentBand
                    tone="muted"
                    eyebrow="De complete keten"
                    title="Dit hoort bij een vliegklare set."
                    description="Een advertentie met ‘complete drone’ bevat lang niet altijd alles om veilig te kunnen vliegen. Controleer ieder onderdeel en de verbinding ertussen."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        {setupParts.map((part) => (
                            <FeatureCard
                                key={part.title}
                                icon={part.icon}
                                title={part.title}
                                body={part.body}
                            />
                        ))}
                    </div>
                    <p className="mt-6 text-sm leading-7 text-night-500 dark:text-night-400">
                        Daarnaast heb je propellers, basisgereedschap,
                        bevestigingsmateriaal, een veilige manier om accu’s te
                        vervoeren en vaak reserveonderdelen nodig. Vraag ook
                        welke computer- en configuratiesoftware bij de set
                        hoort.
                    </p>
                </ContentBand>

                <ContentBand
                    eyebrow="Tweedehands kopen"
                    title="Controleer meer dan alleen of hij opstijgt."
                    description="Tweedehands materiaal kan een slimme start zijn, maar een goedkope set wordt duur als cruciale onderdelen niet samenwerken, versleten zijn of nauwelijks nog ondersteund worden."
                >
                    <div className="grid gap-5 md:grid-cols-3">
                        <article className="border-t-2 border-dds-cyan pt-4">
                            <h3 className="font-semibold">
                                Identiteit en compatibiliteit
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-night-500 dark:text-night-400">
                                Vraag om exacte typenummers of duidelijke foto’s
                                van radio, ontvanger, videozender, goggles en
                                lader. Controleer ook of firmware,
                                configuratiesoftware, kabels en modules nog
                                beschikbaar zijn.
                            </p>
                        </article>
                        <article className="border-t-2 border-dds-cyan pt-4">
                            <h3 className="font-semibold">
                                Slijtage en reparaties
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-night-500 dark:text-night-400">
                                Vraag naar crashes en eerdere reparaties. Let op
                                speling in motoren, beschadigde antennes,
                                slechte soldeerverbindingen en accu’s die warm,
                                beschadigd of gezwollen zijn.
                            </p>
                        </article>
                        <article className="border-t-2 border-dds-cyan pt-4">
                            <h3 className="font-semibold">
                                De echte totaalprijs
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-night-500 dark:text-night-400">
                                Tel ontbrekende accu’s, lader, reserveonderdelen
                                en reparaties mee. Laat de set bij voorkeur
                                werkend demonstreren voordat je betaalt.
                            </p>
                        </article>
                    </div>
                </ContentBand>

                <ContentBand
                    tone="muted"
                    eyebrow="Accu's en laders"
                    title="Veiligheid staat voorop."
                    description="De LiPo-accu’s die racedrones gebruiken zijn licht en krachtig, maar ook het grootste risico in je uitrusting: bij beschadiging, verkeerd laden of onjuiste opslag kunnen ze zwellen of vlam vatten. Dat risico is goed te beheersen zolang je een paar basisregels aanhoudt."
                >
                    <ul className="grid gap-3 sm:grid-cols-2">
                        {safetyChecklist.map((item) => (
                            <li
                                key={item}
                                className="flex items-start gap-3 text-sm leading-6 text-night-500 dark:text-night-400"
                            >
                                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-dds-blue dark:text-dds-cyan" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </ContentBand>
            </div>

            <CtaBand
                eyebrow="Volgende stap"
                title="Zo verloopt je eerste training bij DDS."
                description="Van aanmelden en opbouwen tot de trackwalk, heats, veilig laden en samen opruimen."
                action={{
                    label: 'Je eerste training',
                    href: gettingStartedShow.url('first-dds-event'),
                }}
            />
        </>
    );
}
