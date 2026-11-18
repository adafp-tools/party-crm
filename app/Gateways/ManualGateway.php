<?php

namespace App\Gateways;

use App\Contracts\PaymentGateway;
use App\Models\DuesPayment;

/**
 * Manual (cash / mobile-money reference) gateway — no API calls.
 *
 * All confirmation and reconciliation is done by a treasurer in the UI.
 * This is the only gateway shipped in v1.
 */
class ManualGateway implements PaymentGateway
{
    public function initiate(DuesPayment $payment): array
    {
        return [
            'transaction_id' => $payment->id,
            'redirect_url' => null,
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $payment = DuesPayment::find($transactionId);

        return [
            'status' => $payment?->status ?? 'unknown',
            'provider_reference' => null,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        // Manual gateway has no webhooks.
    }
}
