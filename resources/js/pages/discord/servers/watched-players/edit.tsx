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

type WatchedPlayer = WatchedPlayerFormData & {
    id: number;
};

type Props = {
    server: Server;
    watchedPlayer: WatchedPlayer;
    gameOptions: SelectOption[];
    routingRegionOptions: SelectOption[];
    status?: string;
    error?: string | null;
};

export default function WatchedPlayerEdit({
    server,
    watchedPlayer,
    gameOptions,
    routingRegionOptions,
    status,
    error,
}: Props) {
    const form = useForm<WatchedPlayerFormData>({
        game: watchedPlayer.game,
        routing_region: watchedPlayer.routing_region,
        game_name: watchedPlayer.game_name,
        tag_line: watchedPlayer.tag_line,
        discord_user_id: watchedPlayer.discord_user_id,
        is_active: watchedPlayer.is_active,
    });

    return (
        <>
            <Head
                title={`${server.name} - Edit ${watchedPlayer.game_name}#${watchedPlayer.tag_line}`}
            />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title={`Edit ${watchedPlayer.game_name}#${watchedPlayer.tag_line}`}
                        description={`Update tracking for ${server.name}. Notifications are sent in #${server.discord_channel_name}.`}
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

                {error && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {error}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Tracking settings</CardTitle>
                        <CardDescription>
                            Updating the Riot ID refreshes the latest known
                            match and avoids notification backfill.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <WatchedPlayerForm
                            data={form.data}
                            errors={form.errors}
                            gameOptions={gameOptions}
                            routingRegionOptions={routingRegionOptions}
                            processing={form.processing}
                            submitLabel="Save player"
                            onChange={form.setData}
                            onSubmit={(event) => {
                                event.preventDefault();

                                form.patch(
                                    WatchedPlayerController.update.url({
                                        discordServer: server.id,
                                        watchedPlayer: watchedPlayer.id,
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

WatchedPlayerEdit.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: discordIndex(),
        },
    ],
};
