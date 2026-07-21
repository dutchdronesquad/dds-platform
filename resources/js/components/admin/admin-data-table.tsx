import { Link } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type ServerPagination<TData> = {
    current_page: number;
    data: TData[];
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

type AdminDataTableProps<TData, TValue> = {
    bulkActions?: ReactNode;
    caption: string;
    columns: ColumnDef<TData, TValue>[];
    emptyAction?: ReactNode;
    emptyDescription?: string;
    emptyTitle: string;
    mobileContent?: ReactNode;
    pagination: ServerPagination<TData>;
    resourceLabel: string;
    selectedCount?: number;
    tableClassName?: string;
    toolbar?: ReactNode;
};

type AdminColumnMeta = {
    className?: string;
};

export function AdminDataTable<TData, TValue>({
    bulkActions,
    caption,
    columns,
    emptyAction,
    emptyDescription,
    emptyTitle,
    mobileContent,
    pagination,
    resourceLabel,
    selectedCount = 0,
    tableClassName,
    toolbar,
}: AdminDataTableProps<TData, TValue>) {
    // TanStack Table's callback API is intentionally excluded from React Compiler memoization.
    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        columns,
        data: pagination.data,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: pagination.last_page,
        state: {
            pagination: {
                pageIndex: pagination.current_page - 1,
                pageSize: pagination.per_page,
            },
        },
    });

    return (
        <section className="overflow-hidden rounded-lg border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-950">
            {toolbar && (
                <div className="border-b border-neutral-200 px-4 py-3 sm:px-5 dark:border-neutral-800">
                    {toolbar}
                </div>
            )}

            {selectedCount > 0 && bulkActions && (
                <div className="flex flex-col gap-3 border-b border-signal-300/70 bg-signal-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-signal-500/30 dark:bg-signal-500/5">
                    <p className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                        {selectedCount} geselecteerd
                    </p>
                    <div className="flex flex-wrap items-center gap-2">
                        {bulkActions}
                    </div>
                </div>
            )}

            {mobileContent && pagination.data.length > 0 && (
                <div className="md:hidden">{mobileContent}</div>
            )}

            {mobileContent && pagination.data.length === 0 && (
                <div className="px-6 py-12 text-center md:hidden">
                    <p className="font-medium text-neutral-950 dark:text-white">
                        {emptyTitle}
                    </p>
                    {emptyDescription && (
                        <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                            {emptyDescription}
                        </p>
                    )}
                    {emptyAction && (
                        <div className="mt-4 flex justify-center">
                            {emptyAction}
                        </div>
                    )}
                </div>
            )}

            <div className={cn(mobileContent && 'hidden md:block')}>
                <Table className={cn('min-w-5xl', tableClassName)}>
                    <TableCaption className="sr-only">{caption}</TableCaption>
                    <TableHeader className="bg-neutral-50 text-xs tracking-wide text-neutral-500 uppercase dark:bg-neutral-900/70 dark:text-neutral-400">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const meta = header.column.columnDef
                                        .meta as AdminColumnMeta;

                                    return (
                                        <TableHead
                                            key={header.id}
                                            className={cn(
                                                'h-9 px-4 py-2.5 sm:px-5',
                                                meta?.className,
                                            )}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    className="align-top hover:bg-neutral-50/80 dark:hover:bg-neutral-900/50"
                                >
                                    {row.getVisibleCells().map((cell) => {
                                        const meta = cell.column.columnDef
                                            .meta as AdminColumnMeta;

                                        return (
                                            <TableCell
                                                key={cell.id}
                                                className={cn(
                                                    'px-4 py-3.5 whitespace-normal sm:px-5',
                                                    meta?.className,
                                                )}
                                            >
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-40 px-6 text-center"
                                >
                                    <p className="font-medium text-neutral-950 dark:text-white">
                                        {emptyTitle}
                                    </p>
                                    {emptyDescription && (
                                        <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                            {emptyDescription}
                                        </p>
                                    )}
                                    {emptyAction && (
                                        <div className="mt-4 flex justify-center">
                                            {emptyAction}
                                        </div>
                                    )}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {pagination.total > 0 && (
                <div className="flex flex-col gap-3 border-t border-neutral-200 bg-neutral-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-neutral-800 dark:bg-neutral-900/30">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        {pagination.from !== null && pagination.to !== null
                            ? `${pagination.from}–${pagination.to} van ${pagination.total}`
                            : `${pagination.total} ${resourceLabel}`}
                    </p>

                    {pagination.last_page > 1 && (
                        <nav
                            aria-label={`Paginering voor ${resourceLabel}`}
                            className="flex flex-wrap items-center gap-2"
                        >
                            <PaginationButton
                                disabled={!table.getCanPreviousPage()}
                                href={pagination.prev_page_url}
                                label="Vorige"
                            />
                            <span
                                aria-current="page"
                                className="px-2 text-sm text-neutral-600 tabular-nums dark:text-neutral-400"
                            >
                                Pagina {pagination.current_page} van{' '}
                                {pagination.last_page}
                            </span>
                            <PaginationButton
                                disabled={!table.getCanNextPage()}
                                href={pagination.next_page_url}
                                label="Volgende"
                            />
                        </nav>
                    )}
                </div>
            )}
        </section>
    );
}

type PaginationButtonProps = {
    disabled: boolean;
    href: string | null;
    label: string;
};

function PaginationButton({ disabled, href, label }: PaginationButtonProps) {
    if (disabled || !href) {
        return (
            <Button disabled size="sm" variant="outline">
                {label}
            </Button>
        );
    }

    return (
        <Button asChild size="sm" variant="outline">
            <Link href={href} preserveScroll preserveState>
                {label}
            </Link>
        </Button>
    );
}
