import { Head, router } from '@inertiajs/react';
import {
    Activity,
    CheckCircle2,
    RefreshCw,
    Route as RouteIcon,
    Search,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import { AdminDataTableFacetFilter } from '@/components/admin/admin-data-table-facet-filter';
import {
    AdminResourcePage,
    AdminSummaryCard,
} from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index as redirectsIndex } from '@/routes/redirects';
import { redirectColumns } from './columns';
import type { RedirectRecord } from './columns';

type RedirectStatus = 'active' | 'all' | 'inactive';

type RedirectFilters = {
    search: string;
    status: RedirectStatus;
};

type Props = {
    facets: {
        active: number;
        inactive: number;
    };
    filters: RedirectFilters;
    redirects: ServerPagination<RedirectRecord>;
    summary: {
        active: number;
        hits: number;
        total: number;
    };
};

export default function RedirectsIndex({
    facets,
    filters,
    redirects,
    summary,
}: Props) {
    const hasFilters = filters.search !== '' || filters.status !== 'all';

    return (
        <>
            <Head title="Redirects" />

            <AdminResourcePage
                eyebrow="SEO-beheer"
                title="Legacy redirects"
                description="Controleer welke oude WordPress-paden actief doorverwijzen, waar ze uitkomen en hoe vaak ze zijn gebruikt."
                actions={<RefreshRedirectsButton />}
            >
                <section
                    aria-label="Redirectsamenvatting"
                    className="grid gap-4 sm:grid-cols-3"
                >
                    <AdminSummaryCard
                        label="Totaal"
                        value={summary.total}
                        icon={RouteIcon}
                    />
                    <AdminSummaryCard
                        label="Actief"
                        value={summary.active}
                        icon={CheckCircle2}
                    />
                    <AdminSummaryCard
                        label="Doorkliks"
                        value={summary.hits}
                        icon={Activity}
                    />
                </section>

                <AdminDataTable
                    caption="Overzicht van legacy redirects"
                    columns={redirectColumns}
                    emptyTitle="Geen redirects gevonden"
                    emptyDescription={
                        hasFilters
                            ? 'Pas de zoekopdracht of het statusfilter aan.'
                            : 'Er zijn nog geen legacy redirects vastgelegd.'
                    }
                    pagination={redirects}
                    resourceLabel="redirects"
                    toolbar={
                        <RedirectFilterBar
                            facets={facets}
                            filters={filters}
                            resultCount={redirects.total}
                        />
                    }
                />
            </AdminResourcePage>
        </>
    );
}

function RefreshRedirectsButton() {
    const [isRefreshing, setIsRefreshing] = useState(false);

    function refresh(): void {
        router.reload({
            fresh: true,
            only: ['redirects', 'facets', 'summary'],
            onStart: () => setIsRefreshing(true),
            onFinish: () => setIsRefreshing(false),
        });
    }

    return (
        <Button
            type="button"
            variant="outline"
            onClick={refresh}
            disabled={isRefreshing}
        >
            <RefreshCw
                className={isRefreshing ? 'size-4 animate-spin' : 'size-4'}
            />
            {isRefreshing ? 'Vernieuwen…' : 'Vernieuwen'}
        </Button>
    );
}

function RedirectFilterBar({
    facets,
    filters,
    resultCount,
}: {
    facets: Props['facets'];
    filters: RedirectFilters;
    resultCount: number;
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState<RedirectStatus>(filters.status);
    const [isFiltering, setIsFiltering] = useState(false);
    const appliedFiltersRef = useRef(filters);
    const debounceTimeoutRef = useRef<ReturnType<typeof setTimeout>>(undefined);
    const lastSubmittedFiltersRef = useRef<RedirectFilters>(undefined);
    const requestIdRef = useRef(0);
    const statusRef = useRef<RedirectStatus>(filters.status);

    const applyFilters = useCallback((nextFilters: RedirectFilters): void => {
        const normalizedFilters = normalizeFilters(nextFilters);

        if (filtersAreEqual(normalizedFilters, appliedFiltersRef.current)) {
            return;
        }

        const requestId = ++requestIdRef.current;
        lastSubmittedFiltersRef.current = normalizedFilters;

        router.visit(redirectsRoute(normalizedFilters), {
            only: ['redirects', 'facets', 'filters'],
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
        appliedFiltersRef.current = filters;

        if (
            lastSubmittedFiltersRef.current &&
            filtersAreEqual(lastSubmittedFiltersRef.current, filters)
        ) {
            lastSubmittedFiltersRef.current = undefined;

            return;
        }

        setSearch(filters.search);
        setStatus(filters.status);
        statusRef.current = filters.status;
    }, [filters]);

    useEffect(() => {
        clearTimeout(debounceTimeoutRef.current);

        if (search.trim() === appliedFiltersRef.current.search) {
            return;
        }

        debounceTimeoutRef.current = setTimeout(() => {
            applyFilters({ search, status: statusRef.current });
        }, 400);

        return () => clearTimeout(debounceTimeoutRef.current);
    }, [applyFilters, search]);

    const normalizedSearch = search.trim();
    const isUpdating =
        isFiltering ||
        normalizedSearch !== filters.search ||
        status !== filters.status;
    const hasFilters = normalizedSearch !== '' || status !== 'all';
    const statusOptions: Array<{
        count: number;
        label: string;
        value: Exclude<RedirectStatus, 'all'>;
    }> = [
        { value: 'active', label: 'Actief', count: facets.active },
        { value: 'inactive', label: 'Inactief', count: facets.inactive },
    ];

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        clearTimeout(debounceTimeoutRef.current);
        applyFilters({ search, status });
    }

    function changeStatus(value: string | null): void {
        const nextStatus = value === null ? 'all' : value;

        if (!isRedirectStatus(nextStatus) || nextStatus === status) {
            return;
        }

        clearTimeout(debounceTimeoutRef.current);
        statusRef.current = nextStatus;
        setStatus(nextStatus);
        applyFilters({ search, status: nextStatus });
    }

    function clearSearch(): void {
        clearTimeout(debounceTimeoutRef.current);
        setSearch('');
        applyFilters({ search: '', status });
    }

    function resetFilters(): void {
        clearTimeout(debounceTimeoutRef.current);
        statusRef.current = 'all';
        setSearch('');
        setStatus('all');
        applyFilters({ search: '', status: 'all' });
    }

    function handleSearchKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Escape' && search !== '') {
            event.preventDefault();
            clearSearch();
        }
    }

    return (
        <form
            action={redirectsIndex.url()}
            method="get"
            onSubmit={submit}
            aria-busy={isUpdating}
            className="flex flex-col gap-3"
        >
            {status !== 'all' && (
                <input type="hidden" name="status" value={status} />
            )}

            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                    <div className="relative w-full sm:w-96">
                        <label htmlFor="redirect-search" className="sr-only">
                            Redirects zoeken
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                        <Input
                            id="redirect-search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={handleSearchKeyDown}
                            maxLength={100}
                            placeholder="Zoek redirects…"
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
                            title="Status"
                            selected={status === 'all' ? null : status}
                            options={statusOptions}
                            onChange={changeStatus}
                        />

                        {hasFilters && (
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={resetFilters}
                                className="text-neutral-600 dark:text-neutral-400"
                            >
                                <X className="size-3.5" />
                                Filters wissen
                            </Button>
                        )}
                    </div>
                </div>

                <p
                    aria-live="polite"
                    className="shrink-0 text-xs text-neutral-500 dark:text-neutral-400"
                >
                    {isUpdating
                        ? 'Resultaten worden bijgewerkt…'
                        : `${resultCount} ${resultCount === 1 ? 'redirect' : 'redirects'} gevonden`}
                </p>
            </div>
        </form>
    );
}

function normalizeFilters(filters: RedirectFilters): RedirectFilters {
    return {
        search: filters.search.trim(),
        status: filters.status,
    };
}

function filtersAreEqual(
    first: RedirectFilters,
    second: RedirectFilters,
): boolean {
    return first.search === second.search && first.status === second.status;
}

function redirectsRoute(filters: RedirectFilters) {
    return redirectsIndex({
        query: {
            search: filters.search !== '' ? filters.search : undefined,
            status: filters.status !== 'all' ? filters.status : undefined,
        },
    });
}

function isRedirectStatus(value: string): value is RedirectStatus {
    return ['active', 'all', 'inactive'].includes(value);
}

RedirectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Beheer',
            href: dashboard(),
        },
        {
            title: 'Redirects',
            href: redirectsIndex(),
        },
    ],
};
