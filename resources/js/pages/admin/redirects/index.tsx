import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, Route as RouteIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as redirectsIndex } from '@/routes/redirects';
import { redirectColumns } from './columns';
import type { RedirectRecord } from './columns';
import { DataTable } from './data-table';
import type { ServerPagination } from './data-table';

type Props = {
    redirects: ServerPagination<RedirectRecord>;
    summary: {
        active: number;
        hits: number;
        total: number;
    };
};

export default function RedirectsIndex({ redirects, summary }: Props) {
    return (
        <>
            <Head title="Redirects" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
                <section className="rounded-lg border border-sidebar-border/70 bg-white p-6 shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-3xl">
                            <p className="text-sm font-semibold tracking-wide text-red-600 uppercase dark:text-red-400">
                                SEO-beheer
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-neutral-950 sm:text-3xl dark:text-white">
                                Legacy redirects
                            </h1>
                            <p className="mt-3 text-sm leading-6 text-neutral-600 sm:text-base dark:text-neutral-400">
                                Controleer welke oude WordPress-paden actief
                                doorverwijzen, waar ze uitkomen en hoe vaak ze
                                zijn gebruikt.
                            </p>
                        </div>

                        <Button asChild variant="outline">
                            <Link href={redirectsIndex()} preserveScroll>
                                Vernieuwen
                            </Link>
                        </Button>
                    </div>
                </section>

                <section
                    aria-label="Redirectsamenvatting"
                    className="grid gap-4 sm:grid-cols-3"
                >
                    <SummaryCard
                        label="Totaal"
                        value={summary.total}
                        icon={RouteIcon}
                    />
                    <SummaryCard
                        label="Actief"
                        value={summary.active}
                        icon={CheckCircle2}
                    />
                    <SummaryCard
                        label="Doorkliks"
                        value={summary.hits}
                        icon={Activity}
                    />
                </section>

                <DataTable columns={redirectColumns} pagination={redirects} />
            </div>
        </>
    );
}

type SummaryCardProps = {
    icon: typeof RouteIcon;
    label: string;
    value: number;
};

function SummaryCard({ icon: Icon, label, value }: SummaryCardProps) {
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

RedirectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Beheer',
            href: dashboard(),
        },
        {
            title: 'Redirects',
            href: redirectsIndex(),
        },
    ],
};
