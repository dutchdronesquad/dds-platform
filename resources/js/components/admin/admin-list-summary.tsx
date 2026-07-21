import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminListMetricTone = 'amber' | 'blue' | 'neutral' | 'red';

type AdminListMetric = {
    icon: LucideIcon;
    label: string;
    tone?: AdminListMetricTone;
    value: number | string;
};

type AdminListSummaryProps = {
    label: string;
    metrics: AdminListMetric[];
};

const toneStyles: Record<AdminListMetricTone, string> = {
    amber: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    blue: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
    neutral:
        'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
    red: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
};

export function AdminListSummary({
    label,
    metrics,
}: AdminListSummaryProps): ReactNode {
    const gridClassName =
        metrics.length === 5
            ? 'grid-cols-2 sm:grid-cols-5'
            : metrics.length === 4
              ? 'grid-cols-2 sm:grid-cols-4'
              : 'grid-cols-1 sm:grid-cols-3';

    return (
        <section
            aria-label={label}
            className="overflow-hidden rounded-lg border border-sidebar-border/70 bg-neutral-200 dark:border-sidebar-border dark:bg-neutral-800"
        >
            <div className={cn('grid gap-px', gridClassName)}>
                {metrics.map(
                    (
                        {
                            icon: Icon,
                            label: metricLabel,
                            tone = 'neutral',
                            value,
                        },
                        metricIndex,
                    ) => (
                        <article
                            key={metricLabel}
                            className={cn(
                                'flex items-center gap-3 bg-white px-4 py-3.5 sm:px-5 dark:bg-neutral-950',
                                metrics.length === 5 &&
                                    metricIndex === metrics.length - 1 &&
                                    'col-span-2 sm:col-span-1',
                                metrics.length === 3 &&
                                    'flex-col items-start gap-2 px-3 py-3 sm:flex-row sm:items-center sm:gap-3 sm:px-5 sm:py-3.5',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-8 shrink-0 items-center justify-center rounded-md',
                                    toneStyles[tone],
                                )}
                            >
                                <Icon className="size-4" />
                            </span>
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    {metricLabel}
                                </p>
                                <p className="mt-0.5 text-lg font-semibold text-neutral-950 tabular-nums dark:text-white">
                                    {value}
                                </p>
                            </div>
                        </article>
                    ),
                )}
            </div>
        </section>
    );
}
