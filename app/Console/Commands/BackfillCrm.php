<?php

namespace App\Console\Commands;

use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use App\Services\OperatingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCrm extends Command
{
    protected $signature = 'operating:crm-backfill';

    protected $description = 'Fill phone keys on leads saved before duplicate detection existed';

    public function handle(OperatingCatalog $catalog, OperatingCrm $crm, OperatingWorkflow $workflow): int
    {
        $filled = 0;
        foreach (DB::table('operating_records')->where('type', 'opportunities')->orderBy('id')->cursor() as $lead) {
            $data = $catalog->data($lead);
            $key = $crm->phoneKey($data['lead_phone'] ?? null);
            if ($key && ($data['phone_key'] ?? null) !== $key) {
                $workflow->mark($lead, 'phone_key', $key, 'Phone key backfilled');
                $filled++;
            }
        }
        $this->info($filled.' leads updated.');

        return self::SUCCESS;
    }
}
