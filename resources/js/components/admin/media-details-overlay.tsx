import { Link, useForm } from '@inertiajs/react';
import {
    Check,
    Copy,
    ExternalLink,
    FileText,
    Image as ImageIcon,
    Save,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { update } from '@/actions/App/Http/Controllers/Admin/MediaAssetController';
import { MediaAltTextFields } from '@/components/admin/media-alt-text-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatFileSize } from '@/lib/file-formatting';
import type {
    EditableMediaAsset,
    MediaAssetRecord,
    MediaLocale,
} from '@/types/media';

type MediaAltTextForm = {
    alt_text: Record<string, string>;
};

export function MediaDetailsOverlay({
    details,
    error,
    loading,
    locales,
    mediaAsset,
    onOpenChange,
    onSaved,
    open,
}: {
    details: EditableMediaAsset | null;
    error: string | null;
    loading: boolean;
    locales: MediaLocale[];
    mediaAsset: MediaAssetRecord;
    onOpenChange: (open: boolean) => void;
    onSaved: (altText: Record<string, string>) => void;
    open: boolean;
}) {
    const isMobile = useIsMobile();
    const [isDirty, setIsDirty] = useState(false);

    function changeOpen(nextOpen: boolean): void {
        if (
            !nextOpen &&
            isDirty &&
            !window.confirm(
                'Je hebt niet-opgeslagen wijzigingen. Wil je dit venster toch sluiten?',
            )
        ) {
            return;
        }

        onOpenChange(nextOpen);
    }

    const content = (
        <MediaDetailsContent
            details={details}
            error={error}
            loading={loading}
            locales={locales}
            onDirtyChange={setIsDirty}
            onOpenChange={changeOpen}
            onSaved={onSaved}
        />
    );

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent
                data-test="media-details-overlay"
                data-mode={isMobile ? 'drawer' : 'dialog'}
                className={
                    isMobile
                        ? 'top-auto right-0 bottom-0 left-0 h-[94dvh] max-w-none translate-x-0 translate-y-0 gap-0 overflow-hidden rounded-t-2xl rounded-b-none p-0'
                        : 'h-[min(48rem,calc(100dvh-4rem))] max-h-[90dvh] gap-0 overflow-hidden p-0 sm:max-w-6xl'
                }
            >
                <DialogHeader className="border-b border-neutral-200 px-6 py-5 pr-12 dark:border-neutral-800">
                    <DialogTitle>Mediadetails</DialogTitle>
                    <DialogDescription className="line-clamp-2 break-all">
                        {mediaAsset.filename}
                    </DialogDescription>
                </DialogHeader>
                {content}
            </DialogContent>
        </Dialog>
    );
}

function MediaDetailsContent({
    details,
    error,
    loading,
    locales,
    onDirtyChange,
    onOpenChange,
    onSaved,
}: {
    details: EditableMediaAsset | null;
    error: string | null;
    loading: boolean;
    locales: MediaLocale[];
    onDirtyChange: (isDirty: boolean) => void;
    onOpenChange: (open: boolean) => void;
    onSaved: (altText: Record<string, string>) => void;
}) {
    if (loading && !details) {
        return (
            <div
                role="status"
                aria-live="polite"
                className="flex min-h-80 flex-1 items-center justify-center gap-2 text-sm text-neutral-500"
            >
                <Spinner /> Mediagegevens laden…
            </div>
        );
    }

    if (!details) {
        return (
            <div
                role="alert"
                className="grid min-h-64 flex-1 place-items-center p-6 text-center"
            >
                <div>
                    <p className="font-semibold text-neutral-950 dark:text-white">
                        Mediagegevens konden niet worden geladen
                    </p>
                    <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        {error ?? 'Sluit dit venster en probeer het opnieuw.'}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        className="mt-4"
                        onClick={() => onOpenChange(false)}
                    >
                        Sluiten
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <MediaDetailsInspector
            key={details.id}
            mediaAsset={details}
            locales={locales}
            onDirtyChange={onDirtyChange}
            onOpenChange={onOpenChange}
            onSaved={onSaved}
        />
    );
}

function MediaDetailsInspector({
    locales,
    mediaAsset,
    onDirtyChange,
    onOpenChange,
    onSaved,
}: {
    locales: MediaLocale[];
    mediaAsset: EditableMediaAsset;
    onDirtyChange: (isDirty: boolean) => void;
    onOpenChange: (open: boolean) => void;
    onSaved: (altText: Record<string, string>) => void;
}) {
    const formId = `media-details-form-${mediaAsset.id}`;
    const canEditAltText = mediaAsset.isImage && mediaAsset.capabilities.update;
    const form = useForm<MediaAltTextForm>(update(mediaAsset.id), {
        alt_text: mediaAsset.altText,
    });

    useEffect(() => {
        onDirtyChange(form.isDirty);
    }, [form.isDirty, onDirtyChange]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.submit({
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                onSaved(form.data.alt_text);
            },
        });
    }

    return (
        <div
            aria-busy={form.processing}
            className="grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)] lg:grid-cols-[minmax(0,1.2fr)_minmax(22rem,0.8fr)] lg:grid-rows-1"
        >
            <MediaPreview mediaAsset={mediaAsset} />

            <div className="flex min-h-0 flex-col border-t border-neutral-200 lg:border-t-0 lg:border-l dark:border-neutral-800">
                <div className="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                    <div className="grid content-start gap-6">
                        <MediaMetadata mediaAsset={mediaAsset} />

                        {canEditAltText && (
                            <form
                                id={formId}
                                className="grid gap-5 border-t border-neutral-200 pt-6 dark:border-neutral-800"
                                onSubmit={submit}
                                noValidate
                            >
                                <MediaAltTextFields
                                    altText={form.data.alt_text}
                                    columns="stacked"
                                    disabled={form.processing}
                                    errors={
                                        form.errors as Record<string, string>
                                    }
                                    locales={locales}
                                    onChange={(locale, value) => {
                                        form.setData('alt_text', {
                                            ...form.data.alt_text,
                                            [locale]: value,
                                        });
                                    }}
                                />
                            </form>
                        )}

                        <MediaUsage mediaAsset={mediaAsset} />
                    </div>
                </div>

                <div className="flex shrink-0 items-center justify-between gap-3 border-t border-neutral-200 bg-white px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6 dark:border-neutral-800 dark:bg-neutral-950">
                    <div
                        aria-live="polite"
                        className="text-sm text-emerald-700 dark:text-emerald-300"
                    >
                        {form.recentlySuccessful && (
                            <span className="flex items-center gap-1.5">
                                <Check className="size-4" /> Opgeslagen
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            data-test="media-details-close"
                            onClick={() => onOpenChange(false)}
                        >
                            Sluiten
                        </Button>
                        {canEditAltText && (
                            <Button
                                type="submit"
                                form={formId}
                                disabled={form.processing || !form.isDirty}
                                data-test="media-details-submit"
                            >
                                {form.processing ? <Spinner /> : <Save />}
                                {form.processing ? 'Opslaan…' : 'Opslaan'}
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function MediaPreview({ mediaAsset }: { mediaAsset: EditableMediaAsset }) {
    return (
        <div className="relative flex min-h-44 items-center justify-center overflow-hidden bg-neutral-100 p-4 lg:min-h-0 dark:bg-neutral-900">
            {mediaAsset.isImage ? (
                <img
                    src={mediaAsset.url}
                    alt=""
                    className="max-h-48 w-full object-contain lg:max-h-[38rem]"
                />
            ) : (
                <div className="flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                    <FileText className="size-14" />
                    <span className="text-sm font-medium">PDF-document</span>
                </div>
            )}

            <Button
                asChild
                size="sm"
                variant="secondary"
                className="absolute right-3 bottom-3 bg-white/95 shadow-sm dark:bg-neutral-950/95"
            >
                <a
                    href={mediaAsset.url}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Origineel openen <ExternalLink />
                </a>
            </Button>
        </div>
    );
}

function MediaMetadata({ mediaAsset }: { mediaAsset: EditableMediaAsset }) {
    const [copied, setCopied] = useState(false);

    async function copyUrl(): Promise<void> {
        try {
            await navigator.clipboard.writeText(mediaAsset.url);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    }

    return (
        <section aria-labelledby="media-details-metadata-heading">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h2
                        id="media-details-metadata-heading"
                        className="text-xs font-semibold tracking-wide text-neutral-500 uppercase"
                    >
                        Bestandsgegevens
                    </h2>
                    <p className="mt-2 flex items-center gap-2 font-semibold text-neutral-950 dark:text-white">
                        {mediaAsset.isImage ? (
                            <ImageIcon className="size-4" />
                        ) : (
                            <FileText className="size-4" />
                        )}
                        {friendlyMediaType(mediaAsset.mimeType)}
                    </p>
                </div>
                {mediaAsset.archivedAt && (
                    <Badge
                        variant="outline"
                        className="border-amber-300 text-amber-800 dark:border-amber-500/40 dark:text-amber-300"
                    >
                        Gearchiveerd
                    </Badge>
                )}
            </div>

            <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                <MetadataRow
                    label="Omvang"
                    value={formatFileSize(mediaAsset.sizeBytes)}
                />
                <MetadataRow
                    label="Toegevoegd"
                    value={formatMediaDate(mediaAsset.createdAt)}
                />
                <MetadataRow
                    label="Afmetingen"
                    value={
                        mediaAsset.width && mediaAsset.height
                            ? `${mediaAsset.width} × ${mediaAsset.height} px`
                            : 'Niet van toepassing'
                    }
                />
                <div>
                    <dt className="text-xs text-neutral-500">Bestands-URL</dt>
                    <dd className="mt-0.5">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            className="-ml-2 h-7 px-2"
                            onClick={() => void copyUrl()}
                        >
                            {copied ? <Check /> : <Copy />}
                            {copied ? 'Gekopieerd' : 'URL kopiëren'}
                        </Button>
                    </dd>
                </div>
            </dl>

            <details className="mt-5 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-800">
                <summary className="cursor-pointer text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    Technische gegevens
                </summary>
                <dl className="mt-3 grid gap-3 border-t border-neutral-200 pt-3 text-sm dark:border-neutral-800">
                    <MetadataRow
                        label="MIME-type"
                        value={mediaAsset.mimeType}
                    />
                    <MetadataRow
                        label="Opslagpad"
                        value={mediaAsset.path}
                        breakAnywhere
                        mono
                    />
                </dl>
            </details>
        </section>
    );
}

function MediaUsage({ mediaAsset }: { mediaAsset: EditableMediaAsset }) {
    const usageCount = mediaAsset.usage.length;

    return (
        <section
            aria-labelledby="media-details-usage-heading"
            className="border-t border-neutral-200 pt-6 dark:border-neutral-800"
        >
            <div className="flex items-center gap-2">
                <h2
                    id="media-details-usage-heading"
                    className="font-semibold text-neutral-950 dark:text-white"
                >
                    Gebruik
                </h2>
                <Badge variant="secondary">
                    {usageCount}{' '}
                    {usageCount === 1 ? 'koppeling' : 'koppelingen'}
                </Badge>
            </div>
            <p className="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                {usageCount === 0
                    ? 'Dit bestand is momenteel nergens aan gekoppeld.'
                    : 'Bekijk de plaatsen waar dit bestand wordt gebruikt. Verwijderen blijft geblokkeerd zolang deze koppelingen bestaan.'}
            </p>

            {usageCount > 0 && (
                <ul className="mt-4 divide-y rounded-lg border dark:divide-neutral-800">
                    {mediaAsset.usage.map((usage, index) => (
                        <li
                            key={`${usage.type}-${usage.label}-${index}`}
                            className="flex items-center justify-between gap-3 px-3 py-2.5"
                        >
                            <div className="min-w-0">
                                <p className="text-sm font-medium break-words text-neutral-950 dark:text-white">
                                    {usage.label}
                                </p>
                                <p className="text-xs text-neutral-500">
                                    {usage.type}
                                </p>
                            </div>
                            {usage.href && (
                                <Button asChild size="sm" variant="ghost">
                                    <Link
                                        href={usage.href}
                                        aria-label={`${usage.label} bewerken`}
                                    >
                                        Bewerken
                                    </Link>
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function MetadataRow({
    breakAnywhere = false,
    className,
    label,
    mono = false,
    value,
}: {
    breakAnywhere?: boolean;
    className?: string;
    label: string;
    mono?: boolean;
    value: string;
}) {
    const valueClassName = [
        'mt-0.5 text-neutral-800 dark:text-neutral-200',
        breakAnywhere ? '[overflow-wrap:anywhere]' : '',
        mono ? 'font-mono text-xs leading-5' : '',
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <div className={className}>
            <dt className="text-xs text-neutral-500">{label}</dt>
            <dd className={valueClassName}>{value}</dd>
        </div>
    );
}

function friendlyMediaType(mimeType: string): string {
    const labels: Record<string, string> = {
        'application/pdf': 'PDF-document',
        'image/gif': 'GIF-afbeelding',
        'image/jpeg': 'JPEG-afbeelding',
        'image/png': 'PNG-afbeelding',
        'image/webp': 'WebP-afbeelding',
    };

    return labels[mimeType] ?? mimeType;
}

function formatMediaDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'Onbekend';
    }

    return new Intl.DateTimeFormat('nl-NL', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}
