<?php

namespace App\Http\Controllers;

use App\Services\SeoPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function index()
    {
        $base = config('website.seo_base_url');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (['page-sitemap.xml', 'post-sitemap.xml'] as $file) {
            $xml .= '<sitemap><loc>'.htmlspecialchars($base.'/'.$file, ENT_XML1).'</loc></sitemap>';
        }

        return response($xml.'</sitemapindex>')->header('Content-Type', 'application/xml')->header('X-Robots-Tag', 'noindex');
    }

    public function urls(string $type = 'page')
    {
        $query = DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('r.indexable', true)->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()));
        $type === 'post' ? $query->where('c.kind', 'post') : $query->where('c.kind', '!=', 'post');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($query->join('content_translations as t', fn ($q) => $q->on('t.content_id', '=', 'c.id')->where('t.locale', 'ar'))->select('r.path', 'c.source_updated_at', 't.seo')->orderBy('r.path')->get() as $row) {
            $seo = json_decode($row->seo, true) ?: [];
            if (! app(SeoPolicy::class)->allowsIndexing($seo) || ! app(SeoPolicy::class)->isSelfCanonical($row->path, $seo)) {
                continue;
            }
            $xml .= '<url><loc>'.htmlspecialchars(rtrim(config('website.seo_base_url'), '/').$row->path, ENT_XML1).'</loc>';
            $modifiedAt = $row->source_updated_at ? Carbon::parse($row->source_updated_at) : null;
            if ($modifiedAt && $modifiedAt->lessThanOrEqualTo(now())) {
                $xml .= '<lastmod>'.$modifiedAt->toIso8601String().'</lastmod>';
            }
            $xml .= '</url>';
        }

        return response($xml.'</urlset>')->header('Content-Type', 'application/xml')->header('X-Robots-Tag', 'noindex');
    }
}
