import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Bell,
    Clock,
    Pencil,
    Plus,
    Settings,
    Trash2,
    UserRound,
} from 'lucide-react';
import WatchedPlayerController from '@/actions/App/Http/Controllers/Discord/WatchedPlayerController';
import DestructiveActionDialog from '@/components/destructive-action-dialog';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as discordIndex } from '@/routes/dashboard/discord';
import { edit as editServer } from '@/routes/dashboard/discord/servers';
import {
    create as createWatchedPlayer,
    edit as editWatchedPlayer,
} from '@/routes/dashboard/discord/servers/watched-players';

type Server = {
    id: number;
    name: string;
    discord_guild_id: string;
    discord_channel_name: string;
    bot_installed_at: string | null;
    settings_synced_at: string | null;
};

type WatchedPlayer = {
    id: number;
    game: string;
    routing_region: string;
    game_name: string;
    tag_line: string;
    discord_user_id: string;
    last_seen_match_id: string | null;
    last_polled_at: string | null;
    next_poll_at: string | null;
    poll_interval_seconds: number;
    profile_icon_url: string | null;
    profile_refreshed_at: string | null;
    is_active: boolean;
};

type RecentNotification = {
    id: number;
    game: string;
    riot_match_id: string;
    status: string;
    player_name: string | null;
    profile_icon_url: string | null;
    champion_icon_url: string | null;
    roast_text: string | null;
    failure_reason: string | null;
    delivered_at: string | null;
    created_at: string | null;
};

type Props = {
    server: Server;
    watchedPlayers: WatchedPlayer[];
    canCreateWatchedPlayer: boolean;
    createWatchedPlayerLimitMessage: string | null;
    recentNotifications: RecentNotification[];
    status?: string;
    error?: string | null;
};

const statusStyles: Record<string, string> = {
    sent: 'border-green-200 text-green-700 dark:border-green-950 dark:text-green-300',
    pending:
        'border-amber-200 text-amber-700 dark:border-amber-950 dark:text-amber-300',
    failed: 'border-red-200 text-red-700 dark:border-red-950 dark:text-red-300',
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : 'Never';
}

export default function DiscordServerShow({
    server,
    watchedPlayers,
    canCreateWatchedPlayer,
    createWatchedPlayerLimitMessage,
    recentNotifications,
    status,
    error,
}: Props) {
    return (
        <>
            <Head title={`${server.name} - Tracked players`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title={server.name}
                        description={`Messages will be sent in #${server.discord_channel_name}.`}
                    />

                    <div className="flex flex-wrap gap-3">
                        <Button asChild variant="outline">
                            <Link href={discordIndex()}>
                                <ArrowLeft className="size-4" />
                                Servers
                            </Link>
                        </Button>

                        <Button asChild variant="outline">
                            <Link
                                href={editServer({ discordServer: server.id })}
                            >
                                <Settings className="size-4" />
                                Settings
                            </Link>
                        </Button>

                        {canCreateWatchedPlayer ? (
                            <Button asChild>
                                <Link
                                    href={createWatchedPlayer({
                                        discordServer: server.id,
                                    })}
                                >
                                    <Plus className="size-4" />
                                    Add player
                                </Link>
                            </Button>
                        ) : (
                            <Button disabled>
                                <Plus className="size-4" />
                                Add player
                            </Button>
                        )}
                    </div>
                </div>

                {status && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        {status}
                    </div>
                )}

                {(error || createWatchedPlayerLimitMessage) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {error ?? createWatchedPlayerLimitMessage}
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Tracked players
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {watchedPlayers.length}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Bot linked
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {formatDateTime(server.bot_installed_at)}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">
                                Settings synced
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {formatDateTime(server.settings_synced_at)}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserRound className="size-4" />
                            Tracked players
                        </CardTitle>
                        <CardDescription>
                            Open a player to update its Riot ID, Discord member,
                            or tracking status.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {watchedPlayers.length === 0 ? (
                            <div className="flex flex-col gap-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                                <span>
                                    No players are currently being tracked on
                                    this server.
                                </span>
                                {canCreateWatchedPlayer ? (
                                    <Button asChild variant="outline">
                                        <Link
                                            href={createWatchedPlayer({
                                                discordServer: server.id,
                                            })}
                                        >
                                            <Plus className="size-4" />
                                            Add player
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" disabled>
                                        <Plus className="size-4" />
                                        Add player
                                    </Button>
                                )}
                            </div>
                        ) : (
                            watchedPlayers.map((player) => (
                                <div
                                    key={player.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex min-w-0 gap-3">
                                            <Avatar className="size-11 border">
                                                <AvatarImage
                                                    src={
                                                        player.profile_icon_url ??
                                                        undefined
                                                    }
                                                    alt={`${player.game_name} profile icon`}
                                                    loading="lazy"
                                                />
                                                <AvatarFallback>
                                                    <UserRound className="size-5 text-muted-foreground" />
                                                </AvatarFallback>
                                            </Avatar>

                                            <div className="space-y-2">
                                                <div>
                                                    <p className="font-medium">
                                                        {player.game_name}#
                                                        {player.tag_line}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {player.game.toUpperCase()}{' '}
                                                        ·{' '}
                                                        {player.routing_region}{' '}
                                                        · ping{' '}
                                                        {player.discord_user_id}
                                                    </p>
                                                    {player.profile_refreshed_at && (
                                                        <p className="text-xs text-muted-foreground">
                                                            Profile refreshed{' '}
                                                            {formatDateTime(
                                                                player.profile_refreshed_at,
                                                            )}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex flex-wrap gap-2">
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            player.is_active
                                                                ? 'border-green-200 text-green-700 dark:border-green-950 dark:text-green-300'
                                                                : 'border-muted-foreground/30 text-muted-foreground'
                                                        }
                                                    >
                                                        {player.is_active
                                                            ? 'Active'
                                                            : 'Paused'}
                                                    </Badge>
                                                    <Badge variant="secondary">
                                                        <Clock className="size-3" />
                                                        Next poll{' '}
                                                        {formatDateTime(
                                                            player.next_poll_at,
                                                        )}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-3">
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={editWatchedPlayer({
                                                        discordServer:
                                                            server.id,
                                                        watchedPlayer:
                                                            player.id,
                                                    })}
                                                >
                                                    <Pencil className="size-4" />
                                                    Edit
                                                </Link>
                                            </Button>

                                            <DestructiveActionDialog
                                                form={WatchedPlayerController.destroy.form(
                                                    {
                                                        discordServer:
                                                            server.id,
                                                        watchedPlayer:
                                                            player.id,
                                                    },
                                                )}
                                                title={`Remove ${player.game_name}#${player.tag_line}?`}
                                                description="This player will no longer be tracked on this Discord server. Existing notification history will also be removed."
                                                confirmLabel="Remove player"
                                            >
                                                <Button variant="destructive">
                                                    <Trash2 className="size-4" />
                                                    Remove
                                                </Button>
                                            </DestructiveActionDialog>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Bell className="size-4" />
                            Notifications history
                        </CardTitle>
                        <CardDescription>
                            The latest messages posted or sent to this Discord
                            server.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {recentNotifications.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                There are currently no match notifications for
                                this server.
                            </div>
                        ) : (
                            recentNotifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <div className="relative shrink-0">
                                                <Avatar className="size-11 border">
                                                    <AvatarImage
                                                        src={
                                                            notification.profile_icon_url ??
                                                            undefined
                                                        }
                                                        alt={`${notification.player_name ?? 'Player'} profile icon`}
                                                        loading="lazy"
                                                    />
                                                    <AvatarFallback>
                                                        <UserRound className="size-5 text-muted-foreground" />
                                                    </AvatarFallback>
                                                </Avatar>
                                                {notification.champion_icon_url && (
                                                    <img
                                                        src={
                                                            notification.champion_icon_url
                                                        }
                                                        alt="Played champion"
                                                        className="absolute -right-2 -bottom-1 size-6 rounded-md border-2 border-background bg-background object-cover"
                                                        loading="lazy"
                                                    />
                                                )}
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {notification.player_name ??
                                                        'Unknown player'}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {notification.game.toUpperCase()}{' '}
                                                    ·{' '}
                                                    {notification.riot_match_id}
                                                </p>
                                            </div>
                                        </div>

                                        <Badge
                                            variant="outline"
                                            className={
                                                statusStyles[
                                                    notification.status
                                                ] ?? statusStyles.pending
                                            }
                                        >
                                            {notification.status}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2 text-sm">
                                        {notification.roast_text && (
                                            <p>{notification.roast_text}</p>
                                        )}

                                        {notification.failure_reason && (
                                            <p className="text-red-600 dark:text-red-300">
                                                {notification.failure_reason}
                                            </p>
                                        )}

                                        <p className="text-xs text-muted-foreground">
                                            Created{' '}
                                            {formatDateTime(
                                                notification.created_at,
                                            )}{' '}
                                            · Delivered{' '}
                                            {formatDateTime(
                                                notification.delivered_at,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DiscordServerShow.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: discordIndex(),
        },
    ],
};
