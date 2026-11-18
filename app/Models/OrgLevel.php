<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgLevel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['depth', 'name_key', 'code_required'];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'code_required' => 'boolean',
        ];
    }
}
