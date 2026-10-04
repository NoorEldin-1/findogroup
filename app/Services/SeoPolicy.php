<?php

namespace App\Services;

class SeoPolicy
{
    public function allowsIndexing(array $seo): bool
    {
        return ! preg_match('/(?:^|[\s,])(noindex|none)(?:$|[\s,])/i', $seo['robots'] ?? '');
    }

    public function validCanonical(string $url): bool
    {
        $encoded = preg_replace_callback('/[^\x00-\x7f]/u', fn ($match) => rawurlencode($match[0]), $url);

        return $encoded !== null && (bool) filter_var($encoded, FILTER_VALIDATE_URL)
            && in_array(parse_url($encoded, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    public function validSchemas(array $schemas): bool
    {
        foreach ($schemas as $schema) {
            if (! is_array($schema) || (! isset($schema['@type']) && ! isset($schema['@graph']))) {
                return false;
            }
            if (isset($schema['@type'])) {
                $types = (array) $schema['@type'];
                if (! $types || count(array_filter($types, fn ($type) => is_string($type) && trim($type) !== '')) !== count($types)) {
                    return false;
                }
            }
            if (isset($schema['@graph']) && (! is_array($schema['@graph']) || ! $this->validSchemas($schema['@graph']))) {
                return false;
            }
        }

        return true;
    }

    public function isSelfCanonical(string $path, array $seo): bool
    {
        $expected = rtrim(config('website.seo_base_url'), '/').$path;
        $canonical = $seo['canonical'] ?? $expected;

        return $this->normalize($canonical) === $this->normalize($expected);
    }

    private function normalize(string $url): string
    {
        return rtrim(rawurldecode($url), '/');
    }
}
