import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Flame, Radar, Server, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as playersIndex } from '@/routes/players';
import { index as serversIndex } from '@/routes/servers';
import type { BreadcrumbItem } from '@/types';

type DashboardProps = {
    stats: {
        serverCount: number;
        playerCount: number;
        activePlayerCount: number;
        roastEnabledCount: number;
    };
    servers: Array<{
        id: number;
        name: string;
        discord_guild_id: string;
        alert_channel_name: string | null;
        alert_channel_id: string | null;
        roast_enabled: boolean;
        tracked_players_count: number;
    }>;
    trackedPlayers: Array<{
        id: number;
        riot_id: string;
        game: string;
        game_label: string;
        region: string;
        discord_user_id: string;
        is_active: boolean;
        server_name: string | null;
    }>;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

export default function Dashboard({
    stats,
    servers,
    trackedPlayers,
}: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="rounded-3xl border border-border/70 bg-card/60 p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-3">
                            <Badge
                                variant="secondary"
                                className="rounded-full px-3 py-1 text-sm"
                            >
                                Gamesentry control center
                            </Badge>
                            <Heading
                                title="Manage Discord servers and tracked Riot players"
                                description="This is the first working Gamesentry dashboard slice: define where match alerts go, choose who gets pinged, and prepare the bot for real match monitoring."
                            />
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button asChild>
                                <Link href={serversIndex()} prefetch>
                                    Manage servers
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={playersIndex()} prefetch>
                                    Manage players
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader className="gap-3">
                            <Badge
                                variant="outline"
                                className="w-fit rounded-full px-3 py-1"
                            >
                                <Server className="size-3.5" />
                                Servers
                            </Badge>
                            <CardTitle className="text-3xl">
                                {stats.serverCount}
                            </CardTitle>
                            <CardDescription>
                                Discord servers configured in your dashboard.
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader className="gap-3">
                            <Badge
                                variant="outline"
                                className="w-fit rounded-full px-3 py-1"
                            >
                                <Users className="size-3.5" />
                                Players
                            </Badge>
                            <CardTitle className="text-3xl">
                                {stats.playerCount}
                            </CardTitle>
                            <CardDescription>
                                Riot players currently tracked across your servers.
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader className="gap-3">
                            <Badge
                                variant="outline"
                                className="w-fit rounded-full px-3 py-1"
                            >
                                <Radar className="size-3.5" />
                                Active tracking
                            </Badge>
                            <CardTitle className="text-3xl">
                                {stats.activePlayerCount}
                            </CardTitle>
                            <CardDescription>
                                Players ready for match polling and Discord pings.
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader className="gap-3">
                            <Badge
                                variant="outline"
                                className="w-fit rounded-full px-3 py-1"
                            >
                                <Flame className="size-3.5" />
                                Roast mode
                            </Badge>
                            <CardTitle className="text-3xl">
                                {stats.roastEnabledCount}
                            </CardTitle>
                            <CardDescription>
                                Servers with AI roast messages enabled.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card>
                        <CardHeader className="flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle>Configured servers</CardTitle>
                                <CardDescription>
                                    Your latest Discord destinations for Gamesentry
                                    alerts.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" asChild>
                                <Link href={serversIndex()} prefetch>
                                    View all
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {servers.length > 0 ? (
                                servers.map((server) => (
                                    <div
                                        key={server.id}
                                        className="flex flex-col gap-3 rounded-2xl border border-border/60 p-4 sm:flex-row sm:items-start sm:justify-between"
                                    >
                                        <div className="space-y-1">
                                            <p className="font-medium">
                                                {server.name}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                Guild ID: {server.discord_guild_id}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                Channel:{' '}
                                                {server.alert_channel_name
                                                    ? `#${server.alert_channel_name}`
                                                    : 'Not set yet'}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <Badge
                                                variant="secondary"
                                                className="rounded-full px-3 py-1"
                                            >
                                                {server.tracked_players_count}{' '}
                                                player
                                                {server.tracked_players_count === 1
                                                    ? ''
                                                    : 's'}
                                            </Badge>
                                            <Badge
                                                variant={
                                                    server.roast_enabled
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="rounded-full px-3 py-1"
                                            >
                                                {server.roast_enabled
                                                    ? 'Roasts on'
                                                    : 'Roasts off'}
                                            </Badge>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-border/80 p-6 text-sm text-muted-foreground">
                                    No Discord servers configured yet. Start by
                                    creating one so you can attach channels and
                                    tracked players.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle>Tracked Riot players</CardTitle>
                                <CardDescription>
                                    Recent player entries linked to your Discord
                                    workflow.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" asChild>
                                <Link href={playersIndex()} prefetch>
                                    View all
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {trackedPlayers.length > 0 ? (
                                trackedPlayers.map((trackedPlayer) => (
                                    <div
                                        key={trackedPlayer.id}
                                        className="space-y-2 rounded-2xl border border-border/60 p-4"
                                    >
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <p className="font-medium">
                                                {trackedPlayer.riot_id}
                                            </p>
                                            <Badge
                                                variant={
                                                    trackedPlayer.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="rounded-full px-3 py-1"
                                            >
                                                {trackedPlayer.is_active
                                                    ? 'Active'
                                                    : 'Paused'}
                                            </Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {trackedPlayer.game_label} ·{' '}
                                            {trackedPlayer.region} ·{' '}
                                            {trackedPlayer.server_name ??
                                                'No server'}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Discord ping target:{' '}
                                            {trackedPlayer.discord_user_id}
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-border/80 p-6 text-sm text-muted-foreground">
                                    No tracked players yet. Add a Riot player once
                                    you have a Discord server configured.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
