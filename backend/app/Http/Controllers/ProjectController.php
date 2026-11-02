<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProjectResource::collection(
            Project::query()->orderedWithItems()->get()
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::query()->create([
            'name' => $request->validated('name'),
            'position' => Project::nextPipePosition(),
        ]);

        return (new ProjectResource($project->forResponse()))
            ->response()
            ->setStatusCode(201);
    }
}
