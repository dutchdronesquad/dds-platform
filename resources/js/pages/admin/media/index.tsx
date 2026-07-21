import { Head, router, useHttp } from '@inertiajs/react';
import {
    Archive,
    FileText,
    Image as ImageIcon,
    Images,
    Plus,
    Search,
    Unlink,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import {
    index as mediaIndex,
    show as showMedia,
} from '@/actions/App/Http/Controllers/Admin/MediaAssetController';
import { AdminDataTable } from '@/components/admin/admin-data-table';
import { AdminDataTableFacetFilter } from '@/components/admin/admin-data-table-facet-filter';
import { AdminListSummary } from '@/components/admin/admin-list-summary';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { MediaDetailsOverlay } from '@/components/admin/media-details-overlay';
import { MediaUploadOverlay } from '@/components/admin/media-upload-overlay';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import type {
    EditableMediaAsset,
    MediaAssetRecord,
    MediaIndexProps,
} from '@/types/media';
import { MediaCardGrid } from './card-grid';
import { mediaColumns } from './columns';

type MediaFiltersState = MediaIndexProps['filters'];
type MediaDetailsResponse = { data: EditableMediaAsset };

export default function MediaIndex({
    canCreate,
    filters,
    locales,
    mediaAssets,
    summary,
}: MediaIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);
    const [usage, setUsage] = useState(filters.usage);
    const [status, setStatus] = useState(filters.status);
    const [isFiltering, setIsFiltering] = useState(false);
    const [selectedMediaAsset, setSelectedMediaAsset] =
        useState<MediaAssetRecord | null>(null);
    const [mediaDetails, setMediaDetails] = useState<EditableMediaAsset | null>(
        null,
    );
    const [mediaDetailsError, setMediaDetailsError] = useState<string | null>(
        null,
    );
    const mediaDetailsRequest = useHttp<
        Record<string, never>,
        MediaDetailsResponse
    >({});
    const appliedFiltersRef = useRef(normalizeFilters(filters));
    const debounceTimeoutRef = useRef<ReturnType<typeof setTimeout>>(undefined);
    const lastSubmittedFiltersRef = useRef<MediaFiltersState>(undefined);
    const requestIdRef = useRef(0);

    const applyFilters = useCallback((nextFilters: MediaFiltersState): void => {
        const normalizedFilters = normalizeFilters(nextFilters);

        if (filtersAreEqual(normalizedFilters, appliedFiltersRef.current)) {
            return;
        }

        const requestId = ++requestIdRef.current;
        lastSubmittedFiltersRef.current = normalizedFilters;

        router.visit(mediaRoute(normalizedFilters), {
            only: ['mediaAssets', 'filters'],
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
        setCategory(filters.category);
        setUsage(filters.usage);
        setStatus(filters.status);
    }, [filters]);

    useEffect(() => {
        clearTimeout(debounceTimeoutRef.current);

        if (search.trim() === appliedFiltersRef.current.search) {
            return;
        }

        debounceTimeoutRef.current = setTimeout(() => {
            applyFilters({ search, category, usage, status });
        }, 400);

        return () => clearTimeout(debounceTimeoutRef.current);
    }, [applyFilters, category, search, status, usage]);

    const normalizedSearch = search.trim();
    const isUpdating =
        isFiltering ||
        normalizedSearch !== filters.search ||
        category !== filters.category ||
        usage !== filters.usage ||
        status !== filters.status;
    const hasFilters =
        normalizedSearch !== '' ||
        category !== 'all' ||
        usage !== 'all' ||
        status !== 'active';

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        clearTimeout(debounceTimeoutRef.current);
        applyFilters({ search, category, usage, status });
    }

    function clearFilters(): void {
        setSearch('');
        setCategory('all');
        setUsage('all');
        setStatus('active');
        applyFilters(defaultFilters());
    }

    function clearSearch(): void {
        clearTimeout(debounceTimeoutRef.current);
        setSearch('');
        applyFilters({ search: '', category, usage, status });
    }

    function handleSearchKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Escape' && search !== '') {
            event.preventDefault();
            clearSearch();
        }
    }

    function updateFilter<Key extends keyof Omit<MediaFiltersState, 'search'>>(
        key: Key,
        value: MediaFiltersState[Key],
    ): void {
        clearTimeout(debounceTimeoutRef.current);

        const nextFilters = {
            search,
            category,
            usage,
            status,
            [key]: value,
        };

        if (key === 'category') {
            setCategory(value as MediaFiltersState['category']);
        } else if (key === 'usage') {
            setUsage(value as MediaFiltersState['usage']);
        } else {
            setStatus(value as MediaFiltersState['status']);
        }

        applyFilters(nextFilters);
    }

    function openMediaDetails(mediaAsset: MediaAssetRecord): void {
        mediaDetailsRequest.cancel();
        setSelectedMediaAsset(mediaAsset);
        setMediaDetails(null);
        setMediaDetailsError(null);

        void mediaDetailsRequest.get(showMedia.url(mediaAsset.id), {
            onSuccess: (response) => setMediaDetails(response.data),
            onError: () =>
                setMediaDetailsError(
                    'De actuele bestandsgegevens konden niet worden opgehaald.',
                ),
        });
    }

    function changeMediaDetailsOpen(open: boolean): void {
        if (open) {
            return;
        }

        mediaDetailsRequest.cancel();
        setSelectedMediaAsset(null);
        setMediaDetails(null);
        setMediaDetailsError(null);
    }

    function handleMediaDetailsSaved(altText: Record<string, string>): void {
        setMediaDetails((current) =>
            current ? { ...current, altText } : current,
        );
        setSelectedMediaAsset((current) =>
            current ? { ...current, altText } : current,
        );
    }

    return (
        <>
            <Head title="Media beheren" />
            <AdminResourcePage
                eyebrow="Contentbeheer"
                title="Mediabibliotheek"
                description="Upload één keer, hergebruik overal en controleer eerst de bekende koppelingen voordat een bestand wordt verwijderd."
                actions={
                    canCreate ? (
                        <MediaUploadOverlay
                            locales={locales}
                            trigger={
                                <Button
                                    type="button"
                                    data-test="media-upload-trigger"
                                >
                                    <Plus /> Bestand uploaden
                                </Button>
                            }
                        />
                    ) : undefined
                }
            >
                <AdminListSummary
                    label="Mediasamenvatting"
                    metrics={[
                        { label: 'Actief', value: summary.total, icon: Images },
                        {
                            label: 'Afbeeldingen',
                            value: summary.images,
                            icon: ImageIcon,
                            tone: 'blue',
                        },
                        {
                            label: 'Documenten',
                            value: summary.documents,
                            icon: FileText,
                        },
                        {
                            label: 'Ongebruikt',
                            value: summary.unused,
                            icon: Unlink,
                            tone: 'amber',
                        },
                        {
                            label: 'Gearchiveerd',
                            value: summary.archived,
                            icon: Archive,
                        },
                    ]}
                />

                <AdminDataTable
                    caption="Overzicht van mediabestanden"
                    columns={mediaColumns(openMediaDetails)}
                    emptyTitle="Geen media gevonden"
                    emptyDescription={
                        hasFilters
                            ? 'Pas de filters aan om andere bestanden te zien.'
                            : 'Upload de eerste afbeelding of pdf om de bibliotheek te vullen.'
                    }
                    emptyAction={
                        hasFilters ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={clearFilters}
                            >
                                <X /> Filters wissen
                            </Button>
                        ) : canCreate ? (
                            <MediaUploadOverlay
                                locales={locales}
                                trigger={
                                    <Button
                                        type="button"
                                        data-test="media-upload-empty-trigger"
                                    >
                                        <Plus /> Eerste bestand uploaden
                                    </Button>
                                }
                            />
                        ) : undefined
                    }
                    mobileContent={
                        <MediaCardGrid
                            mediaAssets={mediaAssets.data}
                            onOpenDetails={openMediaDetails}
                        />
                    }
                    pagination={mediaAssets}
                    resourceLabel="media-items"
                    tableClassName="min-w-[56rem]"
                    toolbar={
                        <form
                            action={mediaIndex.url()}
                            method="get"
                            onSubmit={submit}
                            aria-busy={isUpdating}
                            className="flex flex-col gap-3"
                        >
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                                    <div className="relative w-full sm:w-72 lg:w-80 xl:w-96">
                                        <label
                                            htmlFor="media-search"
                                            className="sr-only"
                                        >
                                            Media zoeken
                                        </label>
                                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                                        <Input
                                            id="media-search"
                                            name="search"
                                            value={search}
                                            onChange={(event) =>
                                                setSearch(event.target.value)
                                            }
                                            onKeyDown={handleSearchKeyDown}
                                            maxLength={100}
                                            placeholder="Zoek media…"
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
                                        <MediaFacetFilter
                                            title="Type"
                                            value={category}
                                            defaultValue="all"
                                            options={[
                                                {
                                                    value: 'image',
                                                    label: 'Afbeeldingen',
                                                },
                                                {
                                                    value: 'document',
                                                    label: 'Pdf’s',
                                                },
                                            ]}
                                            onChange={(value) =>
                                                updateFilter('category', value)
                                            }
                                        />
                                        <MediaFacetFilter
                                            title="Gebruik"
                                            value={usage}
                                            defaultValue="all"
                                            options={[
                                                {
                                                    value: 'used',
                                                    label: 'In gebruik',
                                                },
                                                {
                                                    value: 'unused',
                                                    label: 'Ongebruikt',
                                                },
                                            ]}
                                            onChange={(value) =>
                                                updateFilter('usage', value)
                                            }
                                        />
                                        <MediaFacetFilter
                                            title="Status"
                                            value={status}
                                            defaultValue="active"
                                            options={[
                                                {
                                                    value: 'archived',
                                                    label: 'Gearchiveerd',
                                                },
                                                {
                                                    value: 'all',
                                                    label: 'Alle statussen',
                                                },
                                            ]}
                                            onChange={(value) =>
                                                updateFilter('status', value)
                                            }
                                        />
                                        {hasFilters && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                onClick={clearFilters}
                                                size="sm"
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
                                        : `${mediaAssets.total} ${mediaAssets.total === 1 ? 'media-item' : 'media-items'} gevonden`}
                                </p>
                            </div>
                        </form>
                    }
                />
            </AdminResourcePage>

            {selectedMediaAsset && (
                <MediaDetailsOverlay
                    details={mediaDetails}
                    error={mediaDetailsError}
                    loading={mediaDetailsRequest.processing}
                    locales={locales}
                    mediaAsset={selectedMediaAsset}
                    onOpenChange={changeMediaDetailsOpen}
                    onSaved={handleMediaDetailsSaved}
                    open
                />
            )}
        </>
    );
}

function MediaFacetFilter<Value extends string>({
    defaultValue,
    onChange,
    options,
    title,
    value,
}: {
    defaultValue: Value;
    onChange: (value: Value) => void;
    options: Array<{ label: string; value: Value }>;
    title: string;
    value: Value;
}) {
    const selected = value === defaultValue ? [] : [value];

    return (
        <AdminDataTableFacetFilter
            closeOnSelect
            title={title}
            selected={selected}
            options={options}
            onChange={(values) => {
                const nextValue = values.find(
                    (candidate) => candidate !== value,
                );

                onChange((nextValue ?? defaultValue) as Value);
            }}
        />
    );
}

function normalizeFilters(filters: MediaFiltersState): MediaFiltersState {
    return { ...filters, search: filters.search.trim() };
}

function filtersAreEqual(
    left: MediaFiltersState,
    right: MediaFiltersState,
): boolean {
    return (
        left.search === right.search &&
        left.category === right.category &&
        left.usage === right.usage &&
        left.status === right.status
    );
}

function defaultFilters(): MediaFiltersState {
    return {
        search: '',
        category: 'all',
        usage: 'all',
        status: 'active',
    };
}

function mediaRoute(filters: MediaFiltersState) {
    return mediaIndex({
        query: {
            search: filters.search.trim() || undefined,
            category: filters.category === 'all' ? undefined : filters.category,
            usage: filters.usage === 'all' ? undefined : filters.usage,
            status: filters.status === 'active' ? undefined : filters.status,
        },
    });
}

MediaIndex.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Media', href: mediaIndex() },
    ],
};
