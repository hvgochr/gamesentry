import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

type Stats = {
    servers_count: number;
    watched_players_count: number;
    active_watched_players_count: number;
    sent_notifications_count: number;
    pending_notifications_count: number;
    failed_notifications_count: number;
};

type RecentNotification = {
    id: number;
    game: string;
    riot_match_id: string;
    status: string;
    server_name: string | null;
    player_name: string | null;
    roast_text: string | null;
    failure_reason: string | null;
    delivered_at: string | null;
    created_at: string | null;
};

type Props = {
    stats: Stats;
    recentNotifications: RecentNotification[];
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

export default function Dashboard({ stats, recentNotifications }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Gamesentry"
                    description="Monitor your Discord servers, tracked players, and the status of match notifications."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Connected servers</CardDescription>
                            <CardTitle className="text-2xl">
                                {stats.servers_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {stats.watched_players_count} player(s) currently
                            tracked across all servers.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Tracked players</CardDescription>
                            <CardTitle className="text-2xl">
                                {stats.active_watched_players_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {stats.active_watched_players_count} active
                            player(s) being tracked.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Notifications sent
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {stats.sent_notifications_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {stats.pending_notifications_count} notification(s)
                            pending delivery.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Notifications failed
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {stats.failed_notifications_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            Check for errors before restarting the workers.
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Last notifications</CardTitle>
                        <CardDescription>
                            Recent history of messages composed or sent on your
                            servers.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {recentNotifications.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                No match notifications at this time.
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
                                                    'Joueur inconnu'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {notification.server_name ??
                                                    'Serveur'}{' '}
                                                -{' '}
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
                                            <p className="text-foreground">
                                                {notification.roast_text}
                                            </p>
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

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
