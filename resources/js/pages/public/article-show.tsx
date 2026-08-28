import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, UserRound } from 'lucide-react';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import MarkdownContent from '@/components/public/markdown-content';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as eventsIndex } from '@/routes/events';
import { index as newsIndex, show as articleShow } from '@/routes/news';
import type {
    ArticleCategory,
    PublicArticleDetail,
    PublicArticleSummary,
    SeoMetadata,
} from '@/types';

type Props = {
    article: PublicArticleDetail;
    isPreview?: boolean;
    relatedArticles: PublicArticleSummary[];
    seo: SeoMetadata;
};

const categoryLabels: Record<ArticleCategory, string> = {
    news: 'Nieuws',
    announcement: 'Aankondiging',
    community: 'Community',
    race_report: 'Raceverslag',
};

const categoryFilters = [
    { label: 'Nieuws', value: 'news' },
    { label: 'Aankondigingen', value: 'announcement' },
    { label: 'Community', value: 'community' },
    { label: 'Raceverslagen', value: 'race_report' },
] satisfies Array<{ label: string; value: ArticleCategory }>;

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'long',
    timeZone: 'Europe/Amsterdam',
});

export default function ArticleShow({
    article,
    isPreview = false,
    relatedArticles,
    seo,
}: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

            {isPreview && (
                <div
                    role="status"
                    className="border-b border-dds-orange/35 bg-flight-100 px-public-gutter py-3 text-center text-sm font-semibold text-deep-signal dark:border-dds-orange/25 dark:bg-flight-500/15 dark:text-white"
                >
                    Voorbeeldweergave · dit artikel is nog niet gepubliceerd
                </div>
            )}

            <PublicHero
                kicker={categoryLabels[article.category]}
                title={article.title}
                actions={[
                    {
                        label: 'Alle nieuwsartikelen',
                        href: newsIndex.url(),
                    },
                ]}
                media={article.image}
                separatorTone="paper"
                showSeparator={false}
                size="compact"
            />

            <div className="bg-paper text-deep-signal dark:bg-night-950 dark:text-white">
                <section
                    aria-label="Artikel in het kort"
                    className="border-y border-paddock-rule bg-paddock dark:border-white/12 dark:bg-night-900"
                >
                    <div className="mx-auto w-full max-w-7xl px-public-gutter">
                        <dl className="grid gap-px bg-paddock-rule sm:grid-cols-2 dark:bg-white/12">
                            <ArticleQuickFact
                                icon={CalendarDays}
                                label="Gepubliceerd"
                                value={
                                    article.publishedAt === null
                                        ? isPreview
                                            ? 'Nog niet gepubliceerd'
                                            : 'Onbekend'
                                        : dateFormatter.format(
                                              new Date(article.publishedAt),
                                          )
                                }
                            />
                            <ArticleQuickFact
                                icon={UserRound}
                                label="Auteur"
                                value={
                                    article.author?.name ?? 'Dutch Drone Squad'
                                }
                            />
                        </dl>
                    </div>
                </section>

                <div className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-14 lg:py-28">
                    <section
                        aria-labelledby="article-heading"
                        className="min-w-0"
                    >
                        <h2 id="article-heading" className="sr-only">
                            Artikel
                        </h2>
                        <MarkdownContent
                            className="max-w-3xl"
                            html={article.contentHtml}
                        />
                    </section>

                    <ArticleSidebar relatedArticles={relatedArticles} />
                </div>
            </div>

            <CtaBand
                eyebrow="Meer nieuws"
                title="Blijf op de hoogte van Dutch Drone Squad."
                description="Bekijk het volledige overzicht van aankondigingen, raceverslagen en community-updates."
                action={{
                    label: 'Alle nieuwsartikelen',
                    href: newsIndex.url(),
                }}
            />
        </>
    );
}

function ArticleSidebar({
    relatedArticles,
}: {
    relatedArticles: PublicArticleSummary[];
}) {
    const [featuredArticle, ...otherArticles] = relatedArticles;

    return (
        <aside
            aria-label="Meer nieuws"
            className="flex min-w-0 flex-col gap-5 lg:sticky lg:top-28"
            data-testid="article-sidebar"
        >
            <section className="relative overflow-hidden rounded-sm border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900">
                <span
                    aria-hidden="true"
                    className="absolute top-0 right-0 z-10 h-1.5 w-1/3 bg-dds-orange"
                />
                <div className="p-6 sm:p-8 lg:p-6">
                    <p className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                        Verder lezen
                    </p>
                    <h2 className="mt-3 font-public-display text-3xl leading-[1.05] font-semibold tracking-[-0.045em]">
                        Meer uit de paddock.
                    </h2>

                    {featuredArticle ? (
                        <>
                            <Link
                                href={articleShow(featuredArticle.slug)}
                                prefetch
                                className="group mt-6 block overflow-hidden rounded-sm bg-deep-signal text-white focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:bg-night-950 dark:focus-visible:ring-offset-night-900"
                            >
                                <div className="relative aspect-[16/10] overflow-hidden bg-deep-signal">
                                    <img
                                        src={featuredArticle.image.src}
                                        alt={featuredArticle.image.alt}
                                        loading="lazy"
                                        className="size-full object-cover transition duration-500 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
                                    />
                                    <span className="absolute top-3 left-3 rounded-sm bg-deep-signal/90 px-2 py-1 font-mono text-[0.62rem] font-semibold tracking-[0.08em] text-dds-cyan uppercase backdrop-blur-sm">
                                        {
                                            categoryLabels[
                                                featuredArticle.category
                                            ]
                                        }
                                    </span>
                                </div>
                                <div className="p-5">
                                    {featuredArticle.publishedAt && (
                                        <time
                                            dateTime={
                                                featuredArticle.publishedAt
                                            }
                                            className="font-mono text-[0.64rem] font-semibold tracking-[0.08em] text-white/55 uppercase"
                                        >
                                            {dateFormatter.format(
                                                new Date(
                                                    featuredArticle.publishedAt,
                                                ),
                                            )}
                                        </time>
                                    )}
                                    <h3 className="mt-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em] text-white transition-colors group-hover:text-dds-cyan">
                                        {featuredArticle.title}
                                    </h3>
                                    {featuredArticle.excerpt && (
                                        <p className="mt-3 line-clamp-3 text-sm leading-6 text-white/65">
                                            {featuredArticle.excerpt}
                                        </p>
                                    )}
                                    <span className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-dds-cyan">
                                        Lees dit artikel
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none"
                                        />
                                    </span>
                                </div>
                            </Link>

                            {otherArticles.length > 0 && (
                                <ul className="mt-5 divide-y divide-paddock-rule border-t border-paddock-rule pt-2 dark:divide-white/12 dark:border-white/12">
                                    {otherArticles.map((relatedArticle) => (
                                        <li key={relatedArticle.id}>
                                            <Link
                                                href={articleShow(
                                                    relatedArticle.slug,
                                                )}
                                                prefetch
                                                className="group grid grid-cols-[4.5rem_minmax(0,1fr)] gap-4 py-4 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                                            >
                                                <img
                                                    src={
                                                        relatedArticle.image.src
                                                    }
                                                    alt={
                                                        relatedArticle.image.alt
                                                    }
                                                    loading="lazy"
                                                    className="aspect-[4/3] w-full rounded-sm object-cover"
                                                />
                                                <div className="min-w-0">
                                                    <p className="font-mono text-[0.62rem] font-semibold tracking-[0.08em] text-dds-blue uppercase dark:text-dds-cyan">
                                                        {
                                                            categoryLabels[
                                                                relatedArticle
                                                                    .category
                                                            ]
                                                        }
                                                    </p>
                                                    <h3 className="mt-1.5 line-clamp-3 text-sm leading-5 font-semibold text-deep-signal transition-colors group-hover:text-dds-blue dark:text-white dark:group-hover:text-dds-cyan">
                                                        {relatedArticle.title}
                                                    </h3>
                                                </div>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </>
                    ) : (
                        <p className="mt-5 text-sm leading-6 text-signal-muted dark:text-night-400">
                            Dit is momenteel ons enige gepubliceerde artikel.
                            Binnenkort verschijnt hier meer nieuws.
                        </p>
                    )}
                </div>

                <nav
                    aria-label="Blader nieuws op onderwerp"
                    className="border-t border-paddock-rule bg-paddock/70 p-6 dark:border-white/12 dark:bg-white/3"
                >
                    <p className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-signal-muted uppercase dark:text-night-400">
                        Blader op onderwerp
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {categoryFilters.map((category) => (
                            <Link
                                key={category.value}
                                href={newsIndex({
                                    query: { category: category.value },
                                })}
                                prefetch
                                className="dark:text-night-300 inline-flex min-h-9 items-center rounded-sm border border-paddock-rule bg-white px-3 py-2 text-xs font-semibold text-signal-muted transition-colors hover:border-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:border-white/12 dark:bg-night-900 dark:hover:border-dds-cyan dark:hover:text-white"
                            >
                                {category.label}
                            </Link>
                        ))}
                    </div>
                    <Link
                        href={newsIndex()}
                        prefetch
                        className="group mt-5 inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                    >
                        Naar het nieuwsoverzicht
                        <ArrowRight
                            aria-hidden="true"
                            className="size-4 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none"
                        />
                    </Link>
                </nav>
            </section>

            <Link
                href={eventsIndex()}
                prefetch
                className="group relative overflow-hidden rounded-sm bg-deep-signal p-6 text-white shadow-sm transition-colors hover:bg-dds-blue focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:ring-offset-3 focus-visible:outline-none dark:bg-dds-blue dark:hover:bg-flight-700 dark:focus-visible:ring-offset-night-950"
            >
                <span className="flex size-11 items-center justify-center rounded-sm bg-white/8 text-dds-cyan">
                    <CalendarDays aria-hidden="true" className="size-5" />
                </span>
                <p className="mt-5 font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-cyan uppercase">
                    Zelf de baan op
                </p>
                <h2 className="mt-2 font-public-display text-2xl leading-[1.08] font-semibold tracking-[-0.04em]">
                    Van lezen naar vliegen.
                </h2>
                <p className="mt-3 text-sm leading-6 text-white/65">
                    Bekijk trainingen, races en andere momenten waarop we samen
                    vliegen.
                </p>
                <span className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-white">
                    Bekijk de agenda
                    <ArrowRight
                        aria-hidden="true"
                        className="size-4 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none"
                    />
                </span>
            </Link>
        </aside>
    );
}

function ArticleQuickFact({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof CalendarDays;
    label: string;
    value: string;
}) {
    return (
        <div className="min-h-20 min-w-0 bg-paddock px-5 py-4 text-deep-signal sm:min-h-24 sm:px-6 sm:py-5 dark:bg-night-900 dark:text-white">
            <dt className="flex items-center gap-4">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-sm border border-deep-signal/10 bg-white/70 text-dds-blue dark:border-white/12 dark:bg-white/6 dark:text-dds-cyan">
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                <span className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </span>
            </dt>
            <dd className="mt-1.5 pl-14 text-sm leading-5 font-semibold text-deep-signal sm:text-[0.94rem] dark:text-white">
                {value}
            </dd>
        </div>
    );
}
