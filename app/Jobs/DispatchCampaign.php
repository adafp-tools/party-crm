<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Mail\CampaignMailer;
use App\Models\Campaign;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DispatchCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(SmsGateway $sms): void
    {
        // Jobs run without auth context — bypass UnitScopedScope.
        $query = Member::withoutGlobalScopes()
            ->where('status', 'active');

        if ($this->campaign->org_unit_id) {
            $query->where('org_unit_id', $this->campaign->org_unit_id);
        }

        $count = 0;

        if ($this->campaign->type === 'sms') {
            $recipients = (clone $query)
                ->where('sms_opt_out', false)
                ->whereNotNull('phone')
                ->cursor();

            foreach ($recipients as $member) {
                if ($sms->send($member->phone, $this->campaign->body)) {
                    $count++;
                }
            }
        } else {
            $recipients = (clone $query)
                ->where('email_opt_out', false)
                ->whereNotNull('email')
                ->cursor();

            foreach ($recipients as $member) {
                Mail::to($member->email)->send(new CampaignMailer($this->campaign, $member));
                $count++;
            }
        }

        Campaign::withoutGlobalScopes()->where('id', $this->campaign->id)->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_count' => $count,
        ]);
    }

    public function failed(Throwable $e): void
    {
        Campaign::withoutGlobalScopes()->where('id', $this->campaign->id)->update([
            'status' => 'failed',
        ]);
    }
}
