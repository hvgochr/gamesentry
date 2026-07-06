import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bot, Save } from 'lucide-react';
import DiscordInstallController from '@/actions/App/Http/Controllers/Discord/DiscordInstallController';
import DiscordServerController from '@/actions/App/Http/Controllers/Discord/DiscordServerController';
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
import { index as discordIndex } from '@/routes/dashboard/discord';
import { show } from '@/routes/dashboard/discord/servers';

type Channel = {
    id: string;
    name: string;
};

type Server = {
    id: number;
    name: string;
    discord_guild_id: string;
    discord_channel_id: string;
    discord_channel_name: string;
    icon_url: string | null;
};

type Props = {
    server: Server;
    channels: Channel[];
    botInstalled: boolean;
    syncError: string | null;
    status?: string;
    error?: string | null;
};

export default function DiscordServerEdit({
    server,
    channels,
    botInstalled,
    syncError,
    status,
    error,
}: Props) {
    const cannotUpdate =
        !botInstalled || syncError !== null || channels.length === 0;

    return (
        <>
            <Head title={`${server.name} - Edit Discord server`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title={`Edit ${server.name}`}
                        description={`Current destination channel: #${server.discord_channel_name}.`}
                    />

                    <Button asChild variant="outline">
                        <Link href={show({ discordServer: server.id })}>
                            <ArrowLeft className="size-4" />
                            Back to server
                        </Link>
                    </Button>
                </div>

                {status && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        {status}
                    </div>
                )}

                {(error || syncError || !botInstalled) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {error ??
                            syncError ??
                            'The bot is not installed on this Discord server anymore. Reconnect it before updating the channel.'}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Notification channel</CardTitle>
                        <CardDescription>
                            Select the text channel where GameSentry should post
                            match notifications for this server.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...DiscordServerController.update.form({
                                discordServer: server.id,
                            })}
                            options={{ preserveScroll: true }}
                            className="grid max-w-xl gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="discord_channel_id">
                                            Channel
                                        </Label>
                                        <select
                                            id="discord_channel_id"
                                            name="discord_channel_id"
                                            defaultValue={
                                                server.discord_channel_id
                                            }
                                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                            disabled={
                                                processing || cannotUpdate
                                            }
                                        >
                                            {channels.length === 0 ? (
                                                <option value="">
                                                    No channel available
                                                </option>
                                            ) : (
                                                channels.map((channel) => (
                                                    <option
                                                        key={channel.id}
                                                        value={channel.id}
                                                    >
                                                        #{channel.name}
                                                    </option>
                                                ))
                                            )}
                                        </select>
                                        <InputError
                                            message={errors.discord_channel_id}
                                        />
                                    </div>

                                    <div>
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing || cannotUpdate
                                            }
                                        >
                                            <Save className="size-4" />
                                            Save channel
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                    {!botInstalled && (
                        <CardFooter>
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
                                    Reconnect bot
                                </a>
                            </Button>
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

DiscordServerEdit.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: discordIndex(),
        },
    ],
};
