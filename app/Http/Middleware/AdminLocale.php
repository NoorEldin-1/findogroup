<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->input('ui_lang'), ['ar', 'en'], true)) {
            $request->session()->put('admin_locale', $request->input('ui_lang'));
        }
        app()->setLocale($request->session()->get('admin_locale', 'ar'));
        $rules = DB::table('operating_events')->where('action', 'configure_rules')->where('after->effective_date', '<=', today()->toDateString())->latest('id')->value('after')
            ?? DB::table('operating_settings')->where('key', 'rules')->value('data');
        if ($rules) {
            $settings = json_decode($rules, true);
            if (($settings['effective_date'] ?? '9999-12-31') <= today()->toDateString()) {
                config(['operating.thresholds' => $settings['thresholds'], 'operating.scorecards' => $settings['scorecards']]);
            }
        }

        return $next($request);
    }
}
