import { Link } from '@inertiajs/react';
import { ArrowUpRight, ChevronLeft, ChevronRight, Newspaper } from 'lucide-react';
import { PublicHero } from '@/components/public/public-patterns';
import PublicSeoHead from '@/components/public/public-seo-head';
import { cn } from '@/lib/utils';
import { index as eventsIndex } from '@/routes/events';
import { index as newsIndex, show as articleShow } from '@/routes/news';
import type {
    ArticleCategory,
    ArticleCategoryFilter,
    PublicArticlePaginator,
    SeoMetadata,
} from '@/types';

type Props = {
    activeCategory: ArticleCategory | null;
    articles: PublicArticlePaginator;
    categoryFilters: ArticleCategoryFilter[];
    seo: SeoMetadata;
};

const categoryLabels: Record<ArticleCategory, string> = {
    news: 'Nieuws',
    announcement: 'Aankondiging',
    community: 'Community',
    race_report: 'Raceverslag',
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'long',
    timeZone: 'Europe/Amsterdam',
});

export default function NewsIndex({
    activeCategory,
    articles,
    categoryFilters,
    seo,
}: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            <PublicHero
                title="Het laatste nieuws uit de paddock."
                description="Aankondigingen, raceverslagen en community-updates van Dutch Drone Squad."
                actions={[
                    { label: 'Bekijk de agenda', href: eventsIndex.url() },
                ]}
                media={{
                    src: '/images/dds/racing/pilot-at-training.jpg',
                    alt: 'Piloot tijdens een indoor training van Dutch Drone Squad',
                    position: '62% center',
                }}
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-labelledby="news-heading"
                    className="mx-auto w-full max-w-[86rem] px-public-gutter py-16 sm:py-20"
                >
                    <div className="flex flex-col gap-8 border-b border-paddock-rule pb-8 lg:flex-row lg:items-end lg:justify-between dark:border-white/12">
                        <div>
                            <h2
                                id="news-heading"
                                className="font-public-display text-4xl font-semibold tracking-[-0.05em] sm:text-5xl"
                            >
                                Nieuws
                            </h2>
                            <p
                                aria-live="polite"
                                className="mt-4 max-w-2xl text-base leading-7 text-signal-muted dark:text-night-400"
                            >
                                {articles.total === 1
                                    ? '1 artikel gepubliceerd.'
                                    : `${articles.total} artikelen gepubliceerd.`}
                            </p>
                        </div>

                        <nav
                            aria-label="Filter nieuws op categorie"
                            className="flex flex-wrap gap-2"
                        >
                            <FilterLink
                                label="Alles"
                                isActive={activeCategory === null}
                            />
                            {categoryFilters.map((filter) => (
                                <FilterLink
                                    key={filter.value}
                                    label={filter.label}
                                    category={filter.value}
                                    isActive={activeCategory === filter.value}
                                />
                            ))}
                        </nav>
                    </div>

                    {articles.data.length > 0 ? (
                        <>
                            <ul
                                aria-label="Nieuwsartikelen"
                                className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {articles.data.map((article) => (
                                    <li key={article.id}>
                                        <Link
                                            href={articleShow(article.slug)}
                                            prefetch
                                            className="group flex h-full flex-col overflow-hidden rounded-sm border border-paddock-rule bg-white transition-colors hover:border-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:border-white/10 dark:bg-night-900 dark:hover:border-dds-cyan"
                                        >
                                            <div className="relative aspect-[16/9] overflow-hidden bg-deep-signal">
                                                <img
                                                    src={article.image.src}
                                                    alt={article.image.alt}
                                                    loading="lazy"
                                                    className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
                                                />
                                            </div>
                                            <div className="flex flex-1 flex-col p-5 sm:p-6">
                                                <p className="flex flex-wrap items-center gap-2 font-mono text-[0.68rem] font-semibold tracking-[0.09em] text-dds-blue uppercase dark:text-dds-cyan">
                                                    <span className="rounded-sm bg-dds-blue/8 px-2 py-1 text-dds-blue dark:bg-dds-cyan/10 dark:text-dds-cyan">
                                                        {categoryLabels[article.category]}
                                                    </span>
                                                    {article.publishedAt && (
                                                        <span className="text-signal-muted normal-case dark:text-night-400">
                                                            {dateFormatter.format(new Date(article.publishedAt))}
                                                        </span>
                                                    )}
                                                </p>
                                                <h3 className="mt-3 line-clamp-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-deep-signal transition-colors group-hover:text-dds-blue sm:text-[1.7rem] dark:text-white dark:group-hover:text-dds-cyan">
                                                    {article.title}
                                                </h3>
                                                {article.excerpt && (
                                                    <p className="mt-4 line-clamp-3 text-sm leading-6 text-signal-muted dark:text-night-400">
                                                        {article.excerpt}
                                                    </p>
                                                )}
                                                <div className="mt-auto flex items-center justify-end gap-2 pt-6 text-sm font-semibold text-dds-blue dark:text-dds-cyan">
                                                    Lees verder
                                                    <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transition-none" />
                                                </div>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>

                            <Pagination
                                activeCategory={activeCategory}
                                currentPage={articles.current_page}
                                lastPage={articles.last_page}
                            />
                        </>
                    ) : (
                        <div className="mt-10 flex min-h-80 flex-col items-center justify-center rounded-sm border border-dashed border-paddock-rule bg-paddock px-6 py-14 text-center dark:border-white/15 dark:bg-night-900">
                            <span className="flex size-14 items-center justify-center rounded-full bg-white text-dds-blue shadow-sm dark:bg-white/8 dark:text-dds-cyan">
                                <Newspaper className="size-6" />
                            </span>
                            <h2 className="mt-6 font-public-display text-2xl font-semibold tracking-[-0.035em]">
                                Geen nieuws gevonden
                            </h2>
                            <p className="mt-3 max-w-md text-sm leading-6 text-signal-muted dark:text-night-400">
                                {activeCategory === null
                                    ? 'Er zijn nog geen artikelen gepubliceerd. Kijk later opnieuw of bekijk de agenda voor aankomende events.'
                                    : 'Er zijn geen artikelen in deze categorie. Bekijk al het nieuws voor andere artikelen.'}
                            </p>
                            {activeCategory !== null && (
                                <Link
                                    href={newsIndex()}
                                    preserveScroll
                                    preserveState
                                    className="mt-6 inline-flex min-h-11 items-center rounded-sm bg-dds-orange px-5 py-3 text-sm font-semibold text-deep-signal transition-colors hover:bg-flight-400 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none"
                                >
                                    Bekijk al het nieuws
                                </Link>
                            )}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

type FilterLinkProps = {
    category?: ArticleCategory;
    isActive: boolean;
    label: string;
};

function FilterLink({ category, isActive, label }: FilterLinkProps) {
    return (
        <Link
            href={
                category === undefined
                    ? newsIndex()
                    : newsIndex({ query: { category } })
            }
            preserveScroll
            preserveState
            aria-current={isActive ? 'page' : undefined}
            className={cn(
                'inline-flex min-h-10 items-center rounded-sm border px-4 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-night-950',
                isActive
                    ? 'border-deep-signal bg-deep-signal text-white dark:border-dds-cyan dark:bg-dds-cyan dark:text-deep-signal'
                    : 'dark:text-night-300 border-paddock-rule bg-white text-signal-muted hover:border-dds-blue hover:text-deep-signal dark:border-white/15 dark:bg-night-900 dark:hover:border-dds-cyan dark:hover:text-white',
            )}
        >
            {label}
        </Link>
    );
}

type PaginationProps = {
    activeCategory: ArticleCategory | null;
    currentPage: number;
    lastPage: number;
};

function Pagination({ activeCategory, currentPage, lastPage }: PaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    const pageHref = (page: number) =>
        newsIndex({
            query: {
                page,
                ...(activeCategory === null ? {} : { category: activeCategory }),
            },
        });

    return (
        <nav
            aria-label="Paginering nieuws"
            className="mt-12 flex items-center justify-between border-t border-paddock-rule pt-6 dark:border-white/12"
        >
            {currentPage > 1 ? (
                <Link
                    href={pageHref(currentPage - 1)}
                    className="inline-flex min-h-10 items-center gap-2 rounded-sm px-3 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                >
                    <ChevronLeft className="size-4" />
                    Vorige
                </Link>
            ) : (
                <span />
            )}

            <span className="text-sm text-signal-muted dark:text-night-400">
                Pagina {currentPage} van {lastPage}
            </span>

            {currentPage < lastPage ? (
                <Link
                    href={pageHref(currentPage + 1)}
                    className="inline-flex min-h-10 items-center gap-2 rounded-sm px-3 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                >
                    Volgende
                    <ChevronRight className="size-4" />
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}
