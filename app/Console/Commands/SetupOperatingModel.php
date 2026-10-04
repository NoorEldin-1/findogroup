<?php

namespace App\Console\Commands;

use App\Services\OperatingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetupOperatingModel extends Command
{
    protected $signature = 'operating:setup';

    protected $description = 'Create proposed reference profiles and policies from the approved operating-model reference without creating user accounts';

    public function handle(OperatingWorkflow $workflow): int
    {
        DB::transaction(function () use ($workflow): void {
            $profiles = [['محمد عبد الفتاح', 'CEO', 'management', null], ['ميرنا', 'Delivery manager', 'delivery', 'محمد عبد الفتاح'], ['محمد فهمي', 'Flutter developer', 'delivery', 'ميرنا'], ['تيسير', 'Flutter developer', 'delivery', 'ميرنا'], ['محمد النبوي', 'Full Stack PHP Laravel', 'delivery', 'ميرنا'], ['نور الدين', 'Full Stack PHP Laravel', 'delivery', 'ميرنا'], ['خالد', 'WordPress / PHP Laravel', 'delivery', 'ميرنا'], ['زياد', 'QA / Tester', 'delivery', 'ميرنا'], ['هند', 'Content / social media', 'marketing', 'محمد عبد الفتاح'], ['حسام', 'Media buyer', 'marketing', 'محمد عبد الفتاح'], ['آية', 'Graphic designer', 'marketing', 'محمد عبد الفتاح'], ['خلود', 'Video editor', 'marketing', 'محمد عبد الفتاح'], ['مرام', 'Sales / Data entry', 'sales', 'محمد عبد الفتاح']];
            foreach ($profiles as [$name, $role, $department, $manager]) {
                if (DB::table('operating_records')->where('type', 'employees')->where('title', $name)->exists()) {
                    continue;
                }
                $managerId = $manager ? DB::table('operating_records')->where('type', 'employees')->where('title', $manager)->value('id') : null;
                $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'employees', 'title' => $name, 'status' => 'proposed', 'data' => json_encode(['job_title' => $role, 'department' => $department, 'manager_id' => $managerId, 'reference_only' => true], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
                if ($managerId) {
                    DB::table('operating_links')->insert(['record_id' => $id, 'field' => 'manager_id', 'target_id' => $managerId]);
                }
                $workflow->event(DB::table('operating_records')->find($id), 'reference_seed', 'PDF reference profile; proposed, no account provisioned', null, ['name' => $name]);
            }
            $policies = [['بوابات الجودة / Quality gates', '19–23', 'Ready → In Progress → Code Review → QA → Ready to Release → Done. Independent review and evidence for the correct build.'], ['حفظ الأصل / Preserve baselines', '15،18،28', 'Scope versions, original deadline and immutable audit. Deadline revisions need approved changes.'], ['المعايرة / Calibration', '2،28،43–46،55', 'Initial thresholds and scorecard weights are proposed. Approve before the period; calibrate after 30 days.'], ['أول أسبوع / First week', '53–54،59', 'Confirm reporting lines and coverage. Inventory projects. Pilot one project and one content cycle. Review after one week.'], ['ملكية الحقول / Field ownership', '51–52', 'Jira owns software-task state, assignee and due date. Findo owns scope, approvals and financial records. Read-only sync first.'], ['خصوصية التقييم / Evaluation privacy', '43–46،48', 'Self review → direct manager review → management calibration. Evidence and appeal, no automatic sanctions.'], ['خطة 90 يوم / 90-day rollout', '53،55', '1–7: roles and pilot. 8–30: delivery/content baseline. 31–60: integration and gates. 61–90: gradual adoption, reporting, restore test.']];
            foreach ($policies as [$title, $pages, $rule]) {
                if (DB::table('operating_records')->where('type', 'policies')->where('title', $title)->exists()) {
                    continue;
                }
                $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'policies', 'title' => $title, 'status' => 'proposed', 'data' => json_encode(['source_pages' => $pages, 'rule' => $rule, 'parameters' => json_encode(config('operating.thresholds'))], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
                $workflow->event(DB::table('operating_records')->find($id), 'reference_seed', 'Proposed operating policy from PDF, pending management adoption', null, ['title' => $title]);
            }
        });
        $this->info('Reference profiles and proposed policies ready. No employee accounts or actual company results were invented.');

        return self::SUCCESS;
    }
}
