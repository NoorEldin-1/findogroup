<?php

namespace App\Console\Commands;

use App\Services\Cms;
use App\Services\CmsHtml;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairLegacyContentLinks extends Command
{
    protected $signature = 'website:repair-legacy-links {--apply : Save the reviewed repairs with content revisions}';

    protected $description = 'Repair recovered WordPress editor links and placeholders; previews changes by default';

    public function handle(Cms $cms, CmsHtml $html): int
    {
        $rows = DB::table('cms_posts as p')->join('content_routes as r', 'r.content_id', '=', 'p.content_id')->select('p.content_id', 'p.data', 'r.path')->get();
        $postPaths = [];
        foreach ($rows as $row) {
            $post = json_decode($row->data, true);
            $postPaths[(int) $post['id']] = $row->path;
        }
        $aliases = ['/تواصل-معنا' => '/contact', '/project-add' => '/start-project'];
        $article = DB::table('content_routes')->where('path', '/blogs/شركات-بتبيع-لك-الوهم-في-الإمارات-لما')->value('path');
        if ($article) {
            $aliases['/شركات-بتبيع-لك-الوهم-في-الإمارات-لما-ال'] = $article;
        }
        foreach ($aliases as $target) {
            if (! DB::table('content_routes')->where('path', $target)->exists()) {
                $this->error('Missing target: '.$target);

                return self::FAILURE;
            }
        }
        $changes = [];
        foreach ($rows as $row) {
            $post = json_decode($row->data, true);
            $fixed = $html->repairLegacyLinks($post['content']['rendered'], $postPaths, $aliases);
            if ($fixed === $post['content']['rendered']) {
                continue;
            }
            $changes[] = ['id' => $row->content_id, 'path' => $row->path];
            if ($this->option('apply')) {
                DB::transaction(function () use ($row, $post, $fixed, $cms, $html, $postPaths, $aliases) {
                    DB::table('contents')->where('id', $row->content_id)->lockForUpdate()->first();
                    $cms->revision('content', $row->content_id, $cms->snapshot($row->content_id), 'قبل إصلاح روابط WordPress القديمة');
                    $post['content']['rendered'] = $fixed;
                    $post['modified'] = now()->toIso8601String();
                    DB::table('cms_posts')->where('content_id', $row->content_id)->update(['data' => json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
                    foreach (DB::table('content_translations')->where('content_id', $row->content_id)->get() as $translation) {
                        $seo = json_decode($translation->seo, true);
                        $seo['schemas'] = $this->modifiedSchemas($seo['schemas'] ?? [], $post['modified']);
                        DB::table('content_translations')->where('id', $translation->id)->update(['body_html' => $html->repairLegacyLinks($translation->body_html, $postPaths, $aliases), 'seo' => json_encode($seo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
                    }
                    DB::table('contents')->where('id', $row->content_id)->update(['version' => DB::raw('version+1'), 'source_updated_at' => now(), 'updated_at' => now()]);
                    $cms->audit('repair_legacy_links', 'content', $row->content_id);
                    $cms->invalidate();
                });
            }
        }
        $this->line(json_encode(['applied' => (bool) $this->option('apply'), 'count' => count($changes), 'changes' => $changes, 'seo_change' => 'Only Article dateModified updated on repaired pages; other SEO fields preserved'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function modifiedSchemas(array $node, string $timestamp): array
    {
        if (array_intersect((array) ($node['@type'] ?? []), ['Article', 'BlogPosting', 'NewsArticle'])) {
            $node['dateModified'] = $timestamp;
        }
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->modifiedSchemas($value, $timestamp);
            }
        }

        return $node;
    }
}
