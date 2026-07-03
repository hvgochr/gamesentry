<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    public function index(Request $request, PlanLimitService $limits): Response
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        return Inertia::render('billing/index', [
            'billingConfigured' => $this->billingIsConfigured(),
            'currentPlan' => $user->plan->value,
            'plans' => [
                'free' => [
                    'name' => 'Free',
                    'price' => '0 €',
                    'discord_servers' => 1,
                    'watched_players' => 3,
                    'groq_calls_per_day' => 50,
                ],
                'pro' => [
                    'name' => 'Pro',
                    'price' => '4.99 € / month',
                    'discord_servers' => 3,
                    'watched_players' => 15,
                    'groq_calls_per_day' => 250,
                ],
            ],
            'subscription' => $subscription === null ? null : [
                'stripe_status' => $subscription->stripe_status,
                'on_grace_period' => $subscription->onGracePeriod(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
            'checkoutStatus' => $request->query('checkout'),
        ]);
    }

    public function checkout(Request $request): SymfonyResponse|RedirectResponse
    {
        if (! $this->billingIsConfigured()) {
            return $this->backWithToast('error', 'Stripe billing is not configured yet.');
        }

        /** @var User $user */
        $user = $request->user();

        if ($user->subscribed('default')) {
            return $this->backWithToast('success', 'Your Pro subscription is already active.');
        }

        $checkout = $user
            ->newSubscription('default', (string) config('services.stripe.pro_price_id'))
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('dashboard.billing.index', ['checkout' => 'success']),
                'cancel_url' => route('dashboard.billing.index', ['checkout' => 'cancelled']),
            ]);

        return Inertia::location((string) $checkout->asStripeCheckoutSession()->url);
    }

    public function portal(Request $request): SymfonyResponse|RedirectResponse
    {
        if (! $this->billingIsConfigured()) {
            return $this->backWithToast('error', 'Stripe billing is not configured yet.');
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return $this->backWithToast('error', 'Open checkout before accessing the billing portal.');
        }

        return Inertia::location($user->billingPortalUrl(route('dashboard.billing.index')));
    }

    private function billingIsConfigured(): bool
    {
        return filled(config('cashier.key'))
            && filled(config('cashier.secret'))
            && filled(config('services.stripe.pro_price_id'));
    }

    private function backWithToast(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => $type,
            'message' => $message,
        ]);

        return back();
    }
}
