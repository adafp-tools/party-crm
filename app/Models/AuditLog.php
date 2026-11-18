<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    public $updatedAt = null;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'changed_fields',
        'ip_hash',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $subject = null, array $changedFields = []): void
    {
        $request = request();

        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'changed_fields' => $changedFields ?: null,
            'ip_hash' => $request ? hash('sha256', $request->ip() ?? '') : null,
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
