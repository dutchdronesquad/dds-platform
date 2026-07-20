import { Head, Link } from '@inertiajs/react';
import {
    Check,
    CircleCheckBig,
    Code2,
    ShieldAlert,
    ShieldCheck,
    Users,
    X,
} from 'lucide-react';
import RolePermissionController from '@/actions/App/Http/Controllers/Admin/RolePermissionController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';

type RoleRecord = {
    name: string;
    label: string;
    description: string;
    userCount: number;
    permissions: string[];
    isStored: boolean;
    isSynchronized: boolean;
};

type PermissionRecord = {
    value: string;
    label: string;
    description: string;
    isStored: boolean;
};

type PermissionGroup = {
    key: string;
    label: string;
    permissions: PermissionRecord[];
};

type Synchronization = {
    isSynchronized: boolean;
    missingRoles: string[];
    missingPermissions: string[];
    unexpectedRoles: string[];
    unexpectedPermissions: string[];
};

type Props = {
    roles: RoleRecord[];
    permissionGroups: PermissionGroup[];
    synchronization: Synchronization;
};

export default function RolePermissionIndex({
    roles,
    permissionGroups,
    synchronization,
}: Props) {
    return (
        <>
            <Head title="Rollen en rechten" />

            <AdminResourcePage
                eyebrow="Toegangsbeheer"
                title="Rollen en rechten"
                description="Controleer welke code-owned rechten aan iedere beheerrol zijn gekoppeld. Wijzig rollen per gebruiker vanuit het gebruikersbeheer."
                actions={
                    <Button asChild variant="outline">
                        <Link href={usersIndex()}>
                            <Users /> Gebruikers beheren
                        </Link>
                    </Button>
                }
            >
                <SynchronizationAlert synchronization={synchronization} />

                <section aria-labelledby="roles-title">
                    <div className="mb-4">
                        <h2
                            id="roles-title"
                            className="text-lg font-semibold text-neutral-950 dark:text-white"
                        >
                            Actieve rollen
                        </h2>
                        <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                            Rollen worden in code beheerd. Dit overzicht biedt
                            daarom bewust geen acties om rollen of rechten aan
                            te maken of te wijzigen.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        {roles.map((role) => (
                            <article
                                key={role.name}
                                className="rounded-xl border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex min-w-0 items-start gap-3">
                                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-signal-50 text-signal-700 dark:bg-signal-500/10 dark:text-signal-300">
                                            <ShieldCheck className="size-5" />
                                        </span>
                                        <div className="min-w-0">
                                            <h3 className="font-semibold text-neutral-950 dark:text-white">
                                                {role.label}
                                            </h3>
                                            <code className="mt-0.5 block text-xs text-neutral-500">
                                                {role.name}
                                            </code>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className={
                                            role.isSynchronized
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300'
                                        }
                                    >
                                        {role.isSynchronized
                                            ? 'Gesynchroniseerd'
                                            : 'Controle nodig'}
                                    </Badge>
                                </div>

                                <p className="mt-4 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                    {role.description}
                                </p>

                                <dl className="mt-5 grid grid-cols-2 gap-3 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                                    <div>
                                        <dt className="text-xs text-neutral-500">
                                            Gebruikers
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold text-neutral-950 tabular-nums dark:text-white">
                                            {role.userCount}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-neutral-500">
                                            Actieve rechten
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold text-neutral-950 tabular-nums dark:text-white">
                                            {role.permissions.length}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        ))}
                    </div>
                </section>

                <section
                    aria-labelledby="permission-matrix-title"
                    className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-white shadow-xs dark:border-sidebar-border dark:bg-neutral-950"
                >
                    <div className="border-b border-neutral-200 px-4 py-4 sm:px-5 dark:border-neutral-800">
                        <h2
                            id="permission-matrix-title"
                            className="text-lg font-semibold text-neutral-950 dark:text-white"
                        >
                            Rechtenmatrix
                        </h2>
                        <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                            Iedere rij is een waarde uit de Permission-enum; de
                            vinkjes tonen de momenteel opgeslagen toewijzing.
                        </p>
                    </div>

                    <Table className="min-w-2xl">
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-full px-4 sm:px-5">
                                    Recht
                                </TableHead>
                                {roles.map((role) => (
                                    <TableHead
                                        key={role.name}
                                        className="min-w-28 px-4 text-center"
                                    >
                                        {role.label}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {permissionGroups.map((group) => (
                                <PermissionGroupRows
                                    key={group.key}
                                    group={group}
                                    roles={roles}
                                />
                            ))}
                        </TableBody>
                    </Table>
                </section>
            </AdminResourcePage>
        </>
    );
}

function SynchronizationAlert({
    synchronization,
}: {
    synchronization: Synchronization;
}) {
    if (synchronization.isSynchronized) {
        return (
            <Alert className="border-emerald-200 bg-emerald-50/70 text-emerald-950 dark:border-emerald-500/25 dark:bg-emerald-500/8 dark:text-emerald-100">
                <CircleCheckBig />
                <AlertTitle>Database en code zijn gesynchroniseerd</AlertTitle>
                <AlertDescription className="text-emerald-800 dark:text-emerald-200/80">
                    Alle opgeslagen rollen, rechten en toewijzingen sluiten aan
                    op de huidige enumwaarden.
                </AlertDescription>
            </Alert>
        );
    }

    const differences = [
        ...synchronization.missingRoles.map(
            (value) => `Ontbrekende rol: ${value}`,
        ),
        ...synchronization.missingPermissions.map(
            (value) => `Ontbrekend recht: ${value}`,
        ),
        ...synchronization.unexpectedRoles.map(
            (value) => `Onbekende rol: ${value}`,
        ),
        ...synchronization.unexpectedPermissions.map(
            (value) => `Onbekend recht: ${value}`,
        ),
    ];

    return (
        <Alert className="border-amber-200 bg-amber-50/70 text-amber-950 dark:border-amber-500/25 dark:bg-amber-500/8 dark:text-amber-100">
            <ShieldAlert />
            <AlertTitle>De toegangsconfiguratie vraagt controle</AlertTitle>
            <AlertDescription className="text-amber-800 dark:text-amber-200/80">
                <p>
                    Controleer de seeder en opgeslagen data om de database weer
                    met de code in lijn te brengen.
                </p>
                {differences.length > 0 && (
                    <ul className="mt-1 list-disc pl-4">
                        {differences.map((difference) => (
                            <li key={difference}>{difference}</li>
                        ))}
                    </ul>
                )}
            </AlertDescription>
        </Alert>
    );
}

function PermissionGroupRows({
    group,
    roles,
}: {
    group: PermissionGroup;
    roles: RoleRecord[];
}) {
    return (
        <>
            <TableRow className="bg-neutral-50 hover:bg-neutral-50 dark:bg-neutral-900/60 dark:hover:bg-neutral-900/60">
                <TableCell
                    colSpan={roles.length + 1}
                    className="px-4 py-2 text-xs font-semibold tracking-[0.12em] text-neutral-600 uppercase sm:px-5 dark:text-neutral-300"
                >
                    {group.label}
                </TableCell>
            </TableRow>
            {group.permissions.map((permission) => (
                <TableRow key={permission.value}>
                    <TableCell className="px-4 py-3 whitespace-normal sm:px-5">
                        <div className="flex items-start gap-3">
                            <Code2 className="mt-0.5 size-4 shrink-0 text-neutral-400" />
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium text-neutral-950 dark:text-white">
                                        {permission.label}
                                    </span>
                                    {!permission.isStored && (
                                        <Badge variant="destructive">
                                            Ontbreekt
                                        </Badge>
                                    )}
                                </div>
                                <code className="mt-0.5 block text-xs text-neutral-500">
                                    {permission.value}
                                </code>
                                <p className="mt-1 max-w-2xl text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {permission.description}
                                </p>
                            </div>
                        </div>
                    </TableCell>
                    {roles.map((role) => {
                        const isAssigned = role.permissions.includes(
                            permission.value,
                        );

                        return (
                            <TableCell
                                key={role.name}
                                className="px-4 text-center"
                            >
                                <span className="sr-only">
                                    {isAssigned
                                        ? `${permission.label} is toegewezen aan ${role.label}`
                                        : `${permission.label} is niet toegewezen aan ${role.label}`}
                                </span>
                                {isAssigned ? (
                                    <Check
                                        aria-hidden="true"
                                        className="mx-auto size-5 text-emerald-600 dark:text-emerald-400"
                                    />
                                ) : (
                                    <X
                                        aria-hidden="true"
                                        className="mx-auto size-4 text-neutral-300 dark:text-neutral-700"
                                    />
                                )}
                            </TableCell>
                        );
                    })}
                </TableRow>
            ))}
        </>
    );
}

RolePermissionIndex.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Rollen en rechten', href: RolePermissionController() },
    ],
};
