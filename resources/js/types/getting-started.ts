export type GettingStartedEntrySource =
    | 'navigation'
    | 'homepage'
    | 'event'
    | 'location'
    | 'contact'
    | 'footer'
    | 'search';

export type GettingStartedGuideImage = {
    alt: string;
    position?: string;
    src: string;
};

export type GettingStartedGuideSummary = {
    editorialOwner: string;
    eyebrow: string;
    heroImage: GettingStartedGuideImage;
    reviewedAt: string;
    slug: string;
    summary: string;
    title: string;
};
