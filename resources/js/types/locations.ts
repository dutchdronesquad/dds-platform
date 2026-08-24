export type LocationEnvironment = 'indoor' | 'outdoor';

export type PublicLocationSummary = {
    city: string;
    environment: LocationEnvironment;
    excerpt: string | null;
    id: number;
    image: {
        alt: string;
        src: string;
    };
    name: string;
    slug: string;
};

export type PublicLocationDetail = {
    ceilingHeightMetres: string | null;
    city: string;
    countryCode: string;
    descriptionHtml: string | null;
    environment: LocationEnvironment;
    facilities: string[];
    floorSizeSquareMetres: number | null;
    houseNumber: string;
    id: number;
    image: {
        alt: string;
        src: string;
    };
    mapEmbedUrl: string;
    mapUrl: string;
    name: string;
    postalCode: string;
    slug: string;
    street: string;
    websiteUrl: string | null;
};
