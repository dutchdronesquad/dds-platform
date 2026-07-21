import { Head, Link } from '@inertiajs/react';
import { FileImage, FileUp, Save } from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/Admin/MediaAssetController';
import {
    AdminFormActions,
    AdminFormErrorSummary,
    AdminFormLayout,
    AdminFormNavigationGuard,
    AdminFormSection,
} from '@/components/admin/admin-form';
import { AdminResourcePage } from '@/components/admin/admin-resource-page';
import { MediaAltTextFields } from '@/components/admin/media-alt-text-fields';
import { MediaUploadDropzone } from '@/components/admin/media-upload-dropzone';
import { Button } from '@/components/ui/button';
import { useMediaUploadForm } from '@/hooks/use-media-upload-form';
import { dashboard } from '@/routes';
import type { MediaLocale } from '@/types/media';

export default function CreateMediaAsset({
    locales,
}: {
    locales: MediaLocale[];
}) {
    const { form, isImage, progress, removeFile, selectFiles, submit } =
        useMediaUploadForm({ locales });

    return (
        <>
            <Head title="Media uploaden" />
            <AdminResourcePage
                eyebrow="Mediabeheer"
                title="Bestand uploaden"
                description="Voeg één herkenbare afbeelding of pdf toe. Controleer de preview en vul waar nodig een toegankelijke beschrijving in."
                variant="form"
                contentClassName="@container/admin-page"
            >
                <form onSubmit={submit} noValidate>
                    <AdminFormNavigationGuard
                        isDirty={form.isDirty && !form.processing}
                    />
                    <AdminFormActions
                        context={form.data.file?.name ?? 'Nieuw media-item'}
                        isDirty={form.isDirty}
                        isNew
                        processing={form.processing}
                        recentlySuccessful={form.recentlySuccessful}
                    >
                        {form.processing ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => form.cancel()}
                            >
                                Upload stoppen
                            </Button>
                        ) : (
                            <Button asChild type="button" variant="outline">
                                <Link href={index()}>Annuleren</Link>
                            </Button>
                        )}
                        <Button
                            type="submit"
                            disabled={form.processing || !form.data.file}
                            data-test="media-upload-submit"
                        >
                            {form.processing ? <FileUp /> : <Save />}
                            {form.processing
                                ? 'Bestand uploaden…'
                                : 'Toevoegen'}
                        </Button>
                    </AdminFormActions>

                    <AdminFormLayout className="max-w-5xl">
                        <AdminFormErrorSummary
                            errors={form.errors as Record<string, string>}
                        />

                        <AdminFormSection
                            id="upload"
                            icon={FileUp}
                            title="Kies het bestand"
                            description="Sleep een bestand naar het vlak of open de bestandskiezer. Je krijgt eerst een preview voordat de upload begint."
                        >
                            <MediaUploadDropzone
                                disabled={form.processing}
                                error={form.errors.file}
                                file={form.data.file}
                                onFilesSelected={selectFiles}
                                onRemove={removeFile}
                                progress={form.processing ? progress : null}
                            />
                        </AdminFormSection>

                        {isImage && (
                            <AdminFormSection
                                id="toegankelijkheid"
                                icon={FileImage}
                                title="Maak de afbeelding begrijpelijk"
                                description="Deze tekst is een standaardvoorstel. De uiteindelijke alt-tekst blijft afhankelijk van de plek waar de afbeelding wordt gebruikt."
                            >
                                <MediaAltTextFields
                                    altText={form.data.alt_text}
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
                            </AdminFormSection>
                        )}
                    </AdminFormLayout>
                </form>
            </AdminResourcePage>
        </>
    );
}

CreateMediaAsset.layout = {
    breadcrumbs: [
        { title: 'Beheer', href: dashboard() },
        { title: 'Media', href: index() },
        { title: 'Uploaden', href: index() },
    ],
};
