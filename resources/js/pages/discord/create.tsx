import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bot, CheckCircle2, ExternalLink } from 'lucide-react';
import DiscordInstallController from '@/actions/App/Http/Controllers/Discord/DiscordInstallController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create, index } from '@/routes/dashboard/discord';

type Props = {
    discordConfigured: boolean;
    canCreateServer: boolean;
    limitMessage: string | null;
    serverLimit: number | null;
    currentServerCount: number;
    status?: string;
    error?: string | null;
};

export default function DiscordCreate({
    discordConfigured,
    canCreateServer,
    limitMessage,
    serverLimit,
    currentServerCount,
    status,
    error,
}: Props) {
    const disabledReason =
        error ??
        limitMessage ??
        (!discordConfigured
            ? 'The Discord configuration is incomplete. Please enter DISCORD_CLIENT_ID, DISCORD_REDIRECT_URI, and DISCORD_BOT_TOKEN.'
            : null);

    return (
        <>
            <Head title="Add Discord server" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title="Add Discord server"
                        description="Connect the bot to a Discord server, then choose the notification channel."
                    />

                    <Button asChild variant="outline">
                        <Link href={index()}>
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

                {disabledReason && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        {disabledReason}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-[1fr_22rem]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Bot className="size-4" />
                                Discord installation
                            </CardTitle>
                            <CardDescription>
                                Discord will ask you to choose a server. After
                                approval, GameSentry links it and picks the
                                first available text channel by default.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm text-muted-foreground">
                            <div className="flex gap-3">
                                <CheckCircle2 className="mt-0.5 size-4 text-green-600 dark:text-green-400" />
                                <p>
                                    You must be allowed to install apps on the
                                    Discord server.
                                </p>
                            </div>
                            <div className="flex gap-3">
                                <CheckCircle2 className="mt-0.5 size-4 text-green-600 dark:text-green-400" />
                                <p>
                                    The bot needs access to read server members
                                    and post messages in the selected channel.
                                </p>
                            </div>
                            <div className="flex gap-3">
                                <CheckCircle2 className="mt-0.5 size-4 text-green-600 dark:text-green-400" />
                                <p>
                                    You can change the destination channel from
                                    the server edit page after installation.
                                </p>
                            </div>
                        </CardContent>
                        <CardFooter>
                            {discordConfigured && canCreateServer ? (
                                <Button asChild>
                                    <a
                                        href={DiscordInstallController.redirect.url()}
                                    >
                                        <ExternalLink className="size-4" />
                                        Continue to Discord
                                    </a>
                                </Button>
                            ) : (
                                <Button disabled>
                                    <ExternalLink className="size-4" />
                                    Continue to Discord
                                </Button>
                            )}
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Plan usage</CardTitle>
                            <CardDescription>
                                Linked Discord servers available on your current
                                plan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm text-muted-foreground">
                                    Servers
                                </span>
                                <Badge variant="secondary">
                                    {currentServerCount}
                                    {serverLimit === null
                                        ? ' / unlimited'
                                        : ` / ${serverLimit}`}
                                </Badge>
                            </div>

                            {limitMessage && (
                                <p className="text-sm text-muted-foreground">
                                    Upgrade to Pro from Billing to link more
                                    servers.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

DiscordCreate.layout = {
    breadcrumbs: [
        {
            title: 'Discord',
            href: index(),
        },
        {
            title: 'Add server',
            href: create(),
        },
    ],
};
