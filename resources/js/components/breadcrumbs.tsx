import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb className="min-w-0">
                    <BreadcrumbList className="min-w-0 flex-nowrap overflow-hidden text-xs sm:text-sm">
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            const isHiddenOnMobile =
                                index < breadcrumbs.length - 2;

                            return (
                                <Fragment key={`${index}-${item.title}`}>
                                    <BreadcrumbItem
                                        className={
                                            isHiddenOnMobile
                                                ? 'hidden sm:inline-flex'
                                                : 'min-w-0'
                                        }
                                    >
                                        {isLast ? (
                                            <BreadcrumbPage className="truncate font-semibold">
                                                {item.title}
                                            </BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink
                                                asChild
                                                className="truncate rounded-sm focus-visible:ring-2 focus-visible:ring-signal-500/50 focus-visible:outline-none"
                                            >
                                                <Link href={item.href} prefetch>
                                                    {item.title}
                                                </Link>
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && (
                                        <BreadcrumbSeparator
                                            className={
                                                isHiddenOnMobile
                                                    ? 'hidden sm:block'
                                                    : undefined
                                            }
                                        />
                                    )}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
