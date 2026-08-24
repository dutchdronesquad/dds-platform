import { Eyebrow, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import { index as gettingStartedIndex } from '@/routes/getting_started';
import type { SeoMetadata } from '@/types';

type Props = {
    seo: SeoMetadata;
};

type Rule = {
    number: number;
    text: string;
};

const rules: Rule[] = [
    {
        number: 1,
        text: 'Heb respect voor elkaar, maak plezier en deel tips en ervaringen.',
    },
    {
        number: 2,
        text: 'Zet geen spanning op je drone wanneer jouw heat niet aan de beurt is. Uitzonderingen zijn alleen mogelijk in overleg met de organisatie.',
    },
    {
        number: 3,
        text: 'Stel het zendvermogen van je drone in op maximaal 25 mW.',
    },
    {
        number: 4,
        text: 'Voorzie je drone van een kill switch. Armen met de sticks is niet toegestaan.',
    },
    {
        number: 5,
        text: 'Vlieg alleen in de ruimte waar de track staat en houd je drone in de pilot area aan de grond.',
    },
    {
        number: 6,
        text: 'Het betreden van het vliegterrein is geheel op eigen risico.',
    },
    {
        number: 7,
        text: 'Betreed de baan nooit wanneer er wordt gevlogen, ook niet wanneer je bent gecrasht.',
    },
    {
        number: 8,
        text: 'Alleen in het Sportpaleis: loop niet over de houten wielerbaan.',
    },
    {
        number: 9,
        text: 'Zorg voor een aansprakelijkheidsverzekering die schade met of door een modelvliegtuig dekt.',
    },
    {
        number: 10,
        text: 'Dutch Drone Squad is niet aansprakelijk voor schade aan personen of eigendommen, of voor verlies van eigendommen tijdens een event.',
    },
    {
        number: 11,
        text: 'Iedere piloot vliegt op eigen verantwoordelijkheid.',
    },
    {
        number: 12,
        text: 'Meld je uiterlijk 24 uur voor aanvang af; daarna is terugbetaling niet mogelijk.',
    },
];

export default function HouseRules({ seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                size="compact"
                kicker="Veilig en prettig vliegen"
                title="Huisregels"
                description="Deze afspraken gelden tijdens trainingen, races en andere activiteiten van Dutch Drone Squad."
                actions={[
                    { label: 'Bekijk de agenda', href: eventsIndex.url() },
                    {
                        label: 'Bereid je eerste event voor',
                        href: gettingStartedIndex.url({
                            query: { source: 'house-rules' },
                        }),
                    },
                ]}
                media={{
                    src: '/images/dds/racing/pilot-checking-drone.jpg',
                    alt: 'FPV-piloot controleert zijn racedrone naast het parcours',
                    position: '72% center',
                }}
                separatorTone="paper"
            />

            <section
                aria-labelledby="rules-heading"
                className="bg-paper py-public-section text-deep-signal dark:bg-night-950 dark:text-white"
            >
                <div className="mx-auto w-full max-w-7xl px-public-gutter">
                    <header className="grid gap-7 lg:grid-cols-[0.86fr_1.14fr] lg:items-end lg:gap-20">
                        <div>
                            <Eyebrow line={false}>Race briefing</Eyebrow>
                            <h2
                                id="rules-heading"
                                className="mt-4 max-w-2xl font-public-display text-4xl leading-[1] font-semibold tracking-[-0.05em] text-balance sm:text-5xl"
                            >
                                Veiligheid begint vóór de eerste heat.
                            </h2>
                        </div>
                        <div>
                            <p className="dark:text-night-300 max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8">
                                Volg aanwijzingen van de organisatie altijd op.
                                Bespreek een onveilige of onduidelijke situatie
                                voordat je opstijgt. Aanvullende aanwijzingen
                                van de organisatie gaan altijd voor.
                            </p>
                        </div>
                    </header>

                    <ol
                        data-testid="house-rules-list"
                        className="mt-12 divide-y divide-deep-signal/12 border-y border-deep-signal/15 dark:divide-white/10 dark:border-white/12"
                    >
                        {rules.map((rule) => (
                            <li
                                key={rule.number}
                                className="grid gap-3 py-5 sm:grid-cols-[4rem_1fr] sm:gap-6 sm:py-6"
                            >
                                <span
                                    aria-hidden="true"
                                    className="font-mono text-sm font-semibold text-dds-blue dark:text-dds-cyan"
                                >
                                    {String(rule.number).padStart(2, '0')}
                                </span>
                                <div className="text-base leading-7 sm:text-lg sm:leading-8">
                                    <p>{rule.text}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>
        </>
    );
}
