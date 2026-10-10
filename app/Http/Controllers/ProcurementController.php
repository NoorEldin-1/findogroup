<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingProcurement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcurementController extends Controller
{
    public function __construct(private OperatingProcurement $procurement, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function dashboard(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'purchase_requests'), 403);

        return response()->view('admin.operating.procurement-dashboard', $this->procurement->dashboard($request->user()) + [
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }
}
