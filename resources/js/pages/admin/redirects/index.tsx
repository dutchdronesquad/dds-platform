import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, Route as RouteIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as redirectsIndex } from '@/routes/redirects';

type RedirectRecord = {
    hitCount: number;
    id: number;
    isActive: boolean;
    notes: string | null;
    sourcePath: string;
    statusCode: number;
    targetUrl: string;
    updatedAt: string;
};

type Props = {
    redirects: RedirectRecord[];
    summary: {
        active: number;
        hits: number;
        total: number;
    };
};

const dateFormatter = new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

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

                <section className="overflow-hidden rounded-lg border border-sidebar-border/70 bg-white shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
                    {redirects.length === 0 ? (
                        <div className="p-8 text-center">
                            <RouteIcon className="mx-auto size-8 text-neutral-400" />
                            <h2 className="mt-4 font-semibold text-neutral-950 dark:text-white">
                                Nog geen redirects
                            </h2>
                            <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                Voer de redirectseeder uit of laat de
                                WordPress-importer redirects toevoegen.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-5xl text-left text-sm">
                                <caption className="sr-only">
                                    Overzicht van legacy redirects
                                </caption>
                                <thead className="border-b border-neutral-200 bg-neutral-50 text-xs tracking-wide text-neutral-500 uppercase dark:border-neutral-800 dark:bg-neutral-900/70 dark:text-neutral-400">
                                    <tr>
                                        <th scope="col" className="px-5 py-3">
                                            Bron
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Bestemming
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Status
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Hits
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Notitie
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Bijgewerkt
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {redirects.map((redirect) => (
                                        <tr
                                            key={redirect.id}
                                            className="align-top hover:bg-neutral-50/80 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-5 py-4">
                                                <code className="font-medium break-all text-neutral-950 dark:text-white">
                                                    {redirect.sourcePath}
                                                </code>
                                            </td>
                                            <td className="px-5 py-4">
                                                <code className="break-all text-neutral-700 dark:text-neutral-300">
                                                    {redirect.targetUrl}
                                                </code>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    <Badge variant="outline">
                                                        {redirect.statusCode}
                                                    </Badge>
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            redirect.isActive
                                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                                : 'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400'
                                                        }
                                                    >
                                                        {redirect.isActive
                                                            ? 'Actief'
                                                            : 'Inactief'}
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td className="px-5 py-4 font-medium text-neutral-950 tabular-nums dark:text-white">
                                                {redirect.hitCount}
                                            </td>
                                            <td className="max-w-xs px-5 py-4 leading-6 text-neutral-600 dark:text-neutral-400">
                                                {redirect.notes ?? '—'}
                                            </td>
                                            <td className="px-5 py-4 whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {dateFormatter.format(
                                                    new Date(
                                                        redirect.updatedAt,
                                                    ),
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
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
