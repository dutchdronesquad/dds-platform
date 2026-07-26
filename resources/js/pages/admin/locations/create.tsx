import { Head } from '@inertiajs/react';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/LocationController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { LocationForm } from './form';
import type { LocationFormOptions } from './types';

export default function CreateLocation({
    options,
}: {
    options: LocationFormOptions;
}) {
    return (
        <>
            <Head title="Nieuwe locatie" />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Locatiebeheer"
                title="Nieuwe locatie"
                description="Leg de adresgegevens, omgeving en praktische informatie van een nieuwe vlieglocatie vast."
                variant="form"
            >
                <LocationForm form={store.form()} options={options} />
            </AdminResourcePage>
        </>
    );
}

CreateLocation.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Locaties', href: index() },
        { title: 'Nieuwe locatie', href: index() },
    ],
};
