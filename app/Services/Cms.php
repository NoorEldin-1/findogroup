<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Cms
{
    public const COLLECTIONS = ['headerNavLinks' => 'القائمة الرئيسية', 'headerSocialLinks' => 'روابط التواصل الاجتماعي', 'footerQuickLinks' => 'روابط التذييل', 'footerIndustriesLinks' => 'قطاعات التذييل', 'footerServicesLinks' => 'خدمات التذييل', 'footerSystemsLinks' => 'أنظمة التذييل', 'footerBranches' => 'فروع التذييل', 'footerContactItems' => 'التواصل في التذييل', 'testimonials' => 'آراء العملاء والفيديوهات', 'partners' => 'شعارات الشركاء والعملاء', 'projects' => 'بيانات معرض الأعمال', 'services' => 'بيانات الخدمات', 'systems' => 'الأنظمة', 'templates' => 'القوالب وملفات التحميل', 'pricingServices' => 'باقات الخدمات والأسعار', 'pricingGroups' => 'مجموعات الأسعار', 'includedInEveryProject' => 'مزايا الباقات', 'guarantees' => 'الضمانات', 'branches' => 'الفروع ووسائل التواصل', 'trainingProgram' => 'البرنامج التدريبي', 'trainingDepartments' => 'أقسام التدريب', 'categories' => 'تصنيفات المقالات', 'comments' => 'التعليقات المنشورة', 'settings' => 'إعدادات الموقع'];

    public function collection(string $key, array $default = []): array
    {
        $read = fn () => json_decode(DB::table('cms_collections')->where('key', $key)->value('data') ?? 'null', true) ?? $default;

        return DB::transactionLevel() > 0 ? $read() : Cache::remember('cms:collection:'.$key, 300, $read);
    }

    public function publicData(): array
    {
        $read = function () {
            $data = DB::table('cms_collections')->whereIn('key', array_diff(array_keys(self::COLLECTIONS), ['comments', 'categories', 'settings']))->get()->mapWithKeys(fn ($r) => [$r->key => json_decode($r->data, true)])->all();
            foreach (['projects' => 'portfolio', 'services' => 'service', 'systems' => 'system'] as $key => $kind) {
                if (! isset($data[$key]) || ! DB::table('contents')->where('kind', $kind)->exists()) {
                    continue;
                }$allowed = [];
                foreach (DB::table('contents as c')->join('content_routes as r', 'r.content_id', '=', 'c.id')->where('c.kind', $kind)->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))->select('c.source_id', 'c.source_data', 'r.path')->get() as $row) {
                    $source = json_decode($row->source_data ?? '{}', true);
                    $path = $source['render_path'] ?? (str_starts_with($row->source_id, '/') ? $row->source_id : $row->path);
                    $allowed[] = basename($path);
                }$data[$key] = array_values(array_filter($data[$key], fn ($item) => in_array((string) $item['id'], $allowed, true)));
            }

            return $data;
        };

        return DB::transactionLevel() > 0 ? $read() : Cache::remember('cms:public-data', 300, $read);
    }

    public function mediaAlts(): array
    {
        return Cache::remember('cms:media-alts', 300, fn () => DB::table('media_assets')->where('managed_alt', true)->pluck('alt', 'path')->all());
    }

    public function siteDesign(): array
    {
        $s = $this->collection('settings');
        $d = ['texts' => [], 'assets' => []];
        foreach (['Findo Group' => $s['site_name'] ?? 'Findo Group', 'info@findogroup.com' => $s['email'] ?? 'info@findogroup.com', '201100018259' => $s['phone'] ?? '201100018259'] as $old => $new) {
            if ($old !== $new) {
                $d['texts'][$old] = $new;
            }
        }foreach ([['href', 'mailto:info@findogroup.com', 'mailto:'.($s['email'] ?? 'info@findogroup.com')], ['src', '/wp-content/themes/findo-wordpress-theme/react-build/assets/findo-logo.png', $s['logo'] ?? '/wp-content/themes/findo-wordpress-theme/react-build/assets/findo-logo.png'], ['href', 'https://wa.me/201100018259', 'https://wa.me/'.($s['phone'] ?? '201100018259')]] as [$attribute,$old,$new]) {
            if ($old !== $new) {
                $d['assets'][] = ['attribute' => $attribute, 'original' => $old, 'value' => $new];
            }
        }

        return $d;
    }

    public function posts(bool $includeDrafts = false, array $ids = []): array
    {
        $q = DB::table('cms_posts as p')->join('contents as c', 'c.id', '=', 'p.content_id')->orderByDesc('c.published_at');
        $q->where(function ($q) use ($includeDrafts, $ids) {
            $q->where(fn ($q) => $q->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now())));
            if ($includeDrafts && $ids) {
                $q->orWhereIn('c.id', $ids);
            }
        });

        return $q->pluck('p.data')->map(fn ($d) => json_decode($d, true))->all();
    }

    public function invalidate(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->clearContentCache());

            return;
        }$this->clearContentCache();
    }

    private function clearContentCache(): void
    {
        Cache::increment('cms:generation');
        Cache::forget('cms:public-data');
        Cache::forget('cms:media-alts');
        Cache::forget('cms:collection:__fragments');
        Cache::forget('local-public-posts');
        Cache::forget('local-public-posts:'.date('Y-m-d-H-i'));
        foreach (array_keys(self::COLLECTIONS) as $k) {
            Cache::forget('cms:collection:'.$k);
        }
    }

    public function audit(string $action, string $entity, string|int|null $id = null, array $details = []): void
    {
        DB::table('cms_audit')->insert(['user_id' => auth()->id(), 'action' => $action, 'entity' => $entity, 'entity_id' => $id, 'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'created_at' => now()]);
    }

    public function snapshot(int $id): array
    {
        return ['content' => (array) DB::table('contents')->find($id), 'routes' => DB::table('content_routes')->where('content_id', $id)->get()->map(fn ($r) => (array) $r)->all(), 'translations' => DB::table('content_translations')->where('content_id', $id)->get()->map(fn ($r) => (array) $r)->all(), 'post' => DB::table('cms_posts')->where('content_id', $id)->value('data'), 'designs' => DB::table('cms_designs')->where('content_id', $id)->get()->map(fn ($r) => (array) $r)->all()];
    }

    public function revision(string $entity, string|int $id, array $snapshot, string $description): void
    {
        DB::table('cms_revisions')->insert(['entity' => $entity, 'entity_id' => (string) $id, 'user_id' => auth()->id(), 'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'description' => $description, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function assertRole(array $roles): void
    {
        abort_unless(auth()->user() && in_array(auth()->user()->cmsRole(), $roles, true), 403);
    }

    public function validPath(string $path, bool $allowAssets = false): bool
    {
        $reserved = $allowAssets ? 'admin|admin-assets|login|logout|local-api|forms|up|build|storage' : 'admin|admin-assets|login|logout|local-api|forms|up|build|storage|media|assets|wp-content';

        return str_starts_with($path, '/') && ! str_starts_with($path, '//')
            && ! str_contains($path, '%')
            && ! preg_match('~[?#\\\\\x00-\x20<>]|(?:^|/)\.\.?(/|$)|\.(xml|txt|php)$~iu', $path)
            && ! preg_match('~^/('.$reserved.')(/|$)~i', $path)
            && mb_strlen($path) <= 1800;
    }

    public function safeUrl(?string $value): bool
    {
        return ! $value || ((str_starts_with($value, '/') && ! str_starts_with($value, '//')) || preg_match('~^(https?://|mailto:|tel:|#)~i', $value)) && ! preg_match('~[\x00-\x1f<>"\\\\]~', $value);
    }
}
