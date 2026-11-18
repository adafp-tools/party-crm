<?php

namespace App\Contracts;

use App\Models\DuesPayment;

/**
 * Driver interface for payment providers.
 *
 * To add a new provider (telebirr, Wave, Orange Money):
 *   1. Create app/Gateways/TelebirrGateway.php implementing this interface.
 *   2. Bind it in AppServiceProvider::register() behind a config flag.
 *   3. Register a webhook route pointing to handleWebhook().
 *   4. Document auth credentials in .env.example.
 */
interface PaymentGateway
{
    /**
     * Initiate a payment request with the provider.
     * Returns the provider's transaction ID or redirect URL.
     *
     * @return array{transaction_id: string, redirect_url: ?string}
     */
    public function initiate(DuesPayment $payment): array;

    /**
     * Poll the provider for the current status of a transaction.
     *
     * @return array{status: string, provider_reference: ?string}
     */
    public function checkStatus(string $transactionId): array;

    /**
     * Handle an inbound webhook from the provider and update the payment.
     */
    public function handleWebhook(array $payload): void;
}
