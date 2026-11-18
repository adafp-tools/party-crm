<?php

namespace App\Gateways;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * No-op SMS gateway for local development and testing.
 * Logs the message rather than sending it.
 */
class NullSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): bool
    {
        Log::info('SMS (null gateway)', ['to' => $phone, 'body' => $message]);

        return true;
    }
}
