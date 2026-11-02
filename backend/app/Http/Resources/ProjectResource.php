<?php

namespace App\Http\Resources;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->activeItems;
        $bottomId = $items->sortBy([['position', 'asc'], ['id', 'asc']])->first()?->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'items' => $items->map(fn (Item $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'kind' => $item->kind instanceof ItemKind ? $item->kind->value : (string) $item->kind,
                'occurs_at' => $item->occurs_at?->toIso8601String(),
                'position' => $item->position,
                'is_bottom' => $item->id === $bottomId,
            ])->values(),
        ];
    }
}
