<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingMilestones;
use App\Services\OperatingProjects;
use App\Services\OperatingSprints;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(private OperatingProjects $projects, private OperatingSprints $sprints, private OperatingMilestones $milestones, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function milestones(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->access->can($user, 'milestones'), 403);
        $filters = $request->validate(['project' => 'nullable|integer', 'owner' => 'nullable|integer']);

        return response()->view('admin.operating.milestones-tracker', $this->milestones->tracker($user, $filters) + [
            'filters' => $filters,
            'projects' => $this->access->query($user, 'projects')->where('status', '!=', 'archived')->orderBy('title')->pluck('title', 'id'),
            'owners' => $this->access->can($user, 'employees') ? $this->access->query($user, 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect(),
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }

    public function portfolio(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'projects'), 403);
        $filters = $request->validate(['status' => 'nullable|string|max:40', 'priority' => 'nullable|string|max:40', 'type' => 'nullable|string|max:40']);
        $fields = collect($this->catalog->entity('projects')['fields']);

        return response()->view('admin.operating.projects-portfolio', [
            'rows' => $this->projects->portfolio($request->user(), $filters),
            'filters' => $filters,
            'priorities' => $fields->firstWhere('key', 'priority')['options'],
            'types' => $fields->firstWhere('key', 'project_type')['options'],
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }

    public function sprints(int $id, Request $request): Response
    {
        $user = $request->user();
        $project = $this->access->record($user, $id);
        abort_unless($project->type === 'projects', 404);
        abort_unless($this->access->can($user, 'tasks') && $this->access->can($user, 'sprints'), 403);

        return response()->view('admin.operating.project-sprints', $this->sprints->board($user, $project) + [
            'project' => $project,
            'canPlan' => in_array($user->role, ['owner', 'delivery', 'tech_lead'], true),
            'priorities' => collect($this->catalog->entity('tasks')['fields'])->firstWhere('key', 'priority')['options'],
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }

    public function planSprint(int $id, Request $request): Response
    {
        $project = $this->access->record($request->user(), $id);
        abort_unless($project->type === 'projects', 404);
        $input = $request->validate(['sprint_id' => 'nullable|integer', 'task_ids' => 'required|array|min:1|max:100', 'task_ids.*' => 'integer', 'reason' => 'required|string|max:2000']);
        $moved = $this->sprints->plan($request->user(), $project, $input['sprint_id'] ?? null, $input['task_ids'], $input['reason']);

        return back()->with('success', __('عدد المهام المنقولة: :count', ['count' => $moved]));
    }
}
