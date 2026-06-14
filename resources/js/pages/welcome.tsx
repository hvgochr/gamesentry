import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    BellRing,
    Bot,
    Check,
    ChevronRight,
    Clock3,
    Crosshair,
    MessageSquareText,
    ShieldCheck,
    Swords,
    Trophy,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { login } from '@/routes';
/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */
import { index as dashboard } from '@/routes/dashboard';
import type { Auth } from '@/types';

type Game = {
    name: string;
    status: 'Available' | 'Coming soon';
    description: string;
    icon: LucideIcon;
    features: string[];
};

type Step = {
    title: string;
    description: string;
    icon: LucideIcon;
};

type Plan = {
    name: string;
    price: string;
    description: string;
    features: string[];
    highlighted?: boolean;
};

type PageProps = {
    auth: Auth;
};

const games: Game[] = [
    {
        name: 'League of Legends',
        status: 'Available',
        description:
            'Track new matches, summarize performance, and send Discord notifications after each game.',
        icon: Swords,
        features: ['Match history polling', 'KDA summaries', 'AI roasts'],
    },
    {
        name: 'Teamfight Tactics',
        status: 'Available',
        description:
            'Follow placements, final comps, traits, and match outcomes for tracked players.',
        icon: Trophy,
        features: ['Placement tracking', 'Comp snapshots', 'Trait highlights'],
    },
    {
        name: 'Valorant',
        status: 'Coming soon',
        description:
            'Valorant support is planned, but not available yet. The current focus is making LoL and TFT reliable in production.',
        icon: Crosshair,
        features: ['Match alerts planned', 'Agent context planned', 'Roadmap'],
    },
];

const steps: Step[] = [
    {
        title: 'Install the bot',
        description:
            'Authorize GameSentry on a Discord server and choose the channel for alerts.',
        icon: Bot,
    },
    {
        title: 'Track players',
        description:
            'Add a Riot ID, select the game, and connect it to the Discord member to mention.',
        icon: Users,
    },
    {
        title: 'Poll safely',
        description:
            'Scheduled jobs check Riot match history and back off when rate limits are reached.',
        icon: Clock3,
    },
    {
        title: 'Post recaps',
        description:
            'The app builds a short summary, generates a roast, and sends the Discord message.',
        icon: MessageSquareText,
    },
];

const plans: Plan[] = [
    {
        name: 'Free',
        price: '0 EUR',
        description: 'For testing GameSentry with a small server.',
        features: [
            '1 Discord server',
            '3 tracked players',
            '50 AI roasts per day',
            'League of Legends and TFT',
        ],
    },
    {
        name: 'Pro',
        price: '5 EUR',
        description: 'For active Discord groups that need more capacity.',
        highlighted: true,
        features: [
            '3 Discord servers',
            '15 tracked players',
            '250 AI roasts per day',
            'Higher room for upcoming features',
        ],
    },
];

function SectionHeading({
    eyebrow,
    title,
    description,
}: {
    eyebrow: string;
    title: string;
    description: string;
}) {
    return (
        <div className="mx-auto max-w-2xl text-center">
            <p className="text-sm font-medium text-muted-foreground">
                {eyebrow}
            </p>
            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-foreground md:text-4xl">
                {title}
            </h2>
            <p className="mt-4 text-base leading-7 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}

export default function Welcome() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="GameSentry">
                <meta
                    name="description"
                    content="GameSentry tracks Riot matches and posts Discord notifications for League of Legends, TFT, and future Valorant support."
                />
            </Head>

            <main className="min-h-screen bg-background text-foreground">
                <header className="sticky top-0 z-50 border-b bg-background/90 backdrop-blur">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <a href="#top" className="flex items-center gap-3">
                            <span className="flex size-9 items-center justify-center rounded-md border bg-card">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            <span className="font-semibold">GameSentry</span>
                        </a>

                        <nav className="hidden items-center gap-6 text-sm text-muted-foreground md:flex">
                            <a
                                href="#games"
                                className="transition-colors hover:text-foreground"
                            >
                                Games
                            </a>
                            <a
                                href="#setup"
                                className="transition-colors hover:text-foreground"
                            >
                                Setup
                            </a>
                            <a
                                href="#pricing"
                                className="transition-colors hover:text-foreground"
                            >
                                Pricing
                            </a>
                        </nav>

                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="hidden sm:inline-flex"
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    {/* @chisel-registration */}
                                    <Button asChild>
                                        <Link href={register()}>
                                            Get started
                                        </Link>
                                    </Button>
                                    {/* @end-chisel-registration */}
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <section
                    id="top"
                    className="flex min-h-[calc(100vh-4rem)] border-b"
                >
                    <div className="mx-auto flex w-full max-w-6xl items-center px-4 py-16 sm:px-6 lg:px-8">
                        <div className="max-w-4xl">
                            <Badge variant="outline" className="mb-5">
                                <BellRing className="size-3" />
                                Discord match alerts for Riot games
                            </Badge>

                            <h1 className="text-4xl font-semibold tracking-tight md:text-6xl">
                                Track games, ping friends, keep the recap
                                automatic.
                            </h1>

                            <p className="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                                GameSentry monitors League of Legends and TFT
                                matches, generates AI roasts, and posts them in
                                your Discord server.
                            </p>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                {auth.user ? (
                                    <Button asChild size="lg">
                                        <Link href={dashboard()}>
                                            Open dashboard
                                            <ChevronRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        {/* @chisel-registration */}
                                        <Button asChild size="lg">
                                            <Link href={register()}>
                                                Start tracking
                                                <ChevronRight className="size-4" />
                                            </Link>
                                        </Button>
                                        {/* @end-chisel-registration */}
                                    </>
                                )}
                                <Button asChild size="lg" variant="outline">
                                    <a href="#setup">How it works</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="games" className="border-b bg-muted/30 py-16">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            eyebrow="Game coverage"
                            title="Available now for League of Legends and TFT."
                            description="Here's what GameSentry does for each game, with Valorant support planned for the future."
                        />

                        <div className="mt-10 grid gap-4 lg:grid-cols-3">
                            {games.map((game) => {
                                const Icon = game.icon;

                                return (
                                    <Card key={game.name}>
                                        <CardHeader>
                                            <div className="mb-4 flex items-center justify-between gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-md border bg-background">
                                                    <Icon className="size-5" />
                                                </span>
                                                <Badge
                                                    variant={
                                                        game.status ===
                                                        'Available'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {game.status}
                                                </Badge>
                                            </div>
                                            <CardTitle>{game.name}</CardTitle>
                                            <CardDescription>
                                                {game.description}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="grid gap-2">
                                            {game.features.map((feature) => (
                                                <div
                                                    key={feature}
                                                    className="flex items-center gap-2 text-sm"
                                                >
                                                    <Check className="size-4 text-muted-foreground" />
                                                    {feature}
                                                </div>
                                            ))}
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <section id="setup" className="border-b py-16">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            eyebrow="Setup"
                            title="From Discord install to automatic recaps."
                            description="The flow stays close to the dashboard: connect a server, add tracked players, and let scheduled jobs handle match checks and notifications."
                        />

                        <div className="relative mx-auto mt-10 max-w-3xl">
                            <div className="absolute top-5 bottom-5 left-1/2 w-px -translate-x-1/2 bg-border" />
                            {steps.map((step, index) => {
                                const Icon = step.icon;

                                return (
                                    <div
                                        key={step.title}
                                        className="relative flex flex-col items-center pb-6 last:pb-0"
                                    >
                                        <div className="relative z-10 mb-4 flex size-10 shrink-0 items-center justify-center rounded-md border bg-background">
                                            <Icon className="size-5" />
                                        </div>

                                        <Card className="w-full">
                                            <CardHeader>
                                                <div className="mb-1 flex justify-center">
                                                    <Badge variant="outline">
                                                        Step {index + 1}
                                                    </Badge>
                                                </div>
                                                <CardTitle className="text-center">
                                                    {step.title}
                                                </CardTitle>
                                                <CardDescription className="mx-auto max-w-xl text-center">
                                                    {step.description}
                                                </CardDescription>
                                            </CardHeader>
                                        </Card>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <section className="border-b bg-muted/30 py-16">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            eyebrow="Operations"
                            title="Designed around the parts that can fail."
                            description="GameSentry keeps the visible product simple while the backend handles polling, API pressure, Discord delivery, and ownership boundaries."
                        />

                        <div className="mt-10 grid gap-6 lg:grid-cols-3">
                            {[
                                [
                                    Clock3,
                                    'Backoff-aware polling',
                                    'Riot checks slow down when external limits are reached.',
                                ],
                                [
                                    MessageSquareText,
                                    'Readable summaries',
                                    'Notifications include structured match details and a short AI line.',
                                ],
                                [
                                    ShieldCheck,
                                    'Server ownership',
                                    'Users only manage Discord servers linked to their account.',
                                ],
                            ].map(([Icon, title, description]) => (
                                <Card key={title as string}>
                                    <CardHeader>
                                        <span className="mb-4 flex size-10 items-center justify-center rounded-md border bg-background">
                                            <Icon className="size-5" />
                                        </span>
                                        <CardTitle>{title as string}</CardTitle>
                                        <CardDescription>
                                            {description as string}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>

                <section id="pricing" className="py-16">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            eyebrow="Pricing"
                            title="Simple limits before payments are enabled."
                            description="The free tier is sufficient for testing and small servers, while the Pro tier offers more capacity for active groups."
                        />

                        <div className="mx-auto mt-10 grid max-w-4xl gap-4 md:grid-cols-2">
                            {plans.map((plan) => (
                                <Card
                                    key={plan.name}
                                    className={
                                        plan.highlighted
                                            ? 'border-primary'
                                            : undefined
                                    }
                                >
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <CardTitle>
                                                    {plan.name}
                                                </CardTitle>
                                                <CardDescription className="mt-2">
                                                    {plan.description}
                                                </CardDescription>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-end gap-2">
                                            <span className="text-3xl font-semibold">
                                                {plan.price}
                                            </span>
                                            {plan.name === 'Pro' && (
                                                <span className="pb-1 text-sm text-muted-foreground">
                                                    / month
                                                </span>
                                            )}
                                        </div>

                                        <div className="mt-6 grid gap-3">
                                            {plan.features.map((feature) => (
                                                <div
                                                    key={feature}
                                                    className="flex items-center gap-2 text-sm"
                                                >
                                                    <Check className="size-4 text-muted-foreground" />
                                                    {feature}
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>

                <footer className="border-t bg-muted/30 py-8">
                    <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 text-sm text-muted-foreground sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                        <div className="flex items-center gap-3 text-foreground">
                            <AppLogo />
                        </div>

                        <div className="flex flex-wrap gap-4">
                            <a
                                href="#games"
                                className="transition-colors hover:text-foreground"
                            >
                                Games
                            </a>
                            <a
                                href="#setup"
                                className="transition-colors hover:text-foreground"
                            >
                                Setup
                            </a>
                            <a
                                href="#pricing"
                                className="transition-colors hover:text-foreground"
                            >
                                Pricing
                            </a>
                        </div>
                    </div>
                </footer>
            </main>
        </>
    );
}
