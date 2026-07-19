import { router } from '@inertiajs/react';
import {
    AlertCircleIcon,
    CheckCircle2,
    CircleAlert,
    FilePenLine,
    LoaderCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

export function AdminFormLayout({
    aside,
    asideFirstOnSmallScreens = true,
    asideLayoutClassName,
    children,
    className,
    contentClassName,
}: {
    aside?: ReactNode;
    asideFirstOnSmallScreens?: boolean;
    asideLayoutClassName?: string;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
}) {
    return (
        <div
            data-testid="admin-form-layout"
            className={cn(
                'mx-auto grid w-full max-w-[103rem] items-start gap-5 px-4 py-4 sm:px-6 sm:py-6 lg:gap-6 lg:py-7',
                aside &&
                    (asideLayoutClassName ??
                        '@min-[56rem]/admin-page:grid-cols-[minmax(0,1fr)_18.5rem]'),
                className,
            )}
        >
            <div
                data-testid="admin-form-content"
                className={cn(
                    'grid min-w-0 overflow-clip rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950',
                    aside &&
                        asideFirstOnSmallScreens &&
                        'order-2 @min-[56rem]/admin-page:order-none',
                    contentClassName,
                )}
            >
                {children}
            </div>
            {aside && (
                <aside
                    data-testid="admin-form-aside"
                    className={cn(
                        'min-w-0 self-start @min-[56rem]/admin-page:sticky @min-[56rem]/admin-page:top-32',
                        asideFirstOnSmallScreens &&
                            'order-1 @min-[56rem]/admin-page:order-none',
                    )}
                >
                    {aside}
                </aside>
            )}
        </div>
    );
}

export function AdminFormSection({
    children,
    className,
    compact = false,
    description,
    id,
    icon: Icon,
    tone = 'default',
    title,
}: {
    children: ReactNode;
    className?: string;
    compact?: boolean;
    description?: string;
    id?: string;
    icon?: LucideIcon;
    tone?: 'danger' | 'default';
    title: string;
}) {
    return (
        <section
            id={id}
            data-testid="admin-form-section"
            data-tone={tone}
            className={cn(
                'scroll-mt-32 border-b px-5 py-7 last:border-b-0 sm:px-7 sm:py-9',
                tone === 'danger'
                    ? 'border-destructive/20 bg-destructive/[0.025] py-4 sm:py-5 dark:bg-destructive/[0.045]'
                    : 'border-neutral-200 dark:border-neutral-800',
                className,
            )}
        >
            <div
                className={cn(
                    tone === 'danger' &&
                        'sm:flex sm:items-center sm:justify-between sm:gap-6',
                )}
            >
                <div
                    data-danger-header={tone === 'danger' ? '' : undefined}
                    className={cn(
                        'flex max-w-3xl items-start gap-3.5',
                        tone === 'danger' && 'min-w-0 flex-1',
                    )}
                >
                    {Icon && (
                        <span
                            className={cn(
                                'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                                tone === 'danger'
                                    ? 'size-8 rounded-lg bg-destructive/8 text-destructive ring-destructive/15 dark:bg-destructive/12 dark:ring-destructive/25'
                                    : 'bg-signal-50 text-signal-700 ring-signal-100 dark:bg-signal-500/10 dark:text-signal-300 dark:ring-signal-500/15',
                            )}
                        >
                            <Icon
                                aria-hidden="true"
                                className={cn(
                                    'size-4.5',
                                    tone === 'danger' && 'size-4',
                                )}
                            />
                        </span>
                    )}
                    <div className="min-w-0">
                        <h2
                            className={cn(
                                'text-base font-semibold tracking-tight',
                                tone === 'danger'
                                    ? 'text-destructive'
                                    : 'text-neutral-950 dark:text-white',
                            )}
                        >
                            {title}
                        </h2>
                        {description && (
                            <p
                                className={cn(
                                    'mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400',
                                    tone === 'danger' &&
                                        'mt-0.5 text-xs leading-5 sm:text-sm sm:leading-5',
                                )}
                            >
                                {description}
                            </p>
                        )}
                    </div>
                </div>
                <div
                    data-danger-content={tone === 'danger' ? '' : undefined}
                    className={cn(
                        compact ? 'mt-5 grid gap-4' : 'mt-6 grid gap-5',
                        Icon && tone !== 'danger' && 'sm:pl-12.5',
                        tone === 'danger' &&
                            'mt-3 flex shrink-0 items-center border-t border-destructive/15 pt-3 sm:mt-0 sm:border-t-0 sm:pt-0',
                    )}
                >
                    {children}
                </div>
            </div>
        </section>
    );
}

export function AdminFormActions({
    children,
    context,
    destructiveActions,
    isDirty,
    isNew = false,
    processing,
    recentlySuccessful,
}: {
    children: ReactNode;
    context: string;
    destructiveActions?: ReactNode;
    isDirty: boolean;
    isNew?: boolean;
    processing: boolean;
    recentlySuccessful: boolean;
}) {
    const state = processing
        ? 'processing'
        : recentlySuccessful
          ? 'saved'
          : isDirty
            ? 'dirty'
            : isNew
              ? 'new'
              : 'unchanged';
    const status = {
        dirty: {
            description: 'Sla op voordat je verdergaat',
            icon: CircleAlert,
            label: 'Nog niet opgeslagen',
            tone: 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-300',
        },
        new: {
            description: 'Klaar om ingevuld te worden',
            icon: FilePenLine,
            label: 'Nieuw formulier',
            tone: 'bg-signal-50 text-signal-700 dark:bg-signal-500/12 dark:text-signal-300',
        },
        processing: {
            description: 'Wijzigingen worden verwerkt',
            icon: LoaderCircle,
            label: 'Bezig met opslaan…',
            tone: 'bg-flight-50 text-flight-700 dark:bg-flight-500/12 dark:text-flight-300',
        },
        saved: {
            description: 'Je wijzigingen zijn bijgewerkt',
            icon: CheckCircle2,
            label: 'Opgeslagen',
            tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-300',
        },
        unchanged: {
            description: 'Je bekijkt de opgeslagen versie',
            icon: CheckCircle2,
            label: 'Geen wijzigingen',
            tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-300',
        },
    }[state];
    const StatusIcon = status.icon;

    return (
        <div
            data-testid="admin-form-actions"
            role="group"
            aria-label="Formulieracties"
            className="sticky top-14 z-30 border-y border-neutral-200 bg-white/95 shadow-sm backdrop-blur-xl dark:border-neutral-800 dark:bg-neutral-950/95"
        >
            <div className="mx-auto grid w-full max-w-[103rem] gap-2.5 px-4 py-2.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                <div className="flex min-w-0 items-center gap-3">
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                            status.tone,
                        )}
                    >
                        <StatusIcon
                            className={cn(
                                'size-4',
                                state === 'processing' && 'animate-spin',
                            )}
                        />
                    </span>
                    <div className="min-w-0">
                        <p
                            data-testid="admin-form-context"
                            className="truncate text-sm font-semibold text-neutral-950 dark:text-white"
                        >
                            {context}
                        </p>
                        <div
                            data-testid="admin-form-save-status"
                            data-state={state}
                            aria-live="polite"
                            className="mt-0.5 flex min-h-4 min-w-0 items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400"
                        >
                            <span className="font-medium text-neutral-700 dark:text-neutral-300">
                                {status.label}
                            </span>
                            <span
                                aria-hidden="true"
                                className="hidden sm:inline"
                            >
                                ·
                            </span>
                            <span className="hidden truncate sm:inline">
                                {status.description}
                            </span>
                        </div>
                    </div>
                    {destructiveActions}
                </div>
                <div className="grid w-full grid-cols-2 items-center gap-2 sm:ml-auto sm:flex sm:w-auto [&>*]:w-full sm:[&>*]:w-auto">
                    {children}
                </div>
            </div>
        </div>
    );
}

export function AdminFormErrorSummary({
    errors,
}: {
    errors: Record<string, string>;
}) {
    const messages = Array.from(new Set(Object.values(errors)));
    const errorSummaryRef = useRef<HTMLDivElement>(null);
    const errorSignature = messages.join('\n');

    useEffect(() => {
        if (errorSignature) {
            errorSummaryRef.current?.focus();
        }
    }, [errorSignature]);

    if (messages.length === 0) {
        return null;
    }

    return (
        <div
            ref={errorSummaryRef}
            tabIndex={-1}
            className="border-b border-destructive/25 bg-destructive/[0.025] px-5 py-4 outline-none focus-visible:ring-2 focus-visible:ring-destructive/50 focus-visible:ring-offset-2 sm:px-7 dark:bg-destructive/[0.04]"
        >
            <Alert variant="destructive">
                <AlertCircleIcon />
                <AlertTitle>Controleer de gemarkeerde velden</AlertTitle>
                <AlertDescription>
                    <ul className="list-inside list-disc text-sm">
                        {messages.map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                </AlertDescription>
            </Alert>
        </div>
    );
}

export function AdminFormOutline({
    description,
    items,
    title = 'Formulieroverzicht',
}: {
    description?: string;
    items: Array<{
        description: string;
        icon: LucideIcon;
        id: string;
        title: string;
    }>;
    title?: string;
}) {
    const [activeItemId, setActiveItemId] = useState(items[0]?.id ?? '');
    const activeItemIndex = Math.max(
        items.findIndex((item) => item.id === activeItemId),
        0,
    );

    useEffect(() => {
        const sections = items
            .map((item) => document.getElementById(item.id))
            .filter((section): section is HTMLElement => section !== null);

        if (sections.length === 0) {
            return;
        }

        const updateActiveSection = () => {
            const readingLine = Math.min(window.innerHeight * 0.3, 260);
            const currentSection =
                sections
                    .filter(
                        (section) =>
                            section.getBoundingClientRect().top <= readingLine,
                    )
                    .at(-1) ?? sections[0];

            setActiveItemId(currentSection.id);
        };

        updateActiveSection();
        window.addEventListener('scroll', updateActiveSection, {
            passive: true,
        });

        return () => {
            window.removeEventListener('scroll', updateActiveSection);
        };
    }, [items]);

    return (
        <section className="hidden border-t border-neutral-200 @min-[56rem]/admin-page:block dark:border-neutral-800">
            <div className="px-5 pt-5 pb-4">
                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm font-semibold text-neutral-950 dark:text-white">
                        {title}
                    </p>
                    <span className="text-[0.68rem] font-semibold tracking-wide text-neutral-500 uppercase tabular-nums dark:text-neutral-400">
                        {activeItemIndex + 1}/{items.length}
                    </span>
                </div>
                {description && (
                    <p className="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        {description}
                    </p>
                )}
                <div
                    aria-hidden="true"
                    className="mt-3 h-1 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800"
                >
                    <div
                        className="h-full rounded-full bg-signal-500 transition-[width] duration-300"
                        style={{
                            width: `${((activeItemIndex + 1) / items.length) * 100}%`,
                        }}
                    />
                </div>
            </div>
            <nav
                aria-label={title}
                className="grid border-t border-neutral-200 p-2 dark:border-neutral-800"
            >
                {items.map((item, index) => {
                    const Icon = item.icon;
                    const isActive = activeItemId === item.id;

                    return (
                        <a
                            key={item.id}
                            href={`#${item.id}`}
                            aria-current={isActive ? 'location' : undefined}
                            className={cn(
                                'group flex items-start gap-3 rounded-xl px-3 py-2.5 transition-colors focus-visible:ring-2 focus-visible:ring-signal-500/50 focus-visible:outline-none',
                                isActive
                                    ? 'text-signal-800 dark:text-signal-200 bg-signal-50 dark:bg-signal-500/12'
                                    : 'hover:bg-neutral-50 dark:hover:bg-neutral-900/70',
                            )}
                        >
                            <span
                                className={cn(
                                    'relative mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg transition-colors',
                                    isActive
                                        ? 'bg-white text-signal-700 shadow-xs dark:bg-neutral-950 dark:text-signal-300'
                                        : 'bg-neutral-100 text-neutral-500 group-hover:text-signal-700 dark:bg-neutral-900 dark:text-neutral-400 dark:group-hover:text-signal-300',
                                )}
                            >
                                <Icon className="size-3.5" />
                                <span className="sr-only">
                                    Stap {index + 1}
                                </span>
                            </span>
                            <span className="min-w-0">
                                <span className="block text-sm font-medium text-current">
                                    {item.title}
                                </span>
                                <span className="mt-0.5 block text-xs leading-4 text-neutral-500 dark:text-neutral-400">
                                    {item.description}
                                </span>
                            </span>
                        </a>
                    );
                })}
            </nav>
        </section>
    );
}

export function AdminFormNavigationGuard({ isDirty }: { isDirty: boolean }) {
    useEffect(() => {
        const removeBeforeListener = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (!isDirty || visit.method !== 'get' || visit.prefetch) {
                return;
            }

            return window.confirm(
                'Je hebt niet-opgeslagen wijzigingen. Wil je deze pagina toch verlaten?',
            );
        });

        const handleBeforeUnload = (event: BeforeUnloadEvent) => {
            if (!isDirty) {
                return;
            }

            event.preventDefault();
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            removeBeforeListener();
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [isDirty]);

    return null;
}
