<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMedia extends Command
{
    protected $signature = 'website:import-media';

    protected $description = 'Register the original published media with content hashes and alt text';

    public function handle(): int
    {
        $file = storage_path('app/imports/media.jsonl');
        if (! is_file($file)) {
            $this->error('Media manifest missing');

            return self::FAILURE;
        }
        $count = 0;
        $stream = fopen($file, 'r');
        while (($line = fgets($stream)) !== false) {
            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            DB::table('media_assets')->updateOrInsert(['path' => $row['path']], array_merge($row, ['created_at' => now(), 'updated_at' => now()]));
            $count++;
        }
        fclose($stream);
        $this->info("Registered {$count} media records.");

        return self::SUCCESS;
    }
}
