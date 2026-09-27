import type { ColumnDef } from '@tanstack/react-table';
import { Form, Head } from '@inertiajs/react';
import { ArrowUpRight, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import IntegrationController from '@/actions/App/Http/Controllers/Admin/IntegrationController';
import {
    destroy,
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/Admin/TrackDrawConnectionController';
import { AdminConfirmationDialog } from '@/components/admin/admin-confirmation-dialog';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import type {
    adminTableFeatures,
    ServerPagination,
} from '@/components/admin/admin-data-table';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Connection = {
    id: number;
    name: string;
    events_count: number;
    updated_at: string;
};

function ConnectionDialog({
    connection,
    studioUrl,
}: {
    connection?: Connection;
    studioUrl?: string;
}) {
    const [open, setOpen] = useState(false);
    const prefix = connection
        ? `connection-${connection.id}`
        : 'new-connection';
    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {connection ? (
                    <Button
                        variant="outline"
                        size="sm"
                        aria-label={`Bewerk ${connection.name}`}
                    >
                        <Pencil />
                        Bewerken
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        Koppeling toevoegen
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {connection
                            ? 'Koppeling bewerken'
                            : 'TrackDraw koppelen'}
                    </DialogTitle>
                    <DialogDescription>
                        {connection
                            ? 'Pas de naam aan of vervang de API-key. Opgeslagen eventbanen veranderen niet.'
                            : 'Sla een API-key op voor het account waarvan je banen wilt gebruiken.'}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...(connection
                        ? update.form(connection.id)
                        : store.form())}
                    onSuccess={() => setOpen(false)}
                    options={{ preserveScroll: true }}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`${prefix}-name`}>
                                    Naam van de koppeling
                                </Label>
                                <Input
                                    id={`${prefix}-name`}
                                    name="name"
                                    defaultValue={connection?.name ?? ''}
                                    placeholder="Bijvoorbeeld DDS of Privé-account"
                                    required
                                    maxLength={255}
                                    aria-invalid={Boolean(errors.name)}
                                    aria-describedby={`${prefix}-name-error`}
                                />
                                <InputError
                                    id={`${prefix}-name-error`}
                                    message={errors.name}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`${prefix}-key`}>
                                    {connection
                                        ? 'Nieuwe API-key (optioneel)'
                                        : 'API-key'}
                                </Label>
                                <Input
                                    id={`${prefix}-key`}
                                    name="api_key"
                                    type="password"
                                    required={!connection}
                                    autoComplete="new-password"
                                    maxLength={1000}
                                    aria-invalid={Boolean(errors.api_key)}
                                    aria-describedby={`${prefix}-key-help ${prefix}-key-error`}
                                />
                                <p
                                    id={`${prefix}-key-help`}
                                    className="text-sm leading-6 text-muted-foreground"
                                >
                                    {connection
                                        ? 'Laat leeg om de huidige sleutel te behouden.'
                                        : 'Open TrackDraw Studio en maak via je profielvenster een API-key aan. Je sleutel wordt versleuteld opgeslagen en nooit teruggetoond.'}
                                </p>
                                <InputError
                                    id={`${prefix}-key-error`}
                                    message={errors.api_key}
                                />
                            </div>
                            {!connection && (
                                <a
                                    href={studioUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 text-sm font-medium text-signal-700 underline-offset-4 hover:underline dark:text-signal-300"
                                >
                                    TrackDraw Studio openen
                                    <ArrowUpRight className="size-4" />
                                </a>
                            )}
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Annuleren
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Opslaan…'
                                        : connection
                                          ? 'Wijzigingen opslaan'
                                          : 'Koppeling opslaan'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function TrackDrawSettings({
    connections,
    studioUrl,
}: {
    connections: ServerPagination<Connection>;
    studioUrl: string;
}) {
    return (
        <>
            <Head title="TrackDraw-integratie" />
            <AdminResourcePage
                eyebrow="Integraties"
                title="TrackDraw"
                description="Beheer de accounts waarvan je banen wilt tonen. Kies bij een event de koppeling en het project."
                actions={<ConnectionDialog studioUrl={studioUrl} />}
            >
                <AdminDataTable
                    caption="TrackDraw-koppelingen"
                    columns={connectionColumns}
                    pagination={connections}
                    resourceLabel="koppelingen"
                    emptyTitle="Nog geen TrackDraw-koppelingen"
                    emptyDescription="Voeg een API-key toe met een herkenbare naam. Daarna kun je bij events banen uit dat account kiezen."
                    tableClassName="min-w-0 md:min-w-[42rem]"
                />
                <p className="text-sm leading-6 text-muted-foreground">
                    De toegang wordt gecontroleerd wanneer je bij een event een
                    baan ophaalt. Een opgeslagen baan wijzigt alleen als je deze
                    zelf ververst.
                </p>
            </AdminResourcePage>
        </>
    );
}

const connectionColumns: ColumnDef<typeof adminTableFeatures, Connection>[] = [
    {
        accessorKey: 'name',
        header: 'Koppeling',
        cell: ({ row }) => (
            <div className="min-w-0">
                <p className="font-semibold break-words text-neutral-950 dark:text-white">
                    {row.original.name}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    API-key opgeslagen
                </p>
                <p className="mt-1 text-xs text-muted-foreground sm:hidden">
                    {row.original.events_count}{' '}
                    {row.original.events_count === 1 ? 'event' : 'events'}
                </p>
            </div>
        ),
    },
    {
        accessorKey: 'events_count',
        header: 'Events',
        meta: { className: 'hidden sm:table-cell' },
        cell: ({ row }) => (
            <span className="tabular-nums">{row.original.events_count}</span>
        ),
    },
    {
        accessorKey: 'updated_at',
        header: 'Bijgewerkt',
        meta: { className: 'hidden md:table-cell' },
        cell: ({ row }) => (
            <time
                dateTime={row.original.updated_at}
                className="text-muted-foreground"
            >
                {new Date(row.original.updated_at).toLocaleDateString('nl-NL')}
            </time>
        ),
    },
    {
        id: 'actions',
        header: '',
        meta: { className: 'w-36 text-right' },
        cell: ({ row }) => (
            <div className="flex items-center justify-end gap-1">
                <ConnectionDialog connection={row.original} />
                <AdminConfirmationDialog
                    intent="delete"
                    subject={row.original.name}
                    form={destroy.form(row.original.id)}
                    description={`De koppeling “${row.original.name}” wordt verwijderd. ${row.original.events_count > 0 ? 'Opgeslagen banen blijven zichtbaar. Kies bij de betrokken events een andere koppeling om ze te verversen.' : 'Opgeslagen eventbanen blijven behouden.'}`}
                    trigger={
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Verwijder ${row.original.name}`}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    }
                />
            </div>
        ),
    },
];

TrackDrawSettings.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Integraties', href: IntegrationController() },
        { title: 'TrackDraw', href: index() },
    ],
};
