import { Form, Head, Link } from '@inertiajs/react';
import { Bot, CheckCircle2, Server, Trash2, Users } from 'lucide-react';
import DiscordInstallController from '@/actions/App/Http/Controllers/Discord/DiscordInstallController';
import DiscordServerController from '@/actions/App/Http/Controllers/Discord/DiscordServerController';
import DestructiveActionDialog from '@/components/destructive-action-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/dashboard/discord';
import { show } from '@/routes/dashboard/discord/servers';

type GuildChannel = {
    id: string;
    name: string;
};

type ConnectedServer = {
    id: number;
    name: string;
    discord_guild_id: string;
    discord_channel_id: string;
    discord_channel_name: string;
    icon_url: string | null;
    watched_players_count: number;
    channels: GuildChannel[];
    bot_installed: boolean;
    sync_error: string | null;
};

type Props = {
    discordConfigured: boolean;
    servers: ConnectedServer[];
    status?: string;
    error?: string | null;
};

export default function DiscordIndex({
    discordConfigured,
    servers,
    status,
    error,
}: Props) {
    return (
        <>
            <Head title="Discord" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Discord"
                    description="Install the bot on a server, then configure the channel that will receive the roasts."
                />

                {status && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        {status}
                    </div>
                )}

                {(error || !discordConfigured) && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-950 dark:bg-red-950/40 dark:text-red-300">
                        {error ??
                            'The Discord configuration is incomplete. Please enter DISCORD_CLIENT_ID, DISCORD_REDIRECT_URI, and DISCORD_BOT_TOKEN.'}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Bot className="size-4" />
                            Install the Discord bot
                        </CardTitle>
                        <CardDescription>
                            Click on the button, choose a server in Discord,
                            then you'll be redirected here. Gamesentry will
                            automatically link the server to a default text
                            channel, which you can change later.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-muted-foreground">
                        <p>1. Open Discord by clicking the button below.</p>
                        <p>2. Select the server on which to install the bot.</p>
                        <p>
                            3. You'll be redirected here to check the chat room
                            and manage players.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button asChild disabled={!discordConfigured}>
                            <a href={DiscordInstallController.redirect.url()}>
                                Add the bot to a server
                            </a>
                        </Button>
                    </CardFooter>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Connected servers</CardTitle>
                        <CardDescription>
                            Update the destination room, reinstall the bot if
                            necessary, or remove a server that no longer needs
                            to be monitored.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {servers.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                No Discord servers have been added yet.
                            </div>
                        ) : (
                            servers.map((server) => (
                                <div
                                    key={server.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex items-center gap-4">
                                            {server.icon_url ? (
                                                <img
                                                    src={server.icon_url}
                                                    alt={server.name}
                                                    className="size-12 rounded-lg object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-12 items-center justify-center rounded-lg bg-muted text-sm font-semibold">
                                                    {server.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </div>
                                            )}

                                            <div className="space-y-1">
                                                <p className="font-medium">
                                                    {server.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Current channel : #
                                                    {
                                                        server.discord_channel_name
                                                    }
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {
                                                        server.watched_players_count
                                                    }{' '}
                                                    tracked player(s).
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 text-sm">
                                            {server.bot_installed ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-green-700 dark:bg-green-950/40 dark:text-green-300">
                                                    <CheckCircle2 className="size-4" />
                                                    Active bot
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                    <Bot className="size-4" />
                                                    Offline bot
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {server.sync_error && (
                                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                                            {server.sync_error}
                                        </div>
                                    )}

                                    <div className="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                        <Form
                                            {...DiscordServerController.update.form(
                                                {
                                                    discordServer: server.id,
                                                },
                                            )}
                                            options={{ preserveScroll: true }}
                                            className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end"
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <div className="grid flex-1 gap-2">
                                                        <Label
                                                            htmlFor={`channel-${server.id}`}
                                                        >
                                                            Change channel
                                                        </Label>
                                                        <select
                                                            id={`channel-${server.id}`}
                                                            name="discord_channel_id"
                                                            defaultValue={
                                                                server.discord_channel_id
                                                            }
                                                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                            disabled={
                                                                processing ||
                                                                !server.bot_installed ||
                                                                server.sync_error !==
                                                                    null ||
                                                                server.channels
                                                                    .length ===
                                                                    0
                                                            }
                                                        >
                                                            {server.channels
                                                                .length ===
                                                            0 ? (
                                                                <option value="">
                                                                    No channel
                                                                    available
                                                                </option>
                                                            ) : (
                                                                server.channels.map(
                                                                    (
                                                                        channel,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                channel.id
                                                                            }
                                                                            value={
                                                                                channel.id
                                                                            }
                                                                        >
                                                                            #
                                                                            {
                                                                                channel.name
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )
                                                            )}
                                                        </select>
                                                        <InputError
                                                            message={
                                                                errors.discord_channel_id
                                                            }
                                                        />
                                                    </div>

                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        disabled={
                                                            processing ||
                                                            !server.bot_installed ||
                                                            server.sync_error !==
                                                                null ||
                                                            server.channels
                                                                .length === 0
                                                        }
                                                    >
                                                        Update
                                                    </Button>
                                                </>
                                            )}
                                        </Form>

                                        <div className="flex flex-wrap gap-3">
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={show({
                                                        discordServer:
                                                            server.id,
                                                    })}
                                                >
                                                    <Users className="size-4" />
                                                    Manage players
                                                </Link>
                                            </Button>

                                            {!server.bot_installed && (
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                >
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
                                                        <Server className="size-4" />
                                                        Reinstall bot
                                                    </a>
                                                </Button>
                                            )}

                                            <DestructiveActionDialog
                                                form={DiscordServerController.destroy.form(
                                                    {
                                                        discordServer:
                                                            server.id,
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
