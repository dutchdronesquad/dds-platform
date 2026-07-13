import { Head } from '@inertiajs/react';
import { Compass, RadioTower } from 'lucide-react';
import {
    ContentBand,
    CtaBand,
    FeatureCard,
    PageIntro,
} from '@/components/public/public-patterns';

type PublicPageSection = {
    body: string;
    heading: string;
};

type PublicPage = {
    description: string;
    eyebrow: string;
    primaryAction: {
        href: string;
        label: string;
    };
    sections: PublicPageSection[];
    title: string;
    visual: {
        alt: string;
        position?: string;
        src: string;
    };
};

type Props = {
    page: PublicPage;
};

export default function PublicShell({ page }: Props) {
    return (
        <>
            <Head title={page.title} />

            <PageIntro
                eyebrow={page.eyebrow}
                title={page.title}
                description={page.description}
                action={page.primaryAction}
                media={page.visual}
            />

            <ContentBand
                eyebrow="On this frequency"
                title={`Waar ${page.title.toLowerCase()} voor staat.`}
                description="Deze tijdelijke publieke inhoud laat de vaste informatierichting zien totdat het bijbehorende domeinmodel en beheerde content beschikbaar zijn."
                tone="muted"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    {page.sections.map((section, index) => (
                        <FeatureCard
                            key={section.heading}
                            title={section.heading}
                            body={section.body}
                            icon={index === 0 ? Compass : RadioTower}
                            index={String(index + 1).padStart(2, '0')}
                        />
                    ))}
                </div>
            </ContentBand>

            <CtaBand
                title="Blijf niet aan de zijlijn."
                description="Volg de volgende stap op deze pagina en ontdek waar je kunt vliegen, bouwen of samenwerken met DDS."
                action={page.primaryAction}
            />
        </>
    );
}
