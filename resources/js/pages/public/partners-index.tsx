import { Link } from '@inertiajs/react';
import PublicExternalLink from '@/components/public/public-external-link';
import { Eyebrow, PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { contact } from '@/routes';
import type { PublicPartner, SeoMetadata } from '@/types';

type Props = {
    partners: PublicPartner[];
    seo: SeoMetadata;
};

export default function PartnersIndex({ partners, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="Samen maken we meer mogelijk."
                description="Van een vaste indoorlocatie tot materiaal en expertise: onze partners helpen Dutch Drone Squad om trainingen, races en de Nederlandse FPV-community verder te brengen."
                actions={[
                    { label: 'Bekijk hun bijdrage', href: '#partners' },
                    { label: 'Werk met ons samen', href: contact.url() },
                ]}
                media={{
                    src: '/images/dds/racing/race-control.jpg',
                    alt: 'Race control tijdens een indoor FPV-event van Dutch Drone Squad',
                    position: '58% center',
                }}
                separatorTone="air"
            />

            <section
                id="partners"
                aria-labelledby="partners-heading"
                className="scroll-mt-20 bg-air py-public-section text-deep-signal"
            >
                <div className="mx-auto w-full max-w-7xl px-public-gutter">
                    <div className="grid gap-7 lg:grid-cols-2 lg:items-end lg:gap-12 xl:gap-20">
                        <div>
                            <Eyebrow line={false}>Partners & sponsors</Eyebrow>
                            <h2
                                id="partners-heading"
                                className="mt-5 max-w-xl font-public-display text-[clamp(1.65rem,7.2vw,3rem)] leading-[1] font-semibold tracking-[-0.05em]"
                            >
                                <span className="block whitespace-nowrap">
                                    Partners die onze
                                </span>{' '}
                                <span className="block whitespace-nowrap">
                                    races mogelijk maken.
                                </span>
                            </h2>
                        </div>
                        <p className="max-w-2xl text-base leading-7 text-signal-muted sm:text-lg sm:leading-8">
                            Onze partners dragen ieder op hun eigen manier bij
                            aan DDS: van een vaste indoorlocatie tot gesponsord
                            trackmateriaal. Zo kunnen we blijven trainen, racen
                            en bouwen aan de FPV-community.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-5 md:grid-cols-2 lg:mt-16">
                        {partners.map((partner) => (
                            <article
                                key={partner.key}
                                className="flex min-w-0 flex-col border border-deep-signal/12 bg-paper"
                            >
                                <div className="flex min-h-52 items-center justify-center border-b border-deep-signal/10 bg-white p-8 sm:min-h-60 sm:p-10">
                                    <img
                                        src={partner.logoSrc}
                                        alt={partner.logoAlt}
                                        loading="lazy"
                                        className="max-h-20 max-w-full object-contain sm:max-h-24"
                                    />
                                </div>
                                <div className="flex flex-1 flex-col p-6 sm:p-8">
                                    <h3 className="font-public-display text-3xl font-semibold tracking-[-0.04em]">
                                        {partner.name}
                                    </h3>
                                    {partner.description && (
                                        <p className="mt-4 text-sm leading-7 text-signal-muted sm:text-base">
                                            {partner.description}
                                        </p>
                                    )}
                                    <PublicExternalLink
                                        href={partner.websiteUrl}
                                        aria-label={`Bezoek de website van ${partner.name}`}
                                        className="mt-7 min-h-11 self-start text-sm font-semibold text-dds-blue transition-colors hover:text-deep-signal"
                                    >
                                        Bezoek website
                                    </PublicExternalLink>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-deep-signal text-white">
                <div className="mx-auto grid w-full max-w-7xl gap-7 px-public-gutter py-14 sm:py-16 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div className="max-w-3xl">
                        <h2 className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl">
                            Ook iets mogelijk maken?
                        </h2>
                        <p className="mt-4 max-w-2xl text-base leading-7 text-white/65">
                            Neem contact op als je samen met DDS een race,
                            demonstratie, workshop of communityproject wilt
                            realiseren.
                        </p>
                    </div>
                    <Link
                        href={contact.url()}
                        prefetch
                        className="inline-flex min-h-11 items-center justify-center rounded-md bg-dds-cyan px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-white focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:ring-offset-deep-signal focus-visible:outline-none"
                    >
                        Start een gesprek
                    </Link>
                </div>
            </section>
        </>
    );
}
