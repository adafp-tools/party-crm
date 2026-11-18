<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSequence extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'membership_sequences';

    protected $fillable = [
        'org_unit_id',
        'next_seq',
    ];

    protected function casts(): array
    {
        return [
            'next_seq' => 'integer',
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }
}
