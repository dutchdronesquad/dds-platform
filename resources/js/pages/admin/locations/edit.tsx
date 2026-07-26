import { Head } from '@inertiajs/react';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/LocationController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { LocationForm } from './form';
import type { EditableLocation, LocationFormOptions } from './types';

export default function EditLocation({
    location,
    options,
}: {
    location: EditableLocation;
    options: LocationFormOptions;
}) {
    return (
        <>
            <Head title={`${location.name} bewerken`} />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Locatiebeheer"
                title={location.name}
                description="Werk de locatiegegevens bij. Wijzigingen zijn direct zichtbaar op de publieke locatiepagina."
                variant="form"
            >
                <LocationForm
                    location={location}
                    form={update.form(location.id)}
                    options={options}
                />
            </AdminResourcePage>
        </>
    );
}

EditLocation.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Locaties', href: index() },
        { title: 'Locatie bewerken', href: index() },
    ],
};
