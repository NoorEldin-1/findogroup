<?php

namespace Tests\Feature;

use App\Services\CmsHtml;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlogPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['website.prevent_indexing' => false]);
    }

    public function test_page_two_serves_its_own_articles_metadata_and_real_links_without_javascript(): void
    {
        $this->seedBlog(10);

        $response = $this->get('/blogs?page=2');

        $response->assertOk()->assertHeader('X-Robots-Tag', 'index, follow')
            ->assertSee('href="/blogs/post-1"', false)->assertDontSee('href="/blogs/post-10"', false)
            ->assertSee('rel="canonical" href="https://findogroup.com/blogs?page=2"', false)
            ->assertSee('<title>Blog — الصفحة 2</title>', false)
            ->assertSee('property="og:title" content="Blog — الصفحة 2"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('class="original-card-design"', false);
        $xpath = new DOMXPath(app(CmsHtml::class)->document($response->getContent()));
        $this->assertSame('/blogs', $xpath->query('//nav//a[1]')->item(0)->getAttribute('href'));
        $props = json_decode($xpath->query('//*[@data-island="Blogs"]')->item(0)->getAttribute('data-props'), true);
        $this->assertSame(2, $props['initialFilters']['page']);
        $this->assertSame(['post-1'], array_column($props['initialData']['posts'], 'slug'));
        $this->assertArrayNotHasKey('content', $props['initialData']['posts'][0]);
    }

    public function test_first_page_has_a_crawlable_next_link_and_disabled_previous_control(): void
    {
        $this->seedBlog(10);

        $response = $this->get('/blogs');

        $response->assertOk()->assertSee('href="/blogs?page=2"', false)
            ->assertSee('rel="canonical" href="https://findogroup.com/blogs"', false);
        $xpath = new DOMXPath(app(CmsHtml::class)->document($response->getContent()));
        $this->assertSame(9, $xpath->query('//a[contains(@class,"original-card-design")]')->length);
        $this->assertSame(1, $xpath->query('//nav/span[@aria-disabled="true"]')->length);
        $this->assertSame('/blogs?page=2', $xpath->query('//nav/a[last()]')->item(0)->getAttribute('href'));
    }

    public function test_explicit_first_page_and_slash_redirect_directly_to_the_clean_url(): void
    {
        $this->seedBlog(1);

        $this->get('/blogs/?page=1&utm_source=example')->assertStatus(301)->assertRedirect('/blogs?utm_source=example');
    }

    #[DataProvider('invalidPages')]
    public function test_invalid_and_out_of_range_pages_return_404(string $query): void
    {
        $this->seedBlog(1);

        $this->get('/blogs?'.$query)->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, follow');
    }

    public static function invalidPages(): array
    {
        return ['zero' => ['page=0'], 'negative' => ['page=-1'], 'text' => ['page=unknown'], 'decimal' => ['page=2.5'], 'array' => ['page[]=2'], 'out_of_range' => ['page=2'], 'large' => ['page=1000000']];
    }

    public function test_filtered_pagination_keeps_category_search_and_date_without_index_bloat(): void
    {
        $this->travelTo('2026-10-22 10:00:00');
        $this->seedBlog(10);
        $this->postRecord('wrong-category', '2026-10-21 11:00:00', 'published', 2);
        $this->postRecord('too-old', '2026-09-01 11:00:00');

        $response = $this->get('/blogs?category=1&search=Example&date=week&page=2');

        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('href="/blogs/post-1"', false)->assertDontSee('href="/blogs/wrong-category"', false)
            ->assertDontSee('href="/blogs/too-old"', false)
            ->assertSee('href="/blogs?category=1&amp;search=Example&amp;date=week"', false)
            ->assertSee('rel="canonical" href="https://findogroup.com/blogs?category=1&amp;search=Example&amp;date=week&amp;page=2"', false);
    }

    public function test_empty_search_results_remain_usable_and_user_input_is_escaped(): void
    {
        $this->seedBlog(1);

        $this->get('/blogs?search='.rawurlencode('<img src=x onerror=alert(1)>'))
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('لا توجد مقالات')->assertSee('href="/blogs"', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_unknown_legacy_category_parameters_keep_the_existing_canonical_listing(): void
    {
        $this->seedBlog(1);

        $this->get('/blogs?category=retired-category/page/5/')->assertOk()
            ->assertSee('href="/blogs/post-1"', false)
            ->assertSee('rel="canonical" href="https://findogroup.com/blogs"', false);
    }

    public function test_draft_future_and_archived_articles_are_never_listed(): void
    {
        $this->travelTo('2026-10-22 10:00:00');
        $this->seedBlog(1);
        $this->postRecord('draft-hidden', '2026-10-21 12:00:00', 'draft');
        $this->postRecord('future-hidden', '2026-10-23 12:00:00');
        $this->postRecord('archived-hidden', '2026-10-21 12:00:00', 'archived');

        $this->get('/blogs')->assertOk()->assertSee('href="/blogs/post-1"', false)
            ->assertDontSee('draft-hidden')->assertDontSee('future-hidden')->assertDontSee('archived-hidden');
    }

    public function test_api_uses_the_same_stable_order_when_publication_times_are_equal(): void
    {
        $this->seedBlog(10);

        $this->getJson('/local-api/posts?perPage=9&page=2')->assertOk()
            ->assertJsonPath('total', 10)->assertJsonPath('totalPages', 2)
            ->assertJsonCount(1, 'posts')->assertJsonPath('posts.0.slug', 'post-1');
    }

    public function test_english_pagination_retains_the_english_title_and_self_canonical(): void
    {
        $this->seedBlog(10);

        $this->withUnencryptedCookie('findo_language', 'en')->get('/blogs?page=2')
            ->assertOk()->assertSee('<title>Blog — Page 2</title>', false)
            ->assertSee('aria-label="Blog pages"', false)->assertSee('dir="ltr"', false);
    }

    private function seedBlog(int $count): void
    {
        $this->travelTo('2026-10-22 10:00:00');
        $id = DB::table('contents')->insertGetId(['kind' => 'page', 'source_system' => 'test', 'source_id' => '/blogs', 'status' => 'published']);
        DB::table('content_routes')->insert(['content_id' => $id, 'path' => '/blogs', 'indexable' => true]);
        foreach (['ar', 'en'] as $locale) {
            DB::table('content_translations')->insert(['content_id' => $id, 'locale' => $locale, 'title' => 'Blog', 'body_html' => file_get_contents(base_path('tests/Fixtures/blog-page.html')), 'seo' => json_encode(['title' => 'Blog', 'description' => 'Blog description', 'canonical' => 'https://findogroup.com/blogs'])]);
        }
        DB::table('cms_collections')->insert(['key' => 'categories', 'data' => json_encode([['id' => 1, 'slug' => 'example', 'name' => 'Example', 'count' => $count], ['id' => 2, 'slug' => 'other', 'name' => 'Other', 'count' => 1]])]);
        for ($i = 1; $i <= $count; $i++) {
            $this->postRecord('post-'.$i, '2026-10-21 10:00:00');
        }
    }

    private function postRecord(string $slug, string $date, string $status = 'published', int $category = 1): void
    {
        $id = DB::table('contents')->insertGetId(['kind' => 'post', 'source_system' => 'test', 'source_id' => '/blogs/'.$slug, 'status' => $status, 'published_at' => $date]);
        DB::table('content_routes')->insert(['content_id' => $id, 'path' => '/blogs/'.$slug, 'indexable' => true]);
        DB::table('cms_posts')->insert(['content_id' => $id, 'data' => json_encode(['id' => $id, 'slug' => $slug, 'date' => $date, 'title' => ['rendered' => 'Example '.$slug], 'excerpt' => ['rendered' => '<p>Example excerpt</p>'], 'content' => ['rendered' => '<p>Useful content</p>'], 'categories' => [$category]])]);
    }
}
