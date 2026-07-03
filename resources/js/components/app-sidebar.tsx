import { Link, usePage } from '@inertiajs/react';
import {
    Bot,
    CreditCard,
    FolderGit2,
    LayoutGrid,
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
import { index as dashboard } from '@/routes/dashboard';
import { index as admin } from '@/routes/dashboard/admin';
import { index as billing } from '@/routes/dashboard/billing';
import { index as discord } from '@/routes/dashboard/discord';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Discord',
        href: discord(),
        icon: Bot,
    },
    {
        title: 'Billing',
        href: billing(),
        icon: CreditCard,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/hvgochr/gamesentry',
        icon: FolderGit2,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const navItems = auth.can.view_admin
        ? [
              ...mainNavItems,
              {
                  title: 'Admin',
                  href: admin(),
                  icon: ShieldCheck,
              },
          ]
        : mainNavItems;

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
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
