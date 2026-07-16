export type EventType = 'training' | 'race' | 'demo' | 'workshop' | 'other';

export type EventStatus = 'published' | 'cancelled';

export type EventRegistrationStatus = 'closed' | 'open' | 'waitlist' | 'full';

export type SeasonTicketSalesState =
    'coming_soon' | 'available' | 'sold_out' | 'closed';

export type PublicSeasonTicket = {
    capacity: number | null;
    copy: string | null;
    priceCents: number | null;
    registrationUrl: string | null;
    salesClosesAt: string | null;
    salesOpensAt: string | null;
    salesState: SeasonTicketSalesState;
};

export type PublicSeasonContext = {
    endsAt: string | null;
    eventCount: number;
    id: number;
    name: string;
    slug: string;
    startsAt: string | null;
    ticket: PublicSeasonTicket | null;
};

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
    registrationDeadlineAt: string | null;
    registrationOpensAt: string | null;
    registrationStatus: EventRegistrationStatus;
    season: {
        id: number;
        name: string;
        slug: string;
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
    registrationUrl: string | null;
    seasonContext: PublicSeasonContext | null;
};

export type PublicSeasonEvent = PublicEventSummary;

export type PublicSeasonDetail = PublicSeasonContext & {
    events: PublicSeasonEvent[];
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
