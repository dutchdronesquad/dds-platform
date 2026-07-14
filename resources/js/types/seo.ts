export type SeoMetadata = {
    canonicalUrl: string;
    description: string;
    openGraph: {
        description: string;
        image: string;
        imageAlt: string;
        siteName: string;
        title: string;
        type: string;
        url: string;
    };
    robots: string;
    title: string;
};
