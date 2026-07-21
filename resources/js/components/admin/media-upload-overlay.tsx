import { FileImage, FileUp, Save } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { AdminFormErrorSummary } from '@/components/admin/admin-form';
import { MediaAltTextFields } from '@/components/admin/media-alt-text-fields';
import { MediaUploadDropzone } from '@/components/admin/media-upload-dropzone';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useMediaUploadForm } from '@/hooks/use-media-upload-form';
import { useIsMobile } from '@/hooks/use-mobile';
import type { MediaLocale } from '@/types/media';

export function MediaUploadOverlay({
    locales,
    trigger,
}: {
    locales: MediaLocale[];
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const { form, isImage, progress, removeFile, selectFiles, submit } =
        useMediaUploadForm({
            locales,
            returnToLibrary: true,
            onSuccess: () => setOpen(false),
        });

    function changeOpen(nextOpen: boolean) {
        if (
            !nextOpen &&
            !form.processing &&
            form.isDirty &&
            !window.confirm(
                'Je hebt een bestand geselecteerd. Wil je de upload sluiten?',
            )
        ) {
            return;
        }

        if (!nextOpen && form.processing) {
            return;
        }

        if (!nextOpen) {
            form.resetAndClearErrors();
        }

        setOpen(nextOpen);
    }

    const uploadForm = (
        <form
            onSubmit={submit}
            noValidate
            className="flex min-h-0 flex-1 flex-col overflow-hidden"
        >
            <div className="min-h-0 flex-1 overflow-y-auto">
                <AdminFormErrorSummary
                    errors={form.errors as Record<string, string>}
                />

                <div className="grid gap-6 p-4 sm:p-6">
                    <section aria-labelledby="media-upload-file-heading">
                        <div className="mb-4 flex items-start gap-3">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-signal-50 text-signal-700 ring-1 ring-signal-100 dark:bg-signal-500/10 dark:text-signal-300 dark:ring-signal-500/15">
                                <FileUp
                                    aria-hidden="true"
                                    className="size-4.5"
                                />
                            </span>
                            <div>
                                <h2
                                    id="media-upload-file-heading"
                                    className="font-semibold text-neutral-950 dark:text-white"
                                >
                                    Kies het bestand
                                </h2>
                                <p className="mt-1 text-sm leading-5 text-neutral-600 dark:text-neutral-400">
                                    Controleer de preview voordat je het item
                                    toevoegt.
                                </p>
                            </div>
                        </div>

                        <MediaUploadDropzone
                            disabled={form.processing}
                            error={form.errors.file}
                            file={form.data.file}
                            onFilesSelected={selectFiles}
                            onRemove={removeFile}
                            progress={form.processing ? progress : null}
                        />
                    </section>

                    {isImage && (
                        <section
                            aria-labelledby="media-upload-alt-heading"
                            className="border-t border-neutral-200 pt-6 dark:border-neutral-800"
                        >
                            <div className="mb-4 flex items-start gap-3">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-signal-50 text-signal-700 ring-1 ring-signal-100 dark:bg-signal-500/10 dark:text-signal-300 dark:ring-signal-500/15">
                                    <FileImage
                                        aria-hidden="true"
                                        className="size-4.5"
                                    />
                                </span>
                                <div>
                                    <h2
                                        id="media-upload-alt-heading"
                                        className="font-semibold text-neutral-950 dark:text-white"
                                    >
                                        Maak de afbeelding begrijpelijk
                                    </h2>
                                    <p className="mt-1 text-sm leading-5 text-neutral-600 dark:text-neutral-400">
                                        Voeg per taal een korte beschrijving toe
                                        als de afbeelding betekenis draagt.
                                    </p>
                                </div>
                            </div>

                            <MediaAltTextFields
                                altText={form.data.alt_text}
                                disabled={form.processing}
                                errors={form.errors as Record<string, string>}
                                locales={locales}
                                onChange={(locale, value) => {
                                    form.setData('alt_text', {
                                        ...form.data.alt_text,
                                        [locale]: value,
                                    });
                                }}
                            />
                        </section>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-2 border-t border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
                <Button
                    type="button"
                    variant="outline"
                    disabled={form.processing}
                    onClick={() => changeOpen(false)}
                >
                    Annuleren
                </Button>
                <Button
                    type="submit"
                    disabled={form.processing || !form.data.file}
                    data-test="media-upload-submit"
                >
                    {form.processing ? <FileUp /> : <Save />}
                    {form.processing ? 'Uploaden…' : 'Toevoegen'}
                </Button>
            </div>
        </form>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={changeOpen}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent
                    side="bottom"
                    data-test="media-upload-overlay"
                    data-mode="drawer"
                    className="max-h-[92dvh] gap-0 overflow-hidden rounded-t-2xl p-0"
                >
                    <SheetHeader className="border-b border-neutral-200 px-4 py-4 pr-12 text-left dark:border-neutral-800">
                        <SheetTitle>Media toevoegen</SheetTitle>
                        <SheetDescription>
                            Upload één afbeelding of pdf naar de bibliotheek.
                        </SheetDescription>
                    </SheetHeader>
                    {uploadForm}
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent
                data-test="media-upload-overlay"
                data-mode="dialog"
                className="max-h-[88vh] gap-0 overflow-hidden p-0 sm:max-w-3xl"
            >
                <DialogHeader className="border-b border-neutral-200 px-6 py-5 pr-12 dark:border-neutral-800">
                    <DialogTitle>Media toevoegen</DialogTitle>
                    <DialogDescription>
                        Upload één afbeelding of pdf naar de mediabibliotheek.
                    </DialogDescription>
                </DialogHeader>
                {uploadForm}
            </DialogContent>
        </Dialog>
    );
}
