import { Link, usePage } from '@inertiajs/react';
import { Home, LayoutDashboard, Route as RouteIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as redirectsIndex } from '@/routes/redirects';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Publieke site',
        href: home(),
        icon: Home,
    },
];

export function AppSidebar() {
    const { management } = usePage().props;
    const mainNavItems: NavItem[] = [
        {
            title: 'Overzicht',
            href: dashboard(),
            icon: LayoutDashboard,
        },
        ...(management?.canViewRedirects
            ? [
                  {
                      title: 'Redirects',
                      href: redirectsIndex(),
                      icon: RouteIcon,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
