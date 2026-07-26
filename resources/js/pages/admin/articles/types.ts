import type { AdminActivity } from '@/components/admin/admin-activity-metadata';
import type { ServerPagination } from '@/components/admin/admin-data-table';
import type { MediaPickerAsset } from '@/types/media';

export type AdminArticleCategory =
    'news' | 'announcement' | 'community' | 'race_report';

export type AdminArticleStatus = 'draft' | 'published' | 'archived';

export type SelectOption = {
    label: string;
    value: string;
};

export type ArticleRecord = {
    activity: Pick<AdminActivity, 'updatedAt'>;
    author: { id: number; name: string } | null;
    capabilities: {
        delete: boolean;
        update: boolean;
    };
    category: AdminArticleCategory;
    id: number;
    publishedAt: string | null;
    slug: string;
    status: AdminArticleStatus;
    title: string;
};

export type ArticleIndexProps = {
    articles: ServerPagination<ArticleRecord>;
    canCreate: boolean;
    categoryOptions: SelectOption[];
    filters: {
        search: string;
    };
    statusOptions: SelectOption[];
};

export type ArticleFormOptions = {
    authors: { id: number; label: string }[];
    categories: SelectOption[];
    statuses: SelectOption[];
};

export type EditableArticle = {
    activity: AdminActivity;
    authorId: number | null;
    capabilities: {
        delete: boolean;
    };
    category: AdminArticleCategory;
    content: string;
    coverImage: MediaPickerAsset | null;
    coverImageId: number | null;
    id: number;
    publishedAt: string | null;
    slug: string;
    status: AdminArticleStatus;
    title: string;
};
