<?php

namespace App\Models;

use App\Scopes\CampaignUnitScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'org_unit_id',
        'type',
        'title',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_at',
        'recipient_count',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CampaignUnitScope);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
