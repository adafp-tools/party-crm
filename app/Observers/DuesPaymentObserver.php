<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\DuesPayment;

class DuesPaymentObserver
{
    public function created(DuesPayment $payment): void
    {
        AuditLog::record('dues_payment.recorded', $payment);
    }

    public function updated(DuesPayment $payment): void
    {
        if (! $payment->wasChanged('status')) {
            return;
        }

        AuditLog::record('dues_payment.'.$payment->status, $payment, ['status']);
    }
}
