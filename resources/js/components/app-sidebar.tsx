import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    Home,
    LayoutDashboard,
    Route as RouteIcon,
    ShieldCheck,
    Tags,
    Users,
} from 'lucide-react';
import { index as eventsIndex } from '@/actions/App/Http/Controllers/Admin/EventController';
import RolePermissionController from '@/actions/App/Http/Controllers/Admin/RolePermissionController';
import { index as seasonsIndex } from '@/actions/App/Http/Controllers/Admin/SeasonController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
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
    const workspaceItems: NavItem[] = [
        {
            title: 'Overzicht',
            href: dashboard(),
            icon: LayoutDashboard,
        },
    ];
    const contentItems: NavItem[] = [
        ...(management?.canViewEvents
            ? [
                  {
                      title: 'Events',
                      href: eventsIndex(),
                      icon: CalendarDays,
                  },
              ]
            : []),
        ...(management?.canManageSeasons
            ? [
                  {
                      title: 'Seizoenen',
                      href: seasonsIndex(),
                      icon: Tags,
                  },
              ]
            : []),
    ];
    const systemItems: NavItem[] = [
        ...(management?.canViewUsers
            ? [
                  {
                      title: 'Gebruikers',
                      href: usersIndex(),
                      icon: Users,
                  },
              ]
            : []),
        ...(management?.canViewRoles
            ? [
                  {
                      title: 'Rollen en rechten',
                      href: RolePermissionController(),
                      icon: ShieldCheck,
                  },
              ]
            : []),
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
            <SidebarHeader className="border-b border-sidebar-border/70 pb-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-auto py-2 focus-visible:ring-signal-500/50"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-0 pt-1.5">
                <NavMain label="Werkruimte" items={workspaceItems} exact />
                {contentItems.length > 0 && (
                    <NavMain
                        label="Content"
                        items={contentItems}
                        exact={false}
                    />
                )}
                {systemItems.length > 0 && (
                    <NavMain
                        label="Systeem"
                        items={systemItems}
                        exact={false}
                    />
                )}
            </SidebarContent>

            <SidebarFooter className="gap-1 pt-2">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
