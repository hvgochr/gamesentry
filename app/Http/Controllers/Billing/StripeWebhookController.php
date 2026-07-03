<?php

namespace App\Http\Controllers\Billing;

use App\Models\User;
use App\Services\Billing\SubscriptionPlanService;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
    public function __construct(private readonly SubscriptionPlanService $plans)
    {
        parent::__construct();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->syncPlanForCustomer($payload);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): ?Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncPlanForCustomer($payload);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $this->syncPlanForCustomer($payload);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerDeleted(array $payload): Response
    {
        $user = $this->userFromPayload($payload);
        $response = parent::handleCustomerDeleted($payload);

        if ($user !== null) {
            $this->plans->sync($user);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncPlanForCustomer(array $payload): void
    {
        $user = $this->userFromPayload($payload);

        if ($user !== null) {
            $this->plans->sync($user);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function userFromPayload(array $payload): ?User
    {
        $customerId = data_get($payload, 'data.object.customer')
            ?? data_get($payload, 'data.object.id');

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        return User::query()
            ->where('stripe_id', $customerId)
            ->first();
    }
}
