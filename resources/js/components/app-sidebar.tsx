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
import DdsBrand from '@/components/dds-brand';
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
    useSidebar,
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
    const { state } = useSidebar();
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
            <SidebarHeader className="p-2 pb-1">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-16 justify-center rounded-xl border border-sidebar-border/80 bg-linear-to-br from-white via-flight-50/45 to-signal-50/70 p-0 text-sidebar-foreground shadow-xs group-data-[collapsible=icon]:rounded-lg hover:border-signal-300/60 hover:from-flight-50 hover:to-signal-100/70 hover:text-sidebar-foreground focus-visible:ring-signal-500/60 dark:border-white/10 dark:from-white/6 dark:via-white/4 dark:to-signal-500/10 dark:hover:border-signal-400/35 dark:hover:from-white/8 dark:hover:to-signal-500/15"
                        >
                            <Link
                                href={dashboard()}
                                prefetch
                                aria-label="Dutch Drone Squad dashboard"
                                data-testid="sidebar-brand"
                            >
                                <DdsBrand
                                    className={
                                        state === 'collapsed'
                                            ? 'relative z-10'
                                            : 'relative z-10 [&>span:first-child]:h-12 [&>span:first-child]:w-32 [&>span:first-child]:px-2'
                                    }
                                    compact={state === 'collapsed'}
                                    logoOnly
                                />
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
