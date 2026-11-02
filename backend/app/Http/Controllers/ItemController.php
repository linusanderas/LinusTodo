<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveItemRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function store(StoreItemRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();

        $project->items()->create([
            'title' => $data['title'],
            'kind' => $data['kind'],
            'occurs_at' => $data['kind'] === 'todo' ? null : $data['occurs_at'],
            'position' => Item::nextPositionFor($project->id),
        ]);

        return (new ProjectResource($project->forResponse()))
            ->response()
            ->setStatusCode(201);
    }

    public function move(MoveItemRequest $request, Project $project, Item $item): ProjectResource
    {
        $this->ensureItemInProject($project, $item);
        $item->move($request->validated('direction'));

        return new ProjectResource($project->forResponse());
    }

    public function complete(Project $project, Item $item): ProjectResource
    {
        $this->ensureItemInProject($project, $item);
        $item->complete();

        return new ProjectResource($project->forResponse());
    }

    private function ensureItemInProject(Project $project, Item $item): void
    {
        abort_unless((int) $item->project_id === (int) $project->id, 404);
    }
}
