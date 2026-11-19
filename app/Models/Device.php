<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'platform',
        'token_id',
        'last_synced_at',
        'revoked_at',
        'wipe_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'revoked_at' => 'datetime',
            'wipe_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function needsWipe(): bool
    {
        return $this->wipe_requested_at !== null;
    }
}
