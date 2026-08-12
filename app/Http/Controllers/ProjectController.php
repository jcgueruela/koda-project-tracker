<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectController
{
    public function index(Request $request): JsonResponse
    {
        $sortable = ['name', 'client_name', 'status', 'priority', 'start_date', 'due_date', 'created_at'];

        $sort = in_array($request->query('sort'), $sortable, true)
            ? $request->query('sort')
            : 'created_at';

        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $projects = Project::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = mb_strtolower($request->query('search'));

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(client_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return ProjectResource::collection($projects)->response();
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

        return ProjectResource::make($project)->response()->setStatusCode(201);
    }

    public function show(Project $project): JsonResponse
    {
        return ProjectResource::make($project)->response();
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return ProjectResource::make($project)->response();
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }
}
