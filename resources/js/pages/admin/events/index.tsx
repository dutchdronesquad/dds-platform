import { Head, Link, router } from '@inertiajs/react';
import {
    Ban,
    CalendarDays,
    FilePenLine,
    Plus,
    Search,
    Send,
    Tags,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import {
    create,
    index,
} from '@/actions/App/Http/Controllers/Admin/EventController';
import { index as seasonsIndex } from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import { AdminDataTableFacetFilter } from '@/components/admin/admin-data-table-facet-filter';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { eventColumns } from './columns';
import type {
    AdminEventStatus,
    AdminEventType,
    EventIndexProps,
} from './types';

type EventFiltersState = EventIndexProps['filters'];

export default function EventsIndex({
    canCreate,
    canManageSeasons,
    events,
    filters,
    statusOptions,
    summary,
    typeOptions,
}: EventIndexProps) {
    const hasFilters =
        filters.search !== '' ||
        filterValues(filters.status).length > 0 ||
        filterValues(filters.type).length > 0;

    return (
        <>
            <Head title="Events beheren" />

            <AdminResourcePage
                eyebrow="Eventbeheer"
                title="Events"
                description="Plan events, bewaak inschrijvingen en bepaal precies wat zichtbaar is op de publieke kalender."
                actions={
                    <>
                        {canManageSeasons && (
                            <Button asChild variant="outline">
                                <Link href={seasonsIndex()}>
                                    <Tags />
                                    Seizoenen
                                </Link>
                            </Button>
                        )}
                        {canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus />
                                    Nieuw event
                                </Link>
                            </Button>
                        )}
                    </>
                }
            >
                <AdminListSummary
                    label="Eventsamenvatting"
                    metrics={[
                        {
                            label: 'Alle events',
                            value: summary.total,
                            icon: CalendarDays,
                        },
                        {
                            label: 'Concept',
                            value: summary.drafts,
                            icon: FilePenLine,
                            tone: 'amber',
                        },
                        {
                            label: 'Gepubliceerd',
                            value: summary.published,
                            icon: Send,
                            tone: 'blue',
                        },
                        {
                            label: 'Geannuleerd',
                            value: summary.cancelled,
                            icon: Ban,
                            tone: 'red',
                        },
                    ]}
                />

                <AdminDataTable
                    caption="Overzicht van events"
                    columns={eventColumns}
                    emptyTitle="Geen events gevonden"
                    emptyDescription={
                        hasFilters
                            ? 'Pas de zoekopdracht of filters aan.'
                            : 'Maak het eerste event aan om de kalender te vullen.'
                    }
                    pagination={events}
                    resourceLabel="events"
                    tableClassName="min-w-0 md:min-w-[58rem] lg:min-w-[68rem]"
                    toolbar={
                        <EventFilterBar
                            filters={filters}
                            resultCount={events.total}
                            statusOptions={statusOptions}
                            typeOptions={typeOptions}
                        />
                    }
                />
            </AdminResourcePage>
        </>
    );
}

function EventFilterBar({
    filters,
    resultCount,
    statusOptions,
    typeOptions,
}: Pick<EventIndexProps, 'filters' | 'statusOptions' | 'typeOptions'> & {
    resultCount: number;
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(() => filterValues(filters.status));
    const [type, setType] = useState(() => filterValues(filters.type));
    const [isFiltering, setIsFiltering] = useState(false);
    const appliedFiltersRef = useRef(normalizeFilters(filters));
    const debounceTimeoutRef = useRef<ReturnType<typeof setTimeout>>(undefined);
    const lastSubmittedFiltersRef = useRef<EventFiltersState>(undefined);
    const requestIdRef = useRef(0);
    const statusRef = useRef(filterValues(filters.status));
    const typeRef = useRef(filterValues(filters.type));

    const applyFilters = useCallback((nextFilters: EventFiltersState): void => {
        const normalizedFilters = normalizeFilters(nextFilters);

        if (filtersAreEqual(normalizedFilters, appliedFiltersRef.current)) {
            return;
        }

        const requestId = ++requestIdRef.current;
        lastSubmittedFiltersRef.current = normalizedFilters;

        router.visit(eventsRoute(normalizedFilters), {
            only: ['events', 'filters'],
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
        const normalizedFilters = normalizeFilters(filters);
        appliedFiltersRef.current = normalizedFilters;

        if (
            lastSubmittedFiltersRef.current &&
            filtersAreEqual(lastSubmittedFiltersRef.current, normalizedFilters)
        ) {
            lastSubmittedFiltersRef.current = undefined;

            return;
        }

        setSearch(filters.search);
        setStatus(normalizedFilters.status);
        setType(normalizedFilters.type);
        statusRef.current = normalizedFilters.status;
        typeRef.current = normalizedFilters.type;
    }, [filters]);

    useEffect(() => {
        clearTimeout(debounceTimeoutRef.current);

        if (search.trim() === appliedFiltersRef.current.search) {
            return;
        }

        debounceTimeoutRef.current = setTimeout(() => {
            applyFilters({
                search,
                status: statusRef.current,
                type: typeRef.current,
            });
        }, 400);

        return () => clearTimeout(debounceTimeoutRef.current);
    }, [applyFilters, search]);

    const normalizedSearch = search.trim();
    const isUpdating =
        isFiltering ||
        normalizedSearch !== filters.search ||
        !valuesAreEqual(status, filters.status) ||
        !valuesAreEqual(type, filters.type);
    const hasFilters =
        normalizedSearch !== '' || status.length > 0 || type.length > 0;

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        clearTimeout(debounceTimeoutRef.current);
        applyFilters({ search, status, type });
    }

    function changeStatus(values: string[]): void {
        const nextStatus = values.filter(isEventStatus);

        if (valuesAreEqual(nextStatus, status)) {
            return;
        }

        clearTimeout(debounceTimeoutRef.current);
        statusRef.current = nextStatus;
        setStatus(nextStatus);
        applyFilters({ search, status: nextStatus, type });
    }

    function changeType(values: string[]): void {
        const nextType = values.filter(isEventType);

        if (valuesAreEqual(nextType, type)) {
            return;
        }

        clearTimeout(debounceTimeoutRef.current);
        typeRef.current = nextType;
        setType(nextType);
        applyFilters({ search, status, type: nextType });
    }

    function clearSearch(): void {
        clearTimeout(debounceTimeoutRef.current);
        setSearch('');
        applyFilters({ search: '', status, type });
    }

    function resetFilters(): void {
        clearTimeout(debounceTimeoutRef.current);
        statusRef.current = [];
        typeRef.current = [];
        setSearch('');
        setStatus([]);
        setType([]);
        applyFilters({ search: '', status: [], type: [] });
    }

    function handleSearchKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Escape' && search !== '') {
            event.preventDefault();
            clearSearch();
        }
    }

    return (
        <form
            action={index.url()}
            method="get"
            onSubmit={submit}
            aria-busy={isUpdating}
            className="flex flex-col gap-3"
        >
            {status.map((value) => (
                <input
                    key={value}
                    type="hidden"
                    name="status[]"
                    value={value}
                />
            ))}
            {type.map((value) => (
                <input key={value} type="hidden" name="type[]" value={value} />
            ))}

            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                    <div className="relative w-full sm:w-80 lg:w-96 xl:w-md">
                        <label htmlFor="event-search" className="sr-only">
                            Events zoeken
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                        <Input
                            id="event-search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={handleSearchKeyDown}
                            maxLength={100}
                            placeholder="Zoek events…"
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
                            selected={status}
                            options={statusOptions}
                            onChange={changeStatus}
                        />
                        <AdminDataTableFacetFilter
                            title="Type"
                            selected={type}
                            options={typeOptions}
                            onChange={changeType}
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
                        : `${resultCount} ${resultCount === 1 ? 'event' : 'events'} gevonden`}
                </p>
            </div>
        </form>
    );
}

function normalizeFilters(filters: EventFiltersState): EventFiltersState {
    return {
        search: filters.search.trim(),
        status: filterValues(filters.status),
        type: filterValues(filters.type),
    };
}

function filtersAreEqual(
    first: EventFiltersState,
    second: EventFiltersState,
): boolean {
    return (
        first.search === second.search &&
        valuesAreEqual(first.status, second.status) &&
        valuesAreEqual(first.type, second.type)
    );
}

function eventsRoute(filters: EventFiltersState) {
    return index({
        query: {
            search: filters.search !== '' ? filters.search : undefined,
            status: filters.status.length > 0 ? filters.status : undefined,
            type: filters.type.length > 0 ? filters.type : undefined,
        },
    });
}

function isEventStatus(value: string): value is AdminEventStatus {
    return ['cancelled', 'draft', 'published'].includes(value);
}

function isEventType(value: string): value is AdminEventType {
    return ['demo', 'other', 'race', 'training', 'workshop'].includes(value);
}

function filterValues<TValue extends string>(
    values: TValue[] | TValue,
): TValue[] {
    if (Array.isArray(values)) {
        return [...values];
    }

    return values === 'all' ? [] : [values];
}

function valuesAreEqual(
    first: string[] | string,
    second: string[] | string,
): boolean {
    const firstValues = filterValues(first);
    const secondValues = filterValues(second);

    return (
        firstValues.length === secondValues.length &&
        firstValues.every((value, index) => value === secondValues[index])
    );
}

EventsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Beheer',
            href: dashboard(),
        },
        {
            title: 'Events',
            href: index(),
        },
    ],
};
