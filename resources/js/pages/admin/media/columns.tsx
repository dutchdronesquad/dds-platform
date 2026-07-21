import { Form } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Archive, FileText, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    archive,
    restore,
} from '@/actions/App/Http/Controllers/Admin/MediaAssetArchiveController';
import { destroy } from '@/actions/App/Http/Controllers/Admin/MediaAssetController';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminRowActions } from '@/components/admin/admin-row-actions';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { formatFileSize } from '@/lib/file-formatting';
import type { MediaAssetRecord } from '@/types/media';

export function mediaColumns(
    onOpenDetails: (mediaAsset: MediaAssetRecord) => void,
): ColumnDef<MediaAssetRecord>[] {
    return [
        {
            id: 'preview',
            header: 'Voorbeeld',
            meta: { className: 'w-28' },
            cell: ({ row }) => (
                <button
                    type="button"
                    data-test={`media-details-table-preview-${row.original.id}`}
                    aria-label={`Details van ${row.original.filename} bekijken`}
                    onClick={() => onOpenDetails(row.original)}
                    className="block w-24 cursor-pointer rounded-md text-left ring-offset-2 transition hover:ring-2 hover:ring-signal-500/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    {row.original.isImage ? (
                        <img
                            src={row.original.url}
                            alt=""
                            loading="lazy"
                            decoding="async"
                            className="aspect-video w-full rounded-md bg-neutral-100 object-cover dark:bg-neutral-900"
                        />
                    ) : (
                        <span className="flex aspect-video w-full items-center justify-center rounded-md bg-neutral-100 text-neutral-400 dark:bg-neutral-900">
                            <FileText className="size-7" />
                        </span>
                    )}
                </button>
            ),
        },
        {
            accessorKey: 'filename',
            header: 'Bestand',
            cell: ({ row }) => (
                <div className="min-w-52">
                    <button
                        type="button"
                        data-test={`media-details-table-trigger-${row.original.id}`}
                        onClick={() => onOpenDetails(row.original)}
                        className="max-w-full truncate text-left font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                    >
                        {row.original.filename}
                    </button>
                    <p className="mt-1 text-xs text-neutral-500">
                        {row.original.mimeType}
                        {row.original.width && row.original.height
                            ? ` · ${row.original.width} × ${row.original.height} px`
                            : ''}
                    </p>
                </div>
            ),
        },
        {
            id: 'size',
            header: 'Omvang',
            meta: { className: 'hidden md:table-cell' },
            cell: ({ row }) => (
                <span className="text-sm text-neutral-700 dark:text-neutral-300">
                    {formatFileSize(row.original.sizeBytes)}
                </span>
            ),
        },
        {
            accessorKey: 'usageCount',
            header: 'Gebruik',
            cell: ({ row }) => (
                <button
                    type="button"
                    data-test={`media-usage-trigger-${row.original.id}`}
                    onClick={() => onOpenDetails(row.original)}
                    className="rounded-sm text-left text-sm font-medium text-neutral-700 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-neutral-300 dark:hover:text-signal-300"
                >
                    {row.original.usageCount === 0
                        ? 'Ongebruikt'
                        : row.original.usageCount === 1
                          ? '1 koppeling'
                          : `${row.original.usageCount} koppelingen`}
                </button>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            meta: { className: 'hidden sm:table-cell' },
            cell: ({ row }) => (
                <Badge
                    variant="outline"
                    className={
                        row.original.archivedAt
                            ? 'border-amber-300 text-amber-800 dark:border-amber-500/40 dark:text-amber-300'
                            : undefined
                    }
                >
                    {row.original.archivedAt ? 'Gearchiveerd' : 'Actief'}
                </Badge>
            ),
        },
        {
            id: 'actions',
            header: '',
            meta: { className: 'w-12 text-right' },
            cell: ({ row }) => (
                <MediaActions
                    mediaAsset={row.original}
                    onOpenDetails={onOpenDetails}
                />
            ),
        },
    ];
}

export function MediaActions({
    mediaAsset,
    onOpenDetails,
}: {
    mediaAsset: MediaAssetRecord;
    onOpenDetails: (mediaAsset: MediaAssetRecord) => void;
}) {
    const [archiveDialogOpen, setArchiveDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    if (!mediaAsset.capabilities.update && !mediaAsset.capabilities.delete) {
        return null;
    }

    return (
        <div className="flex justify-end">
            <AdminRowActions label={`Acties voor ${mediaAsset.filename}`}>
                {mediaAsset.capabilities.update && (
                    <>
                        <DropdownMenuItem asChild>
                            <button
                                type="button"
                                className="w-full"
                                onClick={() => onOpenDetails(mediaAsset)}
                            >
                                <Pencil /> Bewerken
                            </button>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {mediaAsset.archivedAt ? (
                            <Form {...restore.form(mediaAsset.id)}>
                                {({ processing }) => (
                                    <DropdownMenuItem asChild>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full"
                                        >
                                            <RotateCcw /> Herstellen
                                        </button>
                                    </DropdownMenuItem>
                                )}
                            </Form>
                        ) : (
                            <DropdownMenuItem
                                onSelect={() => setArchiveDialogOpen(true)}
                            >
                                <Archive /> Archiveren
                            </DropdownMenuItem>
                        )}
                    </>
                )}

                {mediaAsset.capabilities.delete && (
                    <>
                        {mediaAsset.capabilities.update && (
                            <DropdownMenuSeparator />
                        )}
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setDeleteDialogOpen(true)}
                        >
                            <Trash2 /> Definitief verwijderen
                        </DropdownMenuItem>
                    </>
                )}
            </AdminRowActions>

            {archiveDialogOpen && (
                <AdminConfirmationDialog
                    form={archive.form(mediaAsset.id)}
                    intent="archive"
                    subject={mediaAsset.filename}
                    open
                    onOpenChange={setArchiveDialogOpen}
                />
            )}
            {deleteDialogOpen && (
                <AdminConfirmationDialog
                    form={destroy.form(mediaAsset.id)}
                    intent="delete"
                    subject={mediaAsset.filename}
                    description="Het database-item en het opgeslagen bestand worden definitief verwijderd."
                    open
                    onOpenChange={setDeleteDialogOpen}
                />
            )}
        </div>
    );
}
