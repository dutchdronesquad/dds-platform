import type { LucideIcon } from 'lucide-react';
import { RadioTower } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import PublicButton from './public-button';
import PublicExternalLink from './public-external-link';

type Action = {
    external?: boolean;
    href: string;
    label: string;
};

type Media = {
    alt: string;
    className?: string;
    position?: string;
    src: string;
};

type PublicHeroProps = {
    actions: Action[];
    description?: string;
    kicker?: string;
    media: Media;
    separatorTone?: 'air' | 'deep-signal' | 'paper';
    showSeparator?: boolean;
    size?: 'default' | 'compact';
    title: ReactNode;
};

export function PublicHero({
    actions,
    description,
    kicker,
    media,
    separatorTone = 'paper',
    showSeparator = true,
    size = 'default',
    title,
}: PublicHeroProps) {
    return (
        <>
            <section
                className={cn(
                    'relative isolate flex items-center overflow-hidden bg-ink text-white',
                    size === 'compact'
                        ? description
                            ? 'min-h-[36rem] sm:min-h-[40rem]'
                            : 'min-h-[32rem] sm:min-h-[36rem]'
                        : 'min-h-[44rem] sm:min-h-[46rem]',
                )}
            >
                <img
                    src={media.src}
                    alt={media.alt}
                    fetchPriority="high"
                    className={cn(
                        'absolute inset-0 -z-30 h-full w-full object-cover',
                        media.className,
                    )}
                    style={
                        media.position
                            ? { objectPosition: media.position }
                            : undefined
                    }
                />
                <div className="absolute inset-0 -z-20 bg-linear-to-r from-ink/92 from-3% via-ink/55 via-52% to-ink/10" />
                <div className="absolute inset-0 -z-10 bg-linear-to-t from-ink/48 via-transparent to-ink/16" />
                <div
                    className={cn(
                        'mx-auto w-full max-w-7xl px-public-gutter',
                        size === 'compact'
                            ? 'pt-32 pb-16 sm:pt-36 sm:pb-20'
                            : 'pt-36 pb-20 sm:pt-40 sm:pb-24',
                    )}
                >
                    <div className="max-w-3xl">
                        {kicker && (
                            <p className="text-signal-200 mb-6 flex items-center gap-3 text-xs font-semibold tracking-[0.08em] uppercase">
                                <span className="size-2 rounded-full bg-flight-500" />
                                {kicker}
                            </p>
                        )}
                        <h1
                            className={cn(
                                'max-w-3xl font-public-display leading-[0.95] font-semibold tracking-[-0.055em] text-white sm:text-balance',
                                size === 'compact'
                                    ? 'text-4xl sm:text-5xl lg:text-6xl'
                                    : 'text-5xl sm:text-6xl lg:text-7xl',
                            )}
                        >
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-6 max-w-2xl text-base leading-7 text-white/72 sm:text-lg sm:leading-8">
                                {description}
                            </p>
                        )}
                        <div className="mt-8 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-6">
                            {actions.map((action, index) =>
                                action.external ? (
                                    <PublicExternalLink
                                        key={action.label}
                                        href={action.href}
                                        className="min-h-11 px-0 py-3 text-sm font-semibold text-white transition-colors hover:text-signal-300"
                                    >
                                        {action.label}
                                    </PublicExternalLink>
                                ) : (
                                    <PublicButton
                                        key={action.label}
                                        href={action.href}
                                        inverse
                                        variant={
                                            index === 0 ? 'primary' : 'text'
                                        }
                                    >
                                        {action.label}
                                    </PublicButton>
                                ),
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {showSeparator && <HeroSeparator tone={separatorTone} />}
        </>
    );
}

type HeroSeparatorProps = {
    tone: 'air' | 'deep-signal' | 'paper';
};

export function HeroSeparator({ tone }: HeroSeparatorProps) {
    return (
        <div
            aria-hidden="true"
            data-testid="hero-separator"
            className={cn(
                'relative z-10 -mt-10 h-10 overflow-hidden sm:-mt-14 sm:h-14',
                tone === 'air' && 'text-air',
                tone === 'deep-signal' && 'text-deep-signal',
                tone === 'paper' && 'text-paper dark:text-night-950',
            )}
        >
            <svg
                viewBox="0 0 390 40"
                preserveAspectRatio="none"
                className="h-full w-full sm:hidden"
            >
                <path
                    fill="currentColor"
                    d="M0 40V27H100L124 8H226L252 27H390V40Z"
                />
                <path
                    d="M0 27H100L124 8H153"
                    fill="none"
                    stroke="var(--color-dds-orange)"
                    strokeWidth="3"
                    vectorEffect="non-scaling-stroke"
                />
                <path
                    d="M226 8L252 27H390"
                    fill="none"
                    stroke="var(--color-dds-cyan)"
                    strokeWidth="3"
                    vectorEffect="non-scaling-stroke"
                />
            </svg>
            <svg
                viewBox="0 0 1440 64"
                preserveAspectRatio="none"
                className="hidden h-full w-full sm:block"
            >
                <path
                    fill="currentColor"
                    d="M0 64V48H360L430 15H755L820 48H1120L1190 29H1440V64Z"
                />
                <path
                    d="M0 48H360L430 15H575"
                    fill="none"
                    stroke="var(--color-dds-orange)"
                    strokeWidth="4"
                    vectorEffect="non-scaling-stroke"
                />
                <path
                    d="M755 15L820 48H1120L1190 29H1440"
                    fill="none"
                    stroke="var(--color-dds-cyan)"
                    strokeWidth="4"
                    vectorEffect="non-scaling-stroke"
                />
            </svg>
        </div>
    );
}

type PageIntroProps = {
    action: Action;
    description: string;
    eyebrow: string;
    media: Media;
    title: string;
};

export function PageIntro({
    action,
    description,
    eyebrow,
    media,
    title,
}: PageIntroProps) {
    return (
        <section className="overflow-hidden bg-night-50 py-12 sm:py-16 lg:py-20 dark:bg-night-900">
            <div className="mx-auto grid w-full max-w-7xl items-center gap-10 px-public-gutter lg:grid-cols-[0.9fr_1.1fr]">
                <div className="flex items-center">
                    <div className="max-w-3xl">
                        <Eyebrow>{eyebrow}</Eyebrow>
                        <h1 className="mt-5 font-public-display text-4xl font-bold tracking-[-0.04em] text-balance text-night-950 sm:text-5xl lg:text-6xl dark:text-white">
                            {title}
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-night-500 sm:text-lg sm:leading-8 dark:text-night-400">
                            {description}
                        </p>
                        <PublicButton href={action.href} className="mt-8">
                            {action.label}
                        </PublicButton>
                    </div>
                </div>

                <div className="relative min-h-[22rem] overflow-hidden rounded-2xl bg-night-100 shadow-public-card sm:min-h-[28rem] dark:bg-night-800">
                    <img
                        src={media.src}
                        alt={media.alt}
                        className="absolute inset-0 h-full w-full object-cover"
                        style={{ objectPosition: media.position ?? 'center' }}
                    />
                    <div className="absolute inset-0 ring-1 ring-black/5 ring-inset" />
                </div>
            </div>
        </section>
    );
}

type ContentBandProps = {
    children: ReactNode;
    description?: string;
    eyebrow: string;
    title: string;
    tone?: 'light' | 'muted' | 'dark';
};

export function ContentBand({
    children,
    description,
    eyebrow,
    title,
    tone = 'light',
}: ContentBandProps) {
    return (
        <section
            className={cn(
                'py-public-section',
                tone === 'light' && 'bg-white dark:bg-night-950',
                tone === 'muted' && 'bg-night-50 dark:bg-night-900',
                tone === 'dark' && 'bg-night-950 text-white',
            )}
        >
            <div className="mx-auto w-full max-w-7xl px-public-gutter">
                <div className="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:gap-16">
                    <div className="max-w-xl">
                        <Eyebrow inverse={tone === 'dark'}>{eyebrow}</Eyebrow>
                        <h2
                            className={cn(
                                'mt-4 font-public-display text-3xl font-bold tracking-[-0.035em] text-balance sm:text-4xl',
                                tone === 'dark'
                                    ? 'text-white'
                                    : 'text-night-950 dark:text-white',
                            )}
                        >
                            {title}
                        </h2>
                        {description && (
                            <p
                                className={cn(
                                    'mt-5 text-base leading-7',
                                    tone === 'dark'
                                        ? 'text-white/62'
                                        : 'text-night-500 dark:text-night-400',
                                )}
                            >
                                {description}
                            </p>
                        )}
                    </div>
                    <div>{children}</div>
                </div>
            </div>
        </section>
    );
}

type FeatureCardProps = {
    body: string;
    icon: LucideIcon;
    index?: string;
    title: string;
};

export function FeatureCard({
    body,
    icon: Icon,
    index,
    title,
}: FeatureCardProps) {
    return (
        <article className="group rounded-xl border border-night-200 bg-white p-6 shadow-sm transition duration-200 hover:border-flight-300 hover:shadow-md motion-reduce:transition-none dark:border-white/10 dark:bg-night-800 dark:hover:border-flight-400">
            <div className="flex items-start justify-between gap-6">
                <span className="flex size-11 items-center justify-center rounded-lg bg-flight-50 text-flight-700 dark:bg-flight-500/10 dark:text-flight-300">
                    <Icon className="size-5" />
                </span>
                {index && (
                    <span className="text-xs font-medium tracking-[0.1em] text-night-400">
                        {index}
                    </span>
                )}
            </div>
            <h3 className="mt-7 text-xl font-semibold tracking-[-0.02em] text-night-950 dark:text-white">
                {title}
            </h3>
            <p className="mt-3 text-sm leading-6 text-night-500 dark:text-night-400">
                {body}
            </p>
        </article>
    );
}

type MediaStripProps = {
    items: (Media & { caption: string; label: string })[];
};

export function MediaStrip({ items }: MediaStripProps) {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            {items.map((item) => (
                <figure
                    key={item.src}
                    className="group relative min-h-72 overflow-hidden rounded-xl bg-night-900 shadow-sm md:min-h-80"
                >
                    <img
                        src={item.src}
                        alt={item.alt}
                        className="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.015] motion-reduce:transform-none motion-reduce:transition-none"
                        style={{ objectPosition: item.position ?? 'center' }}
                    />
                    <div className="absolute inset-0 bg-linear-to-t from-night-950 via-night-950/10 to-transparent" />
                    <figcaption className="absolute right-0 bottom-0 left-0 p-5 text-white sm:p-6">
                        <span className="text-xs font-semibold tracking-wide text-flight-300">
                            {item.label}
                        </span>
                        <p className="mt-2 max-w-xs text-sm leading-6 text-white/78">
                            {item.caption}
                        </p>
                    </figcaption>
                </figure>
            ))}
        </div>
    );
}

type CtaBandProps = {
    action: Action;
    description: string;
    eyebrow?: string;
    title: string;
};

export function CtaBand({
    action,
    description,
    eyebrow = 'Next heat',
    title,
}: CtaBandProps) {
    return (
        <section className="bg-night-900 text-white">
            <div className="mx-auto grid w-full max-w-7xl items-center gap-8 px-public-gutter py-12 sm:py-16 lg:grid-cols-[1fr_auto]">
                <div className="max-w-3xl">
                    <p className="flex items-center gap-2 text-xs font-semibold tracking-[0.12em] text-signal-300 uppercase">
                        <RadioTower className="size-4" />
                        {eyebrow}
                    </p>
                    <h2 className="mt-4 font-public-display text-3xl font-bold tracking-[-0.035em] text-balance sm:text-4xl">
                        {title}
                    </h2>
                    <p className="mt-4 max-w-2xl text-sm leading-6 text-white/65 sm:text-base">
                        {description}
                    </p>
                </div>
                <PublicButton href={action.href} variant="primary">
                    {action.label}
                </PublicButton>
            </div>
        </section>
    );
}

type EyebrowProps = {
    children: ReactNode;
    inverse?: boolean;
    line?: boolean;
};

export function Eyebrow({
    children,
    inverse = false,
    line = true,
}: EyebrowProps) {
    return (
        <p
            data-testid="page-eyebrow"
            className={cn(
                'flex items-center gap-3 text-xs font-semibold tracking-[0.14em] uppercase',
                line && 'before:h-px before:w-7',
                inverse
                    ? cn('text-signal-300', line && 'before:bg-flight-400')
                    : cn(
                          'text-signal-700 dark:text-signal-300',
                          line && 'before:bg-flight-500',
                      ),
            )}
        >
            {children}
        </p>
    );
}
