import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import WatchedPlayerController from '@/actions/App/Http/Controllers/Discord/WatchedPlayerController';
import WatchedPlayerForm from '@/components/discord/watched-player-form';
import type {
    SelectOption,
    WatchedPlayerFormData,
} from '@/components/discord/watched-player-form';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as discordIndex } from '@/routes/dashboard/discord';
import { show as showServer } from '@/routes/dashboard/discord/servers';

type Server = {
    id: number;
    name: string;
    discord_channel_name: string;
};

type Props = {
    server: Server;
    gameOptions: SelectOption[];
    routingRegionOptions: SelectOption[];
    canCreateWatchedPlayer: boolean;
    limitMessage: string | null;
    status?: string;
    error?: string | null;
};

export default function WatchedPlayerCreate({
    server,
    gameOptions,
    routingRegionOptions,
    canCreateWatchedPlayer,
    limitMessage,
    status,
    error,
}: Props) {
    const form = useForm<WatchedPlayerFormData>({
        game: gameOptions[0]?.value ?? 'lol',
        routing_region: routingRegionOptions[0]?.value ?? 'europe',
        game_name: '',
        tag_line: '',
        discord_user_id: '',
        is_active: true,
    });

    return (
        <>
            <Head title={`${server.name} - Add player`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title="Add tracked player"
                        description={`The player will be monitored for ${server.name} and notifications will be sent in #${server.discord_channel_name}.`}
                    />

                    <Button asChild variant="outline">
                        <Link href={showServer({ discordServer: server.id })}>
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

                {(error || limitMessage) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {error ?? limitMessage}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Player identity</CardTitle>
                        <CardDescription>
                            GameSentry resolves the Riot account and stores the
                            latest match so old games are not posted.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <WatchedPlayerForm
                            data={form.data}
                            errors={form.errors}
                            gameOptions={gameOptions}
                            routingRegionOptions={routingRegionOptions}
                            processing={form.processing}
                            disabled={!canCreateWatchedPlayer}
                            submitLabel="Add player"
                            onChange={form.setData}
                            onSubmit={(event) => {
                                event.preventDefault();

                                form.post(
                                    WatchedPlayerController.store.url({
                                        discordServer: server.id,
                                    }),
                                    { preserveScroll: true },
                                );
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

WatchedPlayerCreate.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: discordIndex(),
        },
    ],
};
