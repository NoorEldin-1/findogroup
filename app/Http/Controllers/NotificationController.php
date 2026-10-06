<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access) {}

    public function index(Request $request): Response
    {
        return response()->view('admin.operating.notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(30),
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }

    public function markRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('تم تعليم الإشعارات كمقروءة.'));
    }
}
