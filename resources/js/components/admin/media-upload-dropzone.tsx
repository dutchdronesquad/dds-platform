import {
    FileText,
    Image as ImageIcon,
    RefreshCw,
    Trash2,
    UploadCloud,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { DragEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { formatFileSize } from '@/lib/file-formatting';
import { cn } from '@/lib/utils';

export const MEDIA_UPLOAD_ACCEPT =
    'image/jpeg,image/png,image/webp,image/gif,application/pdf';

export function MediaUploadDropzone({
    disabled,
    error,
    file,
    onFilesSelected,
    onRemove,
    progress,
}: {
    disabled: boolean;
    error?: string;
    file: File | null;
    onFilesSelected: (files: File[]) => void;
    onRemove: () => void;
    progress: number | null;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const isImage = file?.type.startsWith('image/') ?? false;

    function openFilePicker() {
        if (!disabled) {
            inputRef.current?.click();
        }
    }

    function handleDrop(event: DragEvent<HTMLDivElement>) {
        event.preventDefault();
        setIsDragging(false);

        if (!disabled) {
            onFilesSelected(Array.from(event.dataTransfer.files));
        }
    }

    function resetInput() {
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }

    function removeFile() {
        resetInput();
        onRemove();
    }

    const fileInput = (
        <input
            ref={inputRef}
            id="file"
            name="file"
            type="file"
            accept={MEDIA_UPLOAD_ACCEPT}
            disabled={disabled}
            data-test="media-upload-input"
            className="hidden"
            tabIndex={-1}
            onClick={(event) => {
                event.currentTarget.value = '';
            }}
            onChange={(event) => {
                onFilesSelected(Array.from(event.currentTarget.files ?? []));
            }}
        />
    );

    return (
        <div className="grid gap-3">
            {fileInput}

            {file ? (
                <div
                    data-test="media-upload-preview"
                    className={cn(
                        'overflow-hidden rounded-2xl border bg-neutral-50 dark:bg-neutral-900/60',
                        error
                            ? 'border-destructive/50'
                            : 'border-neutral-200 dark:border-neutral-800',
                    )}
                >
                    <div className="grid gap-5 p-4 sm:grid-cols-[11rem_minmax(0,1fr)] sm:p-5">
                        <SelectedFilePreview file={file} />

                        <div className="flex min-w-0 flex-col justify-center">
                            <div className="flex items-center gap-2 text-xs font-semibold tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                                {isImage ? (
                                    <ImageIcon
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                ) : (
                                    <FileText
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                )}
                                <span data-test="media-upload-file-type">
                                    {mediaFileTypeLabel(file)}
                                </span>
                            </div>
                            <p className="mt-2 truncate text-base font-semibold text-neutral-950 dark:text-white">
                                {file.name}
                            </p>
                            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-neutral-600 dark:text-neutral-400">
                                <span data-test="media-upload-file-size">
                                    {formatFileSize(file.size)}
                                </span>
                            </div>

                            {progress === null ? (
                                <div className="mt-5 flex flex-col gap-2 sm:flex-row">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={disabled}
                                        data-test="media-upload-replace"
                                        aria-invalid={Boolean(error)}
                                        aria-describedby={
                                            error
                                                ? 'file-help file-error'
                                                : 'file-help'
                                        }
                                        onClick={() => {
                                            resetInput();
                                            openFilePicker();
                                        }}
                                    >
                                        <RefreshCw /> Ander bestand
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        disabled={disabled}
                                        data-test="media-upload-remove"
                                        className="text-neutral-600 hover:text-destructive dark:text-neutral-300"
                                        onClick={removeFile}
                                    >
                                        <Trash2 /> Verwijderen
                                    </Button>
                                </div>
                            ) : (
                                <UploadProgress percentage={progress} />
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <div
                    data-test="media-upload-dropzone"
                    onDragEnter={(event) => {
                        event.preventDefault();

                        if (!disabled) {
                            setIsDragging(true);
                        }
                    }}
                    onDragOver={(event) => event.preventDefault()}
                    onDragLeave={(event) => {
                        if (
                            !event.currentTarget.contains(
                                event.relatedTarget as Node | null,
                            )
                        ) {
                            setIsDragging(false);
                        }
                    }}
                    onDrop={handleDrop}
                    className={cn(
                        'flex min-h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed px-5 py-8 text-center transition-colors',
                        isDragging
                            ? 'border-signal-500 bg-signal-50 dark:bg-signal-500/10'
                            : error
                              ? 'border-destructive/55 bg-destructive/[0.025]'
                              : 'border-neutral-300 bg-neutral-50/70 hover:border-signal-400 hover:bg-signal-50/50 dark:border-neutral-700 dark:bg-neutral-900/40 dark:hover:border-signal-500/70 dark:hover:bg-signal-500/5',
                    )}
                >
                    <span className="flex size-12 items-center justify-center rounded-2xl bg-white text-signal-700 shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-950 dark:text-signal-300 dark:ring-neutral-800">
                        <UploadCloud aria-hidden="true" className="size-6" />
                    </span>
                    <p className="mt-4 font-semibold text-neutral-950 dark:text-white">
                        Sleep één bestand hierheen
                    </p>
                    <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        of kies het bestand op je apparaat
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        className="mt-5 bg-white dark:bg-neutral-950"
                        aria-invalid={Boolean(error)}
                        aria-describedby={
                            error ? 'file-help file-error' : 'file-help'
                        }
                        onClick={openFilePicker}
                    >
                        Bestand kiezen
                    </Button>
                </div>
            )}

            <p
                id="file-help"
                className="text-xs leading-5 text-neutral-500 dark:text-neutral-400"
            >
                JPEG, PNG, WebP, GIF of PDF · maximaal 20 MB · één bestand per
                upload
            </p>
            <div data-test={error ? 'media-upload-error' : undefined}>
                <InputError id="file-error" message={error} />
            </div>
        </div>
    );
}

function SelectedFilePreview({ file }: { file: File }) {
    const isImage = file.type.startsWith('image/');
    const [previewUrl] = useState<string | null>(() =>
        isImage ? URL.createObjectURL(file) : null,
    );

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    return (
        <div className="flex aspect-video min-h-36 items-center justify-center overflow-hidden rounded-xl bg-neutral-200 text-neutral-500 ring-1 ring-neutral-950/5 sm:aspect-square dark:bg-neutral-800 dark:text-neutral-400 dark:ring-white/10">
            {previewUrl ? (
                <img
                    src={previewUrl}
                    alt=""
                    data-test="media-upload-preview-image"
                    className="size-full object-contain"
                />
            ) : (
                <FileText aria-hidden="true" className="size-14" />
            )}
        </div>
    );
}

function UploadProgress({ percentage }: { percentage: number }) {
    const normalizedPercentage = Math.min(Math.max(percentage, 0), 100);
    const status =
        normalizedPercentage >= 100
            ? 'Bestand verwerken…'
            : `Uploaden: ${normalizedPercentage}%`;

    return (
        <div
            data-test="media-upload-progress"
            className="mt-5 grid gap-2"
            role="status"
            aria-live="polite"
        >
            <progress
                max={100}
                value={normalizedPercentage}
                aria-label="Uploadvoortgang"
                className="h-2 w-full overflow-hidden rounded-full accent-signal-500"
            />
            <p className="text-xs font-medium text-neutral-600 dark:text-neutral-300">
                {status}
            </p>
        </div>
    );
}

function mediaFileTypeLabel(file: File): string {
    const labels: Record<string, string> = {
        'application/pdf': 'PDF-document',
        'image/gif': 'GIF-afbeelding',
        'image/jpeg': 'JPEG-afbeelding',
        'image/png': 'PNG-afbeelding',
        'image/webp': 'WebP-afbeelding',
    };

    return labels[file.type] ?? (file.type || 'Bestand');
}
