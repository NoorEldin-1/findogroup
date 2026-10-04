<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'))->header('X-Robots-Tag', 'noindex, nofollow');
        }
        abort_unless($request->user()->active && array_key_exists($request->user()->role, config('operating.roles')), 403);
        if (! $request->is('admin', 'admin/logout', 'admin/operations/*', 'admin/api/v1/operations*')) {
            $role = $request->user()->cmsRole();
            abort_unless($role, 403);
            if ($request->is('admin/users', 'admin/revisions*', 'admin/performance*')) {
                abort_unless($request->user()->role === 'owner', 403);
            }
            if ($request->is('admin/collections/*') && $role === 'seo') {
                abort(403);
            }
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
