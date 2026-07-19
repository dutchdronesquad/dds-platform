import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Ban, EyeOff, Pencil, Send, Tags, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    edit,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import {
    cancel,
    publish,
    unpublish,
} from '@/actions/App/Http/Controllers/Admin/EventStatusController';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminRowActions } from '@/components/admin/admin-row-actions';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { EventRecord } from './types';

const eventTypeLabels: Record<EventRecord['type'], string> = {
    demo: 'Demo',
    other: 'Overig',
    race: 'Race',
    training: 'Training',
    workshop: 'Workshop',
};

const eventTypeStyles: Record<EventRecord['type'], string> = {
    demo: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
    other: 'border-neutral-200 bg-neutral-100 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
    race: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300',
    training:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    workshop:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
};

const registrationLabels: Record<EventRecord['registrationStatus'], string> = {
    closed: 'Registratie gesloten',
    full: 'Registratie vol',
    open: 'Registratie open',
    waitlist: 'Wachtlijst',
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeZone: 'Europe/Amsterdam',
});

const timeFormatter = new Intl.DateTimeFormat('nl-NL', {
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

export const eventColumns: ColumnDef<EventRecord>[] = [
    {
        accessorKey: 'title',
        header: 'Event',
        cell: ({ row }) => {
            const startsAt = new Date(row.original.startsAt);

            return (
                <div className="min-w-0 sm:min-w-60">
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
                    <div className="mt-2 flex flex-wrap items-center gap-1.5 sm:hidden">
                        <span className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                            {dateFormatter.format(startsAt)} ·{' '}
                            {timeFormatter.format(startsAt)}
                        </span>
                        <EventTypeBadge
                            type={row.original.type}
                            className="h-5 px-1.5 text-[10px]"
                        />
                        <AdminStatusBadge
                            status={row.original.status}
                            className="h-5 px-1.5 text-[10px]"
                        />
                    </div>
                </div>
            );
        },
    },
    {
        accessorKey: 'startsAt',
        header: 'Start',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => {
            const startsAt = new Date(row.original.startsAt);

            return (
                <div className="whitespace-nowrap">
                    <p className="font-medium text-neutral-800 dark:text-neutral-200">
                        {dateFormatter.format(startsAt)}
                    </p>
                    <p className="mt-0.5 text-xs text-neutral-500">
                        {timeFormatter.format(startsAt)} uur
                    </p>
                </div>
            );
        },
    },
    {
        id: 'location',
        header: 'Locatie',
        meta: {
            className: 'hidden md:table-cell',
        },
        cell: ({ row }) => (
            <div className="min-w-40 text-sm">
                <p className="font-medium text-neutral-800 dark:text-neutral-200">
                    {row.original.location.name}
                </p>
                <p className="mt-0.5 text-xs text-neutral-500">
                    {row.original.location.city}
                </p>
            </div>
        ),
    },
    {
        accessorKey: 'type',
        header: 'Type',
        meta: {
            className: 'hidden md:table-cell',
        },
        cell: ({ row }) => <EventTypeBadge type={row.original.type} />,
    },
    {
        id: 'season',
        header: 'Seizoen',
        meta: {
            className: 'hidden lg:table-cell',
        },
        cell: ({ row }) =>
            row.original.season ? (
                <Badge
                    variant="secondary"
                    className="max-w-48 justify-start gap-1.5 bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                >
                    <Tags />
                    <span className="truncate">{row.original.season.name}</span>
                </Badge>
            ) : (
                <span className="text-xs font-medium text-neutral-400 dark:text-neutral-500">
                    Los event
                </span>
            ),
    },
    {
        id: 'status',
        header: 'Status',
        meta: {
            className: 'hidden sm:table-cell',
        },
        cell: ({ row }) => (
            <div className="flex min-w-40 flex-wrap items-center gap-2">
                <AdminStatusBadge status={row.original.status} />
                <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    {registrationLabels[row.original.registrationStatus]}
                </span>
            </div>
        ),
    },
    {
        id: 'actions',
        header: '',
        meta: {
            className: 'w-12 text-right',
        },
        cell: ({ row }) => <EventActions event={row.original} />,
    },
];

function EventTypeBadge({
    className,
    type,
}: {
    className?: string;
    type: EventRecord['type'];
}) {
    return (
        <Badge
            variant="outline"
            className={cn(eventTypeStyles[type], className)}
        >
            {eventTypeLabels[type]}
        </Badge>
    );
}

type PendingEventAction = 'cancel' | 'delete' | 'publish' | 'unpublish';

function EventActions({ event }: { event: EventRecord }) {
    const [pendingAction, setPendingAction] =
        useState<PendingEventAction | null>(null);
    const canPublish =
        event.capabilities.publish && event.status !== 'published';
    const canUnpublish =
        event.capabilities.publish && event.status === 'published';
    const canCancel = event.capabilities.cancel && event.status === 'published';
    const canDelete = event.capabilities.delete;
    const hasPrimaryActions =
        event.capabilities.update || canPublish || canUnpublish;
    const hasActions = hasPrimaryActions || canCancel || canDelete;
    const confirmation = pendingAction
        ? {
              publish: {
                  form: publish.form(event.id),
                  intent: 'publish' as const,
              },
              unpublish: {
                  form: unpublish.form(event.id),
                  intent: 'unpublish' as const,
              },
              cancel: {
                  form: cancel.form(event.id),
                  intent: 'cancel' as const,
              },
              delete: {
                  form: destroy.form(event.id),
                  intent: 'delete' as const,
              },
          }[pendingAction]
        : null;

    if (!hasActions) {
        return null;
    }

    return (
        <div className="flex justify-end">
            <AdminRowActions label={`Acties voor ${event.title}`}>
                {event.capabilities.update && (
                    <DropdownMenuItem asChild>
                        <Link href={edit(event.id)}>
                            <Pencil />
                            Bewerken
                        </Link>
                    </DropdownMenuItem>
                )}

                {canPublish && (
                    <DropdownMenuItem
                        onSelect={() => setPendingAction('publish')}
                    >
                        <Send />
                        Publiceren
                    </DropdownMenuItem>
                )}

                {canUnpublish && (
                    <DropdownMenuItem
                        onSelect={() => setPendingAction('unpublish')}
                    >
                        <EyeOff />
                        Publicatie intrekken
                    </DropdownMenuItem>
                )}

                {hasPrimaryActions && (canCancel || canDelete) && (
                    <DropdownMenuSeparator />
                )}

                {canCancel && (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setPendingAction('cancel')}
                    >
                        <Ban />
                        Event annuleren
                    </DropdownMenuItem>
                )}

                {canDelete && (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setPendingAction('delete')}
                    >
                        <Trash2 />
                        Verwijderen
                    </DropdownMenuItem>
                )}
            </AdminRowActions>

            {confirmation && (
                <AdminConfirmationDialog
                    form={confirmation.form}
                    intent={confirmation.intent}
                    subject={event.title}
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setPendingAction(null);
                        }
                    }}
                />
            )}
        </div>
    );
}
