<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebsiteContent
{
    public function page(string $path, string $locale): ?array
    {
        return Cache::remember('website:v2:'.Cache::get('cms:generation', 0).':'.hash('sha256', $path.'|'.$locale), max(30, min(3600, (int) (app(Cms::class)->collection('settings')['cache_seconds'] ?? 300))), function () use ($path, $locale) {
            $row = DB::table('content_routes as r')
                ->join('contents as c', 'c.id', '=', 'r.content_id')
                ->join('content_translations as t', 't.content_id', '=', 'c.id')
                ->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))
                ->where('r.path', $path)->where('t.locale', $locale)->where('c.status', 'published')
                ->select('r.path', 'r.indexable', 'c.id', 'c.kind', 'c.published_at', 'c.source_updated_at', 't.title', 't.body_html', 't.seo', 't.view_data')->first();
            if (! $row && $locale !== 'ar') {
                return $this->page($path, 'ar');
            }
            if (! $row) {
                return null;
            }
            $data = (array) $row;
            $data['seo'] = json_decode($row->seo, true) ?: [];
            $data['view_data'] = json_decode($row->view_data ?? '{}', true) ?: [];

            $data['design'] = json_decode(DB::table('cms_designs')->where('content_id', $data['id'])->where('locale', $locale)->value('data') ?? '{}', true);
            $data['body_html'] = app(CmsHtml::class)->fragments($data['body_html'], app(Cms::class)->collection('__fragments')[$locale] ?? []);
            $data['body_html'] = app(CmsHtml::class)->apply(app(CmsHtml::class)->apply($data['body_html'], app(Cms::class)->siteDesign()), $data['design']);
            $data['body_html'] = app(CmsHtml::class)->mediaAlts($data['body_html'], app(Cms::class)->mediaAlts());
            $data['seo']['schemas'] = app(WebsiteSchema::class)->normalize($data['seo']['schemas'] ?? [], $data['body_html'], $data['kind'], app(Cms::class)->collection('settings'));

            return $data;
        });
    }
}
