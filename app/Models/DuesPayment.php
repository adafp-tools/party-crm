<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuesPayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'member_id',
        'dues_plan_id',
        'amount',
        'currency',
        'period_from',
        'period_to',
        'method',
        'reference',
        'receipt_no',
        'status',
        'rejection_reason',
        'recorded_by',
        'confirmed_by',
        'reconciled_by',
        'confirmed_at',
        'reconciled_at',
        'client_uuid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_from' => 'date',
            'period_to' => 'date',
            'confirmed_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function duesPlan(): BelongsTo
    {
        return $this->belongsTo(DuesPlan::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopeRecorded($query)
    {
        return $query->where('status', 'recorded');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeReconciled($query)
    {
        return $query->where('status', 'reconciled');
    }
}
