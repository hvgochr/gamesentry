import { Form, Head } from '@inertiajs/react';
import {
    Ban,
    Bell,
    DatabaseZap,
    PauseCircle,
    PlayCircle,
    RefreshCcw,
    Server,
    Trash2,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AdminDiscordServerController from '@/actions/App/Http/Controllers/Admin/AdminDiscordServerController';
import AdminFailedJobController from '@/actions/App/Http/Controllers/Admin/AdminFailedJobController';
import AdminNotificationController from '@/actions/App/Http/Controllers/Admin/AdminNotificationController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import AdminWatchedPlayerController from '@/actions/App/Http/Controllers/Admin/AdminWatchedPlayerController';
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
import { index as adminIndex } from '@/routes/dashboard/admin';

type Stats = {
    users_count: number;
    paused_users_count: number;
    servers_count: number;
    active_watched_players_count: number;
    pending_jobs_count: number;
    failed_jobs_count: number;
    failed_notifications_count: number;
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    plan: string;
    paused_at: string | null;
    created_at: string | null;
    servers_count: number;
    watched_players_count: number;
    groq_calls_today: number;
    groq_daily_limit: number | null;
};

type FailedNotification = {
    id: number;
    game: string;
    riot_match_id: string;
    server_name: string;
    user_name: string;
    player_name: string | null;
    failure_reason: string | null;
    created_at: string | null;
};

type LinkedServer = {
    id: number;
    name: string;
    discord_guild_id: string;
    user_name: string;
    user_email: string;
    watched_players_count: number;
    updated_at: string | null;
};

type ActiveWatchedPlayer = {
    id: number;
    game: string;
    name: string;
    server_name: string;
    user_name: string;
    next_poll_at: string | null;
};

type FailedJob = {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    name: string;
    failed_at: string;
    exception: string;
};

type QueueBacklog = {
    queue: string;
    total: number;
    pending: number;
    reserved: number;
    delayed: number;
    oldest_created_at: string | null;
};

type SchedulerHealth = {
    last_run_at: string | null;
    is_recent: boolean;
};

type RiotBackoff = {
    game: string;
    label: string;
    dispatch_limit_per_minute: number;
    active_players_count: number;
    due_players_count: number;
    backoff_until: string | null;
    is_backing_off: boolean;
};

type Props = {
    stats: Stats;
    users: AdminUser[];
    recentFailedNotifications: FailedNotification[];
    servers: LinkedServer[];
    activeWatchedPlayers: ActiveWatchedPlayer[];
    failedJobs: FailedJob[];
    queueBacklog: QueueBacklog[];
    scheduler: SchedulerHealth;
    riotBackoff: RiotBackoff[];
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : 'Never';
}

function metric(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

function usageLabel(used: number, limit: number | null): string {
    return limit === null ? `${used} / unlimited` : `${used} / ${limit}`;
}

export default function AdminDashboard({
    stats,
    users,
    recentFailedNotifications,
    servers,
    activeWatchedPlayers,
    failedJobs,
    queueBacklog,
    scheduler,
    riotBackoff,
}: Props) {
    return (
        <>
            <Head title="Admin" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Admin"
                    description="Operational controls for users, queues, notifications, and Riot pressure."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        icon={Users}
                        label="Users"
                        value={stats.users_count}
                        detail={`${stats.paused_users_count} paused`}
                    />
                    <MetricCard
                        icon={Server}
                        label="Discord servers"
                        value={stats.servers_count}
                        detail={`${stats.active_watched_players_count} active tracked players`}
                    />
                    <MetricCard
                        icon={DatabaseZap}
                        label="Queue backlog"
                        value={stats.pending_jobs_count}
                        detail={`${stats.failed_jobs_count} failed jobs`}
                    />
                    <MetricCard
                        icon={Bell}
                        label="Failed notifications"
                        value={stats.failed_notifications_count}
                        detail="Retry manually after checking the failure"
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Users</CardTitle>
                            <CardDescription>
                                Role, plan, usage, and account pause controls.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {users.map((user) => (
                                <div
                                    key={user.id}
                                    className="grid gap-3 rounded-lg border p-3 lg:grid-cols-[1.2fr_1fr_auto]"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="truncate font-medium">
                                                {user.name}
                                            </p>
                                            <Badge variant="outline">
                                                {user.role}
                                            </Badge>
                                            <Badge variant="secondary">
                                                {user.plan}
                                            </Badge>
                                            {user.paused_at && (
                                                <Badge variant="destructive">
                                                    paused
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-3 gap-2 text-sm">
                                        <MiniStat
                                            label="Servers"
                                            value={user.servers_count}
                                        />
                                        <MiniStat
                                            label="Players"
                                            value={user.watched_players_count}
                                        />
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Groq
                                            </p>
                                            <p className="font-medium">
                                                {usageLabel(
                                                    user.groq_calls_today,
                                                    user.groq_daily_limit,
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-end">
                                        {user.paused_at ? (
                                            <Form
                                                {...AdminUserController.resume.form(
                                                    { user: user.id },
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={processing}
                                                    >
                                                        <PlayCircle className="size-4" />
                                                        Resume
                                                    </Button>
                                                )}
                                            </Form>
                                        ) : (
                                            <DestructiveActionDialog
                                                form={AdminUserController.pause.form(
                                                    { user: user.id },
                                                )}
                                                title={`Pause ${user.name}?`}
                                                description="This user will be blocked from linking Discord servers or changing tracked players until an admin resumes the account."
                                                confirmLabel="Pause user"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <PauseCircle className="size-4" />
                                                    Pause
                                                </Button>
                                            </DestructiveActionDialog>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Scheduler</CardTitle>
                            <CardDescription>
                                Heartbeat and Riot backoff state.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="rounded-lg border p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            Scheduler heartbeat
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDateTime(
                                                scheduler.last_run_at,
                                            )}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            scheduler.is_recent
                                                ? 'secondary'
                                                : 'destructive'
                                        }
                                    >
                                        {scheduler.is_recent
                                            ? 'recent'
                                            : 'stale'}
                                    </Badge>
                                </div>
                            </div>

                            {riotBackoff.map((game) => (
                                <div
                                    key={game.game}
                                    className="rounded-lg border p-3"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                {game.label}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {game.due_players_count} due /{' '}
                                                {game.active_players_count}{' '}
                                                active, budget{' '}
                                                {game.dispatch_limit_per_minute}
                                                /min
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                game.is_backing_off
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            {game.is_backing_off
                                                ? 'backoff'
                                                : 'normal'}
                                        </Badge>
                                    </div>
                                    {game.backoff_until && (
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            Backoff until{' '}
                                            {formatDateTime(game.backoff_until)}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent failed notifications</CardTitle>
                            <CardDescription>
                                Retry only after the failure reason makes sense.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentFailedNotifications.length === 0 ? (
                                <EmptyState label="No failed notifications." />
                            ) : (
                                recentFailedNotifications.map(
                                    (notification) => (
                                        <div
                                            key={notification.id}
                                            className="rounded-lg border p-3"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="font-medium">
                                                        {notification.player_name ??
                                                            'Unknown player'}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {
                                                            notification.server_name
                                                        }{' '}
                                                        -{' '}
                                                        {notification.game.toUpperCase()}{' '}
                                                        -{' '}
                                                        {
                                                            notification.riot_match_id
                                                        }
                                                    </p>
                                                </div>
                                                <DestructiveActionDialog
                                                    form={AdminNotificationController.retry.form(
                                                        {
                                                            notification:
                                                                notification.id,
                                                        },
                                                    )}
                                                    title="Retry this notification?"
                                                    description="This will queue the notification again and may send a Discord message if the retry succeeds."
                                                    confirmLabel="Retry notification"
                                                    confirmVariant="default"
                                                >
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <RefreshCcw className="size-4" />
                                                        Retry
                                                    </Button>
                                                </DestructiveActionDialog>
                                            </div>
                                            <p className="mt-2 text-sm text-red-600 dark:text-red-300">
                                                {notification.failure_reason ??
                                                    'No failure reason stored.'}
                                            </p>
                                        </div>
                                    ),
                                )
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Failed jobs</CardTitle>
                            <CardDescription>
                                Recent queue failures and manual retry.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {failedJobs.length === 0 ? (
                                <EmptyState label="No failed jobs." />
                            ) : (
                                failedJobs.map((job) => (
                                    <div
                                        key={job.uuid}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {job.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {job.connection} /{' '}
                                                    {job.queue} -{' '}
                                                    {formatDateTime(
                                                        job.failed_at,
                                                    )}
                                                </p>
                                            </div>
                                            <DestructiveActionDialog
                                                form={AdminFailedJobController.retry.form(
                                                    { uuid: job.uuid },
                                                )}
                                                title="Retry this failed job?"
                                                description="This will push the failed job back onto the queue. Only retry it if the underlying problem has been fixed."
                                                confirmLabel="Retry job"
                                                confirmVariant="default"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <RefreshCcw className="size-4" />
                                                    Retry
                                                </Button>
                                            </DestructiveActionDialog>
                                        </div>
                                        <p className="mt-2 text-sm text-red-600 dark:text-red-300">
                                            {job.exception}
                                        </p>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Queue backlog</CardTitle>
                            <CardDescription>
                                Pending, reserved, and delayed jobs by queue.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {queueBacklog.length === 0 ? (
                                <EmptyState label="No queued jobs." />
                            ) : (
                                queueBacklog.map((queue) => (
                                    <div
                                        key={queue.queue}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="font-medium">
                                                {queue.queue}
                                            </p>
                                            <Badge variant="outline">
                                                {queue.total} total
                                            </Badge>
                                        </div>
                                        <div className="mt-3 grid grid-cols-3 gap-2 text-sm">
                                            <MiniStat
                                                label="Pending"
                                                value={queue.pending}
                                            />
                                            <MiniStat
                                                label="Reserved"
                                                value={queue.reserved}
                                            />
                                            <MiniStat
                                                label="Delayed"
                                                value={queue.delayed}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Linked servers</CardTitle>
                            <CardDescription>
                                Remove linked Discord servers when needed.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {servers.length === 0 ? (
                                <EmptyState label="No linked servers." />
                            ) : (
                                servers.map((server) => (
                                    <div
                                        key={server.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {server.name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {server.user_name} -{' '}
                                                    {
                                                        server.watched_players_count
                                                    }{' '}
                                                    player(s)
                                                </p>
                                            </div>
                                            <DestructiveActionDialog
                                                form={AdminDiscordServerController.unlink.form(
                                                    {
                                                        discordServer:
                                                            server.id,
                                                    },
                                                )}
                                                title={`Unlink ${server.name}?`}
                                                description="This will remove the linked Discord server, its tracked players, and related notifications from GameSentry."
                                                confirmLabel="Unlink server"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                >
                                                    <Trash2 className="size-4" />
                                                    Unlink
                                                </Button>
                                            </DestructiveActionDialog>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Active tracking</CardTitle>
                            <CardDescription>
                                Disable watched-player polling immediately.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {activeWatchedPlayers.length === 0 ? (
                                <EmptyState label="No active tracked players." />
                            ) : (
                                activeWatchedPlayers.map((player) => (
                                    <div
                                        key={player.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {player.name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {player.server_name} -{' '}
                                                    {player.game.toUpperCase()}
                                                </p>
                                            </div>
                                            <DestructiveActionDialog
                                                form={AdminWatchedPlayerController.disable.form(
                                                    {
                                                        watchedPlayer:
                                                            player.id,
                                                    },
                                                )}
                                                title={`Disable tracking for ${player.name}?`}
                                                description="This will stop polling this player immediately. Tracking will stay disabled until it is manually re-enabled later."
                                                confirmLabel="Disable tracking"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Ban className="size-4" />
                                                    Disable
                                                </Button>
                                            </DestructiveActionDialog>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function MetricCard({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: LucideIcon;
    label: string;
    value: number;
    detail: string;
}) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardDescription className="flex items-center gap-2">
                    <Icon className="size-4" />
                    {label}
                </CardDescription>
                <CardTitle className="text-2xl">{metric(value)}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
                {detail}
            </CardContent>
        </Card>
    );
}

function MiniStat({ label, value }: { label: string; value: number }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="font-medium">{metric(value)}</p>
        </div>
    );
}

function EmptyState({ label }: { label: string }) {
    return (
        <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            {label}
        </div>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: adminIndex(),
        },
    ],
};
