<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'member_status_history';

    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
