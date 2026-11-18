<?php

namespace App\Models;

use App\Scopes\UnitScopedScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'org_unit_id',
        'title',
        'type',
        'starts_at',
        'ends_at',
        'location_text',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function attendanceRate(): float
    {
        $total = $this->attendance()->whereIn('status', ['attended', 'absent'])->count();

        if ($total === 0) {
            return 0.0;
        }

        return round($this->attendance()->where('status', 'attended')->count() / $total * 100, 1);
    }
}
