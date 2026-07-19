import { Head, router } from '@inertiajs/react';
import {
    BadgeCheck,
    Search,
    ShieldCheck,
    UserCheck,
    Users,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { index } from '@/actions/App/Http/Controllers/Admin/UserController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import { AdminDataTableFacetFilter } from '@/components/admin/admin-data-table-facet-filter';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { userColumns } from './columns';
import type { UserRecord } from './types';

type UserFilters = {
    search: string;
    role: string[];
    verification: string[];
    account: string[];
    activity: string[];
};

type Props = {
    users: ServerPagination<UserRecord>;
    filters: UserFilters;
    facets: {
        roles: Record<string, number>;
        verified: number;
        unverified: number;
        active: number;
        inactive: number;
        recent: number;
        never: number;
    };
    summary: {
        total: number;
        active: number;
        admins: number;
        unverified: number;
    };
};

export default function UsersIndex({ users, filters, facets, summary }: Props) {
    const [search, setSearch] = useState(filters.search);
    const hasFilters =
        filters.search !== '' ||
        filters.role.length > 0 ||
        filters.verification.length > 0 ||
        filters.account.length > 0 ||
        filters.activity.length > 0;

    function visit(nextFilters: UserFilters): void {
        router.visit(
            index({
                query: {
                    search: nextFilters.search || undefined,
                    role: nextFilters.role.length
                        ? nextFilters.role
                        : undefined,
                    verification: nextFilters.verification.length
                        ? nextFilters.verification
                        : undefined,
                    account: nextFilters.account.length
                        ? nextFilters.account
                        : undefined,
                    activity: nextFilters.activity.length
                        ? nextFilters.activity
                        : undefined,
                },
            }),
            {
                only: ['users', 'filters', 'facets'],
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        visit({ ...filters, search: search.trim() });
    }

    function resetFilters(): void {
        setSearch('');
        visit({
            search: '',
            role: [],
            verification: [],
            account: [],
            activity: [],
        });
    }

    return (
        <>
            <Head title="Gebruikers" />
            <AdminResourcePage
                eyebrow="Toegangsbeheer"
                title="Gebruikers"
                description="Beheer profielgegevens, rollen, taalvoorkeur en accounttoegang zonder directe databasewijzigingen."
            >
                <AdminListSummary
                    label="Gebruikerssamenvatting"
                    metrics={[
                        { label: 'Totaal', value: summary.total, icon: Users },
                        {
                            label: 'Actief',
                            value: summary.active,
                            icon: UserCheck,
                            tone: 'blue',
                        },
                        {
                            label: 'Beheerders',
                            value: summary.admins,
                            icon: ShieldCheck,
                        },
                        {
                            label: 'Onbevestigd',
                            value: summary.unverified,
                            icon: BadgeCheck,
                            tone: 'amber',
                        },
                    ]}
                />

                <AdminDataTable
                    caption="Overzicht van platformgebruikers"
                    columns={userColumns}
                    emptyTitle="Geen gebruikers gevonden"
                    emptyDescription={
                        hasFilters
                            ? 'Pas de zoekopdracht of filters aan.'
                            : 'Er zijn nog geen gebruikersaccounts.'
                    }
                    pagination={users}
                    resourceLabel="gebruikers"
                    tableClassName="min-w-0 lg:min-w-5xl"
                    toolbar={
                        <form onSubmit={submit} className="flex flex-col gap-3">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                                    <div className="relative w-full sm:w-80">
                                        <label
                                            htmlFor="user-search"
                                            className="sr-only"
                                        >
                                            Gebruikers zoeken
                                        </label>
                                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                                        <Input
                                            id="user-search"
                                            value={search}
                                            onChange={(event) =>
                                                setSearch(event.target.value)
                                            }
                                            maxLength={100}
                                            placeholder="Zoek naam of e-mail…"
                                            className="h-9 pr-20 pl-9"
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="ghost"
                                            className="absolute top-1/2 right-1 h-7 -translate-y-1/2 px-2"
                                        >
                                            Zoek
                                        </Button>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <AdminDataTableFacetFilter
                                            title="Rol"
                                            selected={filters.role}
                                            options={[
                                                {
                                                    value: 'admin',
                                                    label: 'Beheerder',
                                                    count:
                                                        facets.roles.admin ?? 0,
                                                },
                                                {
                                                    value: 'editor',
                                                    label: 'Redacteur',
                                                    count:
                                                        facets.roles.editor ??
                                                        0,
                                                },
                                                {
                                                    value: 'none',
                                                    label: 'Geen rol',
                                                    count:
                                                        facets.roles.none ?? 0,
                                                },
                                            ]}
                                            onChange={(role) =>
                                                visit({
                                                    ...filters,
                                                    search: search.trim(),
                                                    role,
                                                })
                                            }
                                        />
                                        <AdminDataTableFacetFilter
                                            title="E-mail"
                                            selected={filters.verification}
                                            options={[
                                                {
                                                    value: 'verified',
                                                    label: 'Bevestigd',
                                                    count: facets.verified,
                                                },
                                                {
                                                    value: 'unverified',
                                                    label: 'Onbevestigd',
                                                    count: facets.unverified,
                                                },
                                            ]}
                                            onChange={(verification) =>
                                                visit({
                                                    ...filters,
                                                    search: search.trim(),
                                                    verification,
                                                })
                                            }
                                        />
                                        <AdminDataTableFacetFilter
                                            title="Account"
                                            selected={filters.account}
                                            options={[
                                                {
                                                    value: 'active',
                                                    label: 'Actief',
                                                    count: facets.active,
                                                },
                                                {
                                                    value: 'inactive',
                                                    label: 'Inactief',
                                                    count: facets.inactive,
                                                },
                                            ]}
                                            onChange={(account) =>
                                                visit({
                                                    ...filters,
                                                    search: search.trim(),
                                                    account,
                                                })
                                            }
                                        />
                                        <AdminDataTableFacetFilter
                                            title="Activiteit"
                                            selected={filters.activity}
                                            options={[
                                                {
                                                    value: 'recent',
                                                    label: 'Laatste 7 dagen',
                                                    count: facets.recent,
                                                },
                                                {
                                                    value: 'never',
                                                    label: 'Geen sessie',
                                                    count: facets.never,
                                                },
                                            ]}
                                            onChange={(activity) =>
                                                visit({
                                                    ...filters,
                                                    search: search.trim(),
                                                    activity,
                                                })
                                            }
                                        />
                                        {hasFilters && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={resetFilters}
                                            >
                                                <X /> Filters wissen
                                            </Button>
                                        )}
                                    </div>
                                </div>
                                <p
                                    aria-live="polite"
                                    className="shrink-0 text-xs text-neutral-500"
                                >
                                    {users.total}{' '}
                                    {users.total === 1
                                        ? 'gebruiker'
                                        : 'gebruikers'}{' '}
                                    gevonden
                                </p>
                            </div>
                        </form>
                    }
                />
            </AdminResourcePage>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Gebruikers', href: index() },
    ],
};
