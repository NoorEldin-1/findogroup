<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncMigrationMetadata extends Command
{
    protected $signature = 'website:sync-metadata';

    protected $description = 'Apply verified live SEO metadata and historical redirects from the migration manifests';

    public function handle(): int
    {
        $seoPath = storage_path('app/imports/seo-export.jsonl');
        if (! is_file($seoPath)) {
            $this->error('SEO manifest missing');

            return self::FAILURE;
        }
        $count = 0;
        foreach (file($seoPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            if (isset($row['error'])) {
                continue;
            }
            $id = DB::table('content_routes')->where('path', $row['path'])->value('content_id');
            if (! $id) {
                $this->warn('Unmapped SEO route: '.$row['path']);

                continue;
            }
            DB::table('content_translations')->where('content_id', $id)->where('locale', 'ar')->update([
                'seo' => json_encode($row['seo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'title' => $row['seo']['title'], 'excerpt' => $row['seo']['description'] ?? '', 'updated_at' => now(),
            ]);
            DB::table('content_routes')->where('content_id', $id)->update(['indexable' => ! str_contains($row['seo']['robots'] ?? '', 'noindex')]);
            $count++;
        }
        $redirects = json_decode(file_get_contents(storage_path('app/imports/redirects.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($redirects as $source => $target) {
            if ($source === $target || $source === '/') {
                continue;
            }
            DB::table('redirects')->updateOrInsert(['source_path' => $source], ['target_path' => $target, 'status' => 301, 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::flush();
        $this->info("Synchronized {$count} SEO routes and ".count($redirects).' historical redirects.');

        return self::SUCCESS;
    }
}
