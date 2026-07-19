import { Head } from '@inertiajs/react';
import { index as eventsIndex } from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { SeasonForm } from './form';
import type { EditableSeason, SalesStateOption } from './types';

export default function EditSeason({
    salesStateOptions,
    season,
}: {
    salesStateOptions: SalesStateOption[];
    season: EditableSeason;
}) {
    return (
        <>
            <Head title={`${season.name} bewerken`} />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Eventbeheer"
                title={season.name}
                description="Werk de seizoensnaam en de optionele ticketverkoop bij voor alle gekoppelde events."
                variant="form"
            >
                <SeasonForm
                    form={update.form(season.slug)}
                    salesStateOptions={salesStateOptions}
                    season={season}
                />
            </AdminResourcePage>
        </>
    );
}

EditSeason.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Events', href: eventsIndex() },
        { title: 'Seizoenen', href: index() },
        { title: 'Seizoen bewerken', href: index() },
    ],
};
