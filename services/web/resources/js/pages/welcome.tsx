import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Bot,
    Clock,
    Flame,
    Gamepad2,
    MessageSquareText,
    Radar,
    Server,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppFooter from '@/components/app-footer';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

type WelcomeProps = {
    canRegister?: boolean;
};

type Highlight = {
    title: string;
    description: string;
    icon: LucideIcon;
};

const heroStats = [
    {
        label: 'Supported titles',
        value: '2 Riot games',
        description:
            'League of Legends and Teamfight Tactics in one control panel. Valorant is coming soon.',
    },
    {
        label: 'Alert flow',
        value: 'Match -> embed -> roast',
        description:
            'Every new game becomes a Discord update with a ping and a generated jab.',
    },
    {
        label: 'Server control',
        value: 'Per guild settings',
        description:
            'Choose channels, tracked players, and the Discord IDs that should get called out.',
    },
];

const featureHighlights: Highlight[] = [
    {
        title: 'Continuous Riot match monitoring',
        description:
            'Gamesentry keeps checking Riot match history so your community hears about fresh results without manual refreshes.',
        icon: Radar,
    },
    {
        title: 'Discord-first notifications',
        description:
            'Send embeds to the right channel, ping the linked Discord member, and make every result visible where your team already hangs out.',
        icon: MessageSquareText,
    },
    {
        title: 'AI-powered roast messages',
        description:
            'Add a little spice to the match alerts with custom AI-generated banter into every alert so wins feel better and losses feel deserved.',
        icon: Flame,
    },
    {
        title: 'One dashboard for every server',
        description:
            'Manage tracked players, regions, linked Discord IDs, and future bot settings from a single authenticated workspace.',
        icon: ShieldCheck,
    },
];

const supportedGames: Highlight[] = [
    {
        title: 'League of Legends',
        description:
            'Track solo queues and the inevitable post-match blame game.',
        icon: Gamepad2,
    },
    {
        title: 'Teamfight Tactics',
        description:
            'Watch every top 4 recovery and 8th place collapse unfold in real time.',
        icon: Sparkles,
    },
    {
        title: 'Valorant',
        description:
            'Coming soon with ranked match support to keep your team updated.',
        icon: Clock,
    },
];

const workflowSteps = [
    {
        step: '01',
        title: 'Link a Riot player',
        description:
            'Add a Riot ID like `Faker#EUW`, choose the correct region, and attach the Discord user that should be pinged.',
    },
    {
        step: '02',
        title: 'Choose where alerts go',
        description:
            'Pick the Discord channel where embeds are sent so every server gets its own dedicated match feed.',
    },
    {
        step: '03',
        title: 'Let the bot watch for matches and post results',
        description:
            'Gamesentry continuously monitors Riot match history and posts an update as soon as a new game is detected.',
    },
];

export default function Welcome({ canRegister = true }: WelcomeProps) {
    const { auth } = usePage().props;

    const primaryHref = auth.user
        ? dashboard()
        : canRegister
          ? register()
          : login();
    const primaryLabel = auth.user
        ? 'Open dashboard'
        : canRegister
          ? 'Create your account'
          : 'Log in';

    return (
        <>
            <Head title="Gamesentry">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-background text-foreground">
                <div className="overflow-hidden">

                    <header className="border-b border-border/60">
                        <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-5 lg:px-8">
                            <Link
                                href={auth.user ? dashboard() : '/'}
                                className="flex items-center gap-3"
                            >
                                <div className="flex size-10 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                                    <Bot className="size-5" />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm font-semibold tracking-wide">
                                        Gamesentry
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        Discord bot control center
                                    </span>
                                </div>
                            </Link>

                            <nav className="flex items-center gap-3">
                                {auth.user ? (
                                    <Button asChild>
                                        <Link href={dashboard()} prefetch>
                                            Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button variant="ghost" asChild>
                                            <Link href={login()} prefetch>
                                                Log in
                                            </Link>
                                        </Button>

                                        {canRegister && (
                                            <Button asChild>
                                                <Link
                                                    href={register()}
                                                    prefetch
                                                >
                                                    Register
                                                </Link>
                                            </Button>
                                        )}
                                    </>
                                )}
                            </nav>
                        </div>
                    </header>

                    <main className="mx-auto flex w-full max-w-6xl flex-col gap-24 px-6 py-16 lg:px-8 lg:py-24">
                        <section className="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                            <div className="flex flex-col gap-8">
                                <div className="flex flex-col gap-4">
                                    <Badge
                                        variant="secondary"
                                        className="rounded-full px-3 py-1 text-sm"
                                    >
                                        <Server className="size-3.5" />
                                        Built for Riot match alerts on Discord
                                    </Badge>

                                    <div className="flex flex-col gap-5">
                                        <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                            Track Riot matches, trigger Discord
                                            alerts, and roast every result in
                                            real time.
                                        </h1>

                                        <p className="max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg">
                                            Gamesentry gives communities an
                                            authenticated dashboard, and a bot
                                            workflow built around one goal:
                                            detect new matches fast, post
                                            polished embeds, and ping the right
                                            player with just enough AI-generated
                                            disrespect.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <Button size="lg" asChild>
                                        <Link href={primaryHref} prefetch>
                                            {primaryLabel}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>

                                    <Button size="lg" variant="outline" asChild>
                                        <a href="#how-it-works">
                                            See how it works
                                        </a>
                                    </Button>
                                </div>

                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    <Badge
                                        variant="outline"
                                        className="rounded-full px-3 py-1"
                                    >
                                        <Users className="size-3.5" />
                                        Multi-server friendly
                                    </Badge>
                                    <Badge
                                        variant="outline"
                                        className="rounded-full px-3 py-1"
                                    >
                                        <ShieldCheck className="size-3.5" />
                                        Authenticated dashboard
                                    </Badge>
                                    <Badge
                                        variant="outline"
                                        className="rounded-full px-3 py-1"
                                    >
                                        <Flame className="size-3.5" />
                                        AI-generated roasts
                                    </Badge>
                                </div>
                            </div>

                            <Card className="border-border/70 bg-card/80 py-0 shadow-xl backdrop-blur">
                                <CardHeader className="gap-4 border-b border-border/60 px-6 py-6">
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                <Bot className="size-5" />
                                            </div>
                                            <div className="flex flex-col gap-1">
                                                <CardTitle>
                                                    Dashboard preview
                                                </CardTitle>
                                                <CardDescription>
                                                    A control panel for tracked
                                                    players and server-level bot
                                                    settings.
                                                </CardDescription>
                                            </div>
                                        </div>

                                        <Badge className="rounded-full px-3 py-1">
                                            Auth ready
                                        </Badge>
                                    </div>
                                </CardHeader>

                                <CardContent className="grid gap-4 px-6 py-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="flex flex-col gap-2 rounded-2xl border border-border/60 bg-muted/70 p-4">
                                            <span className="text-xs tracking-[0.24em] text-muted-foreground uppercase">
                                                Tracked player
                                            </span>
                                            <span className="text-lg font-semibold">
                                                Faker#EUW
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                League Of Legends / EUW / ping `@Faker`
                                            </span>
                                        </div>

                                        <div className="flex flex-col gap-2 rounded-2xl border border-border/60 bg-muted/70 p-4">
                                            <span className="text-xs tracking-[0.24em] text-muted-foreground uppercase">
                                                Alert channel
                                            </span>
                                            <span className="text-lg font-semibold">
                                                #match-feed
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                Embeds, mentions, and roast
                                                output in one place
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-3 rounded-2xl border border-border/60 bg-muted/50 p-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-medium">
                                                Last bot event
                                            </span>
                                            <Badge
                                                variant="secondary"
                                                className="rounded-full px-3 py-1"
                                            >
                                                New match detected
                                            </Badge>
                                        </div>

                                        <div className="grid gap-3 text-sm sm:grid-cols-[auto_1fr] sm:items-start">
                                            <span className="text-muted-foreground">
                                                Embed
                                            </span>
                                            <span className="font-medium">
                                                Faker just went 0/10/2 in his
                                                latest solo queue match.
                                            </span>

                                            <span className="text-muted-foreground">
                                                Roast
                                            </span>
                                            <span className="leading-6 text-muted-foreground">
                                                "Oof, looks like Faker's mechanics took a lunch break..."
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </section>

                        <section className="grid gap-4 md:grid-cols-3">
                            {heroStats.map((stat) => (
                                <Card
                                    key={stat.label}
                                    className="gap-4 bg-card/70"
                                >
                                    <CardHeader className="gap-2">
                                        <CardDescription>
                                            {stat.label}
                                        </CardDescription>
                                        <CardTitle className="text-2xl">
                                            {stat.value}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm leading-6 text-muted-foreground">
                                            {stat.description}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </section>

                        <section className="flex flex-col gap-8">
                            <div className="flex max-w-3xl flex-col gap-3">
                                <Badge
                                    variant="outline"
                                    className="rounded-full px-3 py-1 text-sm"
                                >
                                    What Gamesentry handles
                                </Badge>
                                <h2 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                    Everything needed to turn Riot matches into
                                    Discord moments.
                                </h2>
                                <p className="text-base leading-7 text-muted-foreground">
                                    The landing page tells the story, while the
                                    dashboard is where server owners manage the
                                    players, channels, and notifications that
                                    power the bot.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                {featureHighlights.map((feature) => {
                                    const Icon = feature.icon;

                                    return (
                                        <Card
                                            key={feature.title}
                                            className="gap-4"
                                        >
                                            <CardHeader className="gap-4">
                                                <div className="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                    <Icon className="size-5" />
                                                </div>
                                                <div className="flex flex-col gap-2">
                                                    <CardTitle>
                                                        {feature.title}
                                                    </CardTitle>
                                                    <CardDescription className="leading-6">
                                                        {feature.description}
                                                    </CardDescription>
                                                </div>
                                            </CardHeader>
                                        </Card>
                                    );
                                })}
                            </div>
                        </section>

                        <section className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                            <div className="flex flex-col gap-3">
                                <Badge
                                    variant="outline"
                                    className="rounded-full px-3 py-1 text-sm"
                                >
                                    Supported games
                                </Badge>
                                <h2 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                    One workflow for every Riot title you care
                                    about.
                                </h2>
                                <p className="text-base leading-7 text-muted-foreground">
                                    Gamesentry starts with the two games
                                    already in scope for the bot and keeps the
                                    experience consistent across each queue and
                                    each server.
                                </p>
                            </div>

                            <div className="grid gap-4">
                                {supportedGames.map((game) => {
                                    const Icon = game.icon;

                                    return (
                                        <Card
                                            key={game.title}
                                            className="gap-4"
                                        >
                                            <CardHeader className="flex-row items-start gap-4">
                                                <div className="flex size-11 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                                    <Icon className="size-5" />
                                                </div>
                                                <div className="flex flex-col gap-2">
                                                    <CardTitle>
                                                        {game.title}
                                                    </CardTitle>
                                                    <CardDescription className="leading-6">
                                                        {game.description}
                                                    </CardDescription>
                                                </div>
                                            </CardHeader>
                                        </Card>
                                    );
                                })}
                            </div>
                        </section>

                        <section
                            id="how-it-works"
                            className="flex flex-col gap-8"
                        >
                            <div className="flex flex-col gap-3">
                                <Badge
                                    variant="outline"
                                    className="rounded-full px-3 py-1 text-sm"
                                >
                                    How it works
                                </Badge>
                                <h2 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                    From player setup to Discord roast in four
                                    steps.
                                </h2>
                                <p className="text-base leading-7 text-muted-foreground">
                                    The bot logic stays simple on purpose:
                                    identify the player, watch Riot for new
                                    matches, and ship the result back to Discord
                                    fast.
                                </p>
                            </div>

                            <div className="grid gap-4">
                                {workflowSteps.map((step) => (
                                    <Card key={step.step} className="gap-4">
                                        <CardHeader className="gap-3">
                                            <div className="flex items-center gap-3">
                                                <Badge className="rounded-full px-3 py-1">
                                                    {step.step}
                                                </Badge>
                                                <CardTitle>
                                                    {step.title}
                                                </CardTitle>
                                            </div>
                                            <CardDescription className="leading-6">
                                                {step.description}
                                            </CardDescription>
                                        </CardHeader>
                                    </Card>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-4xl border border-border/70 bg-card/60 p-8 shadow-sm lg:p-10">
                            <div className="flex flex-col gap-8">
                                <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                                    <div className="flex max-w-3xl flex-col gap-4">
                                        <Badge
                                            variant="secondary"
                                            className="rounded-full px-3 py-1 text-sm"
                                        >
                                            Ready to launch
                                        </Badge>
                                        <h2 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                            Give your Discord server a match feed
                                            people actually pay attention to.
                                        </h2>
                                        <p className="text-base leading-7 text-muted-foreground">
                                            Gamesentry is at its best when it
                                            turns fresh League or TFT matches
                                            into something your community sees
                                            instantly: a clean embed, the right
                                            ping, and a little post-game chaos.
                                        </p>
                                    </div>

                                    <div className="flex flex-col gap-3 sm:flex-row lg:justify-end">
                                        <Button size="lg" asChild>
                                            <Link href={primaryHref} prefetch>
                                                {primaryLabel}
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" asChild>
                                            <a href="#how-it-works">
                                                Review the workflow
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>

                    <AppFooter
                        isAuthenticated={Boolean(auth.user)}
                        canRegister={canRegister}
                    />
                </div>
            </div>
        </>
    );
}
