import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { store } from '@/actions/App/Http/Controllers/Admin/MediaAssetController';
import { MEDIA_UPLOAD_ACCEPT } from '@/components/admin/media-upload-dropzone';
import type { MediaLocale } from '@/types/media';

const MAX_UPLOAD_SIZE = 20 * 1024 * 1024;
const SUPPORTED_MEDIA_TYPES = new Set(MEDIA_UPLOAD_ACCEPT.split(','));

export type MediaUploadForm = {
    alt_text: Record<string, string>;
    file: File | null;
    return_to: 'library' | null;
};

export function useMediaUploadForm({
    locales,
    onSuccess,
    returnToLibrary = false,
}: {
    locales: MediaLocale[];
    onSuccess?: () => void;
    returnToLibrary?: boolean;
}) {
    const emptyAltText = () =>
        Object.fromEntries(locales.map((locale) => [locale.code, '']));
    const form = useForm<MediaUploadForm>(store(), {
        alt_text: emptyAltText(),
        file: null,
        return_to: returnToLibrary ? 'library' : null,
    });

    function selectFiles(files: File[]) {
        if (files.length !== 1) {
            form.setError(
                'file',
                files.length === 0
                    ? 'Kies een bestand om toe te voegen.'
                    : 'Kies één bestand per upload.',
            );

            return;
        }

        const [file] = files;
        const validationError = validateFile(file);

        if (validationError) {
            form.setError('file', validationError);

            return;
        }

        form.clearErrors('file');
        form.setData({
            ...form.data,
            alt_text: emptyAltText(),
            file,
        });
    }

    function removeFile() {
        form.setData({
            ...form.data,
            alt_text: emptyAltText(),
            file: null,
        });
        form.clearErrors('file');
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!form.data.file) {
            form.setError('file', 'Kies een bestand om toe te voegen.');

            return;
        }

        form.submit({
            preserveScroll: returnToLibrary,
            onSuccess: () => {
                form.reset();
                form.clearErrors();
                onSuccess?.();
            },
        });
    }

    return {
        form,
        isImage: form.data.file?.type.startsWith('image/') ?? false,
        progress: form.progress?.percentage ?? null,
        removeFile,
        selectFiles,
        submit,
    };
}

function validateFile(file: File): string | null {
    if (file.size === 0) {
        return `“${file.name}” is leeg en kan niet worden geüpload.`;
    }

    if (file.size > MAX_UPLOAD_SIZE) {
        return `“${file.name}” is groter dan de toegestane 20 MB.`;
    }

    if (file.type && !SUPPORTED_MEDIA_TYPES.has(file.type)) {
        return `“${file.name}” heeft geen ondersteund bestandstype. Kies een JPEG, PNG, WebP, GIF of PDF.`;
    }

    return null;
}
