import { useHttp } from '@inertiajs/react';
import { FileImage, Images, Search, X } from 'lucide-react';
import { useState } from 'react';
import MediaAssetPickerController from '@/actions/App/Http/Controllers/Admin/MediaAssetPickerController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { MediaPickerAsset } from '@/types/media';

type PickerResponse = {
    data: MediaPickerAsset[];
};

export function MediaAssetPicker({
    describedBy,
    id,
    invalid = false,
    name,
    onChange,
    selected,
}: {
    describedBy?: string;
    id: string;
    invalid?: boolean;
    name: string;
    onChange: (mediaAsset: MediaPickerAsset | null) => void;
    selected: MediaPickerAsset | null;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<MediaPickerAsset[]>([]);
    const request = useHttp<Record<string, never>, PickerResponse>({});

    function loadResults(query: string): void {
        void request.get(
            MediaAssetPickerController.url({
                query: { search: query.trim() || undefined },
            }),
            {
                onSuccess: (response) => setResults(response.data),
            },
        );
    }

    function changeOpen(nextOpen: boolean): void {
        setOpen(nextOpen);

        if (nextOpen) {
            loadResults(search);
        } else {
            request.cancel();
        }
    }

    return (
        <div id={id} aria-describedby={describedBy} className="grid gap-3">
            <input type="hidden" name={name} value={selected?.id ?? ''} />

            {selected ? (
                <div
                    className={cn(
                        'grid gap-3 rounded-lg border bg-neutral-50 p-3 sm:grid-cols-[8rem_minmax(0,1fr)_auto] sm:items-center dark:bg-neutral-900/60',
                        invalid && 'border-destructive',
                    )}
                >
                    <MediaPreview mediaAsset={selected} />
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-neutral-950 dark:text-white">
                            {selected.filename}
                        </p>
                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {selected.width && selected.height
                                ? `${selected.width} × ${selected.height} px`
                                : selected.mimeType}
                        </p>
                        {selected.archivedAt && (
                            <p className="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                Gearchiveerd — blijft gekoppeld totdat je een
                                andere afbeelding kiest.
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2 sm:flex-col">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => changeOpen(true)}
                        >
                            Wijzigen
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => onChange(null)}
                        >
                            <X />
                            Wissen
                        </Button>
                    </div>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => changeOpen(true)}
                    className={cn(
                        'hover:text-signal-800 dark:hover:text-signal-200 flex min-h-28 w-full items-center justify-center gap-3 rounded-lg border border-dashed bg-neutral-50 px-4 text-sm font-medium text-neutral-700 transition-colors hover:border-signal-400 hover:bg-signal-50/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:bg-neutral-900/50 dark:text-neutral-300 dark:hover:border-signal-500 dark:hover:bg-signal-500/5',
                        invalid && 'border-destructive',
                    )}
                >
                    <Images className="size-5" />
                    Kies uit de mediabibliotheek
                </button>
            )}

            <Dialog open={open} onOpenChange={changeOpen}>
                <DialogContent className="max-h-[85vh] overflow-hidden p-0 sm:max-w-4xl">
                    <DialogHeader className="border-b px-5 py-4 pr-12">
                        <DialogTitle>Afbeelding kiezen</DialogTitle>
                        <DialogDescription>
                            Zoek op de oorspronkelijke bestandsnaam of
                            alternatieve tekst. Gearchiveerde media zijn niet
                            selecteerbaar.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        className="flex gap-2 px-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            loadResults(search);
                        }}
                    >
                        <div className="relative min-w-0 flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                maxLength={100}
                                placeholder="Zoek media…"
                                className="pl-9"
                                autoFocus
                            />
                        </div>
                        <Button type="submit" variant="outline">
                            Zoeken
                        </Button>
                    </form>

                    <div className="min-h-64 overflow-y-auto px-5 pb-5">
                        {request.processing ? (
                            <div className="flex min-h-64 items-center justify-center gap-2 text-sm text-neutral-500">
                                <Spinner /> Media laden…
                            </div>
                        ) : results.length > 0 ? (
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                {results.map((mediaAsset) => (
                                    <button
                                        key={mediaAsset.id}
                                        type="button"
                                        onClick={() => {
                                            onChange(mediaAsset);
                                            setOpen(false);
                                        }}
                                        className="group overflow-hidden rounded-lg border bg-white text-left shadow-xs transition hover:border-signal-400 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:bg-neutral-950 dark:hover:border-signal-500"
                                    >
                                        <MediaPreview mediaAsset={mediaAsset} />
                                        <span className="group-hover:text-signal-800 dark:group-hover:text-signal-200 block truncate px-3 py-2 text-xs font-medium text-neutral-800 dark:text-neutral-200">
                                            {mediaAsset.filename}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="flex min-h-64 flex-col items-center justify-center gap-2 text-center">
                                <FileImage className="size-8 text-neutral-400" />
                                <p className="font-medium text-neutral-950 dark:text-white">
                                    Geen afbeeldingen gevonden
                                </p>
                                <p className="max-w-sm text-sm text-neutral-500">
                                    Upload eerst een afbeelding in de
                                    mediabibliotheek of probeer een andere
                                    zoekterm.
                                </p>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function MediaPreview({ mediaAsset }: { mediaAsset: MediaPickerAsset }) {
    return mediaAsset.isImage ? (
        <img
            src={mediaAsset.url}
            alt=""
            className="aspect-video h-full w-full bg-neutral-100 object-cover dark:bg-neutral-900"
        />
    ) : (
        <span className="flex aspect-video h-full w-full items-center justify-center bg-neutral-100 text-neutral-400 dark:bg-neutral-900">
            <FileImage className="size-8" />
        </span>
    );
}
