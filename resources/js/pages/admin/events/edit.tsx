import { Head } from '@inertiajs/react';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { EventForm } from './form';
import type { EditableEvent, EventFormOptions } from './types';

export default function EditEvent({
    canManageSeasons,
    event,
    options,
}: {
    canManageSeasons: boolean;
    event: EditableEvent;
    options: EventFormOptions;
}) {
    return (
        <>
            <Head title={`${event.title} bewerken`} />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Eventbeheer"
                title={event.title}
                description="Werk de eventinformatie bij en gebruik de aparte statusacties om publieke zichtbaarheid te wijzigen."
                variant="form"
            >
                <EventForm
                    canManageSeasons={canManageSeasons}
                    event={event}
                    form={update.form(event.id)}
                    options={options}
                />
            </AdminResourcePage>
        </>
    );
}

EditEvent.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Events', href: index() },
        { title: 'Event bewerken', href: index() },
    ],
};
