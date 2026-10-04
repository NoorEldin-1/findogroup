<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class JiraSync
{
    public function __construct(private OperatingWorkflow $workflow) {}

    public function configure(array $settings, ?string $token): void
    {
        $host = parse_url($settings['base_url'], PHP_URL_HOST);
        abort_unless(is_string($host) && preg_match('/^[a-z0-9-]+\.atlassian\.net$/i', $host) && parse_url($settings['base_url'], PHP_URL_SCHEME) === 'https', 422);
        $old = DB::table('operating_integrations')->where('key', 'jira')->first();
        DB::table('operating_integrations')->updateOrInsert(['key' => 'jira'], ['settings' => json_encode(['base_url' => 'https://'.$host, 'email' => $settings['email'], 'jql' => $settings['jql']]), 'secret' => $token ? Crypt::encryptString($token) : $old?->secret, 'status' => 'configured', 'updated_at' => now(), 'created_at' => $old?->created_at ?? now()]);
        $this->workflow->event(null, 'integration_config', 'Jira configuration saved; token omitted from audit', null, ['integration' => 'jira']);
    }

    public function import(array $issues, ?int $retryId = null): object
    {
        $runId = $retryId ?? DB::table('operating_sync_runs')->insertGetId(['integration' => 'jira-import', 'status' => 'running', 'payload' => json_encode(['issues' => $issues], JSON_UNESCAPED_UNICODE), 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        if ($retryId) {
            DB::table('operating_sync_runs')->where('id', $runId)->update(['status' => 'running', 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        }
        try {
            if (count($issues) > 5000 || ! array_is_list($issues)) {
                throw new \InvalidArgumentException('Expected an issues list with at most 5000 records.');
            }
            $count = DB::transaction(function () use ($issues): int {
                $count = 0;
                foreach ($issues as $issue) {
                    if (! is_array($issue) || empty($issue['id']) || empty($issue['key']) || empty($issue['fields']['summary']) || ! preg_match('/^[A-Z][A-Z0-9_]*-\d+$/i', $issue['key'])) {
                        throw new \InvalidArgumentException('A Jira issue needs id, key and fields.summary.');
                    }
                    $fields = $issue['fields'];
                    $status = $fields['status']['name'] ?? 'Unknown';
                    $old = DB::table('operating_records')->where('source', 'jira')->where('external_id', (string) $issue['id'])->lockForUpdate()->first();
                    $employee = ! empty($fields['assignee']['accountId']) ? DB::table('operating_records')->where('type', 'employees')->where('data->jira_account_id', $fields['assignee']['accountId'])->value('id') : null;
                    $data = ['jira_key' => $issue['key'], 'source_status' => $status, 'assignee_name' => $fields['assignee']['displayName'] ?? null, 'assignee_id' => $employee, 'original_due' => $old ? (json_decode($old->data, true)['original_due'] ?? null) : ($fields['duedate'] ?? null), 'approved_due' => $fields['duedate'] ?? null, 'source_updated_at' => $fields['updated'] ?? null, 'jira_project' => $fields['project']['key'] ?? null, 'implementation_url' => $fields['customfield_pr_url'] ?? null];
                    $row = ['type' => 'tasks', 'title' => mb_substr($fields['summary'], 0, 500), 'status' => 'backlog', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'source' => 'jira', 'external_id' => (string) $issue['id'], 'synced_at' => now(), 'updated_at' => now()];
                    if ($old) {
                        $row['version'] = $old->version + 1;
                        DB::table('operating_records')->where('id', $old->id)->update($row);
                        $id = $old->id;
                    } else {
                        $id = DB::table('operating_records')->insertGetId($row + ['uuid' => Str::uuid()->toString(), 'version' => 1, 'created_by' => auth()->id(), 'created_at' => now()]);
                    }
                    $record = DB::table('operating_records')->find($id);
                    $this->workflow->event($record, 'sync', 'Read-only Jira issue '.$issue['key'], $old ? (array) $old : null, (array) $record);
                    $count++;
                }

                return $count;
            });
            DB::table('operating_sync_runs')->where('id', $runId)->update(['status' => 'succeeded', 'records' => $count, 'error' => null, 'updated_at' => now()]);
        } catch (Throwable $error) {
            DB::table('operating_sync_runs')->where('id', $runId)->update(['status' => 'failed', 'error' => $error instanceof \InvalidArgumentException ? $error->getMessage() : 'Import failed; no partial records were committed.', 'updated_at' => now()]);
        }

        return DB::table('operating_sync_runs')->find($runId);
    }

    public function sync(): object
    {
        $connection = DB::table('operating_integrations')->where('key', 'jira')->first();
        $runId = DB::table('operating_sync_runs')->insertGetId(['integration' => 'jira-api', 'status' => 'running', 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        try {
            if (! $connection?->secret) {
                throw new \RuntimeException('not configured');
            }
            $settings = json_decode($connection->settings, true);
            $issues = [];
            $next = null;
            for ($page = 0; $page < 50; $page++) {
                $response = Http::acceptJson()->withBasicAuth($settings['email'], Crypt::decryptString($connection->secret))->withOptions(['allow_redirects' => false])->timeout(20)->retry(2, 300)->get($settings['base_url'].'/rest/api/3/search/jql', array_filter(['jql' => $settings['jql'], 'maxResults' => 100, 'fields' => 'summary,status,assignee,duedate,updated,project', 'nextPageToken' => $next]));
                $response->throw();
                $payload = $response->json();
                if (! is_array($payload['issues'] ?? null)) {
                    throw new \RuntimeException('unexpected response');
                }
                $issues = array_merge($issues, $payload['issues']);
                $next = $payload['nextPageToken'] ?? null;
                if (! $next) {
                    break;
                }
            }
            if ($next) {
                throw new \RuntimeException('search exceeded batch limit');
            }
            $import = $this->import($issues);
            if ($import->status !== 'succeeded') {
                throw new \RuntimeException('import failed');
            }
            DB::table('operating_sync_runs')->where('id', $runId)->update(['status' => 'succeeded', 'records' => $import->records, 'updated_at' => now()]);
            DB::table('operating_integrations')->where('key', 'jira')->update(['status' => 'connected', 'last_success_at' => now(), 'last_error' => null, 'updated_at' => now()]);
        } catch (Throwable $error) {
            $message = $connection?->secret ? 'Jira sync failed. Check permissions, JQL, network or batch size. No credentials are included in this log.' : 'Jira credentials are not configured.';
            DB::table('operating_sync_runs')->where('id', $runId)->update(['status' => 'failed', 'error' => $message, 'updated_at' => now()]);
            DB::table('operating_integrations')->where('key', 'jira')->update(['status' => 'error', 'last_error' => $message, 'updated_at' => now()]);
        }

        return DB::table('operating_sync_runs')->find($runId);
    }
}
