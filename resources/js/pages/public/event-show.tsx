import { CalendarDays, MapPin } from 'lucide-react';
import {
    ContentBand,
    CtaBand,
    FeatureCard,
    PageIntro,
} from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import type { SeoMetadata } from '@/types';

type Props = {
    seo: SeoMetadata;
    slug: string;
};

export default function EventShow({ seo, slug }: Props) {
    const readableTitle = slug
        .split('-')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    const title = readableTitle || 'Event';

    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PageIntro
                eyebrow="Event preview"
                title={title}
                description="Deze detailpagina reserveert de publieke URL voor een DDS-event. Het echte eventmodel kan later datum, locatie, programma en deelnamegegevens leveren."
                action={{
                    label: 'Terug naar events',
                    href: eventsIndex.url(),
                }}
                media={{
                    src: '/images/dds/racing/indoor-track.jpg',
                    alt: 'Indoor FPV-raceparcours waar Dutch Drone Squad events organiseert',
                    position: '56% center',
                }}
            />

            <ContentBand
                eyebrow="Race briefing"
                title="Alles wat je voor de start nodig hebt."
                description="De vaste eventopbouw maakt praktische informatie straks snel scanbaar op mobiel én desktop."
                tone="muted"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <FeatureCard
                        index="01"
                        icon={CalendarDays}
                        title="Eventinformatie"
                        body="Datum, type activiteit en registratie worden hier zichtbaar zodra DDS-009 het eventdomein toevoegt."
                    />
                    <FeatureCard
                        index="02"
                        icon={MapPin}
                        title="Locatie en regels"
                        body="De pagina houdt ruimte voor locatie, huisregels en praktische aanwijzingen voor veilig deelnemen."
                    />
                </div>
            </ContentBand>

            <CtaBand
                title="Bekijk de volledige agenda."
                description="Ontdek welke trainingen, races, demo’s en community-activiteiten binnenkort op de planning staan."
                action={{
                    label: 'Alle events',
                    href: eventsIndex.url(),
                }}
            />
        </>
    );
}
