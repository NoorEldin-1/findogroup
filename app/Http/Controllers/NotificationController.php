<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /**
     * Inbox tabs: the notification kinds each one collects; "all" and "unread" are not filtered by kind.
     */
    public const TABS = ['all' => [], 'unread' => [], 'sla' => ['crm_sla'], 'leads' => ['lead_assigned'], 'reminders' => ['follow_up_due', 'follow_up_overdue']];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access) {}

    public function index(Request $request): Response
    {
        $tab = array_key_exists((string) $request->input('tab'), self::TABS) ? (string) $request->input('tab') : 'all';
        $scoped = function (string $name) use ($request) {
            $query = $request->user()->notifications();
            if ($name === 'unread') {
                $query->whereNull('read_at');
            }
            if (self::TABS[$name]) {
                $query->where(fn ($kinds) => collect(self::TABS[$name])->each(fn (string $kind) => $kinds->orWhere('data', 'like', '%"kind":"'.$kind.'"%')));
            }

            return $query;
        };

        return response()->view('admin.operating.notifications', [
            'notifications' => $scoped($tab)->latest()->paginate(30)->withQueryString(),
            'tab' => $tab,
            'counts' => collect(array_keys(self::TABS))->mapWithKeys(fn (string $name): array => [$name => $name === 'all' ? $request->user()->unreadNotifications()->count() : $scoped($name)->whereNull('read_at')->count()])->all(),
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
