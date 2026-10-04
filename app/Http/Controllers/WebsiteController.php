<?php

namespace App\Http\Controllers;

use App\Services\LegacyRedirects;
use App\Services\SeoPolicy;
use App\Services\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteController extends Controller
{
    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function redirectUrl(Request $request, string $target, array $ignoredQuery = []): string
    {
        $parts = parse_url(preg_replace_callback('/[^\x00-\x7f]/u', fn ($match) => rawurlencode($match[0]), $target));
        parse_str($parts['query'] ?? '', $targetQuery);
        $incomingQuery = array_diff_key($request->query(), array_flip($ignoredQuery));
        $query = http_build_query(array_replace($incomingQuery, $targetQuery), '', '&', PHP_QUERY_RFC3986);
        $url = $this->encodePath(rawurldecode($parts['path'] ?? '/'));

        return $url.($query !== '' ? '?'.$query : '').(isset($parts['fragment']) ? '#'.rawurlencode(rawurldecode($parts['fragment'])) : '');
    }

    public function __invoke(Request $request, WebsiteContent $content, string $path = '')
    {
        $path = '/'.ltrim(rawurldecode($request->getPathInfo()), '/');
        $normalized = $path === '/' ? '/' : rtrim($path, '/');
        $missingLegacyPost = false;
        if ($normalized === '/' && $request->query->has('p')) {
            $sourceId = $request->query('p');
            $target = is_string($sourceId) && ctype_digit($sourceId) ? app(LegacyRedirects::class)->postTarget($sourceId) : null;
            if ($target) {
                return redirect($this->redirectUrl($request, $target, ['p']), 301);
            }
            $missingLegacyPost = true;
        }
        $redirect = DB::table('redirects')->where('source_path', $normalized)->first();
        if ($redirect && ! DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('r.path', $normalized)->where('c.status', 'published')->where(fn ($query) => $query->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))->exists()) {
            $target = $redirect->target_path;
            if (! DB::table('content_routes')->where('path', $target)->exists()) {
                $target = app(LegacyRedirects::class)->target($target) ?? $target;
            }

            return redirect($this->redirectUrl($request, $target), $redirect->status);
        }
        if (! DB::table('content_routes')->where('path', $normalized)->exists()) {
            $legacyTarget = app(LegacyRedirects::class)->target($normalized);
            if ($legacyTarget) {
                return redirect($this->redirectUrl($request, $legacyTarget), 301);
            }
        }
        if ($path !== $normalized) {
            return redirect($this->redirectUrl($request, $normalized), 301);
        }
        $locale = in_array($request->cookie('findo_language'), ['ar', 'en'], true) ? $request->cookie('findo_language') : 'ar';
        $page = $missingLegacyPost ? null : $content->page($normalized, $locale);
        $status = $page && $normalized !== '/__not-found' ? 200 : 404;
        if (! $page) {
            $page = $content->page('/__not-found', $locale) ?? ['title' => 'الصفحة غير موجودة', 'body_html' => '<main><h1>الصفحة غير موجودة</h1><a href="/">الرئيسية</a></main>', 'seo' => [], 'view_data' => [], 'path' => $normalized, 'indexable' => false];
            $page['indexable'] = false;
            $page['path'] = $normalized;
        }
        $page['body_html'] = preg_replace('~https://s\.w\.org/images/core/emoji/[^/]+/svg/~', '/assets/emoji/', $page['body_html']);
        $archived = DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('r.path', $normalized)->where('c.status', 'archived')->exists();
        if ($archived && $status === 404) {
            $status = 410;
        }
        $noindex = config('website.prevent_indexing') || ! $page['indexable'] || ! app(SeoPolicy::class)->allowsIndexing($page['seo']);

        return response()->view('website.page', compact('page', 'locale', 'noindex', 'status'), $status)
            ->header('X-Robots-Tag', $noindex ? 'noindex, follow' : 'index, follow')
            ->header('Content-Security-Policy', "connect-src 'self'; form-action 'self'; base-uri 'self'; object-src 'none'")
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Cache-Control', 'private, no-cache');
    }
}
