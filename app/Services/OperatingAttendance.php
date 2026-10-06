<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperatingAttendance
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow, private OperatingNotifier $notifier) {}

    /**
     * @return array{work_start: string, work_end: string, work_days: list<int>, grace_minutes: int, bonus_min: float, bonus_max: float, bonus_eligibility_percent: float, absence_alert_days: int, currency: string}
     */
    public function rules(): array
    {
        $saved = DB::table('operating_events')->where('action', 'configure_attendance')->where('after->effective_date', '<=', today()->toDateString())->latest('id')->value('after');
        $rules = array_replace(config('operating.attendance'), $saved ? json_decode($saved, true) : []);
        $rules['work_days'] = array_map('intval', $rules['work_days']);
        $rules['grace_minutes'] = (int) $rules['grace_minutes'];
        $rules['absence_alert_days'] = (int) $rules['absence_alert_days'];

        return $rules;
    }

    public function isWorkday(CarbonImmutable $date): bool
    {
        return in_array($date->dayOfWeek, $this->rules()['work_days'], true)
            && ! DB::table('operating_records')->where('type', 'holidays')->where('status', 'active')->where('data->holiday_date', $date->toDateString())->exists();
    }

    /**
     * @return array{start: string, end: string}
     */
    public function schedule(int $employeeId): array
    {
        $data = $this->catalog->data(DB::table('operating_records')->find($employeeId));
        $rules = $this->rules();

        return ['start' => ($data['shift_start'] ?? null) ?: $rules['work_start'], 'end' => ($data['shift_end'] ?? null) ?: $rules['work_end']];
    }

    /**
     * Derive the point and minute counters stored beside the editable fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function derive(array $data, string $status): array
    {
        $data['assignee_id'] = (int) $data['assignee_id'];
        $date = CarbonImmutable::parse($data['work_date']);
        $schedule = $this->schedule($data['assignee_id']);
        $start = $date->setTimeFromTimeString($schedule['start']);
        $end = $date->setTimeFromTimeString($schedule['end']);
        $checkIn = empty($data['check_in']) ? null : CarbonImmutable::parse($data['check_in']);
        $checkOut = empty($data['check_out']) ? null : CarbonImmutable::parse($data['check_out']);
        $workday = $this->isWorkday($date);
        $late = $workday && $checkIn && $checkIn->greaterThan($start->addMinutes($this->rules()['grace_minutes']));
        $data['point'] = match (true) {
            $status === 'excused', ! $workday => null,
            $checkIn === null => 'red',
            $late => 'black',
            default => 'green',
        };
        $data['late_minutes'] = $late ? (int) $start->diffInMinutes($checkIn) : 0;
        $data['worked_minutes'] = $checkIn && $checkOut ? max(0, (int) $checkIn->diffInMinutes($checkOut)) : 0;
        $data['early_leave_minutes'] = $workday && $checkOut && $checkOut->lessThan($end) ? (int) $checkOut->diffInMinutes($end) : 0;
        $data['overtime_minutes'] = ! $checkOut ? 0 : ($workday ? max(0, (int) $end->diffInMinutes($checkOut)) : $data['worked_minutes']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function initialStatus(array $data): string
    {
        return match (true) {
            empty($data['check_in']) => 'absent',
            empty($data['check_out']) => 'open',
            default => 'closed',
        };
    }

    public function bonus(float $commitment): float
    {
        $rules = $this->rules();
        if ($commitment < $rules['bonus_eligibility_percent']) {
            return 0.0;
        }
        if ($rules['bonus_eligibility_percent'] >= 100) {
            return (float) $rules['bonus_max'];
        }

        return round($rules['bonus_min'] + ($rules['bonus_max'] - $rules['bonus_min']) * ($commitment - $rules['bonus_eligibility_percent']) / (100 - $rules['bonus_eligibility_percent']), 2);
    }

    /**
     * Single entry point for web buttons today and attendance devices later.
     */
    public function punch(int $employeeId, CarbonImmutable $at, string $source, string $mode, string $direction, ?int $userId = null): object
    {
        $record = DB::transaction(function () use ($employeeId, $at, $source, $mode, $direction, $userId): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $date = $at->toDateString();
            $stamp = $at->format('Y-m-d\TH:i');
            $existing = DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $date)->where('status', '!=', 'archived')->lockForUpdate()->first();
            if ($direction === 'in') {
                if ($existing) {
                    $this->workflow->fail('تم تسجيل حضور هذا اليوم بالفعل.', 'Check-in for today is already recorded.');
                }
                $data = $this->derive(['assignee_id' => $employeeId, 'work_date' => $date, 'check_in' => $stamp, 'check_out' => null, 'mode' => $mode, 'source' => $source, 'note' => null], 'open');

                return $this->insertRecord($employeeId, $data, 'open', $userId, 'check_in', 'Attendance check-in ('.$source.')');
            }
            if (! $existing || $existing->status !== 'open') {
                $this->workflow->fail('لا يوجد حضور مفتوح اليوم لتسجيل الانصراف.', 'There is no open check-in today to check out.');
            }
            $data = $this->derive(array_replace($this->catalog->data($existing), ['check_out' => $stamp]), 'closed');
            DB::table('operating_records')->where('id', $existing->id)->update(['status' => 'closed', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $existing->version + 1, 'updated_at' => now()]);
            $record = DB::table('operating_records')->find($existing->id);
            $this->workflow->event($record, 'check_out', 'Attendance check-out ('.$source.')', (array) $existing, (array) $record, $userId);

            return $record;
        });
        $data = $this->catalog->data($record);
        if ($direction === 'in' && $data['point'] === 'black') {
            $this->notifier->send([$this->notifier->employeeUser($employeeId)], new OperatingAlert(
                'late_check_in',
                ['ar' => 'تم تسجيل حضور متأخر', 'en' => 'Late check-in recorded'],
                ['ar' => 'تأخير '.$data['late_minutes'].' دقيقة يوم '.$data['work_date'].' (نقطة سوداء).', 'en' => $data['late_minutes'].' minutes late on '.$data['work_date'].' (black point).'],
                '/admin/operations/record/'.$record->id,
            ));
        }

        return $record;
    }

    /**
     * @return array{absent: list<int>, excused: list<int>}
     */
    public function closeDay(CarbonImmutable $date): array
    {
        $result = ['absent' => [], 'excused' => []];
        if (! $this->isWorkday($date)) {
            return $result;
        }
        $day = $date->toDateString();
        foreach (DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->orderBy('id')->get() as $employee) {
            $data = $this->catalog->data($employee);
            if (empty($data['user_id']) || (! empty($data['effective_date']) && $data['effective_date'] > $day)) {
                continue;
            }
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employee->id)->where('data->work_date', $day)->where('status', '!=', 'archived')->exists()) {
                continue;
            }
            $onLeave = DB::table('operating_records')->where('type', 'leave')->where('status', 'approved')->where('data->assignee_id', $employee->id)->where('data->start_date', '<=', $day)->where('data->end_date', '>=', $day)->exists();
            $status = $onLeave ? 'excused' : 'absent';
            DB::transaction(fn (): object => $this->insertRecord($employee->id, $this->derive(['assignee_id' => $employee->id, 'work_date' => $day, 'check_in' => null, 'check_out' => null, 'mode' => 'office', 'source' => 'system', 'note' => null], $status), $status, null, 'close_day', $onLeave ? 'Approved leave covers this workday' : 'No check-in recorded by the end of the workday'));
            $result[$status][] = $employee->id;
        }
        $this->alertMissing($result['absent'], $date);

        return $result;
    }

    /**
     * Count consecutive absent workdays ending on the given date.
     */
    public function missingStreak(int $employeeId, CarbonImmutable $date): int
    {
        $streak = 0;
        $day = $date;
        for ($checked = 0; $checked < 62; $checked++, $day = $day->subDay()) {
            if (! $this->isWorkday($day)) {
                continue;
            }
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $day->toDateString())->where('status', '!=', 'archived')->value('status') !== 'absent') {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    /**
     * @return list<array{employee_id: int, name: string, green: int, black: int, red: int, excused: int, late_minutes: int, early_leave_minutes: int, overtime_minutes: int, worked_hours: float, remote_days: int, commitment: float, bonus: float, eligible: bool}>
     */
    public function monthSummary(?User $viewer, int $year, int $month): array
    {
        $first = CarbonImmutable::create($year, $month, 1);
        $query = $viewer ? $this->access->query($viewer, 'attendance') : DB::table('operating_records')->where('type', 'attendance');
        $records = $query->where('status', '!=', 'archived')->where('data->work_date', '>=', $first->toDateString())->where('data->work_date', '<=', $first->endOfMonth()->toDateString())->get();
        $rows = [];
        foreach ($records->groupBy(fn (object $record): int => (int) $this->catalog->data($record)['assignee_id']) as $employeeId => $items) {
            $days = $items->map(fn (object $record): array => $this->catalog->data($record) + ['status' => $record->status]);
            $green = $days->where('point', 'green')->count();
            $black = $days->where('point', 'black')->count();
            $red = $days->where('point', 'red')->count();
            $counted = $green + $black + $red;
            $commitment = $counted ? round($green / $counted * 100, 2) : 0.0;
            $bonus = $this->bonus($commitment);
            $rows[] = [
                'employee_id' => (int) $employeeId,
                'name' => (string) DB::table('operating_records')->where('id', $employeeId)->value('title'),
                'green' => $green,
                'black' => $black,
                'red' => $red,
                'excused' => $days->where('status', 'excused')->count(),
                'late_minutes' => (int) $days->sum('late_minutes'),
                'early_leave_minutes' => (int) $days->sum('early_leave_minutes'),
                'overtime_minutes' => (int) $days->sum('overtime_minutes'),
                'worked_hours' => round($days->sum('worked_minutes') / 60, 2),
                'remote_days' => $days->filter(fn (array $day): bool => ! empty($day['check_in']) && ($day['mode'] ?? null) === 'remote')->count(),
                'commitment' => (float) $commitment,
                'bonus' => $bonus,
                'eligible' => $bonus > 0,
            ];
        }
        usort($rows, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return array{rows: list<array{employee: object, record: ?object, data: array<string, mixed>, streak: int}>, counts: array{present: int, late: int, absent: int, excused: int, pending: int, remote: int}}
     */
    public function board(User $viewer, CarbonImmutable $date): array
    {
        $records = $this->access->query($viewer, 'attendance')->where('data->work_date', $date->toDateString())->where('status', '!=', 'archived')->get()->keyBy(fn (object $record): int => (int) $this->catalog->data($record)['assignee_id']);
        $managerId = $viewer->role === 'delivery' ? $this->access->employeeId($viewer) : null;
        $employees = DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->orderBy('title')->get()->filter(function (object $employee) use ($viewer, $managerId): bool {
            $data = $this->catalog->data($employee);

            return ! empty($data['user_id']) && ($viewer->role !== 'delivery' || ($data['manager_id'] ?? null) == $managerId);
        });
        $rows = [];
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'pending' => 0, 'remote' => 0];
        foreach ($employees as $employee) {
            $record = $records->get($employee->id);
            $data = $record ? $this->catalog->data($record) : [];
            match (true) {
                ! $record => $counts['pending']++,
                $record->status === 'excused' => $counts['excused']++,
                $record->status === 'absent' => $counts['absent']++,
                default => $counts['present']++,
            };
            if (($data['point'] ?? null) === 'black') {
                $counts['late']++;
            }
            if (! empty($data['check_in']) && ($data['mode'] ?? null) === 'remote') {
                $counts['remote']++;
            }
            $rows[] = ['employee' => $employee, 'record' => $record, 'data' => $data, 'streak' => $this->missingStreak($employee->id, $date)];
        }

        return ['rows' => $rows, 'counts' => $counts];
    }

    /**
     * @return array{record: ?object, data: array<string, mixed>, workday: bool, missing_streak: int, alert_days: int}|null
     */
    public function today(User $user): ?array
    {
        $employeeId = $this->access->employeeId($user);
        if (! $employeeId) {
            return null;
        }
        $today = CarbonImmutable::today();
        $record = DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $today->toDateString())->where('status', '!=', 'archived')->first();
        $streakEnd = $today;
        if ($record?->status !== 'absent') {
            $streakEnd = $today->subDay();
            for ($guard = 0; $guard < 14 && ! $this->isWorkday($streakEnd); $guard++) {
                $streakEnd = $streakEnd->subDay();
            }
        }

        return ['record' => $record, 'data' => $record ? $this->catalog->data($record) : [], 'workday' => $this->isWorkday($today), 'missing_streak' => $this->missingStreak($employeeId, $streakEnd), 'alert_days' => $this->rules()['absence_alert_days']];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertRecord(int $employeeId, array $data, string $status, ?int $userId, string $action, string $reason): object
    {
        $employee = DB::table('operating_records')->find($employeeId);
        $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'attendance', 'title' => $employee->title.' · '.$data['work_date'], 'status' => $status, 'owner_id' => $userId, 'created_by' => $userId, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operating_links')->insert(['record_id' => $id, 'target_id' => $employeeId, 'field' => 'assignee_id']);
        $record = DB::table('operating_records')->find($id);
        $this->workflow->event($record, $action, $reason, null, (array) $record, $userId);

        return $record;
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function alertMissing(array $employeeIds, CarbonImmutable $date): void
    {
        if (! $employeeIds) {
            return;
        }
        $day = $date->toDateString();
        $names = DB::table('operating_records')->whereIn('id', $employeeIds)->orderBy('title')->pluck('title')->implode('، ');
        $boardUrl = '/admin/operations/attendance/board?date='.$day;
        $this->notifier->send($this->notifier->roles(['owner', 'hr']), new OperatingAlert(
            'missing_check_in_digest',
            ['ar' => 'موظفون بلا تسجيل حضور', 'en' => 'Employees without check-in'],
            ['ar' => $day.': '.$names, 'en' => $day.': '.$names],
            $boardUrl,
        ));
        $alertDays = $this->rules()['absence_alert_days'];
        foreach ($employeeIds as $employeeId) {
            $name = DB::table('operating_records')->where('id', $employeeId)->value('title');
            $this->notifier->send([$this->notifier->managerUser($employeeId)], new OperatingAlert(
                'missing_check_in',
                ['ar' => 'عدم تسجيل حضور', 'en' => 'No check-in recorded'],
                ['ar' => $name.' لم يسجّل حضورًا يوم '.$day.' (نقطة حمراء).', 'en' => $name.' did not check in on '.$day.' (red point).'],
                $boardUrl,
            ));
            if ($this->missingStreak($employeeId, $date) === $alertDays) {
                $this->notifier->send([...$this->notifier->roles(['owner', 'hr']), $this->notifier->managerUser($employeeId), $this->notifier->employeeUser($employeeId)], new OperatingAlert(
                    'absence_streak',
                    ['ar' => $alertDays.' أيام عمل متتالية بدون تسجيل حضور', 'en' => $alertDays.' consecutive workdays without check-in'],
                    ['ar' => $name.' لديه '.$alertDays.' أيام عمل متتالية بدون تسجيل حضور حتى '.$day.'. يلزم إجراء من HR أو المدير.', 'en' => $name.' has '.$alertDays.' consecutive workdays without check-in up to '.$day.'. HR or manager action is needed.'],
                    $boardUrl,
                    true,
                ));
            }
        }
    }
}
