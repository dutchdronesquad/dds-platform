import { FileText, ImageOff } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatFileSize } from '@/lib/file-formatting';
import type { MediaAssetRecord } from '@/types/media';
import { MediaActions } from './columns';

export function MediaCardGrid({
    mediaAssets,
    onOpenDetails,
}: {
    mediaAssets: MediaAssetRecord[];
    onOpenDetails: (mediaAsset: MediaAssetRecord) => void;
}) {
    return (
        <div className="grid grid-cols-1 gap-px bg-neutral-200 sm:grid-cols-2 dark:bg-neutral-800">
            {mediaAssets.map((mediaAsset) => (
                <article
                    key={mediaAsset.id}
                    className="min-w-0 bg-white p-3 dark:bg-neutral-950"
                >
                    <div className="relative overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-900">
                        <button
                            type="button"
                            data-test={`media-details-card-preview-${mediaAsset.id}`}
                            aria-label={`Details van ${mediaAsset.filename} bekijken`}
                            onClick={() => onOpenDetails(mediaAsset)}
                            className="group block w-full cursor-pointer focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                        >
                            {mediaAsset.isImage ? (
                                <img
                                    src={mediaAsset.url}
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    className="aspect-video w-full object-cover transition duration-200 group-hover:scale-[1.02] group-focus-visible:scale-[1.02]"
                                />
                            ) : (
                                <span className="flex aspect-video items-center justify-center text-neutral-400 transition group-hover:text-signal-600 group-focus-visible:text-signal-600 dark:group-hover:text-signal-300 dark:group-focus-visible:text-signal-300">
                                    <FileText className="size-10" />
                                </span>
                            )}
                        </button>
                        <div className="absolute top-2 right-2">
                            <MediaActions
                                mediaAsset={mediaAsset}
                                onOpenDetails={onOpenDetails}
                            />
                        </div>
                    </div>

                    <div className="grid gap-3 px-1 pt-3 pb-1">
                        <div className="min-w-0">
                            <button
                                type="button"
                                data-test={`media-details-card-trigger-${mediaAsset.id}`}
                                onClick={() => onOpenDetails(mediaAsset)}
                                className="block max-w-full truncate text-left text-sm font-semibold text-neutral-950 underline-offset-4 hover:text-signal-700 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:text-white dark:hover:text-signal-300"
                            >
                                {mediaAsset.filename}
                            </button>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {formatFileSize(mediaAsset.sizeBytes)}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-1.5">
                            <Badge variant="outline">
                                {mediaAsset.usageCount === 0
                                    ? 'Ongebruikt'
                                    : `${mediaAsset.usageCount} ${mediaAsset.usageCount === 1 ? 'koppeling' : 'koppelingen'}`}
                            </Badge>
                            {mediaAsset.isImage && !hasAltText(mediaAsset) && (
                                <Badge
                                    variant="outline"
                                    className="border-amber-300 text-amber-800 dark:border-amber-500/40 dark:text-amber-300"
                                >
                                    <ImageOff className="size-3" />
                                    Alt-tekst ontbreekt
                                </Badge>
                            )}
                            {mediaAsset.archivedAt && (
                                <Badge
                                    variant="outline"
                                    className="border-amber-300 text-amber-800 dark:border-amber-500/40 dark:text-amber-300"
                                >
                                    Gearchiveerd
                                </Badge>
                            )}
                        </div>
                    </div>
                </article>
            ))}
        </div>
    );
}

function hasAltText(mediaAsset: MediaAssetRecord): boolean {
    return Object.values(mediaAsset.altText).some(
        (altText) => altText.trim() !== '',
    );
}
