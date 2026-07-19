import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Plus, Tags, Ticket } from 'lucide-react';
import { index as eventsIndex } from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    create,
    index as seasonsIndex,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { seasonColumns } from './columns';
import type { SeasonIndexProps } from './types';

export default function SeasonsIndex({ seasons, summary }: SeasonIndexProps) {
    return (
        <>
            <Head title="Seizoenen beheren" />

            <AdminResourcePage
                eyebrow="Eventbeheer"
                title="Seizoenen"
                description="Ondersteun de eventplanning met herkenbare seizoenen en optionele seizoenstickets."
                actions={
                    <>
                        <Button asChild variant="outline">
                            <Link href={eventsIndex()}>
                                <CalendarDays />
                                Events
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Nieuw seizoen
                            </Link>
                        </Button>
                    </>
                }
            >
                <AdminListSummary
                    label="Seizoenssamenvatting"
                    metrics={[
                        {
                            label: 'Seizoenen',
                            value: summary.total,
                            icon: Tags,
                        },
                        {
                            label: 'Met seizoensticket',
                            value: summary.withTickets,
                            icon: Ticket,
                            tone: 'amber',
                        },
                        {
                            label: 'Gekoppelde events',
                            value: summary.events,
                            icon: CalendarDays,
                            tone: 'blue',
                        },
                    ]}
                />

                <AdminDataTable
                    caption="Overzicht van seizoenen"
                    columns={seasonColumns}
                    emptyTitle="Nog geen seizoenen"
                    emptyDescription="Maak een seizoen aan wanneer meerdere events bij elkaar horen of een seizoensticket delen."
                    pagination={seasons}
                    resourceLabel="seizoenen"
                    tableClassName="min-w-0 md:min-w-[48rem]"
                />
            </AdminResourcePage>
        </>
    );
}

SeasonsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Beheer',
            href: dashboard(),
        },
        {
            title: 'Events',
            href: eventsIndex(),
        },
        {
            title: 'Seizoenen',
            href: seasonsIndex(),
        },
    ],
};
