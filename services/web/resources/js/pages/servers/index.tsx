import { Form, Head } from '@inertiajs/react';
import {
    destroy as destroyServer,
    store as storeServer,
    update as updateServer,
} from '@/actions/App/Http/Controllers/DiscordServerController';
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
import { index as serversIndex } from '@/routes/servers';
import type { BreadcrumbItem } from '@/types';

type ServerItem = {
    id: number;
    name: string;
    discord_guild_id: string;
    alert_channel_name: string | null;
    alert_channel_id: string | null;
    roast_enabled: boolean;
    tracked_players_count: number;
};

type ServersPageProps = {
    summary: {
        serverCount: number;
        trackedPlayerCount: number;
    };
    servers: ServerItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Servers',
        href: serversIndex(),
    },
];

export default function DiscordServers({
    summary,
    servers,
}: ServersPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Servers" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                    <Card>
                        <CardHeader>
                            <Heading
                                title="Discord servers"
                                description="Create the server destinations Gamesentry should manage before you start adding tracked Riot players."
                            />
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-2xl border border-border/60 p-4">
                                <p className="text-muted-foreground text-sm">
                                    Configured servers
                                </p>
                                <p className="mt-2 text-3xl font-semibold">
                                    {summary.serverCount}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-border/60 p-4">
                                <p className="text-muted-foreground text-sm">
                                    Tracked players linked
                                </p>
                                <p className="mt-2 text-3xl font-semibold">
                                    {summary.trackedPlayerCount}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Create a server</CardTitle>
                            <CardDescription>
                                Enter the Discord guild and channel details the
                                bot should use.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...storeServer.form()}
                                resetOnSuccess
                                className="grid gap-4 md:grid-cols-2"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="name">
                                                Display name
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="Gamesentry EU"
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="discord_guild_id">
                                                Discord guild ID
                                            </Label>
                                            <Input
                                                id="discord_guild_id"
                                                name="discord_guild_id"
                                                placeholder="123456789012345678"
                                            />
                                            <InputError
                                                message={errors.discord_guild_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="alert_channel_id">
                                                Alert channel ID
                                            </Label>
                                            <Input
                                                id="alert_channel_id"
                                                name="alert_channel_id"
                                                placeholder="987654321098765432"
                                            />
                                            <InputError
                                                message={errors.alert_channel_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="alert_channel_name">
                                                Alert channel name
                                            </Label>
                                            <Input
                                                id="alert_channel_name"
                                                name="alert_channel_name"
                                                placeholder="match-feed"
                                            />
                                            <InputError
                                                message={
                                                    errors.alert_channel_name
                                                }
                                            />
                                        </div>

                                        <div className="flex items-end">
                                            <label className="flex items-center gap-3 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium">
                                                <input
                                                    type="hidden"
                                                    name="roast_enabled"
                                                    value="0"
                                                />
                                                <input
                                                    type="checkbox"
                                                    name="roast_enabled"
                                                    value="1"
                                                    defaultChecked
                                                    className="size-4 rounded border-border"
                                                />
                                                Enable roast messages
                                            </label>
                                        </div>

                                        <div className="md:col-span-2">
                                            <Button disabled={processing}>
                                                Create server
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </section>

                <section>
                    <Card>
                        <CardHeader>
                            <CardTitle>Configured servers</CardTitle>
                            <CardDescription>
                                Update channel details, toggle roast mode, or
                                remove a server from the dashboard.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {servers.length > 0 ? (
                                servers.map((server) => (
                                    <Card
                                        key={server.id}
                                        className="gap-4 border-border/60 bg-background/70 py-0 shadow-none"
                                    >
                                        <CardHeader className="gap-3 border-b border-border/60 py-6">
                                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                                <div>
                                                    <CardTitle>
                                                        {server.name}
                                                    </CardTitle>
                                                    <CardDescription>
                                                        Guild ID:{' '}
                                                        {server.discord_guild_id}
                                                    </CardDescription>
                                                </div>

                                                <div className="flex flex-wrap gap-2">
                                                    <Badge
                                                        variant="secondary"
                                                        className="rounded-full px-3 py-1"
                                                    >
                                                        {
                                                            server.tracked_players_count
                                                        }{' '}
                                                        tracked player
                                                        {server.tracked_players_count ===
                                                        1
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
                                                            ? 'Roasts enabled'
                                                            : 'Roasts disabled'}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4 py-6">
                                            <Form
                                                {...updateServer.form(server.id)}
                                                className="grid gap-4 md:grid-cols-2"
                                            >
                                                {({ errors, processing }) => (
                                                    <>
                                                        <div className="grid gap-2 md:col-span-2">
                                                            <Label
                                                                htmlFor={`name-${server.id}`}
                                                            >
                                                                Display name
                                                            </Label>
                                                            <Input
                                                                id={`name-${server.id}`}
                                                                name="name"
                                                                defaultValue={
                                                                    server.name
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.name
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`discord_guild_id-${server.id}`}
                                                            >
                                                                Discord guild ID
                                                            </Label>
                                                            <Input
                                                                id={`discord_guild_id-${server.id}`}
                                                                name="discord_guild_id"
                                                                defaultValue={
                                                                    server.discord_guild_id
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.discord_guild_id
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`alert_channel_id-${server.id}`}
                                                            >
                                                                Alert channel ID
                                                            </Label>
                                                            <Input
                                                                id={`alert_channel_id-${server.id}`}
                                                                name="alert_channel_id"
                                                                defaultValue={
                                                                    server.alert_channel_id ??
                                                                    ''
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.alert_channel_id
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label
                                                                htmlFor={`alert_channel_name-${server.id}`}
                                                            >
                                                                Alert channel
                                                                name
                                                            </Label>
                                                            <Input
                                                                id={`alert_channel_name-${server.id}`}
                                                                name="alert_channel_name"
                                                                defaultValue={
                                                                    server.alert_channel_name ??
                                                                    ''
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.alert_channel_name
                                                                }
                                                            />
                                                        </div>

                                                        <div className="flex items-end">
                                                            <label className="flex items-center gap-3 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium">
                                                                <input
                                                                    type="hidden"
                                                                    name="roast_enabled"
                                                                    value="0"
                                                                />
                                                                <input
                                                                    type="checkbox"
                                                                    name="roast_enabled"
                                                                    value="1"
                                                                    defaultChecked={
                                                                        server.roast_enabled
                                                                    }
                                                                    className="size-4 rounded border-border"
                                                                />
                                                                Enable roast
                                                                messages
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
                                                {...destroyServer.form(
                                                    server.id,
                                                )}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        variant="destructive"
                                                        disabled={processing}
                                                    >
                                                        Delete server
                                                    </Button>
                                                )}
                                            </Form>
                                        </CardContent>
                                    </Card>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-border/80 p-6 text-sm text-muted-foreground">
                                    No servers yet. Create your first Discord
                                    server above to begin configuring
                                    Gamesentry.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
