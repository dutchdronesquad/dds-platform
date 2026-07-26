import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/Admin/ArticleController';
import { AdminActivityByline } from '@/components/admin/admin-activity-metadata';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminRowActions } from '@/components/admin/admin-row-actions';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import type { ArticleRecord } from './types';

const categoryLabels: Record<ArticleRecord['category'], string> = {
    news: 'Nieuws',
    announcement: 'Aankondiging',
    community: 'Community',
    race_report: 'Raceverslag',
};

const dateTimeFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeZone: 'Europe/Amsterdam',
});

export const articleColumns: ColumnDef<ArticleRecord>[] = [
    {
        accessorKey: 'title',
        header: 'Artikel',
        cell: ({ row }) => (
            <div className="min-w-0 sm:min-w-60">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    {row.original.capabilities.update ? (
                        <Link
                            href={edit(row.original.id)}
                            className="font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                        >
                            {row.original.title}
                        </Link>
                    ) : (
                        <p className="font-semibold text-neutral-950 dark:text-white">
                            {row.original.title}
                        </p>
                    )}
                    <AdminStatusBadge status={row.original.status} />
                </div>
                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    {row.original.author?.name ?? 'Geen auteur'}
                </p>
            </div>
        ),
    },
    {
        id: 'category',
        header: 'Categorie',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <Badge variant="outline">
                {categoryLabels[row.original.category]}
            </Badge>
        ),
    },
    {
        id: 'publishedAt',
        header: 'Gepubliceerd',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <p className="text-neutral-700 dark:text-neutral-300">
                {row.original.publishedAt
                    ? dateTimeFormatter.format(
                          new Date(row.original.publishedAt),
                      )
                    : '—'}
            </p>
        ),
    },
    {
        id: 'activity',
        header: 'Bijgewerkt',
        meta: {
            className: 'hidden xl:table-cell',
        },
        cell: ({ row }) => (
            <AdminActivityByline
                activity={{
                    updatedAt: row.original.activity.updatedAt,
                    updatedBy: null,
                }}
            />
        ),
    },
    {
        id: 'actions',
        header: '',
        meta: {
            className: 'w-12 text-right',
        },
        cell: ({ row }) => <ArticleActions article={row.original} />,
    },
];

function ArticleActions({ article }: { article: ArticleRecord }) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const canDelete = article.capabilities.delete;
    const hasActions = article.capabilities.update || canDelete;

    if (!hasActions) {
        return null;
    }

    return (
        <div className="flex justify-end">
            <AdminRowActions label={`Acties voor ${article.title}`}>
                {article.capabilities.update && (
                    <DropdownMenuItem asChild>
                        <Link href={edit(article.id)}>
                            <Pencil />
                            Bewerken
                        </Link>
                    </DropdownMenuItem>
                )}

                {canDelete && (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setConfirmingDelete(true)}
                    >
                        <Trash2 />
                        Verwijderen
                    </DropdownMenuItem>
                )}
            </AdminRowActions>

            {confirmingDelete && (
                <AdminConfirmationDialog
                    form={destroy.form(article.id)}
                    intent="delete"
                    subject={article.title}
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setConfirmingDelete(false);
                        }
                    }}
                />
            )}
        </div>
    );
}
