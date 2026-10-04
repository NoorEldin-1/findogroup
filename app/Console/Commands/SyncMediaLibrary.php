<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMediaLibrary extends Command
{
    protected $signature = 'website:sync-media-library {manifest=storage/app/cms/media-manifest.csv}';

    protected $description = 'Register recovered public images and videos in the CMS media library';

    public function handle(): int
    {
        $file = $this->argument('manifest');
        if (! is_file($file)) {
            $this->error('Media manifest missing.');

            return self::FAILURE;
        }
        $existing = array_fill_keys(DB::table('media_assets')->pluck('path')->all(), true);
        $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'pdf' => 'application/pdf'];
        $handle = fopen($file, 'r');
        fgetcsv($handle, escape: '');
        $batch = [];
        $added = 0;
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            [$path,$bytes,$sha] = $row;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (isset($existing[$path]) || ! isset($types[$ext]) || ! str_starts_with($path, '/') || str_contains($path, '..') || ! is_file(public_path($path))) {
                continue;
            }$batch[] = ['path' => $path, 'original_url' => $path, 'bytes' => (int) $bytes, 'sha256' => $sha, 'mime' => $types[$ext], 'alt' => null, 'created_at' => now(), 'updated_at' => now()];
            $added++;
            if (count($batch) >= 200) {
                DB::table('media_assets')->insert($batch);
                $batch = [];
            }
        }
        if ($batch) {
            DB::table('media_assets')->insert($batch);
        }fclose($handle);
        $this->info('Added '.$added.' recovered public files to the library.');

        return self::SUCCESS;
    }
}
