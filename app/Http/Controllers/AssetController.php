<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingAssets;
use App\Services\OperatingCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function __construct(private OperatingAssets $assets, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function overview(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'assets') && $this->access->managesAssets($request->user()), 403);

        return response()->view('admin.operating.assets-overview', $this->assets->overview($request->user()) + [
            'categories' => collect($this->catalog->entity('assets')['fields'])->firstWhere('key', 'category')['options'],
            'units' => collect($this->catalog->entity('inventory_items')['fields'])->firstWhere('key', 'unit')['options'],
            'movementKinds' => collect($this->catalog->entity('asset_movements')['fields'])->firstWhere('key', 'movement')['options'] + collect($this->catalog->entity('stock_movements')['fields'])->firstWhere('key', 'movement')['options'],
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }
}
