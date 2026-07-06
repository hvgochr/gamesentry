import { Link, usePage } from '@inertiajs/react';
import { Bot, CreditCard, LayoutGrid, ShieldCheck } from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { show as discordServer } from '@/routes/dashboard/discord/servers';
import type { NavItem } from '@/types';

const baseNavItems: NavItem[] = [
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

export function AppSidebar() {
    const { auth, navigation } = usePage().props;
    const mainNavItems = baseNavItems.map((item) => {
        if (item.title !== 'Discord') {
            return item;
        }

        return {
            ...item,
            items: navigation.discord_servers.map((server) => ({
                title: server.name,
                href: discordServer({ discordServer: server.id }),
            })),
        };
    });

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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
