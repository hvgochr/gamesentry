import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, CreditCard, ExternalLink } from 'lucide-react';
import BillingController from '@/actions/App/Http/Controllers/Billing/BillingController';
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
import { index as billingIndex } from '@/routes/dashboard/billing';

type PlanDetails = {
    name: string;
    price: string;
    discord_servers: number | string;
    watched_players: number | string;
    groq_calls_per_day: number | string;
};

type Subscription = {
    stripe_status: string;
    on_grace_period: boolean;
    ends_at: string | null;
} | null;

type Props = {
    billingConfigured: boolean;
    currentPlan: 'free' | 'pro';
    plans: {
        free: PlanDetails;
        pro: PlanDetails;
    };
    subscription: Subscription;
    checkoutStatus: string | null;
};

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('fr-FR') : 'Never';
}

export default function BillingIndex({
    billingConfigured,
    currentPlan,
    plans,
    subscription,
    checkoutStatus,
}: Props) {
    return (
        <>
            <Head title="Billing" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Billing"
                    description="Manage your Gamesentry plan, limits, and Stripe subscription."
                />

                {!billingConfigured && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-950 dark:bg-amber-950/40 dark:text-amber-300">
                        Stripe billing is not configured yet. Add STRIPE_KEY,
                        STRIPE_SECRET, STRIPE_WEBHOOK_SECRET, and
                        STRIPE_PRO_PRICE_ID to enable checkout.
                    </div>
                )}

                {checkoutStatus === 'success' && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-950 dark:bg-green-950/40 dark:text-green-300">
                        Checkout completed. Stripe may need a few seconds to
                        confirm the subscription through the webhook.
                    </div>
                )}

                {checkoutStatus === 'cancelled' && (
                    <div className="rounded-lg border border-muted px-4 py-3 text-sm text-muted-foreground">
                        Checkout was cancelled. Your current plan is unchanged.
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <PlanCard
                        plan={plans.free}
                        active={currentPlan === 'free'}
                    />

                    <Card className="border-primary/40">
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <CardTitle>{plans.pro.name}</CardTitle>
                                    <CardDescription>
                                        For heavier Discord tracking and AI
                                        notification usage.
                                    </CardDescription>
                                </div>
                                {currentPlan === 'pro' && (
                                    <Badge>
                                        <CheckCircle2 className="size-3" />
                                        Current
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <p className="text-3xl font-semibold">
                                {plans.pro.price}
                            </p>

                            <LimitList plan={plans.pro} />

                            {subscription && (
                                <div className="rounded-lg border p-3 text-sm">
                                    <p className="font-medium">
                                        Subscription status:{' '}
                                        {subscription.stripe_status}
                                    </p>
                                    {subscription.on_grace_period && (
                                        <p className="text-muted-foreground">
                                            Access remains active until{' '}
                                            {formatDateTime(
                                                subscription.ends_at,
                                            )}
                                            .
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2">
                                {currentPlan === 'pro' || subscription ? (
                                    <Form
                                        {...BillingController.portal.form()}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={
                                                    processing ||
                                                    !billingConfigured
                                                }
                                            >
                                                <CreditCard className="size-4" />
                                                Manage billing
                                                <ExternalLink className="size-4" />
                                            </Button>
                                        )}
                                    </Form>
                                ) : (
                                    <Form
                                        {...BillingController.checkout.form()}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={
                                                    processing ||
                                                    !billingConfigured
                                                }
                                            >
                                                <CreditCard className="size-4" />
                                                Upgrade to Pro
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function PlanCard({ plan, active }: { plan: PlanDetails; active: boolean }) {
    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle>{plan.name}</CardTitle>
                        <CardDescription>
                            Enough to try Gamesentry with a small Discord
                            server.
                        </CardDescription>
                    </div>
                    {active && (
                        <Badge variant="secondary">
                            <CheckCircle2 className="size-3" />
                            Current
                        </Badge>
                    )}
                </div>
            </CardHeader>
            <CardContent className="space-y-5">
                <p className="text-3xl font-semibold">{plan.price}</p>
                <LimitList plan={plan} />
            </CardContent>
        </Card>
    );
}

function LimitList({ plan }: { plan: PlanDetails }) {
    return (
        <div className="grid gap-3 text-sm">
            <Limit label="Discord servers" value={plan.discord_servers} />
            <Limit label="Tracked players" value={plan.watched_players} />
            <Limit label="Groq calls / day" value={plan.groq_calls_per_day} />
        </div>
    );
}

function Limit({ label, value }: { label: string; value: number | string }) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-lg border px-3 py-2">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

BillingIndex.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: billingIndex(),
        },
    ],
};
