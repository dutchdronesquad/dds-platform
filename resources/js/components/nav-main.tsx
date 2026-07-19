import { Link } from '@inertiajs/react';
import { useId } from 'react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    exact = true,
    items = [],
    label = 'Platform',
}: {
    exact?: boolean;
    items: NavItem[];
    label?: string;
}) {
    const labelId = useId();
    const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-1.5">
            <SidebarGroupLabel
                id={labelId}
                className="h-7 px-2 text-[11px] font-semibold tracking-[0.12em] text-neutral-400 uppercase dark:text-neutral-500"
            >
                {label}
            </SidebarGroupLabel>
            <nav aria-labelledby={labelId}>
                <SidebarMenu>
                    {items.map((item) => {
                        const isActive = exact
                            ? isCurrentUrl(item.href)
                            : isCurrentOrParentUrl(item.href);

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isActive}
                                    tooltip={{ children: item.title }}
                                    className="h-9 border-l-2 border-transparent px-3 text-[13px] font-medium transition-[background-color,border-color,color] hover:border-flight-300 hover:bg-flight-50/70 hover:text-flight-700 focus-visible:ring-flight-500/50 active:bg-flight-100/80 data-[active=true]:border-flight-500 data-[active=true]:bg-flight-50 data-[active=true]:text-flight-700 dark:hover:border-flight-500/60 dark:hover:bg-flight-500/10 dark:hover:text-flight-300 dark:focus-visible:ring-flight-400/50 dark:active:bg-flight-500/15 dark:data-[active=true]:border-flight-400 dark:data-[active=true]:bg-flight-500/12 dark:data-[active=true]:text-flight-300 [&>svg]:text-flight-500 hover:[&>svg]:text-flight-700 data-[active=true]:[&>svg]:text-flight-700 dark:[&>svg]:text-flight-400 dark:hover:[&>svg]:text-flight-300 dark:data-[active=true]:[&>svg]:text-flight-300"
                                >
                                    <Link
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </nav>
        </SidebarGroup>
    );
}
