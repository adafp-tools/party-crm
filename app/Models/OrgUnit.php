<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class OrgUnit extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'org_level_id',
        'name',
        'code',
        'path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgUnit::class, 'parent_id');
    }

    public function orgLevel(): BelongsTo
    {
        return $this->belongsTo(OrgLevel::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function computePath(): string
    {
        if (! $this->parent_id) {
            return '/'.$this->id.'/';
        }

        $parent = $this->parent ?? OrgUnit::find($this->parent_id);

        return $parent->path.$this->id.'/';
    }

    /**
     * Load the full tree as a nested array, rooted at $parentId (null = top-level).
     *
     * @param  array<OrgUnit>  $flat  All units pre-fetched
     * @return array<int, array{unit: OrgUnit, children: array}>
     */
    public static function buildTree(array $flat, ?string $parentId = null): array
    {
        $nodes = [];
        foreach ($flat as $unit) {
            if ($unit->parent_id === $parentId) {
                $nodes[] = [
                    'unit' => $unit,
                    'children' => static::buildTree($flat, $unit->id),
                ];
            }
        }

        return $nodes;
    }

    /**
     * Update path for this unit and all its descendants in one transaction.
     * Uses recursive traversal so the initial path value never affects descendants.
     */
    public function rebuildPath(): void
    {
        DB::transaction(function () {
            $this->rebuildPathRecursive();
        });
    }

    private function rebuildPathRecursive(): void
    {
        $newPath = $this->computePath();
        $this->updateQuietly(['path' => $newPath]);
        $this->path = $newPath;

        $this->children()->get()->each(fn (OrgUnit $child) => $child->rebuildPathRecursive());
    }
}
