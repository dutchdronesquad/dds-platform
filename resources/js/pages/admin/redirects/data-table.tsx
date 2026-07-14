import { Link } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef } from '@tanstack/react-table';
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

type DataTableProps<TData, TValue> = {
    columns: ColumnDef<TData, TValue>[];
    pagination: ServerPagination<TData>;
};

export function DataTable<TData, TValue>({
    columns,
    pagination,
}: DataTableProps<TData, TValue>) {
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
        <div className="overflow-hidden rounded-lg border border-sidebar-border/70 bg-white shadow-xs dark:border-sidebar-border dark:bg-neutral-950">
            <Table className="min-w-5xl">
                <TableCaption className="sr-only">
                    Overzicht van legacy redirects
                </TableCaption>
                <TableHeader className="bg-neutral-50 text-xs tracking-wide text-neutral-500 uppercase dark:bg-neutral-900/70 dark:text-neutral-400">
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow key={headerGroup.id}>
                            {headerGroup.headers.map((header) => (
                                <TableHead
                                    key={header.id}
                                    className="px-5 py-3"
                                >
                                    {header.isPlaceholder
                                        ? null
                                        : flexRender(
                                              header.column.columnDef.header,
                                              header.getContext(),
                                          )}
                                </TableHead>
                            ))}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.length > 0 ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow key={row.id} className="align-top">
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell
                                        key={cell.id}
                                        className="px-5 py-4 whitespace-normal"
                                    >
                                        {flexRender(
                                            cell.column.columnDef.cell,
                                            cell.getContext(),
                                        )}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell
                                colSpan={columns.length}
                                className="h-28 text-center text-neutral-600 dark:text-neutral-400"
                            >
                                Geen redirects gevonden.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>

            <div className="flex flex-col gap-3 border-t border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                    {pagination.from !== null && pagination.to !== null
                        ? `${pagination.from}–${pagination.to} van ${pagination.total}`
                        : `${pagination.total} redirects`}
                </p>

                <div className="flex items-center gap-2">
                    <PaginationButton
                        disabled={!table.getCanPreviousPage()}
                        href={pagination.prev_page_url}
                        label="Vorige"
                    />
                    <span className="px-2 text-sm text-neutral-600 tabular-nums dark:text-neutral-400">
                        Pagina {pagination.current_page} van{' '}
                        {pagination.last_page}
                    </span>
                    <PaginationButton
                        disabled={!table.getCanNextPage()}
                        href={pagination.next_page_url}
                        label="Volgende"
                    />
                </div>
            </div>
        </div>
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
            <Link href={href} preserveScroll>
                {label}
            </Link>
        </Button>
    );
}
