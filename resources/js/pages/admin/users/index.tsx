import { Head, router } from '@inertiajs/react';
import {
    BadgeCheck,
    Search,
    ShieldCheck,
    UserCheck,
    Users,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import { index } from '@/actions/App/Http/Controllers/Admin/UserController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import { AdminDataTableFacetFilter } from '@/components/admin/admin-data-table-facet-filter';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
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
    const [isFiltering, setIsFiltering] = useState(false);
    const appliedFiltersRef = useRef(normalizeFilters(filters));
    const debounceTimeoutRef = useRef<ReturnType<typeof setTimeout>>(undefined);
    const lastSubmittedFiltersRef = useRef<UserFilters>(undefined);
    const requestIdRef = useRef(0);

    useEffect(() => {
        const normalizedFilters = normalizeFilters(filters);
        appliedFiltersRef.current = normalizedFilters;

        if (
            lastSubmittedFiltersRef.current &&
            filtersAreEqual(lastSubmittedFiltersRef.current, normalizedFilters)
        ) {
            lastSubmittedFiltersRef.current = undefined;

            return;
        }

        setSearch(normalizedFilters.search);
    }, [filters]);

    const applyFilters = useCallback((nextFilters: UserFilters): void => {
        const normalizedFilters = normalizeFilters(nextFilters);

        if (filtersAreEqual(normalizedFilters, appliedFiltersRef.current)) {
            return;
        }

        const requestId = ++requestIdRef.current;
        lastSubmittedFiltersRef.current = normalizedFilters;

        router.visit(usersRoute(normalizedFilters), {
            only: ['users', 'filters', 'facets'],
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsFiltering(true),
            onFinish: () => {
                if (requestId === requestIdRef.current) {
                    setIsFiltering(false);
                }
            },
        });
    }, []);

    useEffect(() => {
        clearTimeout(debounceTimeoutRef.current);

        if (search.trim() === appliedFiltersRef.current.search) {
            return;
        }

        debounceTimeoutRef.current = setTimeout(() => {
            applyFilters({
                ...appliedFiltersRef.current,
                search,
            });
        }, 400);

        return () => clearTimeout(debounceTimeoutRef.current);
    }, [applyFilters, search]);

    const normalizedSearch = search.trim();
    const isUpdating = isFiltering || normalizedSearch !== filters.search;

    const hasFilters =
        normalizedSearch !== '' ||
        filters.role.length > 0 ||
        filters.verification.length > 0 ||
        filters.account.length > 0 ||
        filters.activity.length > 0;

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        clearTimeout(debounceTimeoutRef.current);
        applyFilters({ ...appliedFiltersRef.current, search });
    }

    function clearSearch(): void {
        clearTimeout(debounceTimeoutRef.current);
        setSearch('');
        applyFilters({ ...appliedFiltersRef.current, search: '' });
    }

    function resetFilters(): void {
        clearTimeout(debounceTimeoutRef.current);
        setSearch('');
        applyFilters({
            search: '',
            role: [],
            verification: [],
            account: [],
            activity: [],
        });
    }

    function handleSearchKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Escape' && search !== '') {
            event.preventDefault();
            clearSearch();
        }
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
                        <form
                            action={index.url()}
                            method="get"
                            onSubmit={submit}
                            aria-busy={isUpdating}
                            className="flex flex-col gap-3"
                        >
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
                                            name="search"
                                            value={search}
                                            onChange={(event) =>
                                                setSearch(event.target.value)
                                            }
                                            onKeyDown={handleSearchKeyDown}
                                            maxLength={100}
                                            placeholder="Zoek naam of e-mail…"
                                            autoComplete="off"
                                            className="h-9 pr-18 pl-9"
                                        />
                                        {isUpdating && (
                                            <Spinner className="absolute top-1/2 right-10 -translate-y-1/2 text-neutral-500" />
                                        )}
                                        {search !== '' && (
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                onClick={clearSearch}
                                                aria-label="Zoekopdracht wissen"
                                                className="absolute top-1/2 right-1 size-7 -translate-y-1/2 text-neutral-500 hover:text-neutral-950 dark:hover:text-white"
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        )}
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
                                            onChange={(role) => {
                                                clearTimeout(
                                                    debounceTimeoutRef.current,
                                                );
                                                applyFilters({
                                                    ...appliedFiltersRef.current,
                                                    search,
                                                    role,
                                                });
                                            }}
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
                                            onChange={(verification) => {
                                                clearTimeout(
                                                    debounceTimeoutRef.current,
                                                );
                                                applyFilters({
                                                    ...appliedFiltersRef.current,
                                                    search,
                                                    verification,
                                                });
                                            }}
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
                                            onChange={(account) => {
                                                clearTimeout(
                                                    debounceTimeoutRef.current,
                                                );
                                                applyFilters({
                                                    ...appliedFiltersRef.current,
                                                    search,
                                                    account,
                                                });
                                            }}
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
                                            onChange={(activity) => {
                                                clearTimeout(
                                                    debounceTimeoutRef.current,
                                                );
                                                applyFilters({
                                                    ...appliedFiltersRef.current,
                                                    search,
                                                    activity,
                                                });
                                            }}
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
                                    {isUpdating
                                        ? 'Resultaten worden bijgewerkt…'
                                        : `${users.total} ${users.total === 1 ? 'gebruiker' : 'gebruikers'} gevonden`}
                                </p>
                            </div>
                        </form>
                    }
                />
            </AdminResourcePage>
        </>
    );
}

function normalizeFilters(filters: UserFilters): UserFilters {
    return {
        search: filters.search.trim(),
        role: [...filters.role],
        verification: [...filters.verification],
        account: [...filters.account],
        activity: [...filters.activity],
    };
}

function filtersAreEqual(first: UserFilters, second: UserFilters): boolean {
    return (
        first.search === second.search &&
        valuesAreEqual(first.role, second.role) &&
        valuesAreEqual(first.verification, second.verification) &&
        valuesAreEqual(first.account, second.account) &&
        valuesAreEqual(first.activity, second.activity)
    );
}

function valuesAreEqual(first: string[], second: string[]): boolean {
    return (
        first.length === second.length &&
        first.every((value, index) => value === second[index])
    );
}

function usersRoute(filters: UserFilters) {
    return index({
        query: {
            search: filters.search !== '' ? filters.search : undefined,
            role: filters.role.length > 0 ? filters.role : undefined,
            verification:
                filters.verification.length > 0
                    ? filters.verification
                    : undefined,
            account: filters.account.length > 0 ? filters.account : undefined,
            activity:
                filters.activity.length > 0 ? filters.activity : undefined,
        },
    });
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Gebruikers', href: index() },
    ],
};
