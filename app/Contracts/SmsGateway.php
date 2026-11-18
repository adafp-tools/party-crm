<?php

namespace App\Contracts;

/**
 * Driver interface for outbound SMS providers.
 *
 * To add a new provider (Africa's Talking, Twilio, Nexmo):
 *   1. Create app/Gateways/AfricasTalkingGateway.php implementing this interface.
 *   2. Bind it in AppServiceProvider::register() behind a config flag.
 *   3. Document auth credentials in .env.example.
 */
interface SmsGateway
{
    /**
     * Send an SMS message to the given phone number.
     * Returns true on success, false on failure.
     */
    public function send(string $phone, string $message): bool;
}
