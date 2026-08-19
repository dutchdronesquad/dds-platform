import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    count?: number;
    /**
     * 'alert' (default) only renders the badge when count > 0, styled as
     * something needing attention. 'total' always renders the badge,
     * including zero, styled as a neutral count.
     */
    countVariant?: 'alert' | 'total';
};
