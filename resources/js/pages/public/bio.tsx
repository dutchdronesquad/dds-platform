import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import DdsBrand from '@/components/dds-brand';
import PublicSeoHead from '@/components/public/public-seo-head';
import { cn } from '@/lib/utils';
import { publicSocials } from '@/lib/public-socials';
import { formatEventShortDate } from '@/lib/event-formatting';
import { home } from '@/routes';
import type { SeoMetadata } from '@/types';

type BioItem = {
    title: string;
    href: string;
    note: string | null;
    image: { src: string; alt: string } | null;
    date?: string;
};

type Props = {
    featured: BioItem | null;
    news: BioItem[];
    events: BioItem[];
    links: BioItem[];
    socials: BioItem[];
    seo: SeoMetadata;
};

const container = 'mx-auto w-full max-w-xl px-5 sm:px-8';
const focus =
    'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-dds-blue dark:focus-visible:outline-dds-cyan';

function BioLink({
    item,
    className,
    children,
}: {
    item: BioItem;
    className: string;
    children: ReactNode;
}) {
    return item.href.startsWith('/') ? (
        <Link href={item.href} className={cn(className, focus)}>
            {children}
        </Link>
    ) : (
        <a
            href={item.href}
            className={cn(className, focus)}
            rel="noopener noreferrer"
        >
            {children}
        </a>
    );
}

function ItemImage({
    item,
    className,
    eager = false,
}: {
    item: BioItem;
    className: string;
    eager?: boolean;
}) {
    return item.image ? (
        <img
            src={item.image.src}
            alt={item.image.alt}
            className={cn('object-cover', className)}
            loading={eager ? 'eager' : 'lazy'}
            onError={(event) => {
                event.currentTarget.hidden = true;
            }}
        />
    ) : null;
}

export default function Bio({
    featured,
    news,
    events,
    links,
    socials,
    seo,
}: Props) {
    return (
        <div className="min-h-screen bg-paper [overflow-wrap:anywhere] text-deep-signal dark:bg-night-950 dark:text-white">
            <PublicSeoHead metadata={seo} />
            <header className="bg-ink text-white">
                <div className={cn(container, 'py-3')}>
                    <Link
                        href={home()}
                        aria-label="Dutch Drone Squad home"
                        className={cn(
                            'inline-flex',
                            focus,
                            'focus-visible:outline-white',
                        )}
                    >
                        <DdsBrand
                            inverse
                            className="[&>span:first-child]:h-8 [&>span:first-child]:w-20"
                        />
                    </Link>
                </div>
            </header>
            <main>
                <section
                    aria-labelledby="bio-heading"
                    className="bg-air dark:bg-night-900"
                >
                    <div
                        className={cn(
                            container,
                            'flex flex-col gap-6 pt-6 pb-8 sm:pt-8',
                        )}
                    >
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                Dutch Drone Squad
                            </p>
                            <h1
                                id="bio-heading"
                                className="mt-3 font-public-display text-[2.5rem] leading-[0.97] font-semibold tracking-[-0.055em] sm:text-5xl"
                            >
                                Waar dronepiloten samenkomen.
                            </h1>
                            <p className="mt-3 text-sm leading-6 text-signal-muted dark:text-night-200">
                                Nieuws, vliegavonden en alles wat je nodig hebt
                                om aan te sluiten.
                            </p>
                        </div>
                        {featured && (
                            <BioLink
                                item={featured}
                                className="group overflow-hidden rounded-sm border border-deep-signal/10 bg-white dark:border-white/12 dark:bg-night-950"
                            >
                                {featured.image && (
                                    <div className="relative bg-deep-signal/10">
                                        <ItemImage
                                            item={featured}
                                            className="aspect-[2/1] w-full"
                                            eager
                                        />
                                        <span
                                            aria-hidden="true"
                                            className="absolute top-0 right-0 h-1 w-1/3 bg-dds-orange"
                                        />
                                    </div>
                                )}
                                <div className="p-4 sm:p-5">
                                    <p className="text-xs font-semibold tracking-[0.12em] text-dds-blue uppercase dark:text-dds-cyan">
                                        Uit de paddock
                                    </p>
                                    <h2 className="mt-3 font-public-display text-[1.375rem] leading-[1.08] font-semibold tracking-[-0.04em] group-hover:text-dds-blue sm:text-3xl dark:group-hover:text-dds-cyan">
                                        {featured.title}
                                    </h2>
                                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                                        {featured.note && (
                                            <p className="text-signal-muted dark:text-night-200">
                                                {featured.note}
                                            </p>
                                        )}
                                        <span className="ml-auto inline-flex items-center gap-2 font-semibold text-dds-blue dark:text-dds-cyan">
                                            Lees verder{' '}
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-4 shrink-0"
                                            />
                                        </span>
                                    </div>
                                </div>
                            </BioLink>
                        )}
                        {news.length > 0 && (
                            <section
                                aria-label="Meer nieuws"
                                className="divide-y divide-deep-signal/15 dark:divide-white/15"
                            >
                                {news.map((item) => (
                                    <BioLink
                                        key={item.href}
                                        item={item}
                                        className="group flex items-center gap-4 py-4 first:pt-0 last:pb-0"
                                    >
                                        <ItemImage
                                            item={item}
                                            className="aspect-square w-16 shrink-0 rounded-sm"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <h2 className="font-semibold tracking-[-0.02em] group-hover:text-dds-blue dark:group-hover:text-dds-cyan">
                                                {item.title}
                                            </h2>
                                            {item.note && (
                                                <p className="mt-1 text-sm text-signal-muted dark:text-night-200">
                                                    {item.note}
                                                </p>
                                            )}
                                        </div>
                                        <ArrowUpRight
                                            aria-hidden="true"
                                            className="size-5 shrink-0 text-dds-blue dark:text-dds-cyan"
                                        />
                                    </BioLink>
                                ))}
                            </section>
                        )}
                        {events.length > 0 && (
                            <section
                                aria-label="Komende evenementen"
                                className="flex flex-col gap-3"
                            >
                                {events.map((item) => (
                                    <BioLink
                                        key={item.href}
                                        item={item}
                                        className="group flex items-center gap-4 rounded-sm bg-deep-signal p-4 text-white hover:bg-dds-blue"
                                    >
                                        {item.date && (
                                            <time
                                                dateTime={item.date}
                                                className="flex shrink-0 flex-col border-r border-white/20 pr-4 text-center"
                                            >
                                                <span className="text-3xl leading-none font-semibold tracking-[-0.04em]">
                                                    {
                                                        formatEventShortDate(
                                                            item.date,
                                                        ).day
                                                    }
                                                </span>
                                                <span className="mt-1 text-xs font-semibold uppercase">
                                                    {
                                                        formatEventShortDate(
                                                            item.date,
                                                        ).month
                                                    }
                                                </span>
                                            </time>
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <h2 className="text-lg leading-tight font-semibold tracking-[-0.03em]">
                                                {item.title}
                                            </h2>
                                            {item.note && (
                                                <p className="mt-2 text-sm leading-5 text-white/80">
                                                    {item.note}
                                                </p>
                                            )}
                                        </div>
                                        <ArrowUpRight
                                            aria-hidden="true"
                                            className="size-5 shrink-0 text-dds-cyan group-hover:text-white"
                                        />
                                    </BioLink>
                                ))}
                            </section>
                        )}
                    </div>
                </section>
                {links.length > 0 && (
                    <nav
                        aria-label="Ontdek DDS"
                        className={cn(
                            container,
                            'divide-y divide-paddock-rule py-4 dark:divide-white/15',
                        )}
                    >
                        {links.map((item) => (
                            <BioLink
                                key={item.href}
                                item={item}
                                className="group flex min-h-12 items-center gap-4 py-3 hover:text-dds-blue dark:hover:text-dds-cyan"
                            >
                                <ItemImage
                                    item={item}
                                    className="aspect-square w-12 shrink-0 rounded-sm"
                                />
                                <div className="min-w-0 flex-1">
                                    <span className="text-base font-semibold tracking-[-0.02em]">
                                        {item.title}
                                    </span>
                                    {item.note && (
                                        <p className="mt-1 text-sm text-signal-muted dark:text-night-200">
                                            {item.note}
                                        </p>
                                    )}
                                </div>
                                <ArrowUpRight
                                    aria-hidden="true"
                                    className="size-5 shrink-0 text-dds-blue dark:text-dds-cyan"
                                />
                            </BioLink>
                        ))}
                    </nav>
                )}
            </main>
            {socials.length > 0 && (
                <nav aria-label="Volg DDS" className="bg-ink text-white">
                    <div
                        className={cn(
                            container,
                            'flex flex-wrap justify-center gap-x-6 gap-y-4 py-6',
                        )}
                    >
                        {socials.map((item) => {
                            const icon = publicSocials.find(
                                (social) => social.title === item.title,
                            )?.icon;
                            return (
                                <BioLink
                                    key={item.href}
                                    item={item}
                                    className="inline-flex min-h-11 min-w-11 items-center justify-center gap-2 text-sm font-semibold hover:text-dds-cyan"
                                >
                                    {icon ? (
                                        <>
                                            <svg
                                                aria-hidden="true"
                                                className="size-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="1.8"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                {icon}
                                            </svg>
                                            <span className="sr-only">
                                                {item.title}
                                            </span>
                                        </>
                                    ) : (
                                        <>
                                            {item.title}
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        </>
                                    )}
                                </BioLink>
                            );
                        })}
                    </div>
                </nav>
            )}
        </div>
    );
}
