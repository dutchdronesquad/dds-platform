import { useHttp } from '@inertiajs/react';
import { Check, FileImage, Images, Search, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import MediaAssetPickerController from '@/actions/App/Http/Controllers/Admin/MediaAssetPickerController';
import MediaAssetQuickUploadController from '@/actions/App/Http/Controllers/Admin/MediaAssetQuickUploadController';
import { MediaUploadDropzone } from '@/components/admin/media-upload-dropzone';
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

type QuickUploadResponse = {
    data: MediaPickerAsset;
};

type QuickUploadForm = {
    file: File | null;
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
    const [tab, setTab] = useState<'library' | 'upload'>('library');
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<MediaPickerAsset[]>([]);
    const request = useHttp<Record<string, never>, PickerResponse>({});
    const upload = useHttp<QuickUploadForm, QuickUploadResponse>({
        file: null,
    });
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

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

    function handleSearchChange(value: string): void {
        setSearch(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => loadResults(value), 300);
    }

    function changeOpen(nextOpen: boolean): void {
        setOpen(nextOpen);
        setTab('library');

        if (nextOpen) {
            loadResults(search);
        } else {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            request.cancel();
            upload.cancel();
            upload.reset();
            upload.clearErrors();
        }
    }

    function selectUploadedFiles(files: File[]): void {
        const [file] = files;

        upload.setData('file', file ?? null);
        upload.clearErrors('file');
    }

    function submitUpload(): void {
        if (!upload.data.file) {
            upload.setError('file', 'Kies een bestand om toe te voegen.');

            return;
        }

        void upload.post(MediaAssetQuickUploadController.url(), {
            onSuccess: (response) => {
                onChange(response.data);
                setOpen(false);
                upload.reset();
            },
        });
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
                <DialogContent className="max-h-[88vh] w-[95vw] overflow-hidden p-0 sm:max-w-3xl lg:max-w-5xl xl:max-w-6xl">
                    <DialogHeader className="border-b px-5 py-4 pr-12">
                        <DialogTitle>Afbeelding kiezen</DialogTitle>
                        <DialogDescription>
                            {tab === 'library'
                                ? 'Zoek op de oorspronkelijke bestandsnaam of alternatieve tekst. Gearchiveerde media zijn niet selecteerbaar.'
                                : 'Upload een nieuwe afbeelding. Deze wordt direct geselecteerd en blijft ook beschikbaar in de mediabibliotheek.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex gap-1 border-b px-5 pt-3">
                        <button
                            type="button"
                            onClick={() => setTab('library')}
                            className={cn(
                                'rounded-t-md px-3 py-2 text-sm font-medium transition-colors',
                                tab === 'library'
                                    ? 'text-signal-800 dark:text-signal-200 border-b-2 border-signal-500'
                                    : 'text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200',
                            )}
                        >
                            Bibliotheek
                        </button>
                        <button
                            type="button"
                            onClick={() => setTab('upload')}
                            className={cn(
                                'rounded-t-md px-3 py-2 text-sm font-medium transition-colors',
                                tab === 'upload'
                                    ? 'text-signal-800 dark:text-signal-200 border-b-2 border-signal-500'
                                    : 'text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200',
                            )}
                        >
                            Uploaden
                        </button>
                    </div>

                    {tab === 'upload' ? (
                        <div className="grid gap-4 overflow-y-auto px-5 py-5">
                            <MediaUploadDropzone
                                disabled={upload.processing}
                                error={upload.errors.file}
                                file={upload.data.file}
                                onFilesSelected={selectUploadedFiles}
                                onRemove={() => upload.setData('file', null)}
                                progress={
                                    upload.processing
                                        ? (upload.progress?.percentage ?? null)
                                        : null
                                }
                            />
                            <Button
                                type="button"
                                disabled={
                                    upload.processing || !upload.data.file
                                }
                                onClick={submitUpload}
                                className="justify-self-start"
                            >
                                {upload.processing ? <Spinner /> : <Upload />}
                                {upload.processing
                                    ? 'Uploaden…'
                                    : 'Uploaden en selecteren'}
                            </Button>
                        </div>
                    ) : (
                        <>
                            <div className="relative px-5">
                                <Search className="pointer-events-none absolute top-1/2 left-8 size-4 -translate-y-1/2 text-neutral-500" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        handleSearchChange(event.target.value)
                                    }
                                    maxLength={100}
                                    placeholder="Zoek media…"
                                    className="pr-9 pl-9"
                                    autoFocus
                                />
                                {request.processing && (
                                    <Spinner className="absolute top-1/2 right-8 size-4 -translate-y-1/2 text-neutral-400" />
                                )}
                            </div>

                            <div className="h-112 overflow-y-auto px-5 pb-5">
                                {results.length === 0 && request.processing ? (
                                    <div className="flex h-112 items-center justify-center gap-2 text-sm text-neutral-500">
                                        <Spinner /> Media laden…
                                    </div>
                                ) : results.length > 0 ? (
                                    <div
                                        className={cn(
                                            'grid grid-cols-2 gap-4 transition-opacity duration-150 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
                                            request.processing &&
                                                'pointer-events-none opacity-50',
                                        )}
                                    >
                                        {results.map((mediaAsset) => (
                                            <button
                                                key={mediaAsset.id}
                                                type="button"
                                                onClick={() => {
                                                    onChange(mediaAsset);
                                                    setOpen(false);
                                                }}
                                                className="group overflow-hidden rounded-lg border bg-white text-left shadow-xs transition-all duration-200 ease-out hover:-translate-y-0.5 hover:border-signal-400 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:bg-neutral-950 dark:hover:border-signal-500"
                                            >
                                                <MediaPreview
                                                    mediaAsset={mediaAsset}
                                                />
                                                <span className="group-hover:text-signal-800 dark:group-hover:text-signal-200 block truncate px-3 py-2 text-xs font-medium text-neutral-800 dark:text-neutral-200">
                                                    {mediaAsset.filename}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex h-112 flex-col items-center justify-center gap-2 text-center">
                                        <FileImage className="size-8 text-neutral-400" />
                                        <p className="font-medium text-neutral-950 dark:text-white">
                                            Geen afbeeldingen gevonden
                                        </p>
                                        <p className="max-w-sm text-sm text-neutral-500">
                                            Upload eerst een afbeelding in de
                                            mediabibliotheek of probeer een
                                            andere zoekterm.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}

function MediaPreview({ mediaAsset }: { mediaAsset: MediaPickerAsset }) {
    return (
        <span className="relative block aspect-video w-full overflow-hidden bg-neutral-100 dark:bg-neutral-900">
            {mediaAsset.isImage ? (
                <img
                    src={mediaAsset.url}
                    alt=""
                    className="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-110"
                />
            ) : (
                <span className="flex size-full items-center justify-center text-neutral-400">
                    <FileImage className="size-8" />
                </span>
            )}
            <span className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all duration-200 ease-out group-hover:bg-black/25 group-hover:opacity-100">
                <span className="flex size-9 scale-75 items-center justify-center rounded-full bg-white text-signal-700 shadow-md transition-transform duration-200 ease-out group-hover:scale-100">
                    <Check className="size-4" />
                </span>
            </span>
        </span>
    );
}
