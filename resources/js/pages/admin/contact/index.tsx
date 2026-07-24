import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { AlertTriangle, Inbox, MailCheck, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/ContactController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import type { ContactDeliveryStatus, ContactSubmissionRow } from './types';

type Props = {
    contactSubmissions: ServerPagination<ContactSubmissionRow>;
    filters: {
        search: string;
    };
    summary: {
        delivered: number;
        followUpNeeded: number;
        total: number;
    };
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const deliveryStyles: Record<ContactDeliveryStatus, string> = {
    pending:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    sent: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    not_configured:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
    failed: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
};

const columns: ColumnDef<ContactSubmissionRow>[] = [
    {
        accessorKey: 'name',
        header: 'Contact',
        cell: ({ row }) => (
            <div className="grid gap-1">
                <Link
                    href={show(row.original.id)}
                    className="font-medium text-neutral-950 hover:text-flight-700 dark:text-white dark:hover:text-flight-300"
                >
                    {row.original.name}
                </Link>
                <a
                    href={`mailto:${row.original.email}`}
                    className="text-xs text-neutral-500 hover:text-flight-700 dark:text-neutral-400 dark:hover:text-flight-300"
                >
                    {row.original.email}
                </a>
            </div>
        ),
    },
    {
        accessorKey: 'topicLabel',
        header: 'Onderwerp',
        meta: { className: 'hidden md:table-cell' },
    },
    {
        accessorKey: 'messageExcerpt',
        header: 'Bericht',
        meta: { className: 'hidden lg:table-cell' },
        cell: ({ row }) => (
            <span className="block max-w-md leading-6 text-neutral-600 dark:text-neutral-400">
                {row.original.messageExcerpt}
            </span>
        ),
    },
    {
        accessorKey: 'deliveryStatus',
        header: 'Notificatie',
        cell: ({ row }) => (
            <Badge
                variant="outline"
                className={deliveryStyles[row.original.deliveryStatus]}
            >
                {row.original.deliveryStatusLabel}
            </Badge>
        ),
    },
    {
        accessorKey: 'createdAt',
        header: 'Ontvangen',
        meta: { className: 'hidden sm:table-cell' },
        cell: ({ row }) => (
            <span className="whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                {dateFormatter.format(new Date(row.original.createdAt))}
            </span>
        ),
    },
];

export default function ContactIndex({
    contactSubmissions,
    filters,
    summary,
}: Props) {
    const [search, setSearch] = useState(filters.search);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        router.get(
            index(),
            { search: search.trim() || undefined },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Contactaanvragen" />
            <AdminResourcePage
                eyebrow="Communicatie"
                title="Contactaanvragen"
                description="Bekijk publieke berichten en controleer direct welke aanvragen handmatige opvolging nodig hebben."
            >
                <AdminListSummary
                    label="Contactsamenvatting"
                    metrics={[
                        {
                            label: 'Totaal',
                            value: summary.total,
                            icon: Inbox,
                        },
                        {
                            label: 'E-mail verzonden',
                            value: summary.delivered,
                            icon: MailCheck,
                            tone: 'blue',
                        },
                        {
                            label: 'Opvolging nodig',
                            value: summary.followUpNeeded,
                            icon: AlertTriangle,
                            tone: 'amber',
                        },
                    ]}
                />

                <AdminDataTable
                    caption="Overzicht van contactaanvragen"
                    columns={columns}
                    emptyTitle="Geen contactaanvragen gevonden"
                    emptyDescription={
                        filters.search
                            ? 'Pas je zoekopdracht aan.'
                            : 'Nieuwe publieke berichten verschijnen hier automatisch.'
                    }
                    pagination={contactSubmissions}
                    resourceLabel="contactaanvragen"
                    tableClassName="min-w-[54rem]"
                    toolbar={
                        <form
                            onSubmit={submit}
                            className="flex flex-col gap-2 sm:flex-row"
                        >
                            <div className="relative w-full max-w-md">
                                <label
                                    htmlFor="contact-search"
                                    className="sr-only"
                                >
                                    Contactaanvragen zoeken
                                </label>
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                                <Input
                                    id="contact-search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Zoek op naam, e-mail of bericht"
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit" variant="outline">
                                Zoeken
                            </Button>
                        </form>
                    }
                />
            </AdminResourcePage>
        </>
    );
}

ContactIndex.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Contactaanvragen', href: index() },
    ],
};
