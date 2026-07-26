import { Head, Link, router } from '@inertiajs/react';
import { MapPin, Plus, Search, X } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import {
    create,
    index,
} from '@/actions/App/Http/Controllers/Admin/LocationController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { locationColumns } from './columns';
import type { LocationIndexProps } from './types';

export default function LocationsIndex({
    canCreate,
    filters,
    locations,
}: LocationIndexProps) {
    const hasFilters = filters.search !== '';

    return (
        <>
            <Head title="Locaties beheren" />

            <AdminResourcePage
                eyebrow="Locatiebeheer"
                title="Locaties"
                description="Beheer de vlieg- en eventlocaties van Dutch Drone Squad, inclusief adres, faciliteiten en kaartgegevens."
                actions={
                    canCreate && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Nieuwe locatie
                            </Link>
                        </Button>
                    )
                }
            >
                <AdminDataTable
                    caption="Overzicht van locaties"
                    columns={locationColumns}
                    emptyTitle="Geen locaties gevonden"
                    emptyDescription={
                        hasFilters
                            ? 'Pas de zoekopdracht aan.'
                            : 'Maak de eerste locatie aan zodat events er naartoe kunnen verwijzen.'
                    }
                    pagination={locations}
                    resourceLabel="locaties"
                    tableClassName="min-w-0 sm:min-w-[40rem]"
                    toolbar={
                        <LocationFilterBar
                            filters={filters}
                            resultCount={locations.total}
                        />
                    }
                />
            </AdminResourcePage>
        </>
    );
}

function LocationFilterBar({
    filters,
    resultCount,
}: {
    filters: LocationIndexProps['filters'];
    resultCount: number;
}) {
    const [search, setSearch] = useState(filters.search);
    const [previousFiltersSearch, setPreviousFiltersSearch] = useState(
        filters.search,
    );
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    if (filters.search !== previousFiltersSearch) {
        setPreviousFiltersSearch(filters.search);
        setSearch(filters.search);
    }

    const applyFilters = useCallback((nextSearch: string) => {
        router.get(
            index().url,
            { search: nextSearch || undefined },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }, []);

    function handleSearchChange(value: string): void {
        setSearch(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => applyFilters(value), 300);
    }

    function handleSubmit(formEvent: FormEvent<HTMLFormElement>): void {
        formEvent.preventDefault();

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        applyFilters(search);
    }

    function handleKeyDown(
        keyboardEvent: KeyboardEvent<HTMLInputElement>,
    ): void {
        if (keyboardEvent.key === 'Escape' && search !== '') {
            keyboardEvent.preventDefault();
            handleSearchChange('');
        }
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form
                onSubmit={handleSubmit}
                className="flex w-full max-w-sm items-center gap-2"
            >
                <div className="relative w-full">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                    <Input
                        value={search}
                        onChange={(event) =>
                            handleSearchChange(event.target.value)
                        }
                        onKeyDown={handleKeyDown}
                        maxLength={100}
                        placeholder="Zoek op naam of plaats…"
                        className="pl-9"
                    />
                    {search !== '' && (
                        <button
                            type="button"
                            onClick={() => handleSearchChange('')}
                            aria-label="Zoekopdracht wissen"
                            className="absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-1 text-neutral-400 hover:text-neutral-700 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:hover:text-neutral-200"
                        >
                            <X className="size-4" />
                        </button>
                    )}
                </div>
            </form>

            <p className="flex items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                <MapPin className="size-3.5" />
                {resultCount === 1 ? '1 locatie' : `${resultCount} locaties`}
            </p>
        </div>
    );
}
