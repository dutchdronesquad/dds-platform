import type { ServerPagination } from '@/components/admin/admin-data-table';

export type MediaPickerAsset = {
    altText: Record<string, string>;
    archivedAt: string | null;
    filename: string;
    height: number | null;
    id: number;
    isImage: boolean;
    mimeType: string;
    url: string;
    width: number | null;
};

export type MediaAssetRecord = MediaPickerAsset & {
    capabilities: {
        delete: boolean;
        update: boolean;
    };
    createdAt: string;
    sizeBytes: number;
    updatedAt: string;
    usageCount: number;
};

export type EditableMediaAsset = MediaAssetRecord & {
    path: string;
    usage: Array<{
        href: string | null;
        label: string;
        type: string;
    }>;
};

export type MediaIndexProps = {
    canCreate: boolean;
    filters: {
        category: 'all' | 'document' | 'image';
        search: string;
        status: 'active' | 'all' | 'archived';
        usage: 'all' | 'unused' | 'used';
    };
    locales: MediaLocale[];
    mediaAssets: ServerPagination<MediaAssetRecord>;
    summary: {
        archived: number;
        documents: number;
        images: number;
        total: number;
        unused: number;
    };
};

export type MediaLocale = {
    code: string;
    label: string;
};
