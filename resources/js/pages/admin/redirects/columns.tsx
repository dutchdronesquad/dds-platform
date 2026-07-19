import type { ColumnDef } from '@tanstack/react-table';
import { ArrowRight } from 'lucide-react';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { Badge } from '@/components/ui/badge';

export type RedirectRecord = {
    hitCount: number;
    id: number;
    isActive: boolean;
    notes: string | null;
    sourcePath: string;
    statusCode: number;
    targetUrl: string;
    updatedAt: string;
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

export const redirectColumns: ColumnDef<RedirectRecord>[] = [
    {
        accessorKey: 'sourcePath',
        header: 'Bron',
        cell: ({ row }) => (
            <div className="min-w-0 sm:min-w-52">
                <code className="font-medium break-all text-neutral-950 dark:text-white">
                    {row.original.sourcePath}
                </code>
                <div className="mt-1.5 flex items-start gap-1.5 text-neutral-500 sm:hidden">
                    <ArrowRight className="mt-0.5 size-3 shrink-0" />
                    <code className="text-xs break-all">
                        {row.original.targetUrl}
                    </code>
                </div>
                <div className="mt-2 flex flex-wrap items-center gap-1.5 sm:hidden">
                    <Badge variant="outline" className="h-5 px-1.5 text-[10px]">
                        {row.original.statusCode}
                    </Badge>
                    <AdminStatusBadge
                        status={row.original.isActive ? 'active' : 'inactive'}
                        className="h-5 px-1.5 text-[10px]"
                    />
                    <span className="text-xs text-neutral-500 tabular-nums">
                        {row.original.hitCount}{' '}
                        {row.original.hitCount === 1 ? 'hit' : 'hits'}
                    </span>
                </div>
            </div>
        ),
    },
    {
        accessorKey: 'targetUrl',
        header: 'Bestemming',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <code className="break-all text-neutral-700 dark:text-neutral-300">
                {row.original.targetUrl}
            </code>
        ),
    },
    {
        id: 'status',
        header: 'Status',
        meta: {
            className: 'hidden md:table-cell',
        },
        cell: ({ row }) => (
            <div className="flex flex-wrap gap-2">
                <Badge variant="outline">{row.original.statusCode}</Badge>
                <AdminStatusBadge
                    status={row.original.isActive ? 'active' : 'inactive'}
                />
            </div>
        ),
    },
    {
        accessorKey: 'hitCount',
        header: 'Hits',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <span className="font-medium text-neutral-950 tabular-nums dark:text-white">
                {row.original.hitCount}
            </span>
        ),
    },
    {
        accessorKey: 'notes',
        header: 'Notitie',
        meta: {
            className: 'hidden lg:table-cell',
        },
        cell: ({ row }) => (
            <span className="block max-w-xs leading-6 text-neutral-600 dark:text-neutral-400">
                {row.original.notes ?? '—'}
            </span>
        ),
    },
    {
        accessorKey: 'updatedAt',
        header: 'Bijgewerkt',
        meta: {
            className: 'hidden md:table-cell',
        },
        cell: ({ row }) => (
            <span className="whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                {dateFormatter.format(new Date(row.original.updatedAt))}
            </span>
        ),
    },
];
