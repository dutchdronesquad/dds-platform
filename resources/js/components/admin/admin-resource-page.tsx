import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type AdminResourcePageProps = {
    actions?: ReactNode;
    children: ReactNode;
    description: string;
    eyebrow?: string;
    title: string;
};

export function AdminResourcePage({
    actions,
    children,
    description,
    eyebrow = 'Beheer',
    title,
}: AdminResourcePageProps) {
    return (
        <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
            <section className="rounded-lg border border-sidebar-border/70 bg-white p-6 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                            {eyebrow}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-neutral-950 sm:text-3xl dark:text-white">
                            {title}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-neutral-600 sm:text-base dark:text-neutral-400">
                            {description}
                        </p>
                    </div>

                    {actions && (
                        <div className="flex flex-wrap items-center gap-2">
                            {actions}
                        </div>
                    )}
                </div>
            </section>

            {children}
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
        <article className="rounded-lg border border-sidebar-border/70 bg-white p-5 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                        {label}
                    </p>
                    <p className="mt-2 text-2xl font-semibold text-neutral-950 tabular-nums dark:text-white">
                        {value}
                    </p>
                </div>
                <span className="flex size-10 items-center justify-center rounded-md bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                    <Icon className="size-5" />
                </span>
            </div>
        </article>
    );
}
