<?php

namespace App\Console\Commands;

use App\Services\OperatingWorkflow;
use App\Services\WebsiteSeoAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperatingSeoAudit extends Command
{
    protected $signature = 'operating:seo-audit {--sync : Add current technical errors to the SEO remediation register} {--locale=both : ar, en or both}';

    protected $description = 'Audit both website languages and optionally register actual technical SEO errors without rewriting original content';

    public function handle(WebsiteSeoAudit $audit, OperatingWorkflow $workflow): int
    {
        $option = $this->option('locale');
        if (! in_array($option, ['ar', 'en', 'both'], true)) {
            $this->error('Use ar, en or both.');

            return self::FAILURE;
        }
        $results = [];
        foreach ($option === 'both' ? ['ar', 'en'] : [$option] as $locale) {
            $data = $audit->run($locale);
            $results[$locale] = ['total' => $data['total'], 'technical_error_pages' => $data['errorCount'], 'warning_pages' => count(array_filter($data['issues'], fn (array $issue): bool => count($issue['warnings']) > 0)), 'excluded' => $data['excluded'], 'duplicate_title_groups' => count($data['duplicates'])];
            if ($this->option('sync')) {
                DB::transaction(function () use ($data, $locale, $workflow): void {
                    if (DB::getDriverName() === 'pgsql') {
                        DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
                    }
                    $errorKeys = [];
                    foreach ($data['issues'] as $issue) {
                        if (! $issue['errors']) {
                            continue;
                        }
                        $key = 'seo:'.$locale.':'.$issue['row']->id;
                        $errorKeys[] = $key;
                        $existing = DB::table('operating_records')->where('external_id', $key)->where('source', 'findo')->first();
                        $recordData = ['url' => config('website.seo_base_url').$issue['row']->path, 'language' => $locale, 'source' => 'local', 'severity' => 'error', 'issue' => implode('; ', $issue['errors']), 'checked_at' => today()->toDateString()];
                        if ($existing) {
                            $before = (array) $existing;
                            DB::table('operating_records')->where('id', $existing->id)->update(['data' => json_encode(array_merge(json_decode($existing->data, true), $recordData), JSON_UNESCAPED_UNICODE), 'status' => 'open', 'version' => $existing->version + 1, 'updated_at' => now()]);
                            $id = $existing->id;
                        } else {
                            $before = null;
                            $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'seo_issues', 'title' => 'SEO '.$locale.' · '.$issue['row']->title, 'data' => json_encode($recordData, JSON_UNESCAPED_UNICODE), 'source' => 'findo', 'external_id' => $key, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
                        }
                        $record = DB::table('operating_records')->find($id);
                        $workflow->event($record, 'seo_check', 'Actual local technical audit result; Google indexing is separate', $before, (array) $record);
                    }
                    $checkedKeys = array_map(fn (int $id): string => 'seo:'.$locale.':'.$id, $data['checkedIds']);
                    foreach (DB::table('operating_records')->where('type', 'seo_issues')->where('source', 'findo')->whereIn('external_id', $checkedKeys)->whereNotIn('external_id', $errorKeys)->whereIn('status', ['open', 'in_progress'])->lockForUpdate()->get() as $resolved) {
                        $recordData = json_decode($resolved->data, true);
                        $recordData['recheck_evidence'] = 'Local technical recheck '.now()->toIso8601String().': no current technical errors. Manual verification and Google indexing remain separate.';
                        $recordData['checked_at'] = today()->toDateString();
                        DB::table('operating_records')->where('id', $resolved->id)->update(['status' => 'fixed', 'data' => json_encode($recordData, JSON_UNESCAPED_UNICODE), 'version' => $resolved->version + 1, 'updated_at' => now()]);
                        $workflow->event(DB::table('operating_records')->find($resolved->id), 'seo_recheck', 'Local technical errors cleared; waiting for documented manual verification', (array) $resolved, (array) DB::table('operating_records')->find($resolved->id));
                    }
                });
            }
        }
        $this->line(json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
