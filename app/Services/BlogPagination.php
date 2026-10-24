<?php

namespace App\Services;

use Carbon\Carbon;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Request;

class BlogPagination
{
    public function apply(array $page, Request $request, string $locale): ?array
    {
        $filters = $this->filters($request);
        if ($filters === null) {
            return null;
        }
        $listing = app(PublishedPosts::class)->paginate($filters + ['perPage' => 9]);
        if ($filters['page'] > max(1, $listing['totalPages'])) {
            return null;
        }
        $document = app(CmsHtml::class)->document($page['body_html']);
        $xpath = new DOMXPath($document);
        $island = $xpath->query('//*[@data-island="Blogs"]')->item(0);
        $grid = $xpath->query('.//main//div[contains(@class,"grid-cols-1") and contains(@class,"lg:grid-cols-3")]', $island)->item(0);
        $template = $grid ? $xpath->query('./a[.//h3]', $grid)->item(0)?->cloneNode(true) : null;
        if (! $island || ! $grid || (! $template && $listing['posts'])) {
            throw new \RuntimeException('The blog card template is missing.');
        }
        foreach (iterator_to_array($grid->childNodes) as $node) {
            $grid->removeChild($node);
        }
        $initialPosts = [];
        foreach ($listing['posts'] as $post) {
            $card = $template->cloneNode(true);
            $card->setAttribute('href', '/blogs/'.$post['slug']);
            $title = $this->plain($post['title']['rendered']);
            $this->text($xpath->query('.//h3', $card)->item(0), $title);
            $this->text($xpath->query('.//p', $card)->item(0), $this->plain($post['excerpt']['rendered']));
            $image = $xpath->query('.//img', $card)->item(0);
            $image->setAttribute('src', $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '/placeholder.svg');
            $image->setAttribute('alt', $title);
            $category = $post['_embedded']['wp:term'][0][0]['name'] ?? '';
            $badge = $xpath->query('./div[1]//span', $card)->item(0);
            if ($badge && $category !== '') {
                $this->text($badge, $category);
            } elseif ($badge) {
                $badge->parentNode->removeChild($badge);
            }
            $time = $xpath->query('.//time', $card)->item(0);
            $time->setAttribute('datetime', $post['date']);
            $formatted = Carbon::parse($post['date'])->timezone(config('app.timezone'))->locale('ar')->translatedFormat('j F Y');
            $this->text($time, strtr($formatted, array_combine(range(0, 9), mb_str_split('٠١٢٣٤٥٦٧٨٩'))));
            $readingTime = max(1, (int) ceil(count(preg_split('/\s+/u', $this->plain($post['content']['rendered'] ?? ''))) / 200));
            $reading = $xpath->query('.//time/parent::span/following-sibling::span[1]', $card)->item(0);
            foreach (iterator_to_array($reading->childNodes) as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $reading->removeChild($child);
                }
            }
            $reading->appendChild($document->createTextNode($locale === 'ar' ? $readingTime.' دقائق قراءة' : $readingTime.' min read'));
            $grid->appendChild($card);
            $post['readingTime'] = $readingTime;
            unset($post['content']);
            $initialPosts[] = $post;
        }
        $active = $filters['category'] !== null || $filters['search'] !== '' || $filters['date'] !== 'all';
        $count = $xpath->query('./p[contains(@class,"mb-6")]', $grid->parentNode)->item(0);
        if ($count) {
            $this->text($count, ($locale === 'ar' ? 'عرض '.count($initialPosts).' مقال' : 'Showing '.count($initialPosts).' articles').($active ? ($locale === 'ar' ? ' (مع الفلاتر)' : ' (with filters)') : ''));
        }
        $pagination = $xpath->query('./div[contains(@class,"mt-12") and contains(@class,"justify-center")]', $grid->parentNode)->item(0);
        $navigation = $document->createElement('nav');
        $navigation->setAttribute('class', 'mt-12 flex items-center justify-center gap-2');
        $navigation->setAttribute('aria-label', $locale === 'ar' ? 'ترقيم المدونة' : 'Blog pages');
        if ($listing['totalPages'] > 1) {
            $previous = $pagination ? $xpath->query('./button[1]', $pagination)->item(0) : null;
            $next = $pagination ? $xpath->query('./button[last()]', $pagination)->item(0) : null;
            $this->arrow($navigation, $previous, $filters['page'] - 1, $filters, $filters['page'] === 1, $locale === 'ar' ? 'الصفحة السابقة' : 'Previous page');
            $numbers = $document->createElement('div');
            $numbers->setAttribute('class', 'flex gap-1');
            $start = max(1, min($filters['page'] - 2, $listing['totalPages'] - 4));
            for ($number = $start; $number <= min($listing['totalPages'], $start + 4); $number++) {
                $link = $document->createElement('a', (string) $number);
                $link->setAttribute('href', $this->url($filters, $number));
                $link->setAttribute('class', 'inline-flex items-center justify-center h-10 w-10 rounded-full border font-display font-semibold transition-all duration-200 ease-ds '.($number === $filters['page'] ? 'border-primary bg-primary/15 text-primary' : 'border-border bg-popover text-muted-foreground hover:border-primary/60 hover:text-primary'));
                if ($number === $filters['page']) {
                    $link->setAttribute('aria-current', 'page');
                }
                $numbers->appendChild($link);
            }
            $navigation->appendChild($numbers);
            $this->arrow($navigation, $next, $filters['page'] + 1, $filters, $filters['page'] === $listing['totalPages'], $locale === 'ar' ? 'الصفحة التالية' : 'Next page');
        }
        if ($pagination) {
            $pagination->parentNode->replaceChild($navigation, $pagination);
        } else {
            $grid->parentNode->appendChild($navigation);
        }
        if (! $initialPosts) {
            $empty = $document->createElement('div');
            $empty->setAttribute('class', 'py-24 text-center');
            $heading = $document->createElement('h3');
            $heading->setAttribute('class', 'mb-2 text-xl font-bold text-foreground');
            $this->text($heading, $locale === 'ar' ? 'لا توجد مقالات' : 'No Articles Found');
            $empty->appendChild($heading);
            $clear = $document->createElement('a');
            $clear->setAttribute('href', '/blogs');
            $clear->setAttribute('class', 'text-primary hover:underline');
            $this->text($clear, $locale === 'ar' ? 'مسح جميع الفلاتر' : 'Clear All Filters');
            $empty->appendChild($clear);
            $grid->parentNode->insertBefore($empty, $navigation);
        }
        $categories = app(Cms::class)->collection('categories');
        $island->setAttribute('data-props', json_encode([
            'initialData' => ['posts' => $initialPosts, 'total' => $listing['total'], 'totalPages' => $listing['totalPages']],
            'initialCategories' => $categories, 'initialFilters' => $filters,
            'initialDateFilterOptions' => $this->dateOptions(),
            'initialSeo' => [
                'title' => $page['seo']['title'] ?? $page['title'],
                'baseUrl' => rtrim(config('website.seo_base_url'), '/'),
                'indexable' => ! config('website.prevent_indexing') && $page['indexable'] && app(SeoPolicy::class)->allowsIndexing($page['seo']),
                'robots' => $page['seo']['robots'] ?? 'index, follow, max-image-preview:large',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $search = $xpath->query('.//main//input[@type="text"]', $island)->item(0);
        $search?->setAttribute('value', $filters['search']);
        $bar = $xpath->query('.//main//div[contains(@class,"mb-8") and contains(@class,"flex-col") and contains(@class,"gap-4")]', $island)->item(0);
        if ($bar) {
            foreach (iterator_to_array($bar->firstElementChild->getElementsByTagName('button')) as $index => $button) {
                $this->activeChip($button, $index === 0 ? $filters['category'] === null : ($categories[$index - 1]['id'] ?? null) === $filters['category']);
            }
            foreach (iterator_to_array($bar->lastElementChild->getElementsByTagName('button')) as $index => $button) {
                $this->activeChip($button, ($this->dateOptions()[$index]['value'] ?? null) === $filters['date']);
            }
        }
        $page['body_html'] = app(CmsHtml::class)->body($document);
        $canonical = rtrim(config('website.seo_base_url'), '/').$this->url($filters, $filters['page']);
        $page['seo']['canonical'] = $canonical;
        if ($filters['page'] > 1) {
            $suffix = $locale === 'ar' ? ' — الصفحة '.$filters['page'] : ' — Page '.$filters['page'];
            $title = $page['seo']['title'] ?? $page['title'];
            foreach (['title', 'og:title', 'twitter:title'] as $key) {
                $page['seo'][$key] = $title.$suffix;
            }
        }
        if ($active || ! $initialPosts) {
            $page['indexable'] = false;
        }
        foreach ($page['seo']['schemas'] ?? [] as $index => $schema) {
            if (array_intersect((array) ($schema['@type'] ?? []), ['WebPage', 'CollectionPage'])) {
                $page['seo']['schemas'][$index]['url'] = $canonical;
            }
        }

        return $page;
    }

    private function filters(Request $request): ?array
    {
        $values = $request->only(['page', 'category', 'search', 'date']);
        foreach ($values as $value) {
            if (! is_string($value)) {
                return null;
            }
        }
        $number = $values['page'] ?? '1';
        if (! ctype_digit($number) || strlen($number) > 6 || (int) $number < 1) {
            return null;
        }
        $search = trim($values['search'] ?? '');
        $date = $values['date'] ?? 'all';
        if (mb_strlen($search) > 250 || ! in_array($date, ['all', 'week', 'month', '3months', 'year'], true)) {
            return null;
        }
        $category = null;
        if (! empty($values['category'])) {
            foreach (app(Cms::class)->collection('categories') as $candidate) {
                if ((string) $candidate['id'] === $values['category'] || ($candidate['slug'] ?? '') === rtrim($values['category'], '/')) {
                    $category = (int) $candidate['id'];
                    break;
                }
            }
        }
        $after = collect($this->dateOptions())->firstWhere('value', $date)['after'] ?? null;

        return ['page' => (int) $number, 'category' => $category, 'search' => $search, 'date' => $date, 'after' => $after];
    }

    private function dateOptions(): array
    {
        return [
            ['label' => 'الكل', 'value' => 'all'],
            ['label' => 'هذا الأسبوع', 'value' => 'week', 'after' => now()->startOfDay()->subDays(7)->toIso8601String()],
            ['label' => 'هذا الشهر', 'value' => 'month', 'after' => now()->startOfDay()->subMonthNoOverflow()->toIso8601String()],
            ['label' => 'آخر 3 أشهر', 'value' => '3months', 'after' => now()->startOfDay()->subMonthsNoOverflow(3)->toIso8601String()],
            ['label' => 'هذا العام', 'value' => 'year', 'after' => now()->startOfDay()->subYearNoOverflow()->toIso8601String()],
        ];
    }

    private function activeChip(DOMElement $button, bool $active): void
    {
        $tokens = array_diff(preg_split('/\s+/', $button->getAttribute('class')), ['border-primary', 'bg-primary/15', 'text-primary', 'border-border', 'bg-popover', 'text-foreground']);
        $button->setAttribute('class', implode(' ', $tokens).' '.($active ? 'border-primary bg-primary/15 text-primary' : 'border-border bg-popover text-foreground'));
    }

    private function url(array $filters, int $page): string
    {
        $query = [];
        if ($filters['category'] !== null) {
            $query['category'] = $filters['category'];
        }
        if ($filters['search'] !== '') {
            $query['search'] = $filters['search'];
        }
        if ($filters['date'] !== 'all') {
            $query['date'] = $filters['date'];
        }
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/blogs'.($query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }

    private function arrow(DOMElement $navigation, ?DOMNode $template, int $page, array $filters, bool $disabled, string $label): void
    {
        $document = $navigation->ownerDocument;
        $link = $document->createElement($disabled ? 'span' : 'a');
        $link->setAttribute('class', 'grid h-10 w-10 place-items-center rounded-full border border-border bg-popover text-muted-foreground transition-all duration-200 ease-ds hover:border-primary/60 hover:text-primary'.($disabled ? ' cursor-not-allowed opacity-40' : ''));
        $link->setAttribute('aria-label', $label);
        if ($disabled) {
            $link->setAttribute('aria-disabled', 'true');
        } else {
            $link->setAttribute('href', $this->url($filters, $page));
        }
        foreach ($template ? iterator_to_array($template->childNodes) : [] as $node) {
            $link->appendChild($node->cloneNode(true));
        }
        $navigation->appendChild($link);
    }

    private function text(DOMNode $node, string $value): void
    {
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }
        $node->appendChild($node->ownerDocument->createTextNode($value));
    }

    private function plain(string $html): string
    {
        return trim(html_entity_decode(str_replace(['&hellip;', '&nbsp;'], ['...', ' '], strip_tags($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
