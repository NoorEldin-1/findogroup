<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Cms;
use App\Services\CmsHtml;
use App\Services\CmsRenderer;
use App\Services\SeoPolicy;
use App\Services\WebsiteSeoAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function __construct(private Cms $cms, private CmsHtml $html, private CmsRenderer $renderer) {}

    private function view(string $page, array $data = []): Response
    {
        return response()->view('admin.'.$page, $data + ['collections' => Cms::COLLECTIONS]);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cms' => __($message)]);
    }

    public function login(): Response
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return $this->view('login')->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function authenticate(Request $r): Response
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'cms-login:'.$r->ip().':'.Str::lower($data['email']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->fail('محاولات كثيرة. حاول بعد دقيقة.');
        }if (! Auth::attempt($data + ['active' => true])) {
            RateLimiter::hit($key, 60);
            $this->fail('البريد الإلكتروني أو كلمة المرور غير صحيحة.');
        }RateLimiter::clear($key);
        $r->session()->regenerate();
        $this->cms->audit('login', 'user', Auth::id());

        return redirect()->intended('/admin');
    }

    public function logout(Request $r): Response
    {
        $locale = app()->getLocale();
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        $r->session()->put('admin_locale', $locale);

        return redirect('/login');
    }

    public function dashboard(): Response
    {
        return $this->view('dashboard', ['counts' => ['المقالات' => DB::table('contents')->where('kind', 'post')->where('status', 'published')->count(), 'الصفحات والأقسام' => DB::table('contents')->where('kind', '!=', 'post')->count(), 'الوسائط' => DB::table('media_assets')->count(), 'الطلبات الجديدة' => DB::table('form_submissions')->whereIn('status', ['received_locally', 'new'])->count()], 'recent' => DB::table('cms_audit')->leftJoin('users', 'users.id', '=', 'cms_audit.user_id')->select('cms_audit.*', 'users.name')->latest('cms_audit.id')->limit(8)->get(), 'drafts' => DB::table('contents')->where('status', 'draft')->count(), 'scheduled' => DB::table('contents')->where('status', 'published')->where('published_at', '>', now())->count()]);
    }

    public function contents(Request $r): Response
    {
        $q = DB::table('contents as c')->join('content_routes as p', 'p.content_id', '=', 'c.id')->join('content_translations as t', fn ($q) => $q->on('t.content_id', '=', 'c.id')->where('t.locale', 'ar'))->select('c.*', 'p.path', 'p.indexable', 't.title');
        if ($r->filled('kind')) {
            $q->where('c.kind', $r->kind);
        }if ($r->filled('status')) {
            $q->where('c.status', $r->status);
        }if ($r->filled('q')) {
            $q->where(fn ($q) => $q->where('t.title', 'like', '%'.$r->q.'%')->orWhere('p.path', 'like', '%'.$r->q.'%'));
        }

        return $this->view('contents', ['items' => $q->orderByDesc('c.updated_at')->paginate(25)->withQueryString()]);
    }

    public function edit(int $id, Request $r): Response
    {
        $content = DB::table('contents')->find($id);
        abort_unless($content, 404);
        $locale = $r->input('locale', 'ar');
        abort_unless(in_array($locale, ['ar', 'en']), 404);
        $t = DB::table('content_translations')->where('content_id', $id)->where('locale', $locale)->first();

        return $this->view('edit', ['content' => $content, 'translation' => $t, 'seo' => json_decode($t->seo, true), 'path' => DB::table('content_routes')->where('content_id', $id)->first(), 'post' => json_decode(DB::table('cms_posts')->where('content_id', $id)->value('data') ?? 'null', true), 'locale' => $locale, 'categories' => $this->cms->collection('categories'), 'revisions' => DB::table('cms_revisions')->where('entity', 'content')->where('entity_id', (string) $id)->latest('id')->limit(12)->get()]);
    }

    public function create(Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);

        return $this->view('create', ['kind' => $r->input('kind', 'post'), 'templates' => DB::table('contents as c')->join('content_translations as t', fn ($q) => $q->on('t.content_id', '=', 'c.id')->where('locale', 'ar'))->where('c.kind', '!=', 'post')->select('c.id', 't.title')->get()]);
    }

    public function store(Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $v = $r->validate(['title' => 'required|string|max:250', 'path' => 'required|string|max:1800', 'kind' => 'required|in:post,page', 'template_id' => 'nullable|integer|exists:contents,id']);
        $v['path'] = rtrim(rawurldecode($v['path']), '/') ?: '/';
        if (! $this->cms->validPath($v['path']) || is_file(public_path($v['path'])) || DB::table('content_routes')->where('path', $v['path'])->exists()) {
            $this->fail('الرابط غير صالح أو مستخدم بالفعل.');
        }if ($v['kind'] === 'post' && ! str_starts_with($v['path'], '/blogs/')) {
            $this->fail('رابط المقال يجب أن يبدأ بـ /blogs/.');
        }
        $id = DB::transaction(function () use ($v) {
            $id = DB::table('contents')->insertGetId(['kind' => $v['kind'], 'source_system' => 'cms', 'source_id' => (string) Str::uuid(), 'source_data' => json_encode($v['kind'] === 'post' ? ['component' => 'BlogPost'] : []), 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('content_routes')->insert(['content_id' => $id, 'path' => rtrim($v['path'], '/') ?: '/', 'indexable' => true, 'created_at' => now(), 'updated_at' => now()]);
            foreach (['ar', 'en'] as $locale) {
                $base = isset($v['template_id']) ? DB::table('content_translations')->where('content_id', $v['template_id'])->where('locale', $locale)->value('body_html') : null;
                $base ??= DB::table('content_translations')->join('content_routes', 'content_routes.content_id', '=', 'content_translations.content_id')->where('path', '/about')->where('locale', $locale)->value('body_html') ?? '<main></main>';
                if (! isset($v['template_id'])) {
                    $d = $this->html->document($base);
                    $main = $d->getElementsByTagName('main')->item(0);
                    if ($main) {
                        while ($main->firstChild) {
                            $main->removeChild($main->firstChild);
                        }$section = $d->createElement('section');
                        $section->setAttribute('class', 'cms-custom-block');
                        $h = $d->createElement('h1');
                        $h->appendChild($d->createTextNode($v['title']));
                        $section->appendChild($h);
                        $main->appendChild($section);
                        $base = $this->html->body($d);
                    }
                }DB::table('content_translations')->insert(['content_id' => $id, 'locale' => $locale, 'title' => $v['title'], 'body_html' => $base, 'seo' => json_encode(['title' => $v['title'], 'description' => '', 'canonical' => config('website.seo_base_url').$v['path'], 'schemas' => []]), 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($v['kind'] === 'post') {
                $post = ['id' => 1000000 + $id, 'date' => now()->toIso8601String(), 'modified' => now()->toIso8601String(), 'slug' => rawurlencode(substr($v['path'], 7)), 'link' => config('website.seo_base_url').$v['path'], 'title' => ['rendered' => $v['title']], 'content' => ['rendered' => '<p>ابدأ كتابة مقالك هنا.</p>'], 'excerpt' => ['rendered' => ''], 'categories' => [], '_embedded' => ['wp:featuredmedia' => [['source_url' => '', 'alt_text' => '']]]];
                DB::table('cms_posts')->insert(['content_id' => $id, 'data' => json_encode($post, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
            }$this->cms->audit('create', 'content', $id);

            return $id;
        });
        $this->cms->invalidate();

        return redirect('/admin/content/'.$id)->with('success', __('تم إنشاء مسودة. أكمل المحتوى وبيانات SEO قبل النشر.'));
    }

    public function save(int $id, Request $r): Response
    {
        $v = $r->validate(['version' => 'required|integer', 'locale' => 'required|in:ar,en', 'title' => 'required|string|max:500', 'seo_title' => 'nullable|string|max:500', 'path' => 'required|string|max:1800', 'status' => 'required|in:published,draft,archived', 'published_at' => 'nullable|date', 'description' => 'nullable|string|max:1000', 'canonical' => 'nullable|url:http,https|max:2048', 'image' => 'nullable|string|max:2048', 'schema' => 'nullable|json', 'body' => 'nullable|string|max:2000000', 'excerpt' => 'nullable|string|max:5000', 'image_alt' => 'nullable|string|max:1000', 'categories' => 'nullable|array', 'categories.*' => 'integer', 'indexable' => 'nullable|boolean']);
        $v['path'] = rtrim(rawurldecode($v['path']), '/') ?: '/';
        if (! $this->cms->validPath($v['path']) || ! $this->cms->safeUrl($v['image'] ?? '')) {
            $this->fail('راجع رابط الصفحة أو الصورة.');
        }
        DB::transaction(function () use ($id, $v, $r) {
            $c = DB::table('contents')->where('id', $id)->lockForUpdate()->first();
            abort_unless($c, 404);
            if ($c->version != (int) $v['version']) {
                $this->fail('تم تعديل المحتوى في نافذة أخرى. حدّث الصفحة قبل الحفظ.');
            }$route = DB::table('content_routes')->where('content_id', $id)->first();
            $newPath = rtrim($v['path'], '/') ?: '/';
            if (is_file(public_path($newPath)) || DB::table('content_routes')->where('path', $newPath)->where('content_id', '!=', $id)->exists()) {
                $this->fail('الرابط مستخدم لصفحة أخرى.');
            }if ($c->kind === 'post' && ! str_starts_with($newPath, '/blogs/')) {
                $this->fail('احتفظ بمسار المقال داخل /blogs/.');
            }
            if ($c->kind === 'post' && array_diff($v['categories'] ?? [], array_column($this->cms->collection('categories'), 'id'))) {
                $this->fail('اختر تصنيفات موجودة في الموقع.');
            }
            if ($c->kind === 'post' && $v['status'] === 'published' && auth()->user()->cmsRole() !== 'seo') {
                $text = trim(html_entity_decode(strip_tags($v['body'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text === '' || $text === 'ابدأ كتابة مقالك هنا.') {
                    $this->fail('أضف محتوى المقال قبل النشر.');
                }
            }
            $this->cms->revision('content', $id, $this->cms->snapshot($id), 'قبل حفظ المحتوى وSEO');
            $seo = json_decode(DB::table('content_translations')->where('content_id', $id)->where('locale', $v['locale'])->value('seo'), true);
            $seo['title'] = $v['seo_title'] ?? $v['title'];
            $seo['description'] = $v['description'] ?? '';
            $seo['canonical'] = ($v['canonical'] ?? '') ?: config('website.seo_base_url').$newPath;
            $seo['image'] = $v['image'] ?? '';
            if (str_starts_with($seo['image'], '/')) {
                $seo['image'] = config('website.seo_base_url').$seo['image'];
            }$seo['og:title'] = $seo['twitter:title'] = $seo['title'];
            $seo['og:description'] = $seo['twitter:description'] = $seo['description'];
            $seo['twitter:image'] = $seo['image'];
            $schemas = json_decode($v['schema'] ?? '[]', true);
            if (! is_array($schemas)) {
                $this->fail('البيانات المنظمة يجب أن تكون قائمة JSON.');
            }if (! array_is_list($schemas)) {
                $schemas = [$schemas];
            }
            if (! app(SeoPolicy::class)->validSchemas($schemas)) {
                $this->fail('Schema تحتاج كائنات ذات @type أو @graph صالح، وليست قيمًا منفردة.');
            }
            $seo['schemas'] = $schemas;
            $seo['robots'] = $r->boolean('indexable') ? 'index, follow, max-image-preview:large' : 'noindex, follow';
            if ($newPath !== $route->path) {
                DB::table('redirects')->where('source_path', $newPath)->delete();
                DB::table('redirects')->where('target_path', $route->path)->update(['target_path' => $newPath, 'updated_at' => now()]);
                DB::table('redirects')->updateOrInsert(['source_path' => $route->path], ['target_path' => $newPath, 'status' => 301, 'created_at' => now(), 'updated_at' => now()]);
                if (($v['canonical'] ?? '') === config('website.seo_base_url').$route->path) {
                    $seo['canonical'] = config('website.seo_base_url').$newPath;
                }foreach (DB::table('content_translations')->where('content_id', $id)->get() as $tr) {
                    DB::table('content_translations')->where('id', $tr->id)->update(['seo' => str_replace(config('website.seo_base_url').$route->path, config('website.seo_base_url').$newPath, $tr->seo)]);
                }$seo['schemas'] = json_decode(str_replace(config('website.seo_base_url').$route->path, config('website.seo_base_url').$newPath, json_encode($seo['schemas'])), true);
            }
            DB::table('content_routes')->where('content_id', $id)->update(['path' => $newPath, 'indexable' => $r->boolean('indexable'), 'updated_at' => now()]);
            $status = auth()->user()->cmsRole() === 'seo' ? $c->status : $v['status'];
            $published = auth()->user()->cmsRole() === 'seo' ? $c->published_at : ($v['published_at'] ?? $c->published_at ?? now());
            $source = json_decode($c->source_data ?? '{}', true);
            if ($newPath !== $route->path && in_array($c->kind, ['portfolio', 'service', 'system', 'landing'])) {
                $source['render_path'] ??= $route->path;
            }$source['schedule_pending'] = $status === 'published' && Carbon::parse($published)->isFuture();
            DB::table('contents')->where('id', $id)->update(['source_data' => json_encode($source), 'status' => $status, 'published_at' => $published, 'source_updated_at' => now(), 'updated_at' => now(), 'version' => $c->version + 1]);
            if ($c->kind === 'post') {
                $p = json_decode(DB::table('cms_posts')->where('content_id', $id)->value('data'), true);
                if (auth()->user()->cmsRole() !== 'seo') {
                    $p['title']['rendered'] = e($v['title']);
                    $p['content']['rendered'] = $this->html->clean($v['body'] ?? '');
                    $p['excerpt']['rendered'] = $this->html->clean($v['excerpt'] ?? '');
                }$p['slug'] = rawurlencode(substr($newPath, 7));
                $p['link'] = config('website.seo_base_url').$newPath;
                $p['date'] = $published ? Carbon::parse($published)->toIso8601String() : ($p['date'] ?? now()->toIso8601String());
                $p['modified'] = now()->toIso8601String();
                if (auth()->user()->cmsRole() !== 'seo') {
                    $p['categories'] = array_map('intval', $v['categories'] ?? []);
                    $p['_embedded']['wp:featuredmedia'] = [['source_url' => $v['image'] ?? '', 'alt_text' => $v['image_alt'] ?? '']];
                }DB::table('cms_posts')->where('content_id', $id)->update(['data' => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
                $seo['schemas'] = $this->articleSchemas($seo['schemas'], $p, $seo['canonical']);
                foreach (DB::table('content_translations')->where('content_id', $id)->where('locale', '!=', $v['locale'])->get() as $other) {
                    $otherSeo = json_decode($other->seo, true) ?: [];
                    $otherSeo['schemas'] = $this->articleSchemas($otherSeo['schemas'] ?? [], $p, $otherSeo['canonical'] ?? config('website.seo_base_url').$newPath);
                    DB::table('content_translations')->where('id', $other->id)->update(['seo' => json_encode($otherSeo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
                }
            }
            DB::table('content_translations')->where('content_id', $id)->where('locale', $v['locale'])->update(['title' => $v['title'], 'excerpt' => $v['description'] ?? '', 'seo' => json_encode($seo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
            $this->cms->invalidate();
            if ($c->kind === 'post') {
                $this->renderer->render([$id], true);
                $this->renderer->render(DB::table('content_routes')->whereIn('path', ['/', '/blogs', '/news'])->pluck('content_id')->all());
            }if (in_array($c->kind, ['portfolio', 'service', 'system'])) {
                $this->renderer->refreshPublic(['portfolio' => 'projects', 'service' => 'services', 'system' => 'systems'][$c->kind]);
            }$this->cms->audit('save', 'content', $id, ['path' => $newPath, 'status' => $status]);
        });
        $this->cms->invalidate();

        return back()->with('success', __('تم الحفظ وتحديث المحتوى وSEO والكاش.'));
    }

    private function articleSchemas(array $schemas, array $post, string $canonical): array
    {
        $update = function (array $node) use (&$update, $post, $canonical): array {
            if (array_intersect((array) ($node['@type'] ?? []), ['Article', 'BlogPosting', 'NewsArticle'])) {
                $node['headline'] = html_entity_decode(strip_tags($post['title']['rendered']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $node['datePublished'] = $post['date'];
                $node['dateModified'] = $post['modified'];
                $node['mainEntityOfPage'] = $canonical;
                $node['url'] = $canonical;
                $node['image'] = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
                if (! $node['image']) {
                    unset($node['image']);
                }
                if (str_starts_with($node['image'] ?? '', '/')) {
                    $node['image'] = config('website.seo_base_url').$node['image'];
                }
            }foreach ($node as $k => $v) {
                if (is_array($v)) {
                    $node[$k] = $update($v);
                }
            }

            return $node;
        };
        if (! $schemas) {
            $schemas = [['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'author' => ['@type' => 'Organization', 'name' => 'Findo Group']]];
        }

        return $update($schemas);
    }

    public function preview(int $id, Request $r): Response
    {
        $locale = $r->input('locale', 'ar');
        abort_unless(in_array($locale, ['ar', 'en']), 404);
        $row = DB::table('content_translations as t')->join('contents as c', 'c.id', '=', 't.content_id')->join('content_routes as p', 'p.content_id', '=', 'c.id')->where('c.id', $id)->where('locale', $locale)->select('t.*', 'c.kind', 'p.path')->first();
        abort_unless($row, 404);
        $page = (array) $row;
        $page['seo'] = json_decode($row->seo, true);
        $page['design'] = json_decode(DB::table('cms_designs')->where('content_id', $id)->where('locale', $locale)->value('data') ?? '{}', true);
        $page['body_html'] = $this->html->apply($page['body_html'], $page['design']);

        return response()->view('website.page', ['page' => $page, 'locale' => $locale, 'status' => 200, 'noindex' => true])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function design(int $id, Request $r): Response
    {
        $locale = $r->input('locale', 'ar');
        abort_unless(in_array($locale, ['ar', 'en']), 404);
        $row = DB::table('content_translations')->where('content_id', $id)->where('locale', $locale)->first();
        abort_unless($row, 404);

        return $this->view('design', ['id' => $id, 'locale' => $locale, 'title' => $row->title, 'version' => DB::table('contents')->where('id', $id)->value('version'), 'fields' => $this->html->fields($row->body_html), 'design' => json_decode(DB::table('cms_designs')->where('content_id', $id)->where('locale', $locale)->value('data') ?? '{}', true)]);
    }

    public function saveDesign(int $id, Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $v = $r->validate(['locale' => 'required|in:ar,en', 'version' => 'required|integer', 'design' => 'required|json']);
        $design = json_decode($v['design'], true);
        if (! is_array($design) || strlen($v['design']) > 2000000) {
            $this->fail('محتوى الأقسام غير صالح.');
        }$clean = ['texts' => [], 'assets' => [], 'hidden' => [], 'blocks' => []];
        foreach ($design['texts'] ?? [] as $k => $value) {
            if (! is_string($value) || strlen($value) > 15000) {
                $this->fail('النص غير صالح.');
            }$clean['texts'][$k] = $value;
        }foreach ($design['assets'] ?? [] as $a) {
            if (! in_array($a['attribute'] ?? '', ['src', 'poster', 'href', 'alt']) || ! is_string($a['value'] ?? null) || ($a['attribute'] !== 'alt' && ! $this->cms->safeUrl($a['value']))) {
                $this->fail('رابط وسائط غير صالح.');
            }$clean['assets'][] = $a;
        }$clean['hidden'] = array_values(array_filter($design['hidden'] ?? [], 'is_string'));
        foreach ($design['blocks'] ?? [] as $b) {
            $clean['blocks'][] = ['html' => $this->html->clean($b['html'] ?? '')];
        }
        DB::transaction(function () use ($id, $v, $clean) {
            $c = DB::table('contents')->where('id', $id)->lockForUpdate()->first();
            abort_unless($c, 404);
            if ($c->version != (int) $v['version']) {
                $this->fail('تم تعديل الصفحة. حدّثها قبل الحفظ.');
            }$this->cms->revision('content', $id, $this->cms->snapshot($id), 'قبل تعديل أقسام الصفحة');
            DB::table('cms_designs')->updateOrInsert(['content_id' => $id, 'locale' => $v['locale']], ['data' => json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('contents')->where('id', $id)->update(['version' => $c->version + 1, 'source_updated_at' => now(), 'updated_at' => now()]);
            $this->cms->audit('design', 'content', $id);
        });
        $this->cms->invalidate();

        return back()->with('success', __('تم حفظ النصوص والصور والأقسام؛ التعديلات ظاهرة في الواجهة.'));
    }

    public function collection(string $key): Response
    {
        abort_unless(isset(Cms::COLLECTIONS[$key]), 404);
        $row = DB::table('cms_collections')->where('key', $key)->first();

        return $this->view('collection', ['key' => $key, 'data' => json_decode($row->data ?? '[]', true), 'version' => $row->version ?? 1, 'template' => (json_decode(file_get_contents(storage_path('app/cms/collection-templates.json')), true)[$key] ?? [])]);
    }

    public function saveCollection(string $key, Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        abort_unless(isset(Cms::COLLECTIONS[$key]), 404);
        if ($key === 'settings') {
            $this->cms->assertRole(['owner']);
        }$v = $r->validate(['data' => 'required|json|max:2000000', 'version' => 'required|integer']);
        $data = json_decode($v['data'], true);
        if ($key === 'comments' && is_array($data)) {
            foreach ($data as &$comment) {
                if (isset($comment['content']['rendered'])) {
                    $comment['content']['rendered'] = $this->html->clean($comment['content']['rendered']);
                }
            }unset($comment);
        }if (! is_array($data)) {
            $this->fail('بيانات غير صالحة.');
        }$this->validateCollection($data);
        $this->validateCollectionShape($key, $data);
        DB::transaction(function () use ($key, $data, $v) {
            $row = DB::table('cms_collections')->where('key', $key)->lockForUpdate()->first();
            if ($row && $row->version != $v['version']) {
                $this->fail('تم تعديل القسم في نافذة أخرى. حدّث الصفحة.');
            }$this->cms->revision('collection', $key, ['data' => json_decode($row->data ?? '[]', true)], 'قبل تعديل '.Cms::COLLECTIONS[$key]);
            DB::table('cms_collections')->where('key', $key)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'version' => ($row->version ?? 0) + 1, 'updated_at' => now()]);
            $this->syncCollectionRoutes($key, $data);
            $this->cms->invalidate();
            if (! in_array($key, ['settings', 'categories', 'comments'])) {
                $this->renderer->refreshPublic($key);
            } elseif (in_array($key, ['categories', 'comments'])) {
                $this->renderer->refreshPublic($key, $key === 'comments' ? array_unique(array_merge(array_column(json_decode($row->data ?? '[]', true), 'post'), array_column($data, 'post'))) : []);
            }$this->cms->audit('save', 'collection', $key);
        });
        $this->cms->invalidate();

        return back()->with('success', __('تم حفظ القسم وتحديث الصفحات المرتبطة به.'));
    }

    private function validateCollectionShape(string $key, array $data): void
    {
        $listKeys = ['testimonials', 'partners', 'projects', 'services', 'systems', 'templates', 'pricingServices', 'categories', 'comments', 'headerNavLinks'];
        if (in_array($key, $listKeys, true) && ! array_is_list($data)) {
            $this->fail('هذا القسم يجب أن يكون قائمة عناصر.');
        }
        foreach (in_array($key, $listKeys, true) ? $data : [] as $item) {
            if (! is_array($item)) {
                $this->fail('كل عنصر يجب أن يحتوي حقوله.');
            }
            $required = match ($key) {
                'testimonials' => ['id', 'duration', 'aspect'],'partners' => ['name', 'logo'],'projects' => ['id', 'title', 'description', 'category', 'type', 'image'],'services','systems' => ['id', 'titleAr', 'titleEn'],'templates' => ['id', 'titleAr', 'titleEn', 'category', 'fileType'],'categories' => ['id', 'name', 'slug'],'comments' => ['id', 'post', 'author_name', 'content'],'headerNavLinks' => ['href', 'label', 'labelAr'],default => []
            };
            foreach ($required as $field) {
                if (! array_key_exists($field, $item) || $item[$field] === '') {
                    $this->fail('أكمل الحقل المطلوب: '.$field);
                }
            }
            if ($key === 'testimonials' && (! preg_match('/^PT(?=\d)(?:\d+H)?(?:\d+M)?(?:\d+(?:\.\d+)?S)?$/', $item['duration']) || ! in_array($item['aspect'], ['portrait', 'wide'], true))) {
                $this->fail('راجع مدة الفيديو PT46S ونسبة العرض portrait أو wide.');
            }
            if ($key === 'projects' && (! is_numeric($item['id']) || ! in_array($item['type'], ['website', 'app', 'design'], true))) {
                $this->fail('معرف المشروع رقم؛ نوعه website أو app أو design.');
            }
        }
        if ($key === 'settings') {
            if (! filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $this->fail('بريد التواصل غير صالح.');
            }if (! isset($data['cache_seconds']) || $data['cache_seconds'] < 30 || $data['cache_seconds'] > 3600) {
                $this->fail('مدة الكاش من 30 إلى 3600 ثانية.');
            }
        }
    }

    private function validateCollection(array $data): void
    {
        array_walk_recursive($data, function ($value, $key) {
            if (is_string($value) && (strlen($value) > 30000 || str_contains($value, '<script'))) {
                $this->fail('قيمة غير صالحة.');
            }if (is_string($value) && preg_match('/^(image|logo|video|poster|fileUrl|url|href|source_url)$/i', (string) $key) && ! $this->cms->safeUrl($value)) {
                $this->fail('رابط غير صالح: '.$key);
            }
        });
        $ids = [];
        foreach ($data as $item) {
            if (is_array($item) && isset($item['id'])) {
                if (in_array((string) $item['id'], $ids, true)) {
                    $this->fail('المعرف id يجب أن يكون فريدًا لكل عنصر.');
                }$ids[] = (string) $item['id'];
            }
        }
    }

    private function syncCollectionRoutes(string $key, array $data): void
    {
        $map = ['projects' => ['portfolio', '/portfolio/', 'ProjectDetails'], 'services' => ['service', '/services/', 'ServiceDetails'], 'systems' => ['system', '/systems/', 'SystemDetails']];
        if (! isset($map[$key])) {
            return;
        }[$kind,$prefix,$component] = $map[$key];
        $paths = [];
        $kept = [];
        foreach ($data as $item) {
            $slug = (string) ($item['id'] ?? '');
            if (! $slug || ! preg_match('/^[\pL\pN_-]+$/u', $slug)) {
                $this->fail('المعرف id يجب أن يحتوي حروفًا أو أرقامًا وشرطات فقط.');
            }$path = $prefix.$slug;
            $paths[] = $path;
            $id = DB::table('content_routes')->where('path', $path)->value('content_id') ?? DB::table('contents')->where('kind', $kind)->where(fn ($q) => $q->where('source_id', $path)->orWhere('source_data->render_path', $path))->value('id');
            if ($id) {
                $existing = DB::table('contents')->where('id', $id)->first();
                $source = json_decode($existing->source_data ?? '{}', true);
                if ($source['collection_archived'] ?? false) {
                    unset($source['collection_archived']);
                    DB::table('contents')->where('id', $id)->update(['status' => 'published', 'source_data' => json_encode($source)]);
                }
            }if (! $id) {
                $id = DB::table('contents')->insertGetId(['kind' => $kind, 'source_system' => 'cms', 'source_id' => (string) Str::uuid(), 'source_data' => json_encode(['component' => $component, 'render_path' => $path]), 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('content_routes')->insert(['content_id' => $id, 'path' => $path, 'indexable' => true, 'created_at' => now(), 'updated_at' => now()]);
                foreach (['ar', 'en'] as $locale) {
                    $title = $item[$locale === 'ar' ? 'titleAr' : 'titleEn'] ?? $item['title'] ?? $item[$locale === 'ar' ? 'nameAr' : 'nameEn'] ?? $item['name'] ?? $slug;
                    $title = is_array($title) ? ($title[$locale] ?? $slug) : $title;
                    $description = $item[$locale === 'ar' ? 'descriptionAr' : 'descriptionEn'] ?? $item['description'] ?? '';
                    $description = is_array($description) ? ($description[$locale] ?? '') : $description;
                    $description = Str::limit(strip_tags($description), 160, '');
                    $canonical = rtrim(config('website.seo_base_url'), '/').$path;
                    $schema = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $title, 'description' => $description, 'url' => $canonical, 'inLanguage' => $locale];
                    DB::table('content_translations')->insert(['content_id' => $id, 'locale' => $locale, 'title' => $title, 'body_html' => '', 'seo' => json_encode(['title' => $title, 'description' => $description, 'canonical' => $canonical, 'image' => $item['image'] ?? '', 'schemas' => [$schema]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]);
                }
            }$kept[] = $id;
        }
        $removed = DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('c.kind', $kind)->whereNotIn('c.id', $kept)->pluck('c.id');
        foreach (DB::table('contents')->whereIn('id', $removed)->where('status', 'published')->get() as $removedContent) {
            $source = json_decode($removedContent->source_data ?? '{}', true);
            $source['collection_archived'] = true;
            DB::table('contents')->where('id', $removedContent->id)->update(['status' => 'archived', 'source_data' => json_encode($source), 'updated_at' => now()]);
        }
    }

    public function media(Request $r): Response
    {
        $q = DB::table('media_assets')->where('archived', $r->boolean('archived'));
        if ($r->filled('q')) {
            $q->where(fn ($q) => $q->where('path', 'like', '%'.$r->q.'%')->orWhere('alt', 'like', '%'.$r->q.'%'));
        }

        return $this->view('media', ['items' => $q->orderByDesc('id')->paginate(36)->withQueryString()]);
    }

    public function upload(Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $r->validate(['file' => 'required|file|max:102400|mimes:jpg,jpeg,png,webp,avif,gif,mp4,webm,pdf,docx,xlsx,pptx', 'alt' => 'nullable|string|max:1000']);
        $f = $r->file('file');
        $ext = $f->guessExtension();
        $name = Str::uuid().'.'.$ext;
        $dir = 'media/'.now()->format('Y/m');
        $f->move(public_path($dir), $name);
        $path = '/'.$dir.'/'.$name;
        $id = DB::table('media_assets')->insertGetId(['original_url' => $path, 'path' => $path, 'sha256' => hash_file('sha256', public_path($path)), 'mime' => mime_content_type(public_path($path)), 'bytes' => filesize(public_path($path)), 'alt' => $r->input('alt'), 'created_at' => now(), 'updated_at' => now()]);
        $this->cms->audit('upload', 'media', $id);

        return back()->with('success', 'تم رفع الملف: '.$path);
    }

    public function saveMedia(int $id, Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor', 'seo']);
        $v = $r->validate(['alt' => 'nullable|string|max:1000']);
        $media = DB::table('media_assets')->find($id);
        abort_unless($media, 404);
        DB::table('media_assets')->where('id', $id)->update(['alt' => $v['alt'], 'managed_alt' => true, 'updated_at' => now()]);
        $this->cms->audit('alt', 'media', $id);
        $this->cms->invalidate();

        return back()->with('success', __('تم تحديث النص البديل.'));
    }

    public function archiveMedia(int $id, Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $row = DB::table('media_assets')->find($id);
        abort_unless($row, 404);
        DB::table('media_assets')->where('id', $id)->update(['archived' => ! $row->archived, 'updated_at' => now()]);
        $this->cms->audit($row->archived ? 'restore' : 'archive', 'media', $id);

        return back()->with('success', __('تم تحديث حالة الملف. يظل الرابط يعمل لحماية الصفحات التي تستخدمه.'));
    }

    public function redirects(Request $r): Response
    {
        $q = DB::table('redirects');
        if ($r->filled('q')) {
            $q->where('source_path', 'like', '%'.$r->q.'%');
        }

        return $this->view('redirects', ['items' => $q->orderByDesc('id')->paginate(30)->withQueryString()]);
    }

    public function saveRedirect(Request $r): Response
    {
        $this->cms->assertRole(['owner', 'seo']);
        $v = $r->validate(['source_path' => 'required|string|max:1800', 'target_path' => 'required|string|max:1800', 'status' => 'required|in:301,302']);
        $v['source_path'] = rtrim(rawurldecode($v['source_path']), '/') ?: '/';
        $v['target_path'] = rtrim(rawurldecode($v['target_path']), '/') ?: '/';
        $target = $this->redirectTarget($v['source_path'], $v['target_path']);
        $old = DB::table('redirects')->where('source_path', $v['source_path'])->first();
        $this->cms->revision('redirect', $v['source_path'], ['row' => $old], 'قبل تعديل تحويل');
        DB::table('redirects')->updateOrInsert(['source_path' => $v['source_path']], ['target_path' => $target, 'status' => (int) $v['status'], 'created_at' => now(), 'updated_at' => now()]);
        $this->cms->audit('save', 'redirect', $v['source_path']);

        return back()->with('success', __('تم حفظ التحويل والتحقق من عدم وجود حلقة.'));
    }

    private function redirectTarget(string $source, string $destination): string
    {
        if (! $this->cms->validPath($source) || ! $this->cms->validPath($destination, true) || $source === $destination) {
            $this->fail('استخدم مسارين داخليين مختلفين وصالحين.');
        }if (is_file(public_path($source))) {
            $this->fail('المصدر ملف موجود؛ لن يصل طلبه إلى تحويلات الموقع.');
        }if (DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('r.path', $source)->where('c.status', 'published')->exists()) {
            $this->fail('يوجد محتوى منشور على المصدر. غيّر رابطه أو أرشفه أولًا.');
        }$target = $destination;
        $seen = [$source];
        for ($i = 0; $i < 20; $i++) {
            if (in_array($target, $seen, true)) {
                $this->fail('هذا التحويل ينشئ حلقة.');
            }$seen[] = $target;
            $next = DB::table('redirects')->where('source_path', $target)->value('target_path');
            if (! $next) {
                break;
            }
            if ($i === 19) {
                $this->fail('سلسلة تحويلات طويلة؛ اختر الوجهة النهائية مباشرة.');
            }
            $target = $next;
        }if (! DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')->where('r.path', $target)->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))->exists() && ! is_file(public_path($target))) {
            $this->fail('وجهة التحويل يجب أن تكون صفحة منشورة أو ملفًا موجودًا.');
        }

        return $target;
    }

    public function deleteRedirect(int $id): Response
    {
        $this->cms->assertRole(['owner', 'seo']);
        $row = DB::table('redirects')->find($id);
        abort_unless($row, 404);
        $this->cms->revision('redirect', $row->source_path, ['row' => $row], 'قبل حذف تحويل');
        DB::table('redirects')->where('id', $id)->delete();
        $this->cms->audit('delete', 'redirect', $id);

        return back()->with('success', __('تم حذف التحويل مع الاحتفاظ بسجله.'));
    }

    public function submissions(Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $q = DB::table('form_submissions');
        if ($r->filled('type')) {
            $q->where('form_key', $r->type);
        }

        return $this->view('submissions', ['items' => $q->latest('id')->paginate(20)->withQueryString()]);
    }

    public function saveSubmission(int $id, Request $r): Response
    {
        $this->cms->assertRole(['owner', 'editor']);
        $v = $r->validate(['status' => 'required|in:new,in_progress,done,spam,published']);
        $row = DB::table('form_submissions')->find($id);
        abort_unless($row, 404);
        if ($v['status'] === 'published' && $row->form_key !== 'article_comments') {
            $this->fail('النشر متاح لتعليقات المقالات فقط.');
        }
        DB::transaction(function () use ($id, $v, $row) {
            if ($row->form_key === 'article_comments') {
                $payload = json_decode($row->payload, true);
                $fields = $payload['fields'] ?? $payload;
                $postId = (int) ($fields['post_id'] ?? 0);
                $post = DB::table('cms_posts')->where('data->id', $postId)->first();
                if ($v['status'] === 'published' && ! $post) {
                    $this->fail('المقال المرتبط بالتعليق غير موجود.');
                }
                $collection = DB::table('cms_collections')->where('key', 'comments')->lockForUpdate()->first();
                $before = json_decode($collection->data ?? '[]', true);
                $comments = array_values(array_filter($before, fn ($comment) => ($comment['submission_id'] ?? null) !== $id));
                if ($v['status'] === 'published') {
                    $comments[] = ['id' => 1000000 + $id, 'submission_id' => $id, 'post' => $postId, 'author_name' => $payload['name'] ?? $fields['name'] ?? 'زائر', 'date' => now()->toIso8601String(), 'content' => ['rendered' => nl2br(e($payload['message'] ?? $fields['message'] ?? ''))]];
                }
                if ($comments !== $before) {
                    $this->cms->revision('collection', 'comments', ['data' => $before], 'قبل مراجعة تعليق');
                    DB::table('cms_collections')->where('key', 'comments')->update(['data' => json_encode($comments, JSON_UNESCAPED_UNICODE), 'version' => DB::raw('version+1'), 'updated_at' => now()]);
                    if ($post) {
                        $this->renderer->render([$post->content_id], true);
                    }
                }
            }
            DB::table('form_submissions')->where('id', $id)->update(['status' => $v['status'], 'updated_at' => now()]);
            $this->cms->audit('status', 'submission', $id, $v);
            $this->cms->invalidate();
        });

        return back()->with('success', __('تم تحديث حالة الطلب.'));
    }

    public function users(): Response
    {
        $this->cms->assertRole(['owner']);

        return $this->view('users', ['items' => User::orderBy('id')->get()]);
    }

    public function saveUser(Request $r): Response
    {
        $this->cms->assertRole(['owner']);
        $v = $r->validate(['id' => 'nullable|integer|exists:users,id', 'name' => 'required|string|max:100', 'email' => 'required|email|max:200', 'role' => 'required|in:'.implode(',', array_keys(config('operating.roles'))), 'cms_role' => 'nullable|in:editor,seo', 'password' => 'nullable|string|min:12|max:200']);
        if (User::where('email', $v['email'])->when($v['id'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))->exists()) {
            $this->fail('البريد مستخدم بالفعل.');
        }$user = isset($v['id']) ? User::findOrFail($v['id']) : new User;
        if (! $user->exists && empty($v['password'])) {
            $this->fail('كلمة المرور مطلوبة للحساب الجديد.');
        }if ($user->id === Auth::id() && ($v['role'] !== 'owner' || ! $r->boolean('active'))) {
            $this->fail('لا يمكنك تعطيل حساب المالك الذي تستخدمه.');
        }$user->name = $v['name'];
        $user->email = $v['email'];
        $user->role = $v['role'];
        $user->cms_role = $v['cms_role'] ?? null;
        $user->active = $r->boolean('active');
        if (! empty($v['password'])) {
            $user->password = $v['password'];
        }$user->save();
        $this->cms->audit('save', 'user', $user->id);

        return back()->with('success', __('تم حفظ المستخدم.'));
    }

    public function revisions(): Response
    {
        return $this->view('revisions', ['items' => DB::table('cms_revisions')->latest('id')->paginate(30), 'audit' => DB::table('cms_audit')->leftJoin('users', 'users.id', '=', 'cms_audit.user_id')->select('cms_audit.*', 'users.name')->latest('cms_audit.id')->limit(50)->get()]);
    }

    public function restore(int $id): Response
    {
        $this->cms->assertRole(['owner']);
        $revision = DB::table('cms_revisions')->find($id);
        abort_unless($revision, 404);
        $s = json_decode($revision->snapshot, true);
        DB::transaction(function () use ($revision, $s) {
            if ($revision->entity === 'content') {
                $id = (int) $revision->entity_id;
                $this->cms->revision('content', $id, $this->cms->snapshot($id), 'قبل استرجاع نسخة');
                foreach ($s['routes'] as $route) {
                    if (DB::table('content_routes')->where('path', $route['path'])->where('content_id', '!=', $id)->exists()) {
                        $this->fail('لا يمكن الاسترجاع لأن الرابط مستخدم الآن.');
                    }
                }$c = $s['content'];
                $c['version'] = DB::table('contents')->where('id', $id)->value('version') + 1;
                DB::table('contents')->where('id', $id)->update($c);
                $current = DB::table('content_routes')->where('content_id', $id)->value('path');
                DB::table('content_routes')->where('content_id', $id)->delete();
                DB::table('content_routes')->insert($s['routes']);
                $old = $s['routes'][0]['path'];
                DB::table('redirects')->where('source_path', $old)->delete();
                if ($current !== $old) {
                    DB::table('redirects')->where('target_path', $current)->update(['target_path' => $old, 'updated_at' => now()]);
                    DB::table('redirects')->updateOrInsert(['source_path' => $current], ['target_path' => $old, 'status' => 301, 'created_at' => now(), 'updated_at' => now()]);
                }DB::table('content_translations')->where('content_id', $id)->delete();
                DB::table('content_translations')->insert($s['translations']);
                DB::table('cms_designs')->where('content_id', $id)->delete();
                if ($s['designs']) {
                    DB::table('cms_designs')->insert($s['designs']);
                }if ($s['post']) {
                    DB::table('cms_posts')->where('content_id', $id)->update(['data' => $s['post']]);
                }
            } elseif ($revision->entity === 'collection') {
                $key = $revision->entity_id;
                $previousData = $this->cms->collection($key);
                $this->cms->revision('collection', $key, ['data' => $previousData], 'قبل استرجاع قسم');
                DB::table('cms_collections')->where('key', $key)->update(['data' => json_encode($s['data'], JSON_UNESCAPED_UNICODE), 'version' => DB::raw('version+1'), 'updated_at' => now()]);
                $this->syncCollectionRoutes($key, $s['data']);
                $this->cms->invalidate();
                $this->renderer->refreshPublic($key, $key === 'comments' ? array_unique(array_merge(array_column($previousData, 'post'), array_column($s['data'], 'post'))) : []);
            } elseif ($revision->entity === 'redirect') {
                $current = DB::table('redirects')->where('source_path', $revision->entity_id)->first();
                $this->cms->revision('redirect', $revision->entity_id, ['row' => $current], 'قبل استرجاع تحويل');
                if ($s['row']) {
                    $row = $s['row'];
                    unset($row['id']);
                    $row['target_path'] = $this->redirectTarget($row['source_path'], $row['target_path']);
                    DB::table('redirects')->updateOrInsert(['source_path' => $row['source_path']], $row);
                } else {
                    DB::table('redirects')->where('source_path', $revision->entity_id)->delete();
                }
            }if ($revision->entity === 'content' && ! empty($s['post'])) {
                $this->cms->invalidate();
                $this->renderer->render(DB::table('content_routes')->whereIn('path', ['/', '/blogs', '/news'])->pluck('content_id')->all());
            }
            if ($revision->entity === 'content' && in_array($s['content']['kind'], ['portfolio', 'service', 'system'])) {
                $this->renderer->refreshPublic(['portfolio' => 'projects', 'service' => 'services', 'system' => 'systems'][$s['content']['kind']]);
            }
            $this->cms->audit('restore', $revision->entity, $revision->entity_id);
        });
        $this->cms->invalidate();

        return back()->with('success', __('تم استرجاع النسخة.'));
    }

    public function seo(Request $request): Response
    {
        $locale = $request->input('locale', 'ar');
        abort_unless(in_array($locale, ['ar', 'en']), 404);
        $data = app(WebsiteSeoAudit::class)->run($locale);
        if ($request->input('format') === 'json') {
            return response()->json($data);
        }

        return $this->view('seo', $data);
    }

    public function performance(): Response
    {
        return $this->view('performance', ['stats' => ['cache' => config('cache.default'), 'php' => PHP_VERSION, 'database' => config('database.default'), 'noindex' => config('website.prevent_indexing'), 'generation' => Cache::get('cms:generation', 0)], 'backups' => array_slice(array_reverse(glob(storage_path('backups/*.dump'))), 0, 5)]);
    }

    public function clearCache(): Response
    {
        $this->cms->assertRole(['owner']);
        $this->cms->invalidate();
        $this->cms->audit('invalidate', 'cache');

        return back()->with('success', __('تم تحديث كاش الموقع.'));
    }
}
