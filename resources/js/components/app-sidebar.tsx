import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    Home,
    LayoutDashboard,
    LifeBuoy,
    MapPin,
    Newspaper,
    Route as RouteIcon,
    ShieldCheck,
} from 'lucide-react';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Overzicht',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Events',
        href: `${dashboard.url()}#events`,
        icon: CalendarDays,
    },
    {
        title: 'Projecten',
        href: `${dashboard.url()}#projects`,
        icon: ClipboardList,
    },
    {
        title: 'Nieuws',
        href: `${dashboard.url()}#news`,
        icon: Newspaper,
    },
    {
        title: 'Locaties',
        href: `${dashboard.url()}#locations`,
        icon: MapPin,
    },
    {
        title: 'Redirects',
        href: redirectsIndex(),
        icon: RouteIcon,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Publieke site',
        href: home(),
        icon: Home,
    },
    {
        title: 'Huisregels',
        href: `${dashboard.url()}#house-rules`,
        icon: ShieldCheck,
    },
    {
        title: 'Support',
        href: `${dashboard.url()}#support`,
        icon: LifeBuoy,
    },
];

export function AppSidebar() {
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
