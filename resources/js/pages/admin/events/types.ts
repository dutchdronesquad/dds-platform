import type { ServerPagination } from '@/components/admin/admin-data-table';

export type AdminEventStatus = 'cancelled' | 'draft' | 'published';

export type AdminEventType =
    'demo' | 'other' | 'race' | 'training' | 'workshop';

export type AdminRegistrationStatus = 'closed' | 'full' | 'open' | 'waitlist';

export type SelectOption = {
    label: string;
    value: string;
};

export type EventRecord = {
    capabilities: {
        cancel: boolean;
        delete: boolean;
        publish: boolean;
        update: boolean;
    };
    id: number;
    location: {
        city: string;
        name: string;
    };
    publishedAt: string | null;
    registrationStatus: AdminRegistrationStatus;
    season: {
        name: string;
        slug: string;
    } | null;
    slug: string;
    startsAt: string;
    status: AdminEventStatus;
    title: string;
    type: AdminEventType;
};

export type EventIndexProps = {
    canCreate: boolean;
    canManageSeasons: boolean;
    events: ServerPagination<EventRecord>;
    filters: {
        search: string;
        status: AdminEventStatus[];
        type: AdminEventType[];
    };
    statusOptions: SelectOption[];
    summary: {
        cancelled: number;
        drafts: number;
        published: number;
        total: number;
    };
    typeOptions: SelectOption[];
};

export type EventFormOptions = {
    locations: Array<{ id: number; label: string }>;
    registrationStatuses: SelectOption[];
    seasons: Array<{ id: number; label: string }>;
    types: SelectOption[];
};

export type EditableEvent = {
    capabilities: {
        cancel: boolean;
        delete: boolean;
        publish: boolean;
    };
    capacity: number | null;
    content: string | null;
    endsAt: string | null;
    id: number;
    locationId: number;
    priceEuros: string | null;
    publishedAt: string | null;
    registrationDeadlineAt: string | null;
    registrationOpensAt: string | null;
    registrationStatus: AdminRegistrationStatus;
    registrationUrl: string | null;
    seasonId: number | null;
    slug: string;
    startsAt: string;
    status: AdminEventStatus;
    title: string;
    type: AdminEventType;
};
