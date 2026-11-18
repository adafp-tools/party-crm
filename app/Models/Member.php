<?php

namespace App\Models;

use App\Scopes\UnitScopedScope;
use App\Services\BlindIndexService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'org_unit_id',
        'membership_no',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'email_hash',
        'phone',
        'phone_hash',
        'address_line',
        'occupation',
        'preferred_language',
        'category',
        'status',
        'joined_date',
        'is_youth_wing',
        'is_women_wing',
        'volunteer',
        'skills',
        'photo_path',
        'consent_at',
        'consent_version',
        'consent_channel',
        'sms_opt_out',
        'email_opt_out',
        'created_by',
        'updated_by',
        'client_created_at',
        'sync_version',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_date' => 'date',
            'consent_at' => 'datetime',
            'client_created_at' => 'datetime',
            'is_youth_wing' => 'boolean',
            'is_women_wing' => 'boolean',
            'volunteer' => 'boolean',
            'sms_opt_out' => 'boolean',
            'email_opt_out' => 'boolean',
            'skills' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new UnitScopedScope);

        static::saving(function (Member $member) {
            $bi = app(BlindIndexService::class);
            if ($member->isDirty('email')) {
                $member->email_hash = $member->email ? $bi->compute($member->email) : null;
            }
            if ($member->isDirty('phone')) {
                $member->phone_hash = $member->phone ? $bi->compute($member->phone) : null;
            }
        });
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MemberStatusHistory::class)->orderByDesc('changed_at');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class)->orderByDesc('occurred_at');
    }

    public function duesPayments(): HasMany
    {
        return $this->hasMany(DuesPayment::class)->orderByDesc('created_at');
    }

    public function fullName(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    public function recordStatusChange(string $toStatus, ?string $reason = null, ?string $changedBy = null): void
    {
        MemberStatusHistory::create([
            'member_id' => $this->id,
            'from_status' => $this->status,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => $changedBy ?? auth()->id(),
            'changed_at' => now(),
        ]);

        $this->update(['status' => $toStatus]);
    }
}
