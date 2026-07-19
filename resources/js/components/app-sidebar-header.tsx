import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-30 flex h-14 shrink-0 items-center border-b border-sidebar-border bg-background px-4 shadow-xs sm:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger className="-ml-1 size-9 shrink-0 text-neutral-500 hover:bg-signal-50 hover:text-signal-700 focus-visible:ring-signal-500/50 dark:hover:bg-signal-500/10 dark:hover:text-signal-300" />
                {breadcrumbs.length > 0 && (
                    <>
                        <span
                            aria-hidden="true"
                            className="h-4 w-px shrink-0 bg-sidebar-border"
                        />
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </>
                )}
            </div>
        </header>
    );
}
