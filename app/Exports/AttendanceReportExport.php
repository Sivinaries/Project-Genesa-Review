<?php

namespace App\Exports;

use App\Models\GpsAttendanceLog;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\DB;

class AttendanceReportExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $companyId;
    protected $start;
    protected $end;

    public function __construct($companyId, $start, $end)
    {
        $this->companyId = $companyId;
        $this->start = $start;
        $this->end = $end;
    }

    public function view(): View
    {
        $attendanceData = GpsAttendanceLog::with(['employee.branch', 'employee.position'])
            ->where('gps_attendance_logs.compani_id', $this->companyId)
            ->whereBetween('attendance_date', [$this->start, $this->end])
            ->join('employees', 'gps_attendance_logs.employee_id', '=', 'employees.id')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->leftJoin('attendances', function ($join) {
                $join->on('gps_attendance_logs.employee_id', '=', 'attendances.employee_id')
                    ->whereRaw('attendances.period_start <= ? AND attendances.period_end >= ?', [$this->end, $this->start]);
            })
            ->select(
                'gps_attendance_logs.employee_id',
                'employees.name as employee_name',
                'employees.branch_id',
                DB::raw("SUM(CASE WHEN gps_attendance_logs.status = 'present' AND gps_attendance_logs.check_out_time IS NOT NULL THEN 1 ELSE 0 END) as total_present"),
                DB::raw("SUM(CASE WHEN gps_attendance_logs.status = 'late' AND gps_attendance_logs.check_out_time IS NOT NULL THEN 1 ELSE 0 END) as total_late"),
                DB::raw("SUM(CASE WHEN gps_attendance_logs.status = 'early_leave' THEN 1 ELSE 0 END) as total_early_leave"),
                DB::raw("SUM(CASE WHEN gps_attendance_logs.check_out_time IS NULL THEN 1 ELSE 0 END) as total_incomplete"),
                DB::raw("COUNT(*) as total_days"),
                DB::raw("MAX(attendances.note) as note")
            )
            ->groupBy('gps_attendance_logs.employee_id', 'employees.name', 'employees.branch_id')
            ->orderBy('branches.name')
            ->orderBy('employees.name')
            ->get();

        foreach ($attendanceData as $data) {
            $data->employee = \App\Models\Employee::with(['branch', 'position'])->find($data->employee_id);
        }

        $groupedByBranch = $attendanceData->groupBy(function ($data) {
            return $data->branch_id;
        });

        $branches = [];

        foreach ($groupedByBranch as $branchId => $dataInBranch) {
            $firstData = $dataInBranch->first();
            $branchName = $firstData->employee->branch->name ?? 'Tanpa Cabang';

            $branches[] = [
                'branch_name' => $branchName,
                'attendances' => $dataInBranch,
            ];
        }

        return view('exports.attendanceReport', [
            'branches' => $branches,
            'start' => $this->start,
            'end' => $this->end,
            'companyName' => auth()->user()->compani->name ?? 'Company Name'
        ]);
    }

    public function title(): string
    {
        return 'Attendance Report';
    }
}