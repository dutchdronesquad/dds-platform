import type { AdminActivity } from '@/components/admin/admin-activity-metadata';
import type { ServerPagination } from '@/components/admin/admin-data-table';

export type SalesState = 'available' | 'closed' | 'coming_soon' | 'sold_out';

export type SeasonRecord = {
    activity: Pick<AdminActivity, 'updatedAt' | 'updatedBy'>;
    eventCount: number;
    id: number;
    name: string;
    slug: string;
    ticket: {
        capacity: number | null;
        priceCents: number | null;
        salesState: SalesState;
    } | null;
};

export type SeasonIndexProps = {
    seasons: ServerPagination<SeasonRecord>;
    summary: {
        events: number;
        total: number;
        withTickets: number;
    };
};

export type EditableSeason = {
    activity: AdminActivity;
    eventCount: number;
    id: number;
    name: string;
    slug: string;
    ticketCapacity: number | null;
    ticketCopy: string | null;
    ticketOffered: boolean;
    ticketPriceEuros: string | null;
    ticketRegistrationUrl: string | null;
    ticketSalesClosesAt: string | null;
    ticketSalesOpensAt: string | null;
    ticketSalesState: SalesState;
};

export type SalesStateOption = {
    label: string;
    value: SalesState;
};
