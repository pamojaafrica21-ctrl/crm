<?php

namespace App\Http\Controllers;

use App\Application\Billing\StripePlanSyncService;
use App\Domain\Organizations\Models\Organization;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(private StripePlanSyncService $stripePlanSync)
    {
        parent::__construct();
        $this->stripePlanSync->configureStripe();
    }

    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->updateOrganizationStatus($payload, Organization::STATUS_ACTIVE);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $status = ($payload['data']['object']['status'] ?? '') === 'active'
            ? Organization::STATUS_ACTIVE
            : Organization::STATUS_PAST_DUE;

        $this->updateOrganizationStatus($payload, $status);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $this->updateOrganizationStatus($payload, Organization::STATUS_CANCELLED);

        return $response;
    }

    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $response = parent::handleInvoicePaymentFailed($payload);

        $customerId = $payload['data']['object']['customer'] ?? null;
        if ($customerId) {
            Organization::where('stripe_id', $customerId)->update(['status' => Organization::STATUS_PAST_DUE]);
        }

        return $response;
    }

    private function updateOrganizationStatus(array $payload, string $status): void
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if ($customerId) {
            Organization::where('stripe_id', $customerId)->update([
                'status' => $status,
                'trial_ends_at' => null,
            ]);
        }
    }
}
