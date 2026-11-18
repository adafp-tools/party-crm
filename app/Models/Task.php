<?php

namespace App\Models;

use App\Scopes\UnitScopedScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'org_unit_id',
        'title',
        'details',
        'assignee_id',
        'related_member_id',
        'due_at',
        'status',
        'digest_opt_out',
        'digest_sent_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'digest_sent_at' => 'datetime',
            'digest_opt_out' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new UnitScopedScope);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function relatedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'related_member_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! in_array($this->status, ['done', 'cancelled']);
    }
}
