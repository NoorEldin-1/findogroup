<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingProjects;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(private OperatingProjects $projects, private OperatingAccess $access, private OperatingCatalog $catalog) {}

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
}
