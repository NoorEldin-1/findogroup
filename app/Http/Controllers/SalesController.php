<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingFollowUps;
use App\Services\OperatingSales;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class SalesController extends Controller
{
    public function __construct(private OperatingSales $sales, private OperatingFollowUps $followUps, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function pipeline(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'opportunities'), 403);
        $filters = $request->validate(['assignee' => 'nullable|integer', 'source' => 'nullable|string|max:40']);

        return $this->view('opportunities-pipeline', $this->sales->pipeline($request->user(), $filters) + [
            'filters' => $filters,
            'assignees' => $this->assignees($request),
            'sources' => collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options'],
        ]);
    }

    public function convertLead(int $id, Request $request): Response
    {
        $input = $request->validate(['reason' => 'required|string|max:2000']);
        $client = $this->sales->convertToClient($request->user(), $id, $input['reason']);

        return redirect('/admin/operations/record/'.$client->id)->with('success', __('تم تحويل الفرصة إلى عميل وربطها به.'));
    }

    public function clientProfile(int $id, Request $request): Response
    {
        $client = $this->access->record($request->user(), $id);
        abort_unless($client->type === 'clients', 404);

        return $this->view('client-profile', $this->sales->clientProfile($request->user(), $client));
    }

    public function followUps(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'activities'), 403);
        $input = $request->validate(['assignee' => 'nullable|integer']);

        return $this->view('activities-followups', ['board' => $this->followUps->board($request->user(), $input['assignee'] ?? null), 'assignee' => $input['assignee'] ?? null, 'assignees' => $this->assignees($request)]);
    }

    /**
     * Only managers who can assign work get the rep filter; a rep already sees just their own records.
     *
     * @return Collection<int, string>
     */
    private function assignees(Request $request): Collection
    {
        if (! in_array($request->user()->role, ['owner', 'sales_manager', 'delivery'], true) || ! $this->access->can($request->user(), 'employees')) {
            return collect();
        }

        return $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function view(string $page, array $data): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }
}
