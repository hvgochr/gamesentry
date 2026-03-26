import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, home, login, register } from '@/routes';

type AppFooterProps = {
    isAuthenticated: boolean;
    canRegister: boolean;
};

export default function AppFooter({
    isAuthenticated,
    canRegister,
}: AppFooterProps) {
    const year = new Date().getFullYear();

    return (
        <footer className="border-t border-border/60 bg-muted/20">
            <div className="mx-auto grid w-full max-w-6xl gap-8 px-6 py-10 lg:grid-cols-[1.2fr_0.8fr_0.8fr] lg:px-8">
                <div className="flex max-w-md flex-col gap-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                        </div>
                        <div className="flex flex-col">
                            <span className="text-sm font-semibold tracking-wide">
                                Gamesentry
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Riot match alerts for Discord
                            </span>
                        </div>
                    </div>

                    <p className="text-sm leading-6 text-muted-foreground">
                        Built to turn League of Legends and Teamfight Tactics
                        matches into Discord embeds, pings, and just enough
                        roast energy to keep the server awake.
                    </p>
                </div>

                <div className="flex flex-col gap-3 text-sm">
                    <p className="font-medium">Explore</p>
                    <Link href={home()} className="text-muted-foreground transition-colors hover:text-foreground">
                        Overview
                    </Link>
                    <a href="#how-it-works" className="text-muted-foreground transition-colors hover:text-foreground">
                        How it works
                    </a>
                    <span className="text-muted-foreground">
                        Supported: LoL & TFT (Valorant soon)
                    </span>
                </div>

                <div className="flex flex-col gap-3 text-sm">
                    <p className="font-medium">Access</p>
                    {isAuthenticated ? (
                        <Link
                            href={dashboard()}
                            prefetch
                            className="text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Open dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                prefetch
                                className="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Log in
                            </Link>
                            {canRegister && (
                                <Link
                                    href={register()}
                                    prefetch
                                    className="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Create account
                                </Link>
                            )}
                        </>
                    )}
                </div>
            </div>

            <div className="border-t border-border/60">
                <div className="mx-auto flex w-full max-w-6xl px-6 py-4 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between lg:px-8">
                    <span>{year} Gamesentry. Built for private bot management.</span>
                </div>
            </div>
        </footer>
    );
}
