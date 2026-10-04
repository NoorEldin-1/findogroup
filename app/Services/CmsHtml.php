<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class CmsHtml
{
    public function document(string $html): DOMDocument
    {
        $d = new DOMDocument('1.0', 'UTF-8');
        @$d->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        return $d;
    }

    public function body(DOMDocument $d): string
    {
        $out = '';
        foreach ($d->getElementsByTagName('body')->item(0)->childNodes as $n) {
            $out .= $d->saveHTML($n);
        }

        return $out;
    }

    public function clean(string $html): string
    {
        $d = $this->document($html);
        $xp = new DOMXPath($d);
        foreach (iterator_to_array($xp->query('//script|//style|//iframe|//object|//embed|//form|//input|//button|//link|//meta|//base|//svg|//math|//template')) as $n) {
            $n->parentNode?->removeChild($n);
        }
        foreach ($xp->query('//*') as $n) {
            foreach (iterator_to_array($n->attributes) as $a) {
                if (str_starts_with(strtolower($a->name), 'on') || in_array(strtolower($a->name), ['srcdoc', 'style', 'srcset']) || (in_array(strtolower($a->name), ['href', 'src', 'poster', 'action', 'xlink:href']) && ! app(Cms::class)->safeUrl($a->value))) {
                    $n->removeAttribute($a->name);
                }
            }
        }

        return $this->body($d);
    }

    public function repairLegacyLinks(string $html, array $postPaths, array $pathAliases): string
    {
        $document = $this->document($html);
        $changed = false;
        foreach (iterator_to_array($document->getElementsByTagName('a')) as $anchor) {
            $href = $anchor->getAttribute('href');
            $parts = parse_url(preg_replace_callback('/[^\x00-\x7f]/u', fn ($match) => rawurlencode($match[0]), $href));
            if ($parts === false || (isset($parts['host']) && ! in_array($parts['host'], ['findogroup.com', 'www.findogroup.com'], true))) {
                continue;
            }
            $path = rtrim(rawurldecode($parts['path'] ?? ''), '/');
            $target = null;
            $remove = false;
            if (in_array($path, ['_wp_link_placeholder', '/blogs/_wp_link_placeholder'], true)) {
                $remove = true;
            } elseif ($path === '/wp-admin/post.php') {
                parse_str($parts['query'] ?? '', $query);
                $target = $postPaths[(int) ($query['post'] ?? 0)] ?? null;
                $remove = $target === null;
            } elseif (isset($pathAliases[$path])) {
                $target = $pathAliases[$path];
            }
            if ($remove) {
                while ($anchor->firstChild) {
                    $anchor->parentNode->insertBefore($anchor->firstChild, $anchor);
                }
                $anchor->parentNode->removeChild($anchor);
                $changed = true;
            } elseif ($target !== null) {
                $anchor->setAttribute('href', $target);
                $changed = true;
            }
        }

        return $changed ? $this->body($document) : $html;
    }

    public function fragments(string $html, array $fragments): string
    {
        if (! $fragments) {
            return $html;
        }$d = $this->document($html);
        $xp = new DOMXPath($d);
        foreach ($fragments as $name => $fragment) {
            foreach (iterator_to_array($xp->query('//*[@data-island="'.$name.'"]')) as $node) {
                $f = $this->document($fragment);
                $replacement = $f->getElementsByTagName('body')->item(0)->firstChild;
                if ($replacement) {
                    $node->parentNode?->replaceChild($d->importNode($replacement, true), $node);
                }
            }
        }

        return $this->body($d);
    }

    public function extractFragments(string $html): array
    {
        $d = $this->document($html);
        $xp = new DOMXPath($d);
        $out = [];
        foreach (['SiteHeader', 'SiteFooter'] as $name) {
            $node = $xp->query('//*[@data-island="'.$name.'"]')->item(0);
            if ($node) {
                $out[$name] = $d->saveHTML($node);
            }
        }

        return $out;
    }

    public function mediaAlts(string $html, array $alts): string
    {
        if (! $alts) {
            return $html;
        }$d = $this->document($html);
        foreach ($d->getElementsByTagName('img') as $img) {
            $path = str_replace('https://findogroup.com', '', $img->getAttribute('src'));
            if (array_key_exists($path, $alts)) {
                $img->setAttribute('alt', $alts[$path] ?? '');
            }
        }

        return $this->body($d);
    }

    public function fields(string $html): array
    {
        $d = $this->document($html);
        $xp = new DOMXPath($d);
        $texts = [];
        $assets = [];
        $sections = [];
        foreach ($xp->query('//body//text()[not(ancestor::script) and not(ancestor::style)]') as $n) {
            $v = trim($n->nodeValue);
            if (mb_strlen($v) > 1 && mb_strlen($v) < 2000) {
                $texts[$v] = $v;
            }
        }
        foreach ($xp->query('//img|//video|//source|//a') as $n) {
            foreach (['src', 'poster', 'href', 'alt'] as $attr) {
                if ($n->hasAttribute($attr)) {
                    $v = $n->getAttribute($attr);
                    if ($v !== '') {
                        $assets[$attr.'|'.$v] = ['attribute' => $attr, 'value' => $v];
                    }
                }
            }
        }
        foreach ($xp->query('//section') as $n) {
            $h = $xp->query('.//h2|.//h1', $n)->item(0);
            if ($h) {
                $sections[] = trim($h->textContent);
            }
        }

        return ['texts' => array_values($texts), 'assets' => array_values($assets), 'sections' => array_values(array_unique($sections))];
    }

    public function apply(string $html, array $design): string
    {
        $html = str_replace(['https://erp.findosystem.com/login', 'href="/login"'], ['/admin', 'href="/admin"'], $html);
        if (empty($design['texts']) && empty($design['assets']) && empty($design['hidden']) && empty($design['blocks'])) {
            return $html;
        }
        $d = $this->document($html);
        $xp = new DOMXPath($d);
        foreach ($xp->query('//body//text()[not(ancestor::script) and not(ancestor::style)]') as $n) {
            $old = trim($n->nodeValue);
            if (isset($design['texts'][$old])) {
                $n->nodeValue = str_replace($old, $design['texts'][$old], $n->nodeValue);
            }
        }
        foreach ($xp->query('//body//*') as $n) {
            foreach ($design['assets'] ?? [] as $a) {
                if ($n->hasAttribute($a['attribute']) && $n->getAttribute($a['attribute']) === $a['original']) {
                    $n->setAttribute($a['attribute'], $a['value']);
                }
            }
        }
        foreach (iterator_to_array($xp->query('//section')) as $n) {
            $h = $xp->query('.//h2|.//h1', $n)->item(0);
            if ($h && in_array(trim($h->textContent), array_merge($design['hidden'] ?? [], array_map(fn ($s) => $design['texts'][$s] ?? $s, $design['hidden'] ?? [])), true)) {
                $n->parentNode?->removeChild($n);
            }
        }
        $main = $xp->query('//main')->item(0);
        if ($main) {
            foreach ($design['blocks'] ?? [] as $i => $block) {
                $section = $d->createElement('section');
                $section->setAttribute('class', 'cms-custom-block');
                $section->setAttribute('data-cms-block', (string) $i);
                $fragment = $this->document($this->clean($block['html'] ?? ''));
                foreach (iterator_to_array($fragment->getElementsByTagName('body')->item(0)->childNodes) as $n) {
                    $section->appendChild($d->importNode($n, true));
                }$main->appendChild($section);
            }
        }

        return $this->body($d);
    }
}
