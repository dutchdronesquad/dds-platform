import { Head } from '@inertiajs/react';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/ArticleController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { ArticleForm } from './form';
import type { ArticleFormOptions } from './types';

export default function CreateArticle({
    defaultAuthorId,
    options,
}: {
    defaultAuthorId: number;
    options: ArticleFormOptions;
}) {
    return (
        <>
            <Head title="Nieuw artikel" />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Nieuwsbeheer"
                title="Nieuw artikel"
                description="Leg titel, inhoud en publicatiestatus van een nieuw nieuwsartikel vast."
                variant="form"
            >
                <ArticleForm
                    defaultAuthorId={defaultAuthorId}
                    form={store.form()}
                    options={options}
                />
            </AdminResourcePage>
        </>
    );
}

CreateArticle.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Artikelen', href: index() },
        { title: 'Nieuw artikel', href: index() },
    ],
};
