import { Form, Head } from '@inertiajs/react';
import {
    destroy as destroyTrackedPlayer,
    store as storeTrackedPlayer,
    update as updateTrackedPlayer,
} from '@/actions/App/Http/Controllers/TrackedPlayerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index as playersIndex } from '@/routes/players';
import type { BreadcrumbItem } from '@/types';

type ServerOption = {
    id: number;
    name: string;
    discord_guild_id: string;
};

type GameOption = {
    value: string;
    label: string;
};

type TrackedPlayerItem = {
    id: number;
    discord_server_id: number;
    server_name: string | null;
    game: string;
    game_label: string;
    riot_name: string;
    riot_tagline: string;
    riot_id: string;
    region: string;
    discord_user_id: string;
    is_active: boolean;
};

type PlayersPageProps = {
    summary: {
        playerCount: number;
        activePlayerCount: number;
    };
    servers: ServerOption[];
    gameOptions: GameOption[];
    trackedPlayers: TrackedPlayerItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Players',
        href: playersIndex(),
    },
];

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

export default function Players({
    summary,
    servers,
    gameOptions,
    trackedPlayers,
}: PlayersPageProps) {
    const hasServers = servers.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Players" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                    <Card>
                        <CardHeader>
                            <Heading
                                title="Tracked Riot players"
                                description="Link Riot IDs to Discord members so the bot knows who to ping when a new match is detected."
                            />
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-2xl border border-border/60 p-4">
                                <p className="text-muted-foreground text-sm">
                                    Tracked players
                                </p>
                                <p className="mt-2 text-3xl font-semibold">
                                    {summary.playerCount}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-border/60 p-4">
                                <p className="text-muted-foreground text-sm">
                                    Active tracking
                                </p>
                                <p className="mt-2 text-3xl font-semibold">
                                    {summary.activePlayerCount}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Add a tracked player</CardTitle>
                            <CardDescription>
                                Choose a server, the Riot title, and the Discord
                                ID that should be pinged.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {hasServers ? (
                                <Form
                                    {...storeTrackedPlayer.form()}
                                    resetOnSuccess
                                    className="grid gap-4 md:grid-cols-2"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="discord_server_id">
                                                    Discord server
                                                </Label>
                                                <select
                                                    id="discord_server_id"
                                                    name="discord_server_id"
                                                    className={selectClassName}
                                                    defaultValue={String(
                                                        servers[0].id,
                                                    )}
                                                >
                                                    {servers.map((server) => (
                                                        <option
                                                            key={server.id}
                                                            value={server.id}
                                                        >
                                                            {server.name} (
                                                            {
                                                                server.discord_guild_id
                                                            }
                                                            )
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors.discord_server_id
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="game">
                                                    Game
                                                </Label>
                                                <select
                                                    id="game"
                                                    name="game"
                                                    className={selectClassName}
                                                    defaultValue={
                                                        gameOptions[0]?.value
                                                    }
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
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={errors.game}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="riot_name">
                                                    Riot username
                                                </Label>
                                                <Input
                                                    id="riot_name"
                                                    name="riot_name"
                                                    placeholder="Faker"
                                                />
                                                <InputError
                                                    message={errors.riot_name}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="riot_tagline">
                                                    Riot tagline
                                                </Label>
                                                <Input
                                                    id="riot_tagline"
                                                    name="riot_tagline"
                                                    placeholder="EUW"
                                                />
                                                <InputError
                                                    message={
                                                        errors.riot_tagline
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="region">
                                                    Region
                                                </Label>
                                                <Input
                                                    id="region"
                                                    name="region"
                                                    placeholder="euw"
                                                />
                                                <InputError
                                                    message={errors.region}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="discord_user_id">
                                                    Discord user ID
                                                </Label>
                                                <Input
                                                    id="discord_user_id"
                                                    name="discord_user_id"
                                                    placeholder="123456789012345678"
                                                />
                                                <InputError
                                                    message={
                                                        errors.discord_user_id
                                                    }
                                                />
                                            </div>

                                            <div className="flex items-end md:col-span-2">
                                                <label className="flex items-center gap-3 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium">
                                                    <input
                                                        type="hidden"
                                                        name="is_active"
                                                        value="0"
                                                    />
                                                    <input
                                                        type="checkbox"
                                                        name="is_active"
                                                        value="1"
                                                        defaultChecked
                                                        className="size-4 rounded border-border"
                                                    />
                                                    Start tracking immediately
                                                </label>
                                            </div>

                                            <div className="md:col-span-2">
                                                <Button disabled={processing}>
                                                    Add player
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-border/80 p-6 text-sm text-muted-foreground">
                                    You need at least one Discord server before
                                    you can add tracked Riot players.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section>
                    <Card>
                        <CardHeader>
                            <CardTitle>Tracked players</CardTitle>
                            <CardDescription>
                                Edit Riot IDs, move players between servers, or
                                pause tracking when needed.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {trackedPlayers.length > 0 ? (
                                trackedPlayers.map((trackedPlayer) => (
                                    <Card
                                        key={trackedPlayer.id}
                                        className="gap-4 border-border/60 bg-background/70 py-0 shadow-none"
                                    >
                                        <CardHeader className="gap-3 border-b border-border/60 py-6">
                                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                                <div>
                                                    <CardTitle>
                                                        {trackedPlayer.riot_id}
                                                    </CardTitle>
                                                    <CardDescription>
                                                        {trackedPlayer.game_label}{' '}
                                                        · {trackedPlayer.region}{' '}
                                                        ·{' '}
                                                        {trackedPlayer.server_name ??
                                                            'No server'}
                                                    </CardDescription>
                                                </div>

                                                <div className="flex flex-wrap gap-2">
                                                    <Badge
                                                        variant="secondary"
                                                        className="rounded-full px-3 py-1"
                                                    >
                                                        Discord ID:{' '}
                                                        {
                                                            trackedPlayer.discord_user_id
                                                        }
                                                    </Badge>
                                                    <Badge
                                                        variant={
                                                            trackedPlayer.is_active
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                        className="rounded-full px-3 py-1"
                                                    >
                                                        {trackedPlayer.is_active
                                                            ? 'Tracking live'
                                                            : 'Paused'}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4 py-6">
                                            <Form
                                                {...updateTrackedPlayer.form(
                                                    trackedPlayer.id,
                                                )}
                                                className="grid gap-4 md:grid-cols-2"
                                            >
                                                {({ errors, processing }) => (
                                                    <>
                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`discord_server_id-${trackedPlayer.id}`}
                                                            >
                                                                Discord server
                                                            </Label>
                                                            <select
                                                                id={`discord_server_id-${trackedPlayer.id}`}
                                                                name="discord_server_id"
                                                                className={
                                                                    selectClassName
                                                                }
                                                                defaultValue={String(
                                                                    trackedPlayer.discord_server_id,
                                                                )}
                                                            >
                                                                {servers.map(
                                                                    (server) => (
                                                                        <option
                                                                            key={
                                                                                server.id
                                                                            }
                                                                            value={
                                                                                server.id
                                                                            }
                                                                        >
                                                                            {
                                                                                server.name
                                                                            }{' '}
                                                                            (
                                                                            {
                                                                                server.discord_guild_id
                                                                            }
                                                                            )
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                            <InputError
                                                                message={
                                                                    errors.discord_server_id
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`game-${trackedPlayer.id}`}
                                                            >
                                                                Game
                                                            </Label>
                                                            <select
                                                                id={`game-${trackedPlayer.id}`}
                                                                name="game"
                                                                className={
                                                                    selectClassName
                                                                }
                                                                defaultValue={
                                                                    trackedPlayer.game
                                                                }
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
                                                                message={
                                                                    errors.game
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`riot_name-${trackedPlayer.id}`}
                                                            >
                                                                Riot username
                                                            </Label>
                                                            <Input
                                                                id={`riot_name-${trackedPlayer.id}`}
                                                                name="riot_name"
                                                                defaultValue={
                                                                    trackedPlayer.riot_name
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.riot_name
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`riot_tagline-${trackedPlayer.id}`}
                                                            >
                                                                Riot tagline
                                                            </Label>
                                                            <Input
                                                                id={`riot_tagline-${trackedPlayer.id}`}
                                                                name="riot_tagline"
                                                                defaultValue={
                                                                    trackedPlayer.riot_tagline
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.riot_tagline
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`region-${trackedPlayer.id}`}
                                                            >
                                                                Region
                                                            </Label>
                                                            <Input
                                                                id={`region-${trackedPlayer.id}`}
                                                                name="region"
                                                                defaultValue={
                                                                    trackedPlayer.region
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.region
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`discord_user_id-${trackedPlayer.id}`}
                                                            >
                                                                Discord user ID
                                                            </Label>
                                                            <Input
                                                                id={`discord_user_id-${trackedPlayer.id}`}
                                                                name="discord_user_id"
                                                                defaultValue={
                                                                    trackedPlayer.discord_user_id
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.discord_user_id
                                                                }
                                                            />
                                                        </div>

                                                        <div className="flex items-end md:col-span-2">
                                                            <label className="flex items-center gap-3 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium">
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
                                                                        trackedPlayer.is_active
                                                                    }
                                                                    className="size-4 rounded border-border"
                                                                />
                                                                Tracking enabled
                                                            </label>
                                                        </div>

                                                        <div className="md:col-span-2">
                                                            <Button
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Save changes
                                                            </Button>
                                                        </div>
                                                    </>
                                                )}
                                            </Form>

                                            <Form
                                                {...destroyTrackedPlayer.form(
                                                    trackedPlayer.id,
                                                )}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        variant="destructive"
                                                        disabled={processing}
                                                    >
                                                        Delete player
                                                    </Button>
                                                )}
                                            </Form>
                                        </CardContent>
                                    </Card>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-border/80 p-6 text-sm text-muted-foreground">
                                    No tracked players yet. Add one above once
                                    you have at least one Discord server
                                    configured.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
