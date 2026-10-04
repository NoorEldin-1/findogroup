<?php

namespace App\Console\Commands;

use App\Services\Cms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstallCms extends Command
{
    protected $signature = 'website:install-cms';

    protected $description = 'Populate the CMS from the preserved website without replacing edits';

    public function handle(): int
    {
        $inventory = json_decode(file_get_contents(storage_path('app/cms/seed.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach (['categories', 'comments'] as $k) {
            $inventory[$k] = json_decode(file_get_contents(storage_path('app/imports/'.$k.'.json')), true);
        }
        $inventory['settings'] = ['site_name' => 'Findo Group', 'logo' => '/wp-content/themes/findo-wordpress-theme/react-build/assets/findo-logo.png', 'email' => 'info@findogroup.com', 'phone' => '201100018259', 'default_image' => 'https://findogroup.com/og-image.jpg', 'cache_seconds' => 300];
        foreach ($inventory as $key => $value) {
            if ($key === 'posts') {
                continue;
            }DB::table('cms_collections')->insertOrIgnore(['key' => $key, 'data' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]);
        }
        $posts = json_decode(file_get_contents(storage_path('app/imports/wordpress-posts.json')), true);
        foreach ($posts as $p) {
            $id = DB::table('content_routes')->where('path', '/blogs/'.rawurldecode($p['slug']))->value('content_id');
            if ($id) {
                DB::table('cms_posts')->insertOrIgnore(['content_id' => $id, 'data' => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        app(Cms::class)->invalidate();
        $this->info('CMS initialized; original content preserved.');

        return self::SUCCESS;
    }
}
