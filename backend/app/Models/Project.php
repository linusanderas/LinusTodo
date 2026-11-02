<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'position'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Active items, top of the pipe first.
     *
     * @return HasMany<Item, $this>
     */
    public function activeItems(): HasMany
    {
        return $this->items()
            ->whereNull('completed_at')
            ->orderByDesc('position')
            ->orderByDesc('id');
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeOrderedWithItems(Builder $query): Builder
    {
        return $query->with('activeItems')->orderBy('position')->orderBy('id');
    }

    public static function nextPipePosition(): int
    {
        $max = static::query()->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    public function forResponse(): static
    {
        return static::query()->orderedWithItems()->findOrFail($this->id);
    }
}
