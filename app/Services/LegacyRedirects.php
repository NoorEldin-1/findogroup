<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LegacyRedirects
{
    public function postTarget(string $sourceId): ?string
    {
        return DB::table('cms_posts as p')
            ->join('contents as c', 'c.id', '=', 'p.content_id')
            ->join('content_routes as r', 'r.content_id', '=', 'c.id')
            ->where('p.data->id', (int) $sourceId)
            ->where('c.status', 'published')
            ->where(fn ($query) => $query->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))
            ->orderByDesc('r.indexable')
            ->value('r.path');
    }

    public function target(string $path): ?string
    {
        $plain = preg_replace('~^/(?:ar|en|en_us)(?=/|$)~', '', $path) ?: '/';
        $pageAliases = [
            '/تواصل-معنا' => '/contact',
            '/previous-works' => '/portfolio',
            '/social-media-package' => '/services/social-media',
        ];
        if (isset($pageAliases[$plain])) {
            return $pageAliases[$plain];
        }
        $services = [
            'mobile-app-design-and-development' => 'mobile-apps',
            'web-application-design-and-development' => 'web-ecommerce',
            'ui-ux-design-services' => 'ui-ux', 'best-ui-ux-design-services' => 'ui-ux',
            'graphic-design' => 'graphic-design', 'social-media' => 'social-media',
            'maintenance-and-customer-support' => 'maintenance',
            'audit-it-consulting-services' => 'tech-review',
            'market-analysis-and-expansion-strategy' => 'marketing',
        ];
        if (str_starts_with($plain, '/service/') && isset($services[substr($plain, 9)])) {
            return '/services/'.$services[substr($plain, 9)];
        }
        if ($plain === '/privacy-policy-2') {
            return '/privacy-policy';
        }
        if (preg_match('~^/project/.+~', $plain)) {
            return '/portfolio';
        }
        if (preg_match('~^/(category|tag|author)/.+~', $plain) || preg_match('~^/20\d{2}(?:/\d{2}){0,2}$~', $plain)) {
            return '/blogs';
        }
        if (in_array($plain, ['/feed', '/rss', '/comments/feed'], true)) {
            return '/';
        }
        $candidate = preg_replace('~^/20\d{2}/~', '/', $plain);
        $candidate = preg_replace('~/feed$~', '', $candidate);
        if (! str_starts_with($candidate, '/blogs/') && DB::table('content_routes')->where('path', '/blogs'.$candidate)->exists()) {
            return '/blogs'.$candidate;
        }
        if ($plain !== $path && DB::table('content_routes')->where('path', $plain)->exists()) {
            return $plain;
        }

        return null;
    }
}
