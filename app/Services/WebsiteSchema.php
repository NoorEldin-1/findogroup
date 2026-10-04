<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WebsiteSchema
{
    public function normalize(array $schemas, string $body, string $kind, array $settings): array
    {
        $language = $this->contentLanguage($body, $kind);

        return array_map(fn (array $node) => $this->node($node, $language, $settings), $schemas);
    }

    private function contentLanguage(string $body, string $kind): ?string
    {
        if ($kind === 'post' && preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $body, $article)) {
            $body = $article[1];
        }
        $text = html_entity_decode(strip_tags(preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $body)));
        $arabic = preg_match_all('/[\x{0621}-\x{064a}]/u', $text);
        $latin = preg_match_all('/[a-z]/i', $text);

        if ($arabic >= 20 && $arabic > $latin) {
            return 'ar';
        }
        if ($latin >= 20 && $latin > $arabic * 2) {
            return 'en';
        }

        return null;
    }

    private function node(array $node, ?string $language, array $settings): array
    {
        $types = (array) ($node['@type'] ?? []);
        if (in_array('WebSite', $types, true)) {
            $node['inLanguage'] = ['ar', 'en'];
        } elseif ($language && array_intersect($types, ['WebPage', 'CollectionPage', 'AboutPage', 'ContactPage', 'Article', 'BlogPosting', 'NewsArticle'])) {
            $node['inLanguage'] = $language;
        }
        if (array_intersect($types, ['Organization', 'ProfessionalService']) && $this->isCompany($node)) {
            if (! empty($settings['phone'])) {
                $node['telephone'] = '+'.ltrim($settings['phone'], '+');
            }
            if (! empty($settings['email'])) {
                $node['email'] = $settings['email'];
            }
            if (! empty($settings['logo'])) {
                $logo = str_starts_with($settings['logo'], '/') ? rtrim(config('website.seo_base_url'), '/').$settings['logo'] : $settings['logo'];
                $logoId = is_array($node['logo'] ?? null) ? ($node['logo']['@id'] ?? null) : null;
                $node['logo'] = ['@type' => 'ImageObject', 'url' => $logo];
                if ($logoId) {
                    $node['logo']['@id'] = $logoId;
                }
            }
            if (isset($node['openingHours'])) {
                $hours = array_values(array_filter((array) $node['openingHours'], fn ($value) => ! is_string($value) || ! str_contains($value, '10:00-10:00')));
                if ($hours) {
                    $node['openingHours'] = $hours;
                } else {
                    unset($node['openingHours']);
                }
            }
        }
        if (in_array('BreadcrumbList', $types, true)) {
            foreach ($node['itemListElement'] ?? [] as $index => $item) {
                if (is_string($item['item'] ?? null)) {
                    $node['itemListElement'][$index]['item'] = $this->breadcrumbUrl($item['item']);
                } elseif (is_array($item['item'] ?? null)) {
                    foreach (['@id', 'url'] as $key) {
                        if (is_string($item['item'][$key] ?? null)) {
                            $node['itemListElement'][$index]['item'][$key] = $this->breadcrumbUrl($item['item'][$key]);
                        }
                    }
                }
            }
        }
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->node($value, $language, $settings);
            } elseif (is_string($value) && in_array($key, ['name', 'headline', 'description'], true)) {
                $node[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $node;
    }

    private function isCompany(array $node): bool
    {
        return in_array($node['@id'] ?? '', [rtrim(config('website.seo_base_url'), '/').'/#organization', rtrim(config('website.seo_base_url'), '/').'/#findo'], true)
            || preg_match('/findo\s*group|فايندو\s*جروب/iu', $node['name'] ?? '') === 1;
    }

    private function breadcrumbUrl(string $url): string
    {
        $base = rtrim(config('website.seo_base_url'), '/');
        if (parse_url($url, PHP_URL_HOST) !== parse_url($base, PHP_URL_HOST)) {
            return $url;
        }
        $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?: '/');
        $query = parse_url($url, PHP_URL_QUERY);
        $seen = [];
        for ($step = 0; $step < 5; $step++) {
            if (isset($seen[$path])) {
                return $url;
            }
            $seen[$path] = true;
            $published = DB::table('content_routes as r')->join('contents as c', 'c.id', '=', 'r.content_id')
                ->where('r.path', $path)->where('c.status', 'published')
                ->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()))->exists();
            if ($published) {
                return $base.$path.($query ? '?'.$query : '');
            }
            $redirect = DB::table('redirects')->where('source_path', $path)->whereIn('status', [301, 308])->value('target_path');
            if (! is_string($redirect) || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
                return $url;
            }
            $path = rawurldecode(parse_url($redirect, PHP_URL_PATH) ?: '/');
            $query = parse_url($redirect, PHP_URL_QUERY) ?: $query;
        }

        return $url;
    }
}
