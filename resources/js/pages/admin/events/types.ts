import type { AdminActivity } from '@/components/admin/admin-activity-metadata';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import type { MediaPickerAsset } from '@/types/media';

export type AdminEventStatus = 'cancelled' | 'draft' | 'published';

export type AdminEventSituation =
    | 'closed_registration'
    | 'expired_registration'
    | 'without_content'
    | 'without_cover'
    | 'without_season';

export type AdminEventType =
    'demo' | 'other' | 'race' | 'training' | 'workshop';

export type AdminRegistrationStatus = 'closed' | 'full' | 'open' | 'waitlist';

export type SelectOption = {
    label: string;
    value: string;
};

export type EventRecord = {
    activity: Pick<AdminActivity, 'updatedAt' | 'updatedBy'>;
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
        situation: AdminEventSituation[];
        status: AdminEventStatus[];
        type: AdminEventType[];
    };
    situationOptions: SelectOption[];
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
    activity: AdminActivity;
    capabilities: {
        cancel: boolean;
        delete: boolean;
        publish: boolean;
    };
    capacity: number | null;
    content: string | null;
    coverImage: MediaPickerAsset | null;
    coverImageId: number | null;
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
