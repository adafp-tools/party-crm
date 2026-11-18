<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'member_id',
        'type',
        'summary',
        'occurred_at',
        'by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function byUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by_user_id');
    }
}
