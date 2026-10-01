import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import type { adminTableFeatures } from '@/components/admin/admin-data-table';
import { Ban, Copy, EyeOff, Pencil, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    duplicate,
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

const eventTypeDots: Record<EventRecord['type'], string> = {
    demo: 'bg-amber-500',
    other: 'bg-neutral-400',
    race: 'bg-violet-500',
    training: 'bg-blue-500',
    workshop: 'bg-emerald-500',
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

const primaryLine =
    'block truncate text-sm leading-5 text-neutral-800 dark:text-neutral-200';
const secondaryLine =
    'mt-0.5 block truncate text-xs leading-4 text-neutral-500 dark:text-neutral-400';

export const eventColumns: ColumnDef<typeof adminTableFeatures, EventRecord>[] =
    [
        {
            accessorKey: 'title',
            header: 'Event',
            meta: {
                className: 'sm:w-[35%] 2xl:w-[32%]',
            },
            cell: ({ row }) => {
                const event = row.original;
                const startsAt = new Date(event.startsAt);
                const titleClassName =
                    'block max-w-full truncate text-sm leading-5 font-semibold text-neutral-950 dark:text-white';

                return (
                    <div className="min-w-0">
                        {event.capabilities.update ? (
                            <Link
                                href={edit(event.id)}
                                title={event.title}
                                className={cn(
                                    titleClassName,
                                    'underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:hover:text-signal-300',
                                )}
                            >
                                {event.title}
                            </Link>
                        ) : (
                            <p title={event.title} className={titleClassName}>
                                {event.title}
                            </p>
                        )}
                        <p
                            className={cn(secondaryLine, 'hidden sm:block')}
                            title={`/events/${event.slug}`}
                        >
                            /events/{event.slug}
                        </p>
                        <div className="mt-1.5 grid gap-1 sm:hidden">
                            <div className="flex min-w-0 items-center gap-2">
                                <EventTypeLabel type={event.type} />
                                {event.season && (
                                    <EventSeasonBadge
                                        name={event.season.name}
                                    />
                                )}
                            </div>
                            <p className="text-xs text-neutral-700 dark:text-neutral-300">
                                {dateFormatter.format(startsAt)} ·{' '}
                                {timeFormatter.format(startsAt)} uur
                            </p>
                            <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                {event.location.name} · {event.location.city}
                            </p>
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 pt-0.5">
                                <AdminStatusBadge status={event.status} />
                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {
                                        registrationLabels[
                                            event.registrationStatus
                                        ]
                                    }
                                </span>
                            </div>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'classification',
            header: 'Type',
            meta: {
                className: 'hidden sm:table-cell sm:w-[19%] 2xl:w-[18%]',
            },
            cell: ({ row }) => (
                <div className="min-w-0">
                    <EventTypeLabel type={row.original.type} />
                    {row.original.season && (
                        <div className="mt-1">
                            <EventSeasonBadge name={row.original.season.name} />
                        </div>
                    )}
                </div>
            ),
        },
        {
            id: 'planning',
            header: 'Planning',
            meta: {
                className: 'hidden sm:table-cell sm:w-[24%] 2xl:w-[20%]',
            },
            cell: ({ row }) => {
                const startsAt = new Date(row.original.startsAt);

                return (
                    <div className="min-w-0">
                        <p className={cn(primaryLine, 'font-medium')}>
                            {dateFormatter.format(startsAt)} ·{' '}
                            {timeFormatter.format(startsAt)} uur
                        </p>
                        <p
                            className={cn(secondaryLine, 'flex gap-1')}
                            title={`${row.original.location.name}, ${row.original.location.city}`}
                        >
                            <span className="truncate">
                                {row.original.location.name}
                            </span>
                            <span className="shrink-0">
                                · {row.original.location.city}
                            </span>
                        </p>
                    </div>
                );
            },
        },
        {
            id: 'status',
            header: 'Status',
            meta: {
                className: 'hidden sm:table-cell sm:w-[18%] 2xl:w-[16%]',
            },
            cell: ({ row }) => (
                <div className="min-w-0">
                    <AdminStatusBadge status={row.original.status} />
                    <p className={secondaryLine}>
                        {registrationLabels[row.original.registrationStatus]}
                    </p>
                </div>
            ),
        },
        {
            id: 'activity',
            header: 'Bijgewerkt',
            meta: {
                className: 'hidden 2xl:table-cell 2xl:w-[12%]',
            },
            cell: ({ row }) => (
                <div className="min-w-0">
                    <time
                        dateTime={row.original.activity.updatedAt}
                        className={primaryLine}
                    >
                        {dateFormatter.format(
                            new Date(row.original.activity.updatedAt),
                        )}
                    </time>
                    <p className={secondaryLine}>
                        {row.original.activity.updatedBy?.name ??
                            'Systeem / import'}
                    </p>
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

function EventTypeLabel({ type }: { type: EventRecord['type'] }) {
    return (
        <span className="flex shrink-0 items-center gap-2 text-sm leading-5 font-medium text-neutral-800 dark:text-neutral-200">
            <span
                aria-hidden="true"
                className={cn(
                    'size-1.5 shrink-0 rounded-full',
                    eventTypeDots[type],
                )}
            />
            {eventTypeLabels[type]}
        </span>
    );
}

function EventSeasonBadge({ name }: { name: string }) {
    return (
        <Badge
            variant="outline"
            title={name}
            className="max-w-full min-w-0 shrink border-neutral-200 bg-neutral-50 font-normal text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300"
        >
            <span className="truncate">{name}</span>
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
        event.capabilities.update ||
        event.capabilities.duplicate ||
        canPublish ||
        canUnpublish;
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

                {event.capabilities.duplicate && (
                    <DropdownMenuItem asChild className="w-full">
                        <Link
                            href={duplicate(event.id)}
                            method="post"
                            as="button"
                        >
                            <Copy />
                            Dupliceren
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
