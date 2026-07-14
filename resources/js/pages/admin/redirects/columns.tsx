import type { ColumnDef } from '@tanstack/react-table';
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
});

export const redirectColumns: ColumnDef<RedirectRecord>[] = [
    {
        accessorKey: 'sourcePath',
        header: 'Bron',
        cell: ({ row }) => (
            <code className="font-medium break-all text-neutral-950 dark:text-white">
                {row.original.sourcePath}
            </code>
        ),
    },
    {
        accessorKey: 'targetUrl',
        header: 'Bestemming',
        cell: ({ row }) => (
            <code className="break-all text-neutral-700 dark:text-neutral-300">
                {row.original.targetUrl}
            </code>
        ),
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <div className="flex flex-wrap gap-2">
                <Badge variant="outline">{row.original.statusCode}</Badge>
                <Badge
                    variant="outline"
                    className={
                        row.original.isActive
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                            : 'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400'
                    }
                >
                    {row.original.isActive ? 'Actief' : 'Inactief'}
                </Badge>
            </div>
        ),
    },
    {
        accessorKey: 'hitCount',
        header: 'Hits',
        cell: ({ row }) => (
            <span className="font-medium text-neutral-950 tabular-nums dark:text-white">
                {row.original.hitCount}
            </span>
        ),
    },
    {
        accessorKey: 'notes',
        header: 'Notitie',
        cell: ({ row }) => (
            <span className="block max-w-xs leading-6 text-neutral-600 dark:text-neutral-400">
                {row.original.notes ?? '—'}
            </span>
        ),
    },
    {
        accessorKey: 'updatedAt',
        header: 'Bijgewerkt',
        cell: ({ row }) => (
            <span className="whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                {dateFormatter.format(new Date(row.original.updatedAt))}
            </span>
        ),
    },
];
