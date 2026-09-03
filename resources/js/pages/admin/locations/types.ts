import type { AdminActivity } from '@/components/admin/admin-activity-metadata';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import type { MediaPickerAsset } from '@/types/media';

export type AdminLocationEnvironment = 'indoor' | 'outdoor';

export type SelectOption = {
    label: string;
    value: string;
};

export type LocationRecord = {
    activity: Pick<AdminActivity, 'updatedAt'>;
    capabilities: {
        delete: boolean;
        update: boolean;
    };
    city: string;
    environment: AdminLocationEnvironment;
    eventsCount: number;
    id: number;
    name: string;
    slug: string;
};

export type LocationIndexProps = {
    canCreate: boolean;
    filters: {
        search: string;
    };
    locations: ServerPagination<LocationRecord>;
};

export type LocationFormOptions = {
    environments: SelectOption[];
};

export type EditableLocation = {
    activity: AdminActivity;
    capabilities: {
        delete: boolean;
    };
    ceilingHeightMetres: string | null;
    city: string;
    countryCode: string;
    coverImage: MediaPickerAsset | null;
    coverImageId: number | null;
    description: {
        en?: string;
        nl?: string;
    };
    environment: AdminLocationEnvironment;
    eventsCount: number;
    facilities: string[];
    floorSizeSquareMetres: number | null;
    houseNumber: string;
    id: number;
    latitude: string | null;
    longitude: string | null;
    name: string;
    postalCode: string;
    slug: string;
    street: string;
    websiteUrl: string | null;
};
