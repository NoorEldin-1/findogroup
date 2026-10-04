<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportWebsite extends Command
{
    protected $signature = 'website:import {file=storage/app/imports/website.jsonl}';

    protected $description = 'Import the populated website snapshot idempotently into PostgreSQL';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error('Import manifest missing: '.$file);

            return self::FAILURE;
        }
        $stream = fopen($file, 'r');
        $count = 0;
        while (($line = fgets($stream)) !== false) {
            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            DB::transaction(function () use ($row) {
                $key = ['source_system' => 'findo-snapshot', 'source_id' => $row['path']];
                DB::table('contents')->updateOrInsert($key, [
                    'kind' => $row['kind'], 'status' => 'published',
                    'source_data' => json_encode($row['source'] ?? [], JSON_UNESCAPED_UNICODE),
                    'published_at' => $row['published_at'] ?? null, 'source_updated_at' => $row['modified_at'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $id = DB::table('contents')->where($key)->value('id');
                DB::table('content_routes')->updateOrInsert(['path' => $row['path']], ['content_id' => $id, 'indexable' => $row['indexable'], 'created_at' => now(), 'updated_at' => now()]);
                DB::table('content_translations')->updateOrInsert(['content_id' => $id, 'locale' => $row['locale']], [
                    'title' => $row['seo']['title'] ?? $row['path'], 'excerpt' => $row['seo']['description'] ?? '',
                    'body_html' => $row['html'], 'seo' => json_encode($row['seo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'view_data' => json_encode($row['view_data'] ?? [], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            $count++;
            if ($count % 100 === 0) {
                $this->line("Imported {$count} translations");
            }
        }
        fclose($stream);
        DB::table('import_runs')->insert(['source' => basename($file), 'checksum' => hash_file('sha256', $file), 'summary' => json_encode(['translations' => $count]), 'created_at' => now(), 'updated_at' => now()]);
        Cache::flush();
        $this->info("Imported {$count} populated translations successfully.");

        return self::SUCCESS;
    }
}
