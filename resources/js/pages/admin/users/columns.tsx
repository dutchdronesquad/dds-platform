import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ChevronRight } from 'lucide-react';
import { edit } from '@/actions/App/Http/Controllers/Admin/UserController';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { UserRecord } from './types';

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Europe/Amsterdam',
});

const roleLabels: Record<string, string> = {
    admin: 'Beheerder',
    editor: 'Redacteur',
};

export const userColumns: ColumnDef<UserRecord>[] = [
    {
        accessorKey: 'name',
        header: 'Gebruiker',
        cell: ({ row }) => (
            <div className="min-w-0 sm:min-w-56">
                <Link
                    href={edit(row.original.id)}
                    className="font-medium text-neutral-950 hover:text-flight-700 hover:underline dark:text-white dark:hover:text-flight-300"
                >
                    {row.original.name}
                </Link>
                <p className="mt-0.5 truncate text-xs text-neutral-500">
                    {row.original.email}
                </p>
                <div className="mt-2 flex flex-wrap gap-1.5 sm:hidden">
                    <RoleBadges roles={row.original.roles} />
                    <AdminStatusBadge
                        status={row.original.isActive ? 'active' : 'inactive'}
                        className="h-5 px-1.5 text-[10px]"
                    />
                </div>
            </div>
        ),
    },
    {
        id: 'roles',
        header: 'Rollen',
        meta: { className: 'hidden sm:table-cell' },
        cell: ({ row }) => <RoleBadges roles={row.original.roles} />,
    },
    {
        id: 'verification',
        header: 'E-mail',
        meta: { className: 'hidden md:table-cell' },
        cell: ({ row }) => (
            <Badge variant="outline">
                {row.original.emailVerifiedAt ? 'Bevestigd' : 'Niet bevestigd'}
            </Badge>
        ),
    },
    {
        id: 'account',
        header: 'Account',
        meta: { className: 'hidden sm:table-cell' },
        cell: ({ row }) => (
            <AdminStatusBadge
                status={row.original.isActive ? 'active' : 'inactive'}
            />
        ),
    },
    {
        accessorKey: 'lastActiveAt',
        header: 'Laatste sessie',
        meta: { className: 'hidden lg:table-cell' },
        cell: ({ row }) => (
            <span className="whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                {row.original.lastActiveAt
                    ? dateFormatter.format(new Date(row.original.lastActiveAt))
                    : 'Geen sessie gevonden'}
            </span>
        ),
    },
    {
        id: 'actions',
        header: () => <span className="sr-only">Acties</span>,
        cell: ({ row }) => (
            <div className="flex justify-end">
                <Button asChild variant="ghost" size="sm">
                    <Link href={edit(row.original.id)}>
                        Bewerken
                        <ChevronRight />
                    </Link>
                </Button>
            </div>
        ),
    },
];

function RoleBadges({ roles }: { roles: string[] }) {
    if (roles.length === 0) {
        return <span className="text-xs text-neutral-500">Geen beheerrol</span>;
    }

    return (
        <div className="flex flex-wrap gap-1.5">
            {roles.map((role) => (
                <Badge key={role} variant="secondary">
                    {roleLabels[role] ?? role}
                </Badge>
            ))}
        </div>
    );
}
