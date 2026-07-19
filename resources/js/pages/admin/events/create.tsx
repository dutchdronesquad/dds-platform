import { Head } from '@inertiajs/react';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { EventForm } from './form';
import type { EventFormOptions } from './types';

export default function CreateEvent({
    canManageSeasons,
    options,
}: {
    canManageSeasons: boolean;
    options: EventFormOptions;
}) {
    return (
        <>
            <Head title="Nieuw event" />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Eventbeheer"
                title="Nieuw event"
                description="Bouw een compleet event op van basisgegevens tot inschrijving. Het event blijft een concept totdat je het na opslaan publiceert."
                variant="form"
            >
                <EventForm
                    canManageSeasons={canManageSeasons}
                    form={store.form()}
                    options={options}
                />
            </AdminResourcePage>
        </>
    );
}

CreateEvent.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Events', href: index() },
        { title: 'Nieuw event', href: index() },
    ],
};
