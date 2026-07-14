import { Head } from '@inertiajs/react';
import type { SeoMetadata } from '@/types';

type Props = {
    metadata: SeoMetadata;
};

export default function PublicSeoHead({ metadata }: Props) {
    return (
        <Head>
            <title>{metadata.title}</title>
            <meta
                head-key="description"
                name="description"
                content={metadata.description}
            />
            <meta head-key="robots" name="robots" content={metadata.robots} />
            <link
                head-key="canonical"
                rel="canonical"
                href={metadata.canonicalUrl}
            />
            <meta
                head-key="og:title"
                property="og:title"
                content={metadata.openGraph.title}
            />
            <meta
                head-key="og:description"
                property="og:description"
                content={metadata.openGraph.description}
            />
            <meta
                head-key="og:type"
                property="og:type"
                content={metadata.openGraph.type}
            />
            <meta
                head-key="og:url"
                property="og:url"
                content={metadata.openGraph.url}
            />
            <meta
                head-key="og:image"
                property="og:image"
                content={metadata.openGraph.image}
            />
            <meta
                head-key="og:image:alt"
                property="og:image:alt"
                content={metadata.openGraph.imageAlt}
            />
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content={metadata.openGraph.siteName}
            />
        </Head>
    );
}
