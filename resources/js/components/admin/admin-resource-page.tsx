import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminResourcePageProps = {
    actions?: ReactNode;
    children: ReactNode;
    contentClassName?: string;
    description: string;
    eyebrow?: string;
    title: string;
    variant?: 'default' | 'form';
};

export function AdminResourcePage({
    actions,
    children,
    contentClassName,
    description,
    eyebrow = 'Beheer',
    title,
    variant = 'default',
}: AdminResourcePageProps) {
    const isFormPage = variant === 'form';

    return (
        <div
            data-testid="admin-resource-page"
            className="flex min-h-0 flex-1 flex-col overflow-x-clip bg-neutral-100/75 dark:bg-neutral-900/55"
        >
            <header
                className={cn(
                    'relative overflow-hidden border-b px-4 py-7 sm:px-6 sm:py-8',
                    isFormPage
                        ? 'border-night-800 bg-night-950 text-white'
                        : 'border-sidebar-border/70 bg-white/90 backdrop-blur dark:border-sidebar-border dark:bg-neutral-950/90',
                )}
            >
                {isFormPage && (
                    <>
                        <div className="pointer-events-none absolute -top-28 right-[8%] size-64 rounded-full bg-signal-500/12 blur-3xl" />
                        <div className="pointer-events-none absolute -bottom-32 left-[22%] size-56 rounded-full bg-flight-500/10 blur-3xl" />
                    </>
                )}
                <div
                    data-testid="admin-resource-header-content"
                    className={cn(
                        'relative mx-auto flex w-full max-w-[100rem] flex-col gap-5 lg:flex-row lg:items-end lg:justify-between',
                        contentClassName,
                    )}
                >
                    <div className="max-w-3xl">
                        <p
                            className={cn(
                                'text-xs font-semibold tracking-[0.16em] uppercase',
                                isFormPage
                                    ? 'text-signal-300'
                                    : 'text-signal-700 dark:text-signal-300',
                            )}
                        >
                            {eyebrow}
                        </p>
                        <h1
                            className={cn(
                                'mt-2 text-2xl font-semibold tracking-tight sm:text-3xl',
                                isFormPage
                                    ? 'text-white'
                                    : 'text-neutral-950 dark:text-white',
                            )}
                        >
                            {title}
                        </h1>
                        <p
                            className={cn(
                                'mt-2 max-w-2xl text-sm leading-6 sm:text-base',
                                isFormPage
                                    ? 'text-night-200'
                                    : 'text-neutral-600 dark:text-neutral-400',
                            )}
                        >
                            {description}
                        </p>
                    </div>

                    {actions && (
                        <div className="flex flex-wrap items-center gap-2">
                            {actions}
                        </div>
                    )}
                </div>
            </header>

            <div className={cn(isFormPage ? 'p-0' : 'p-4 sm:p-6 lg:py-7')}>
                <div
                    data-testid="admin-resource-content"
                    className={cn(
                        'mx-auto grid w-full max-w-[100rem] gap-6 lg:gap-8',
                        isFormPage && 'max-w-none gap-0',
                        contentClassName,
                    )}
                >
                    {children}
                </div>
            </div>
        </div>
    );
}

type AdminSummaryCardProps = {
    icon: LucideIcon;
    label: string;
    value: number | string;
};

export function AdminSummaryCard({
    icon: Icon,
    label,
    value,
}: AdminSummaryCardProps) {
    return (
        <article className="rounded-xl border border-sidebar-border/70 bg-white px-4 py-3.5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
            <div className="flex items-center gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-signal-50 text-signal-700 dark:bg-signal-500/10 dark:text-signal-300">
                    <Icon className="size-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                        {label}
                    </p>
                    <p className="mt-0.5 text-xl font-semibold text-neutral-950 tabular-nums dark:text-white">
                        {value}
                    </p>
                </div>
            </div>
        </article>
    );
}
