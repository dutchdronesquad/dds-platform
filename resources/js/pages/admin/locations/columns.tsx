import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import type { adminTableFeatures } from '@/components/admin/admin-data-table';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/Admin/LocationController';
import { AdminActivityByline } from '@/components/admin/admin-activity-metadata';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminRowActions } from '@/components/admin/admin-row-actions';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import type { LocationRecord } from './types';

const environmentLabels: Record<LocationRecord['environment'], string> = {
    indoor: 'Indoor',
    outdoor: 'Outdoor',
};

const environmentStyles: Record<LocationRecord['environment'], string> = {
    indoor: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    outdoor:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
};

export const locationColumns: ColumnDef<
    typeof adminTableFeatures,
    LocationRecord
>[] = [
    {
        accessorKey: 'name',
        header: 'Locatie',
        cell: ({ row }) => (
            <div className="min-w-0 sm:min-w-60">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    {row.original.capabilities.update ? (
                        <Link
                            href={edit(row.original.id)}
                            className="font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                        >
                            {row.original.name}
                        </Link>
                    ) : (
                        <p className="font-semibold text-neutral-950 dark:text-white">
                            {row.original.name}
                        </p>
                    )}
                    <Badge
                        variant="outline"
                        className={environmentStyles[row.original.environment]}
                    >
                        {environmentLabels[row.original.environment]}
                    </Badge>
                </div>
                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    {row.original.city}
                </p>
                <div className="mt-2 grid gap-1.5 sm:hidden">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        {row.original.eventsCount}{' '}
                        {row.original.eventsCount === 1 ? 'event' : 'events'}
                    </p>
                </div>
            </div>
        ),
    },
    {
        id: 'city',
        header: 'Plaats',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <p className="text-neutral-700 dark:text-neutral-300">
                {row.original.city}
            </p>
        ),
    },
    {
        id: 'events',
        header: 'Events',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <p className="text-neutral-700 tabular-nums dark:text-neutral-300">
                {row.original.eventsCount}
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
        cell: ({ row }) => <LocationActions location={row.original} />,
    },
];

function LocationActions({ location }: { location: LocationRecord }) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const canDelete = location.capabilities.delete;
    const hasActions = location.capabilities.update || canDelete;

    if (!hasActions) {
        return null;
    }

    return (
        <div className="flex justify-end">
            <AdminRowActions label={`Acties voor ${location.name}`}>
                {location.capabilities.update && (
                    <DropdownMenuItem asChild>
                        <Link href={edit(location.id)}>
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
                    form={destroy.form(location.id)}
                    intent="delete"
                    subject={location.name}
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

export { environmentLabels, environmentStyles };
