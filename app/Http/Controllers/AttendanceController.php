<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingAttendance;
use App\Services\OperatingCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(private OperatingAttendance $attendance, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function checkIn(Request $request): Response
    {
        $input = $request->validate(['mode' => 'required|in:office,remote']);
        $employeeId = $this->access->employeeId($request->user());
        abort_unless($employeeId, 403);
        $this->attendance->punch($employeeId, CarbonImmutable::now(), 'web', $input['mode'], 'in', $request->user()->id);

        return back()->with('success', __('تم تسجيل الحضور.'));
    }

    public function checkOut(Request $request): Response
    {
        $employeeId = $this->access->employeeId($request->user());
        abort_unless($employeeId, 403);
        $this->attendance->punch($employeeId, CarbonImmutable::now(), 'web', 'office', 'out', $request->user()->id);

        return back()->with('success', __('تم تسجيل الانصراف.'));
    }

    public function board(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['owner', 'hr', 'delivery'], true), 403);
        $input = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = CarbonImmutable::parse($input['date'] ?? today()->toDateString());

        return $this->view('attendance-board', $this->attendance->board($request->user(), $date) + ['date' => $date, 'workday' => $this->attendance->isWorkday($date), 'alertDays' => $this->attendance->rules()['absence_alert_days']]);
    }

    public function monthly(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['owner', 'hr', 'delivery'], true), 403);
        $input = $request->validate(['month' => 'nullable|date_format:Y-m', 'format' => 'nullable|in:csv']);
        $month = CarbonImmutable::createFromFormat('Y-m-d', ($input['month'] ?? today()->format('Y-m')).'-01');
        $rows = $this->attendance->monthSummary($request->user(), $month->year, $month->month);
        if (($input['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Employee', 'Green', 'Black', 'Red', 'Excused', 'Late minutes', 'Early leave minutes', 'Overtime minutes', 'Worked hours', 'Remote days', 'Commitment %', 'Bonus', 'Eligible']);
                foreach ($rows as $row) {
                    fputcsv($out, [preg_match('/^[=+\-@\t\r]/', $row['name']) ? "'".$row['name'] : $row['name'], $row['green'], $row['black'], $row['red'], $row['excused'], $row['late_minutes'], $row['early_leave_minutes'], $row['overtime_minutes'], $row['worked_hours'], $row['remote_days'], $row['commitment'], $row['bonus'], $row['eligible'] ? 'yes' : 'no']);
                }
                fclose($out);
            }, 'attendance-'.$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return $this->view('attendance-monthly', ['rows' => $rows, 'month' => $month, 'rules' => $this->attendance->rules()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function view(string $page, array $data): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }
}
