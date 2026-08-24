export type ArticleCategory =
    'news' | 'announcement' | 'community' | 'race_report';

export type PublicArticleSummary = {
    author: { name: string } | null;
    category: ArticleCategory;
    excerpt: string | null;
    id: number;
    image: {
        alt: string;
        src: string;
    };
    publishedAt: string | null;
    slug: string;
    title: string;
};

export type PublicArticleDetail = PublicArticleSummary & {
    contentHtml: string | null;
};

export type PublicArticlePaginator = {
    current_page: number;
    data: PublicArticleSummary[];
    last_page: number;
    total: number;
};

export type ArticleCategoryFilter = {
    label: string;
    value: ArticleCategory;
};
