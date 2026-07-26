import { Head } from '@inertiajs/react';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/ArticleController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { dashboard } from '@/routes';
import { ArticleForm } from './form';
import type { ArticleFormOptions, EditableArticle } from './types';

export default function EditArticle({
    article,
    options,
}: {
    article: EditableArticle;
    options: ArticleFormOptions;
}) {
    return (
        <>
            <Head title={`${article.title} bewerken`} />
            <AdminResourcePage
                contentClassName="@container/admin-page"
                eyebrow="Nieuwsbeheer"
                title={article.title}
                description="Werk de inhoud, categorie en publicatiestatus van dit artikel bij."
                variant="form"
            >
                <ArticleForm
                    article={article}
                    form={update.form(article.id)}
                    options={options}
                />
            </AdminResourcePage>
        </>
    );
}

EditArticle.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Artikelen', href: index() },
        { title: 'Artikel bewerken', href: index() },
    ],
};
