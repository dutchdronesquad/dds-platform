import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminActivityByline } from '@/components/admin/admin-activity-metadata';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminRowActions } from '@/components/admin/admin-row-actions';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import type { SalesState, SeasonRecord } from './types';

const salesStateLabels: Record<SalesState, string> = {
    available: 'Beschikbaar',
    closed: 'Gesloten',
    coming_soon: 'Binnenkort',
    sold_out: 'Uitverkocht',
};

const currencyFormatter = new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency: 'EUR',
});

export const seasonColumns: ColumnDef<SeasonRecord>[] = [
    {
        accessorKey: 'name',
        header: 'Seizoen',
        cell: ({ row }) => (
            <div className="min-w-0 sm:min-w-64">
                <Link
                    href={edit(row.original.slug)}
                    className="font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                >
                    {row.original.name}
                </Link>
                <div className="mt-2 flex flex-wrap items-center gap-1.5 text-xs sm:hidden">
                    <span className="font-medium text-neutral-600 dark:text-neutral-300">
                        {row.original.eventCount}{' '}
                        {row.original.eventCount === 1 ? 'event' : 'events'}
                    </span>
                    {row.original.ticket ? (
                        <Badge
                            variant="outline"
                            className="h-5 px-1.5 text-[10px] font-normal"
                        >
                            {salesStateLabels[row.original.ticket.salesState]}
                            {row.original.ticket.priceCents !== null &&
                                ` · ${currencyFormatter.format(row.original.ticket.priceCents / 100)}`}
                        </Badge>
                    ) : (
                        <span className="text-neutral-500">Geen ticket</span>
                    )}
                </div>
            </div>
        ),
    },
    {
        accessorKey: 'eventCount',
        header: 'Events',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <div>
                <p className="font-semibold text-neutral-950 tabular-nums dark:text-white">
                    {row.original.eventCount}
                </p>
                <p className="mt-0.5 text-xs text-neutral-500">
                    {row.original.eventCount === 1 ? 'event' : 'events'}
                </p>
            </div>
        ),
    },
    {
        id: 'ticket',
        header: 'Seizoensticket',
        meta: {
            className: 'hidden md:table-cell',
        },
        cell: ({ row }) =>
            row.original.ticket ? (
                <div className="min-w-56">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-neutral-950 tabular-nums dark:text-white">
                            {row.original.ticket.priceCents === null
                                ? 'Prijs niet ingesteld'
                                : currencyFormatter.format(
                                      row.original.ticket.priceCents / 100,
                                  )}
                        </p>
                        <Badge variant="outline" className="font-normal">
                            {salesStateLabels[row.original.ticket.salesState]}
                        </Badge>
                    </div>
                    <p className="mt-1 text-xs text-neutral-500">
                        {row.original.ticket.capacity === null
                            ? 'Geen capaciteitslimiet'
                            : `Maximaal ${row.original.ticket.capacity} tickets`}
                    </p>
                </div>
            ) : (
                <span className="text-sm text-neutral-500">
                    Niet aangeboden
                </span>
            ),
    },
    {
        id: 'activity',
        header: 'Bijgewerkt',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <AdminActivityByline activity={row.original.activity} />
        ),
    },
    {
        id: 'actions',
        header: '',
        meta: {
            className: 'w-12 text-right',
        },
        cell: ({ row }) => <SeasonActions season={row.original} />,
    },
];

function SeasonActions({ season }: { season: SeasonRecord }) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const canDelete = season.eventCount === 0;

    return (
        <div className="flex justify-end">
            <AdminRowActions label={`Acties voor ${season.name}`}>
                <DropdownMenuItem asChild>
                    <Link href={edit(season.slug)}>
                        <Pencil />
                        Bewerken
                    </Link>
                </DropdownMenuItem>

                {canDelete && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setDeleteDialogOpen(true)}
                        >
                            <Trash2 />
                            Verwijderen
                        </DropdownMenuItem>
                    </>
                )}
            </AdminRowActions>

            {deleteDialogOpen && (
                <AdminConfirmationDialog
                    form={destroy.form(season.slug)}
                    intent="delete"
                    subject={season.name}
                    open
                    onOpenChange={setDeleteDialogOpen}
                />
            )}
        </div>
    );
}
