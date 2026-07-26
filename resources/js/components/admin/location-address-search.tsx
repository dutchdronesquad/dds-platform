import { useHttp } from '@inertiajs/react';
import { Loader2, MapPin, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import LocationAddressLookupController from '@/actions/App/Http/Controllers/Admin/LocationAddressLookupController';
import LocationAddressSuggestController from '@/actions/App/Http/Controllers/Admin/LocationAddressSuggestController';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type AddressSuggestion = {
    id: string;
    label: string;
};

export type ResolvedAddress = {
    city: string;
    houseNumber: string;
    latitude: string;
    longitude: string;
    postalCode: string;
    street: string;
};

const MIN_QUERY_LENGTH = 4;
const DEBOUNCE_MS = 300;

export function LocationAddressSearch({
    id = 'address_search',
    onSelect,
}: {
    id?: string;
    onSelect: (address: ResolvedAddress) => void;
}) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<AddressSuggestion[]>([]);
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [status, setStatus] = useState<{
        text: string;
        type: 'error' | 'success';
    } | null>(null);
    const suggest = useHttp<
        Record<string, never>,
        { data: AddressSuggestion[] }
    >({});
    const lookup = useHttp<Record<string, never>, { data: ResolvedAddress }>(
        {},
    );
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent): void {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);

        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function handleQueryChange(value: string): void {
        setQuery(value);
        setStatus(null);
        setActiveIndex(-1);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        const trimmed = value.trim();

        if (trimmed.length < MIN_QUERY_LENGTH) {
            setSuggestions([]);
            setOpen(false);
            suggest.cancel();

            return;
        }

        debounceRef.current = setTimeout(() => {
            void suggest.get(
                LocationAddressSuggestController.url({
                    query: { q: trimmed },
                }),
                {
                    onSuccess: (response) => {
                        setSuggestions(response.data);
                        setOpen(response.data.length > 0);
                    },
                },
            );
        }, DEBOUNCE_MS);
    }

    function selectSuggestion(suggestion: AddressSuggestion): void {
        setQuery(suggestion.label);
        setOpen(false);
        setSuggestions([]);

        void lookup.get(
            LocationAddressLookupController.url({
                query: { id: suggestion.id },
            }),
            {
                onSuccess: (response) => {
                    onSelect(response.data);
                    setStatus({
                        type: 'success',
                        text: 'Adres en coördinaten ingevuld.',
                    });
                },
                onError: () => {
                    setStatus({
                        type: 'error',
                        text: 'Kon de details van dit adres niet ophalen.',
                    });
                },
            },
        );
    }

    function handleKeyDown(event: React.KeyboardEvent<HTMLInputElement>): void {
        if (!open || suggestions.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) =>
                Math.min(index + 1, suggestions.length - 1),
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            selectSuggestion(suggestions[activeIndex]);
        } else if (event.key === 'Escape') {
            setOpen(false);
        }
    }

    const listboxId = `${id}-listbox`;
    const activeOptionId =
        activeIndex >= 0 ? `${id}-option-${activeIndex}` : undefined;

    return (
        <div ref={containerRef} className="relative grid gap-2">
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                <Input
                    id={id}
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listboxId}
                    aria-autocomplete="list"
                    aria-activedescendant={activeOptionId}
                    value={query}
                    onChange={(event) => handleQueryChange(event.target.value)}
                    onKeyDown={handleKeyDown}
                    onFocus={() => suggestions.length > 0 && setOpen(true)}
                    placeholder="Zoek een adres, bijv. Terborchlaan 200, Alkmaar"
                    className="pl-9"
                    autoComplete="off"
                />
                {(suggest.processing || lookup.processing) && (
                    <Loader2 className="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-neutral-400" />
                )}
            </div>
            {open && (
                <ul
                    id={listboxId}
                    role="listbox"
                    className="absolute top-full z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-neutral-200 bg-white shadow-md dark:border-neutral-800 dark:bg-neutral-950"
                >
                    {suggestions.map((suggestion, index) => (
                        <li key={suggestion.id} role="presentation">
                            <button
                                id={`${id}-option-${index}`}
                                type="button"
                                role="option"
                                aria-selected={index === activeIndex}
                                onClick={() => selectSuggestion(suggestion)}
                                onMouseEnter={() => setActiveIndex(index)}
                                className={cn(
                                    'flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-neutral-700 dark:text-neutral-300',
                                    index === activeIndex
                                        ? 'bg-signal-50 dark:bg-signal-500/10'
                                        : 'hover:bg-neutral-50 dark:hover:bg-neutral-900',
                                )}
                            >
                                <MapPin className="size-3.5 shrink-0 text-neutral-400" />
                                {suggestion.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {status && (
                <p
                    role="status"
                    className={cn(
                        'text-xs',
                        status.type === 'success'
                            ? 'text-emerald-700 dark:text-emerald-400'
                            : 'text-amber-700 dark:text-amber-400',
                    )}
                >
                    {status.text}
                </p>
            )}
        </div>
    );
}
