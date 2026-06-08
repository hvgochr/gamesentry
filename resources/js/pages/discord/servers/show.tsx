import { Form, Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Trash2, UserPlus } from 'lucide-react';
import WatchedPlayerController from '@/actions/App/Http/Controllers/Discord/WatchedPlayerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { index as discordIndex } from '@/routes/dashboard/discord';

type Option = {
    value: string;
    label: string;
};

type Server = {
    id: number;
    name: string;
    discord_guild_id: string;
    discord_channel_name: string;
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
    is_active: boolean;
};

type RecentNotification = {
    id: number;
    game: string;
    riot_match_id: string;
    status: string;
    player_name: string | null;
    roast_text: string | null;
    failure_reason: string | null;
    delivered_at: string | null;
    created_at: string | null;
};

type Props = {
    server: Server;
    watchedPlayers: WatchedPlayer[];
    gameOptions: Option[];
    routingRegionOptions: Option[];
    recentNotifications: RecentNotification[];
    status?: string;
    error?: string | null;
};

const statusStyles: Record<string, string> = {
    sent: 'border-green-200 bg-green-50 text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300',
    pending:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300',
    failed: 'border-red-200 bg-red-50 text-red-700 dark:border-red-950 dark:bg-red-950/40 dark:text-red-300',
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : 'Jamais';
}

export default function DiscordServerShow({
    server,
    watchedPlayers,
    gameOptions,
    routingRegionOptions,
    recentNotifications,
    status,
    error,
}: Props) {
    const playerForm = useForm({
        game: gameOptions[0]?.value ?? 'lol',
        routing_region: routingRegionOptions[0]?.value ?? 'europe',
        game_name: '',
        tag_line: '',
        discord_user_id: '',
        is_active: true,
    });

    return (
        <>
            <Head title={`${server.name} - Tracked players`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title={server.name}
                        description={`Messages will be sent in #${server.discord_channel_name}.`}
                    />

                    <Button asChild variant="outline">
                        <Link href={discordIndex()}>
                            <ArrowLeft className="size-4" />
                            Back to servers
                        </Link>
                    </Button>
                </div>

                {status && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        {status}
                    </div>
                )}

                {error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-950 dark:bg-red-950/40 dark:text-red-300">
                        {error}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserPlus className="size-4" />
                            Add a player to track
                        </CardTitle>
                        <CardDescription>
                            The player is initialized with its last known game
                            to avoid any notification backfill.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="grid gap-4 lg:grid-cols-2"
                            onSubmit={(event) => {
                                event.preventDefault();

                                playerForm.post(
                                    WatchedPlayerController.store.url({
                                        discordServer: server.id,
                                    }),
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            playerForm.reset(
                                                'game_name',
                                                'tag_line',
                                                'discord_user_id',
                                            ),
                                    },
                                );
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="game">Game</Label>
                                <select
                                    id="game"
                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={playerForm.data.game}
                                    onChange={(event) =>
                                        playerForm.setData(
                                            'game',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {gameOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={playerForm.errors.game} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="routing_region">
                                    Riot routing region
                                </Label>
                                <select
                                    id="routing_region"
                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={playerForm.data.routing_region}
                                    onChange={(event) =>
                                        playerForm.setData(
                                            'routing_region',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {routingRegionOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={playerForm.errors.routing_region}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="game_name">gameName</Label>
                                <input
                                    id="game_name"
                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={playerForm.data.game_name}
                                    onChange={(event) =>
                                        playerForm.setData(
                                            'game_name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="SummonerName"
                                />
                                <InputError
                                    message={playerForm.errors.game_name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tag_line">tagLine</Label>
                                <input
                                    id="tag_line"
                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm uppercase shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={playerForm.data.tag_line}
                                    onChange={(event) =>
                                        playerForm.setData(
                                            'tag_line',
                                            event.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="EUW"
                                />
                                <InputError
                                    message={playerForm.errors.tag_line}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="discord_user_id">
                                    Discord user id
                                </Label>
                                <input
                                    id="discord_user_id"
                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={playerForm.data.discord_user_id}
                                    onChange={(event) =>
                                        playerForm.setData(
                                            'discord_user_id',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="123456789012345678"
                                />
                                <InputError
                                    message={playerForm.errors.discord_user_id}
                                />
                            </div>

                            <div className="flex items-end">
                                <label className="flex items-center gap-3 text-sm text-muted-foreground">
                                    <input
                                        type="checkbox"
                                        checked={playerForm.data.is_active}
                                        onChange={(event) =>
                                            playerForm.setData(
                                                'is_active',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Active tracking
                                </label>
                            </div>

                            <div className="lg:col-span-2">
                                <Button
                                    type="submit"
                                    disabled={playerForm.processing}
                                >
                                    Add player
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tracked players</CardTitle>
                        <CardDescription>
                            Update the Riot IDs, the Discord member to ping, or
                            stop monitoring.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {watchedPlayers.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                No players are currently being tracked on this
                                server.
                            </div>
                        ) : (
                            watchedPlayers.map((player) => (
                                <div
                                    key={player.id}
                                    className="rounded-xl border p-4"
                                >
                                    <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                {player.game_name}#
                                                {player.tag_line}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {player.game.toUpperCase()} -
                                                ping Discord{' '}
                                                {player.discord_user_id}
                                            </p>
                                        </div>
                                        <div className="text-right text-xs text-muted-foreground">
                                            <p>
                                                Last game known :{' '}
                                                {player.last_seen_match_id ??
                                                    'aucun'}
                                            </p>
                                            <p>
                                                Next poll :{' '}
                                                {player.next_poll_at
                                                    ? new Date(
                                                          player.next_poll_at,
                                                      ).toLocaleString('fr-FR')
                                                    : 'non planifie'}
                                            </p>
                                        </div>
                                    </div>

                                    <Form
                                        {...WatchedPlayerController.update.form(
                                            {
                                                discordServer: server.id,
                                                watchedPlayer: player.id,
                                            },
                                        )}
                                        options={{ preserveScroll: true }}
                                        className="grid gap-4 lg:grid-cols-2"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`game-${player.id}`}
                                                    >
                                                        Game
                                                    </Label>
                                                    <select
                                                        id={`game-${player.id}`}
                                                        name="game"
                                                        defaultValue={
                                                            player.game
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    >
                                                        {gameOptions.map(
                                                            (option) => (
                                                                <option
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        message={errors.game}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`routing-${player.id}`}
                                                    >
                                                        Riot routing region
                                                    </Label>
                                                    <select
                                                        id={`routing-${player.id}`}
                                                        name="routing_region"
                                                        defaultValue={
                                                            player.routing_region
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    >
                                                        {routingRegionOptions.map(
                                                            (option) => (
                                                                <option
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors.routing_region
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`game-name-${player.id}`}
                                                    >
                                                        gameName
                                                    </Label>
                                                    <input
                                                        id={`game-name-${player.id}`}
                                                        name="game_name"
                                                        defaultValue={
                                                            player.game_name
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.game_name
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`tag-line-${player.id}`}
                                                    >
                                                        tagLine
                                                    </Label>
                                                    <input
                                                        id={`tag-line-${player.id}`}
                                                        name="tag_line"
                                                        defaultValue={
                                                            player.tag_line
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm uppercase shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.tag_line
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`discord-user-${player.id}`}
                                                    >
                                                        Discord user id
                                                    </Label>
                                                    <input
                                                        id={`discord-user-${player.id}`}
                                                        name="discord_user_id"
                                                        defaultValue={
                                                            player.discord_user_id
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.discord_user_id
                                                        }
                                                    />
                                                </div>

                                                <div className="flex items-end">
                                                    <label className="flex items-center gap-3 text-sm text-muted-foreground">
                                                        <input
                                                            type="hidden"
                                                            name="is_active"
                                                            value="0"
                                                        />
                                                        <input
                                                            type="checkbox"
                                                            name="is_active"
                                                            value="1"
                                                            defaultChecked={
                                                                player.is_active
                                                            }
                                                        />
                                                        Active tracking
                                                    </label>
                                                </div>

                                                <div className="flex flex-wrap gap-3 lg:col-span-2">
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        disabled={processing}
                                                    >
                                                        <Save className="size-4" />
                                                        Save
                                                    </Button>
                                                </div>
                                            </>
                                        )}
                                    </Form>

                                    <div className="mt-3">
                                        <Form
                                            {...WatchedPlayerController.destroy.form(
                                                {
                                                    discordServer: server.id,
                                                    watchedPlayer: player.id,
                                                },
                                            )}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    variant="destructive"
                                                    disabled={processing}
                                                >
                                                    <Trash2 className="size-4" />
                                                    Remove
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Notifications history</CardTitle>
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
                                        <div>
                                            <p className="font-medium">
                                                {notification.player_name ??
                                                    'Unknown player'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {notification.game.toUpperCase()}{' '}
                                                - {notification.riot_match_id}
                                            </p>
                                        </div>

                                        <div
                                            className={`rounded-full border px-3 py-1 text-xs font-medium ${statusStyles[notification.status] ?? statusStyles.pending}`}
                                        >
                                            {notification.status}
                                        </div>
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
                                            Created at{' '}
                                            {formatDateTime(
                                                notification.created_at,
                                            )}{' '}
                                            - Delivered at{' '}
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
