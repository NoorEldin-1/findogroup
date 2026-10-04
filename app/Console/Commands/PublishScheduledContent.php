<?php

namespace App\Console\Commands;

use App\Services\Cms;
use App\Services\CmsRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishScheduledContent extends Command
{
    protected $signature = 'website:publish-due';

    protected $description = 'Refresh public listings when scheduled content becomes visible';

    public function handle(): int
    {
        $ids = DB::table('contents')->where('status', 'published')->where('published_at', '<=', now())->where('source_data->schedule_pending', true)->pluck('id');
        if ($ids->isEmpty()) {
            return self::SUCCESS;
        }DB::transaction(function () use ($ids) {
            $kinds = DB::table('contents')->whereIn('id', $ids)->pluck('kind')->unique();
            foreach (DB::table('contents')->whereIn('id', $ids)->lockForUpdate()->get() as $row) {
                $data = json_decode($row->source_data, true);
                $data['schedule_pending'] = false;
                DB::table('contents')->where('id', $row->id)->update(['source_data' => json_encode($data), 'source_updated_at' => now(), 'updated_at' => now()]);
            }app(CmsRenderer::class)->render(DB::table('content_routes')->whereIn('path', ['/', '/blogs', '/news'])->pluck('content_id')->all());
            foreach (['portfolio' => 'projects', 'service' => 'services', 'system' => 'systems'] as $kind => $collection) {
                if ($kinds->contains($kind)) {
                    app(CmsRenderer::class)->refreshPublic($collection);
                }
            }
            app(Cms::class)->audit('scheduled_publish', 'content', null, ['ids' => $ids->all()]);
            app(Cms::class)->invalidate();
        });
        $this->info('Published scheduled listings: '.$ids->count());

        return self::SUCCESS;
    }
}
