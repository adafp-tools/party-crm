<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuesPlan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'amount',
        'currency',
        'period',
        'applies_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DuesPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
