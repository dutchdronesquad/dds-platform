import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, UserRound } from 'lucide-react';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import MarkdownContent from '@/components/public/markdown-content';
import PublicSeoHead from '@/components/public/public-seo-head';
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

                <div className="mx-auto grid w-full max-w-7xl gap-12 px-public-gutter py-16 sm:py-20 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start lg:gap-16 lg:py-28">
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

                    <aside
                        aria-label="Meer nieuws"
                        className="min-w-0 lg:sticky lg:top-28"
                        data-testid="article-sidebar"
                    >
                        <section className="relative overflow-hidden rounded-sm border border-paddock-rule bg-white shadow-sm dark:border-white/12 dark:bg-night-900">
                            <span
                                aria-hidden="true"
                                className="absolute top-0 right-0 h-1.5 w-1/3 bg-dds-orange"
                            />
                            <div className="p-6 sm:p-8 lg:p-6">
                                <p className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                    Verder lezen
                                </p>
                                <h2 className="mt-3 font-public-display text-3xl leading-[1.05] font-semibold tracking-[-0.045em]">
                                    Meer uit de paddock.
                                </h2>

                                {relatedArticles.length > 0 ? (
                                    <ul className="mt-6 divide-y divide-paddock-rule dark:divide-white/12">
                                        {relatedArticles.map(
                                            (relatedArticle) => (
                                                <li key={relatedArticle.id}>
                                                    <Link
                                                        href={articleShow(
                                                            relatedArticle.slug,
                                                        )}
                                                        prefetch
                                                        className="group grid grid-cols-[4.5rem_minmax(0,1fr)] gap-4 py-4 first:pt-0 focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none"
                                                    >
                                                        <img
                                                            src={
                                                                relatedArticle
                                                                    .image.src
                                                            }
                                                            alt=""
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
                                                                {
                                                                    relatedArticle.title
                                                                }
                                                            </h3>
                                                        </div>
                                                    </Link>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                ) : (
                                    <p className="mt-5 text-sm leading-6 text-signal-muted dark:text-night-400">
                                        Dit is momenteel ons enige gepubliceerde
                                        artikel. Binnenkort verschijnt hier meer
                                        nieuws.
                                    </p>
                                )}

                                <Link
                                    href={newsIndex()}
                                    prefetch
                                    className="group mt-6 inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-dds-blue hover:text-deep-signal focus-visible:ring-2 focus-visible:ring-dds-cyan focus-visible:outline-none dark:text-dds-cyan dark:hover:text-white"
                                >
                                    Alle nieuwsartikelen
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-4 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none"
                                    />
                                </Link>
                            </div>
                        </section>
                    </aside>
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
        <div className="flex min-h-20 min-w-0 items-center gap-4 bg-paddock px-5 py-4 text-deep-signal sm:min-h-24 sm:px-6 sm:py-5 dark:bg-night-900 dark:text-white">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-sm border border-deep-signal/10 bg-white/70 text-dds-blue dark:border-white/12 dark:bg-white/6 dark:text-dds-cyan">
                <Icon aria-hidden="true" className="size-5" />
            </span>
            <div className="min-w-0">
                <dt className="font-mono text-[0.66rem] font-semibold tracking-[0.12em] text-signal-muted uppercase dark:text-night-400">
                    {label}
                </dt>
                <dd className="mt-1.5 text-sm leading-5 font-semibold text-deep-signal sm:text-[0.94rem] dark:text-white">
                    {value}
                </dd>
            </div>
        </div>
    );
}
