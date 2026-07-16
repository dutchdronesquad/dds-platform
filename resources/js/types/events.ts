export type EventType = 'training' | 'race' | 'demo' | 'workshop' | 'other';

export type EventStatus = 'published' | 'cancelled';

export type EventRegistrationStatus = 'closed' | 'open' | 'waitlist' | 'full';

export type PublicEventSummary = {
    capacity: number | null;
    endsAt: string | null;
    excerpt: string | null;
    id: number;
    image: {
        alt: string;
        src: string;
    };
    location: {
        city: string;
        name: string;
    };
    priceCents: number | null;
    registrationStatus: EventRegistrationStatus;
    season: {
        name: string;
    } | null;
    slug: string;
    startsAt: string;
    status: EventStatus;
    title: string;
    type: EventType;
};

export type PublicEventDetail = Omit<PublicEventSummary, 'location'> & {
    content: string | null;
    location: PublicEventSummary['location'] & {
        houseNumber: string;
        mapEmbedUrl: string;
        mapUrl: string;
        postalCode: string;
        street: string;
    };
    registrationDeadlineAt: string | null;
    registrationOpensAt: string | null;
    registrationUrl: string | null;
};

export type EventTypeFilter = {
    label: string;
    value: EventType;
};

export type PublicEventPaginator = {
    current_page: number;
    data: PublicEventSummary[];
    last_page: number;
    total: number;
};
