<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard.billing.index'))
            ->assertRedirect(route('login'));
    }

    public function test_billing_page_shows_plan_limits_and_configuration_status(): void
    {
        config([
            'cashier.key' => null,
            'cashier.secret' => null,
            'services.stripe.pro_price_id' => null,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.billing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('billing/index')
                ->where('billingConfigured', false)
                ->where('currentPlan', 'free')
                ->where('plans.free.discord_servers', 1)
                ->where('plans.pro.watched_players', 15)
                ->where('subscription', null));
    }

    public function test_billing_page_reports_configured_stripe_when_keys_and_price_exist(): void
    {
        config([
            'cashier.key' => 'pk_test_gamesentry',
            'cashier.secret' => 'sk_test_gamesentry',
            'services.stripe.pro_price_id' => 'price_gamesentry_pro',
        ]);

        $user = User::factory()->pro()->create();

        $this->actingAs($user)
            ->get(route('dashboard.billing.index', ['checkout' => 'success']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('billing/index')
                ->where('billingConfigured', true)
                ->where('currentPlan', 'pro')
                ->where('checkoutStatus', 'success'));
    }

    public function test_checkout_redirects_back_when_billing_is_not_configured(): void
    {
        config([
            'cashier.key' => null,
            'cashier.secret' => null,
            'services.stripe.pro_price_id' => null,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard.billing.index'))
            ->post(route('dashboard.billing.checkout'))
            ->assertRedirect(route('dashboard.billing.index'));
    }
}
