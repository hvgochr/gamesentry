import { Head, Link } from '@inertiajs/react';
import { Bot, Pencil, Plus, Server, Trash2, Users } from 'lucide-react';
import DiscordInstallController from '@/actions/App/Http/Controllers/Discord/DiscordInstallController';
import DiscordServerController from '@/actions/App/Http/Controllers/Discord/DiscordServerController';
import DestructiveActionDialog from '@/components/destructive-action-dialog';
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
import { create, index } from '@/routes/dashboard/discord';
import { edit, show } from '@/routes/dashboard/discord/servers';

type ConnectedServer = {
    id: number;
    name: string;
    discord_guild_id: string;
    discord_channel_id: string;
    discord_channel_name: string;
    icon_url: string | null;
    watched_players_count: number;
    bot_installed_at: string | null;
    settings_synced_at: string | null;
};

type Props = {
    discordConfigured: boolean;
    canCreateServer: boolean;
    createServerLimitMessage: string | null;
    servers: ConnectedServer[];
    status?: string;
    error?: string | null;
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : 'Never';
}

export default function DiscordIndex({
    discordConfigured,
    canCreateServer,
    createServerLimitMessage,
    servers,
    status,
    error,
}: Props) {
    return (
        <>
            <Head title="Discord servers" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Discord servers"
                        description="Manage the servers connected to GameSentry and choose where match notifications are sent."
                    />

                    {discordConfigured && canCreateServer ? (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Add server
                            </Link>
                        </Button>
                    ) : (
                        <Button disabled>
                            <Plus className="size-4" />
                            Add server
                        </Button>
                    )}
                </div>

                {status && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        {status}
                    </div>
                )}

                {(error || !discordConfigured || createServerLimitMessage) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {error ??
                            createServerLimitMessage ??
                            'The Discord configuration is incomplete. Please enter DISCORD_CLIENT_ID, DISCORD_REDIRECT_URI, and DISCORD_BOT_TOKEN.'}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Server className="size-4" />
                            Linked servers
                        </CardTitle>
                        <CardDescription>
                            Open a server to manage tracked players, or edit its
                            destination channel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {servers.length === 0 ? (
                            <div className="flex flex-col gap-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                                <span>
                                    No Discord servers have been added yet.
                                </span>
                                {discordConfigured && canCreateServer ? (
                                    <Button asChild variant="outline">
                                        <Link href={create()}>
                                            <Plus className="size-4" />
                                            Add your first server
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" disabled>
                                        <Plus className="size-4" />
                                        Add your first server
                                    </Button>
                                )}
                            </div>
                        ) : (
                            servers.map((server) => (
                                <div
                                    key={server.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex min-w-0 items-center gap-4">
                                            {server.icon_url ? (
                                                <img
                                                    src={server.icon_url}
                                                    alt={server.name}
                                                    className="size-12 rounded-lg object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-muted text-sm font-semibold">
                                                    {server.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </div>
                                            )}

                                            <div className="min-w-0 space-y-1">
                                                <p className="truncate font-medium">
                                                    {server.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    #
                                                    {
                                                        server.discord_channel_name
                                                    }
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Synced{' '}
                                                    {formatDateTime(
                                                        server.settings_synced_at,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <Badge variant="secondary">
                                                <Users className="size-3" />
                                                {
                                                    server.watched_players_count
                                                }{' '}
                                                players
                                            </Badge>
                                            {server.bot_installed_at ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-green-200 text-green-700 dark:border-green-950 dark:text-green-300"
                                                >
                                                    <Bot className="size-3" />
                                                    Bot linked
                                                </Badge>
                                            ) : (
                                                <Badge
                                                    variant="outline"
                                                    className="border-amber-200 text-amber-700 dark:border-amber-950 dark:text-amber-300"
                                                >
                                                    <Bot className="size-3" />
                                                    Reconnect bot
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-4 flex flex-wrap gap-3">
                                        <Button asChild variant="outline">
                                            <Link
                                                href={show({
                                                    discordServer: server.id,
                                                })}
                                            >
                                                <Users className="size-4" />
                                                Details
                                            </Link>
                                        </Button>

                                        <Button asChild variant="outline">
                                            <Link
                                                href={edit({
                                                    discordServer: server.id,
                                                })}
                                            >
                                                <Pencil className="size-4" />
                                                Edit
                                            </Link>
                                        </Button>

                                        {!server.bot_installed_at && (
                                            <Button asChild variant="outline">
                                                <a
                                                    href={DiscordInstallController.redirect.url(
                                                        {
                                                            query: {
                                                                guild_id:
                                                                    server.discord_guild_id,
                                                            },
                                                        },
                                                    )}
                                                >
                                                    <Bot className="size-4" />
                                                    Reconnect
                                                </a>
                                            </Button>
                                        )}

                                        <DestructiveActionDialog
                                            form={DiscordServerController.destroy.form(
                                                {
                                                    discordServer: server.id,
                                                },
                                            )}
                                            title={`Remove ${server.name}?`}
                                            description="This will unlink the Discord server from your account and remove its tracked players and notification history. This cannot be undone."
                                            confirmLabel="Remove server"
                                        >
                                            <Button variant="destructive">
                                                <Trash2 className="size-4" />
                                                Remove
                                            </Button>
                                        </DestructiveActionDialog>
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

DiscordIndex.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: index(),
        },
    ],
};
