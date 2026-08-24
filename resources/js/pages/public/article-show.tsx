import { CalendarDays, UserRound } from 'lucide-react';
import { CtaBand, PublicHero } from '@/components/public/public-patterns';
import MarkdownContent from '@/components/public/markdown-content';
import PublicSeoHead from '@/components/public/public-seo-head';
import { index as newsIndex } from '@/routes/news';
import type {
    ArticleCategory,
    PublicArticleDetail,
    SeoMetadata,
} from '@/types';

type Props = {
    article: PublicArticleDetail;
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

export default function ArticleShow({ article, seo }: Props) {
    return (
        <>
            <PublicSeoHead metadata={seo} />

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
                                        ? 'Onbekend'
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

                <section
                    aria-labelledby="article-heading"
                    className="mx-auto w-full max-w-3xl px-public-gutter py-16 sm:py-20 lg:py-28"
                >
                    <h2 id="article-heading" className="sr-only">
                        Artikel
                    </h2>
                    <MarkdownContent html={article.contentHtml} />
                </section>
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
