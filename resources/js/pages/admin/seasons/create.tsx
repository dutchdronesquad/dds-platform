import { Head } from '@inertiajs/react';
import { index as eventsIndex } from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { SeasonForm } from './form';
import type { SalesStateOption } from './types';

export default function CreateSeason({
    salesStateOptions,
}: {
    salesStateOptions: SalesStateOption[];
}) {
    return (
        <>
            <Head title="Nieuw seizoen" />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Eventbeheer"
                title="Nieuw seizoen"
                description="Geef het seizoen een herkenbare basis en voeg alleen een seizoensticket toe wanneer bezoekers één pakket voor alle events kunnen kopen."
                variant="form"
            >
                <SeasonForm
                    form={store.form()}
                    salesStateOptions={salesStateOptions}
                />
            </AdminResourcePage>
        </>
    );
}

CreateSeason.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Events', href: eventsIndex() },
        { title: 'Seizoenen', href: index() },
        { title: 'Nieuw seizoen', href: index() },
    ],
};
