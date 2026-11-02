<?php

namespace App\Models;

use App\Enums\ItemKind;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['project_id', 'title', 'kind', 'occurs_at', 'position', 'completed_at'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'occurs_at' => 'datetime',
            'completed_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function nextPositionFor(int $projectId): int
    {
        $max = static::query()->where('project_id', $projectId)->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    public function isBottom(): bool
    {
        if ($this->completed_at !== null) {
            return false;
        }

        $bottomId = static::query()
            ->where('project_id', $this->project_id)
            ->whereNull('completed_at')
            ->orderBy('position')
            ->orderBy('id')
            ->value('id');

        return $bottomId === $this->id;
    }

    public function complete(): void
    {
        DB::transaction(function (): void {
            $current = static::query()->findOrFail($this->id);

            if ($current->completed_at !== null || ! $current->isBottom()) {
                throw ValidationException::withMessages([
                    'item' => 'Bara det understa i röret kan markeras som klart.',
                ]);
            }

            $current->forceFill(['completed_at' => now()])->save();
        });
    }

    public function move(string $direction): void
    {
        DB::transaction(function () use ($direction): void {
            $current = static::query()->findOrFail($this->id);

            if ($current->completed_at !== null) {
                throw ValidationException::withMessages([
                    'item' => 'Den är redan klar.',
                ]);
            }

            $neighbors = static::query()
                ->where('project_id', $current->project_id)
                ->whereNull('completed_at')
                ->whereKeyNot($current->id);

            $neighbor = $direction === 'down'
                ? $neighbors->where('position', '<', $current->position)->orderByDesc('position')->orderByDesc('id')->first()
                : $neighbors->where('position', '>', $current->position)->orderBy('position')->orderBy('id')->first();

            if ($neighbor === null) {
                throw ValidationException::withMessages([
                    'direction' => $direction === 'down'
                        ? 'Den ligger redan längst ner.'
                        : 'Den ligger redan överst.',
                ]);
            }

            $position = $current->position;
            $current->position = $neighbor->position;
            $neighbor->position = $position;
            $current->save();
            $neighbor->save();
        });
    }
}
