<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WebsiteSeoAudit
{
    public function __construct(private CmsHtml $html) {}

    public function run(string $locale): array
    {
        $rows = DB::table('contents as c')->join('content_routes as r', 'r.content_id', '=', 'c.id')->join('content_translations as t', fn ($q) => $q->on('t.content_id', '=', 'c.id')->where('t.locale', $locale))
            ->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))
            ->select('c.id', 'c.status', 'c.kind', 'r.path', 'r.indexable', 't.title', 't.seo', 't.body_html')->get();
        $issues = [];
        $titles = [];
        $total = 0;
        $checkedIds = [];
        $excluded = 0;
        $policy = app(SeoPolicy::class);
        $designs = DB::table('cms_designs')->where('locale', $locale)->pluck('data', 'content_id');
        foreach ($rows as $row) {
            $seo = json_decode($row->seo, true) ?: [];
            if (! $row->indexable || ! $policy->allowsIndexing($seo)) {
                $excluded++;

                continue;
            }
            $total++;
            $checkedIds[] = $row->id;
            $errors = [];
            $warnings = [];
            if (empty($seo['title'])) {
                $errors[] = 'عنوان SEO مفقود';
            }
            if (empty($seo['description'])) {
                $warnings[] = 'وصف البحث مفقود';
            }
            if (empty($seo['canonical'])) {
                $errors[] = 'Canonical مفقود';
            } elseif (! $policy->validCanonical($seo['canonical'])) {
                $errors[] = 'Canonical غير صالح';
            } elseif (! $policy->isSelfCanonical($row->path, $seo)) {
                $warnings[] = 'Canonical يشير لرابط آخر؛ مستبعد من خريطة الموقع';
            }
            if (mb_strlen($seo['title'] ?? '') > 65) {
                $warnings[] = 'عنوان طويل؛ راجع المعاينة';
            }
            if (mb_strlen($seo['description'] ?? '') > 170) {
                $warnings[] = 'وصف طويل؛ راجع المعاينة';
            }
            if (empty($seo['schemas'])) {
                $warnings[] = 'بدون بيانات منظمة؛ راجع مدى ملاءمتها لهذه الصفحة';
            } else {
                foreach ($seo['schemas'] as $schema) {
                    if (! is_array($schema) || (! isset($schema['@type']) && ! isset($schema['@graph']))) {
                        $errors[] = 'راجع بنية Schema ونوعها';
                        break;
                    }
                }
            }
            $body = $this->html->apply($row->body_html, json_decode($designs[$row->id] ?? '{}', true));
            $headings = preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $body, $matches);
            if (! $headings || ! trim(strip_tags(implode(' ', $matches[1] ?? [])))) {
                $errors[] = 'عنوان H1 مفقود أو فارغ';
            } elseif ($headings > 1) {
                $warnings[] = 'أكثر من H1؛ راجع تنظيم العناوين';
            }
            preg_match_all('/<img\b[^>]*>/i', $body, $images);
            $missingAlt = count(array_filter($images[0], fn ($tag) => ! preg_match('/\balt\s*=/i', $tag)));
            if ($missingAlt) {
                $warnings[] = $missingAlt.' صورة بدون خاصية alt';
            }
            $title = trim($seo['title'] ?? '');
            if ($title !== '') {
                $titles[$title][] = $row->id;
            }
            unset($row->body_html, $row->seo);
            if ($errors || $warnings) {
                $issues[] = ['row' => $row, 'errors' => $errors, 'warnings' => $warnings, 'reasons' => array_merge($errors, $warnings)];
            }
        }
        usort($issues, fn ($a, $b) => count($b['errors']) <=> count($a['errors']));
        $data = ['issues' => $issues, 'checkedIds' => $checkedIds, 'total' => $total, 'excluded' => $excluded, 'errorCount' => count(array_filter($issues, fn ($issue) => count($issue['errors']) > 0)), 'duplicates' => array_filter($titles, fn ($ids) => count($ids) > 1), 'locale' => $locale, 'localNoindex' => (bool) config('website.prevent_indexing')];

        return $data;
    }
}
