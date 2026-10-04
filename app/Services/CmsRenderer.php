<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class CmsRenderer
{
    public function render(array $ids, bool $preview = false): array
    {
        if (! $ids) {
            return [];
        }
        $rows = DB::table('contents as c')->join('content_routes as r', 'r.content_id', '=', 'c.id')->whereIn('c.id', $ids)->select('c.*', 'r.path')->get();
        $pages = [];
        foreach ($rows as $r) {
            $source = json_decode($r->source_data ?? '{}', true);
            $name = $source['component'] ?? null;
            if (! $name) {
                continue;
            }$pattern = match ($r->kind) {
                'post' => '/blogs/:slug','portfolio' => '/portfolio/:id','service' => '/services/:id','system' => '/systems/:id','landing' => '/lp/:service',default => $r->path
            };
            foreach (['ar', 'en'] as $locale) {
                $pages[] = ['id' => $r->id, 'name' => $name, 'url' => $r->path, 'renderUrl' => $source['render_path'] ?? $r->path, 'pattern' => $pattern, 'locale' => $locale];
            }
        }
        if (! $pages) {
            return [];
        }
        $cms = app(Cms::class);
        $data = $cms->publicData();
        $data['posts'] = $cms->posts($preview, $ids);
        $data['categories'] = $cms->collection('categories');
        $data['comments'] = $cms->collection('comments');
        $process = new Process([config('website.node_binary', '/opt/homebrew/bin/node'), base_path('bin/render-cms.cjs')], base_path());
        $process->setInput(json_encode(['data' => $data, 'pages' => $pages], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $process->setTimeout(180);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('CMS rendering failed: '.mb_substr($process->getErrorOutput(), -2000));
        }$rendered = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $fragments = null;
        foreach ($rendered as $r) {
            if ($r['url'] === '/') {
                $fragments ??= $cms->collection('__fragments');
                $fragments[$r['locale']] = app(CmsHtml::class)->extractFragments($r['html']);
            }
            $translation = DB::table('content_translations')->where('content_id', $r['id'])->where('locale', $r['locale']);
            if ($translation->value('body_html') !== $r['html']) {
                $translation->update(['body_html' => $r['html'], 'updated_at' => now()]);
                DB::table('contents')->where('id', $r['id'])->update(['source_updated_at' => now(), 'updated_at' => now()]);
            }
        }
        if ($fragments !== null) {
            DB::table('cms_collections')->updateOrInsert(['key' => '__fragments'], ['data' => json_encode($fragments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
        }
        $cms->invalidate();

        return $rendered;
    }

    public function refreshPublic(?string $key = null, array $commentPostIds = []): void
    {
        if (in_array($key, ['categories', 'comments'], true)) {
            $posts = DB::table('cms_posts as p')->join('contents as c', 'c.id', '=', 'p.content_id')->where('c.status', 'published');
            if ($key === 'comments') {
                $posts->whereIn('p.data->id', $commentPostIds);
            }
            foreach (array_chunk($posts->pluck('c.id')->all(), 10) as $ids) {
                $this->render($ids);
            }
            if ($key === 'categories') {
                $this->render(DB::table('content_routes')->whereIn('path', ['/', '/blogs', '/news'])->pluck('content_id')->all());
            }

            return;
        }
        if ($key && (str_starts_with($key, 'header') || str_starts_with($key, 'footer'))) {
            $this->render(DB::table('content_routes')->where('path', '/')->pluck('content_id')->all());

            return;
        }
        $paths = match ($key) {
            'testimonials' => ['/', '/start-project'],'partners' => ['/'],'projects' => ['/', '/portfolio', '/start-project'],'pricingServices','pricingGroups','includedInEveryProject','guarantees' => ['/pricing', '/start-project', '/findo-care', '/digital-marketing'],'branches' => ['/', '/contact'],'templates' => ['/marketing-templates'],'trainingProgram','trainingDepartments' => ['/training', '/training/program', '/training/register', '/training/welcome'],'services' => ['/', '/services', '/start-project'],default => null
        };
        $q = DB::table('contents as c')->join('content_routes as r', 'r.content_id', '=', 'c.id')->where('c.kind', '!=', 'post')->where('c.status', 'published');
        if ($paths !== null) {
            $q->where(function ($q) use ($paths, $key) {
                $q->whereIn('r.path', $paths);
                if ($key === 'projects') {
                    $q->orWhere('c.kind', 'portfolio');
                }if ($key === 'services') {
                    $q->orWhereIn('c.kind', ['service', 'landing']);
                }if ($key === 'systems') {
                    $q->orWhere('c.kind', 'system');
                }
            });
        }
        foreach (array_chunk($q->pluck('c.id')->all(), 10) as $ids) {
            $this->render($ids);
        }
    }
}
