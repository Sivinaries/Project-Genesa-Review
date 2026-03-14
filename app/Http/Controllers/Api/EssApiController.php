<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\Outlet;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Schedule;
use App\Models\Note;
use App\Models\Shift;
use App\Models\GpsAttendanceLog;
use App\Services\LeaveQuotaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EssApiController extends Controller
{
    use ApiResponse;

    public function __construct(private LeaveQuotaService $quotaService) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $employee = Employee::where('email', $request->email)->first();

        if (! $employee || ! Hash::check($request->password, $employee->password)) {
            return $this->error('Email atau password salah.', null, 401);
        }

        $employee->tokens()->delete();

        $token = $employee->createToken('ess-mobile')->plainTextToken;

        return $this->success([
            'token'    => $token,
            'employee' => [
                'id'           => $employee->id,
                'name'         => $employee->name,
                'email'        => $employee->email,
                'phone'        => $employee->phone,
                'position'     => $employee->position?->name,
                'is_head'      => (bool) $employee->position?->is_head,
                'branch'       => $employee->branch?->name,
                'outlet'       => $employee->outlet?->name,
                'join_date'    => $employee->join_date,
                'status'       => $employee->status,
                'payroll_method' => $employee->payroll_method,
            ],
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    public function home(Request $request): JsonResponse
    {
        $employee = $request->user();
        $compani  = $employee->compani;

        $announcements = $compani->announcements->map(fn($a) => [
            'id'      => $a->id,
            'title'   => $a->title,
            'content' => $a->content,
            'created_at' => $a->created_at,
        ]);

        $attendance = Attendance::where('employee_id', $employee->id)
            ->latest('period_start')
            ->first();

        return $this->success([
            'employee' => [
                'id'       => $employee->id,
                'name'     => $employee->name,
                'position' => $employee->position?->name,
                'branch'   => $employee->branch?->name,
                'outlet'   => $employee->outlet?->name,
            ],
            'company' => [
                'name'     => $compani->name,
                'location' => $compani->location,
            ],
            'announcements'     => $announcements,
            'latest_attendance' => $attendance ? [
                'period_start'     => $attendance->period_start,
                'period_end'       => $attendance->period_end,
                'total_present'    => $attendance->total_present,
                'total_late'       => $attendance->total_late,
                'total_sick'       => $attendance->total_sick,
                'total_permission' => $attendance->total_permission,
                'total_alpha'      => $attendance->total_alpha,
                'total_leave'      => $attendance->total_leave,
            ] : null,
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $employee = $request->user();

        $schedules = $employee->schedules()
            ->with('shift')
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date', 'asc')
            ->get();

        $totalMinutes = $schedules->reduce(function ($carry, $item) {
            if ($item->shift) {
                $start = Carbon::parse($item->shift->start_time);
                $end   = Carbon::parse($item->shift->end_time);
                if ($item->shift->is_cross_day) {
                    $end->addDay();
                }
                return $carry + $start->diffInMinutes($end);
            }
            return $carry;
        }, 0);

        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        $nextItem = $schedules->first();
        $nextShift = null;

        if ($nextItem) {
            $nextDate = Carbon::parse($nextItem->date);
            $dayStr   = $nextDate->isToday() ? 'Today'
                : ($nextDate->isTomorrow() ? 'Tomorrow' : $nextDate->format('d M'));
            $timeStr  = $nextItem->shift
                ? Carbon::parse($nextItem->shift->start_time)->format('H:i')
                : '(Off)';

            $nextShift = ['label' => "$dayStr, $timeStr", 'date' => $nextItem->date];
        }

        $scheduleList = $schedules->map(fn($s) => [
            'id'         => $s->id,
            'date'       => $s->date,
            'is_today'   => $s->is_today,
            'is_past'    => $s->is_past,
            'shift'      => $s->shift ? [
                'id'           => $s->shift->id,
                'name'         => $s->shift->name,
                'start_time'   => $s->shift->start_time,
                'end_time'     => $s->shift->end_time,
                'is_cross_day' => $s->shift->is_cross_day,
                'color'        => $s->shift->color,
            ] : null,
        ]);

        return $this->success([
            'schedules'     => $scheduleList,
            'total_minutes' => $totalMinutes,
            'total_hours'   => $hours,
            'total_minutes_remainder' => $minutes,
            'next_shift'    => $nextShift,
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $attendances = $request->user()
            ->attendances()
            ->latest('period_start')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'period_start'     => $a->period_start,
                'period_end'       => $a->period_end,
                'total_present'    => $a->total_present,
                'total_late'       => $a->total_late,
                'total_sick'       => $a->total_sick,
                'total_permission' => $a->total_permission,
                'total_alpha'      => $a->total_alpha,
                'total_leave'      => $a->total_leave,
                'note'             => $a->note,
                'source'           => $a->source,
            ]);

        return $this->success($attendances);
    }

    public function leave(Request $request): JsonResponse
    {
        $employee = $request->user();

        $leaves = $employee->leaves()
            ->latest()
            ->get()
            ->map(fn($l) => [
                'id'            => $l->id,
                'start_date'    => $l->start_date,
                'end_date'      => $l->end_date,
                'type'          => $l->type,
                'note'          => $l->note,
                'status'        => $l->status,
                'duration_days' => $l->duration_days,
                'created_at'    => $l->created_at,
            ]);

        $quota = LeaveQuota::getActiveQuota($employee);

        return $this->success([
            'leaves' => $leaves,
            'quota'  => $quota ? [
                'total_quota'    => $quota->total_quota,
                'used_days'      => $quota->used_days,
                'remaining_days' => $quota->remaining_days,
                'period_start'   => $quota->period_start,
                'period_end'     => $quota->period_end,
            ] : null,
        ]);
    }

    public function reqLeave(Request $request): JsonResponse
    {
        $employee    = $request->user();
        $userCompany = $employee->compani;

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|string|in:izin,sakit,cuti,meninggalkan_pekerjaan,tukar_shift,other',
            'note'       => 'required|string',
        ]);

        if ($data['type'] === 'cuti') {
            $validation = $this->quotaService->validatePersonalLeave(
                $employee,
                $data['start_date'],
                $data['end_date']
            );

            if (! $validation['allowed']) {
                return $this->error($validation['message'], null, 422);
            }
        }

        $leave = Leave::create([
            'employee_id' => $employee->id,
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'type'        => $data['type'],
            'note'        => $data['note'],
            'compani_id'  => $userCompany->id,
            'status'      => 'pending',
        ]);

        $this->logActivity('Create Leave', "Membuat leave '{$employee->name}'", $userCompany->id, $employee->id);
        Cache::forget("leaves_{$userCompany->id}");

        return $this->success([
            'id'         => $leave->id,
            'start_date' => $leave->start_date,
            'end_date'   => $leave->end_date,
            'type'       => $leave->type,
            'status'     => $leave->status,
        ], 'Pengajuan cuti berhasil dikirim.', 201);
    }

    public function overtime(Request $request): JsonResponse
    {
        $overtimes = $request->user()
            ->overtimes()
            ->latest('overtime_date')
            ->get()
            ->map(fn($o) => [
                'id'           => $o->id,
                'overtime_date' => $o->overtime_date,
                'start_time'   => $o->start_time,
                'end_time'     => $o->end_time,
                'status'       => $o->status,
                'overtime_pay' => $o->overtime_pay,
                'note'         => $o->note,
                'created_at'   => $o->created_at,
            ]);

        return $this->success($overtimes);
    }

    public function reqOvertime(Request $request): JsonResponse
    {
        $employee    = $request->user();
        $userCompany = $employee->compani;

        $data = $request->validate([
            'overtime_date' => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
        ]);

        $overtime = Overtime::create([
            'employee_id'   => $employee->id,
            'overtime_date' => $data['overtime_date'],
            'start_time'    => $data['start_time'],
            'end_time'      => $data['end_time'],
            'compani_id'    => $userCompany->id,
            'status'        => 'pending',
        ]);

        $this->logActivity('Create Overtime', "Mengajukan overtime '{$employee->name}'", $userCompany->id, $employee->id);
        Cache::forget("overtimes_{$userCompany->id}");

        return $this->success([
            'id'            => $overtime->id,
            'overtime_date' => $overtime->overtime_date,
            'start_time'    => $overtime->start_time,
            'end_time'      => $overtime->end_time,
            'status'        => $overtime->status,
        ], 'Pengajuan lembur berhasil dikirim.', 201);
    }

    public function payroll(Request $request): JsonResponse
    {
        $payrolls = $request->user()
            ->payrolls()
            ->latest('pay_period_start')
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'pay_period_start' => $p->pay_period_start,
                'pay_period_end'   => $p->pay_period_end,
                'base_salary'      => $p->base_salary,
                'total_allowances' => $p->total_allowances,
                'total_deductions' => $p->total_deductions,
                'net_salary'       => $p->net_salary,
                'status'           => $p->status,
                'payroll_method'   => $p->payroll_method,
                'payment_date'     => $p->payment_date,
            ]);

        return $this->success($payrolls);
    }

    public function payrollDetail(int $id, Request $request): JsonResponse
    {
        $employee = $request->user();

        $payroll = Payroll::with('payrollDetails')
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $details = $payroll->payrollDetails->map(fn($d) => [
            'name'     => $d->name,
            'amount'   => $d->amount,
            'category' => $d->category,
        ]);

        return $this->success([
            'id'               => $payroll->id,
            'pay_period_start' => $payroll->pay_period_start,
            'pay_period_end'   => $payroll->pay_period_end,
            'base_salary'      => $payroll->base_salary,
            'total_allowances' => $payroll->total_allowances,
            'total_deductions' => $payroll->total_deductions,
            'net_salary'       => $payroll->net_salary,
            'status'           => $payroll->status,
            'payment_date'     => $payroll->payment_date,
            'payroll_method'   => $payroll->payroll_method,
            'working_days'     => $payroll->working_days,
            'details'          => $details,
        ]);
    }

    public function note(Request $request): JsonResponse
    {
        $notes = $request->user()
            ->notes()
            ->latest()
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'content'    => $n->content,
                'created_at' => $n->created_at,
                'updated_at' => $n->updated_at,
            ]);

        return $this->success($notes);
    }

    public function profil(Request $request): JsonResponse
    {
        $employee = $request->user()->load(['compani', 'branch', 'outlet', 'position']);

        return $this->success([
            'id'                        => $employee->id,
            'name'                      => $employee->name,
            'email'                     => $employee->email,
            'phone'                     => $employee->phone,
            'address'                   => $employee->address,
            'nik'                       => $employee->nik,
            'npwp'                      => $employee->npwp,
            'join_date'                 => $employee->join_date,
            'status'                    => $employee->status,
            'payroll_method'            => $employee->payroll_method,
            'bank_name'                 => $employee->bank_name,
            'bank_account_no'           => $employee->bank_account_no,
            'bpjs_kesehatan_no'         => $employee->bpjs_kesehatan_no,
            'bpjs_ketenagakerjaan_no'   => $employee->bpjs_ketenagakerjaan_no,
            'participates_bpjs_kes'     => $employee->participates_bpjs_kes,
            'participates_bpjs_tk'      => $employee->participates_bpjs_tk,
            'participates_bpjs_jp'      => $employee->participates_bpjs_jp,
            'position' => [
                'id'      => $employee->position?->id,
                'name'    => $employee->position?->name,
                'is_head' => (bool) $employee->position?->is_head,
            ],
            'branch'  => ['id' => $employee->branch?->id,  'name' => $employee->branch?->name],
            'outlet'  => ['id' => $employee->outlet?->id,  'name' => $employee->outlet?->name],
            'company' => ['id' => $employee->compani?->id, 'name' => $employee->compani?->name],
        ]);
    }

    public function coordinatorSchedule(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $branchId    = $coordinator->branch_id;
        
        $userCompany = $coordinator->compani;

        $outletId = $request->query('outlet_id');

        $employeesQuery = Employee::where('compani_id', $userCompany->id)
            ->where('branch_id', $branchId)
            ->orderBy('name');

        if ($outletId) {
            $employeesQuery->where('outlet_id', $outletId);
        }

        $employees = $employeesQuery->get()->map(fn($e) => [
            'id'     => $e->id,
            'name'   => $e->name,
            'outlet' => $e->outlet?->name,
        ]);

        $shifts = Shift::where('compani_id', $userCompany->id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'start_time'   => $s->start_time,
                'end_time'     => $s->end_time,
                'is_cross_day' => $s->is_cross_day,
                'color'        => $s->color,
            ]);

        $schedulesQuery = Schedule::with(['employee', 'shift'])
            ->where('compani_id', $userCompany->id)
            ->whereHas('employee', fn($q) => $q->where('branch_id', $branchId));

        if ($outletId) {
            $schedulesQuery->whereHas('employee', fn($q) => $q->where('outlet_id', $outletId));
        }

        $schedules = $schedulesQuery
            ->whereBetween('date', [
                now()->startOfMonth()->subWeek(),
                now()->endOfMonth()->addWeek(),
            ])
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'date'        => $s->date,
                'employee_id' => $s->employee_id,
                'employee'    => $s->employee?->name,
                'shift_id'    => $s->shift_id,
                'shift'       => $s->shift ? [
                    'id'         => $s->shift->id,
                    'name'       => $s->shift->name,
                    'start_time' => $s->shift->start_time,
                    'end_time'   => $s->shift->end_time,
                    'color'      => $s->shift->color,
                ] : null,
            ]);

        $outlets = Outlet::where('branch_id', $branchId)
            ->get()
            ->map(fn($o) => ['id' => $o->id, 'name' => $o->name]);

        return $this->success(compact('employees', 'shifts', 'schedules', 'outlets'));
    }

    public function coordinatorStoreSchedule(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);
        $userCompany = $coordinator->compani;

        $request->validate([
            'employee_ids'   => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_id'       => 'required|exists:shifts,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
        ]);

        $shift  = Shift::findOrFail($request->shift_id);
        $period = CarbonPeriod::create($request->start_date, $request->end_date);
        $count  = 0;

        DB::beginTransaction();
        try {
            foreach ($request->employee_ids as $empId) {
                $emp = Employee::find($empId);
                if (! $emp || $emp->branch_id != $coordinator->branch_id) continue;

                foreach ($period as $date) {
                    Schedule::updateOrCreate(
                        ['compani_id' => $userCompany->id, 'employee_id' => $empId, 'date' => $date->format('Y-m-d')],
                        ['shift_id' => $shift->id]
                    );
                    $count++;
                }
            }
            DB::commit();

            $this->logActivity('Assign Schedule (Coord)', "Assign Shift {$shift->name} ke {$count} hari.", $userCompany->id, $coordinator->id);

            return $this->success(['total_assigned' => $count], "$count jadwal berhasil disimpan.", 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menyimpan jadwal.', $e->getMessage(), 500);
        }
    }

    public function coordinatorUpdateSchedule(Request $request, int $id): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);
        $userCompany = $coordinator->compani;

        $request->validate([
            'shift_id'    => 'required|exists:shifts,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        $schedule = Schedule::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->with('employee')
            ->firstOrFail();

        if ($schedule->employee->branch_id != $coordinator->branch_id) {
            return $this->error('Anda tidak memiliki akses untuk mengedit jadwal karyawan cabang lain.', null, 403);
        }

        if ($request->employee_id != $schedule->employee_id) {
            $newEmp = Employee::findOrFail($request->employee_id);
            if ($newEmp->branch_id != $coordinator->branch_id) {
                return $this->error('Karyawan pengganti harus dari cabang yang sama.', null, 422);
            }

            $exists = Schedule::where('compani_id', $userCompany->id)
                ->where('employee_id', $request->employee_id)
                ->where('date', $schedule->date)
                ->exists();

            if ($exists) {
                return $this->error('Karyawan pengganti sudah memiliki jadwal di tanggal tersebut.', null, 409);
            }
        }

        $schedule->update([
            'shift_id'    => $request->shift_id,
            'employee_id' => $request->employee_id,
        ]);

        $this->logActivity('Update Schedule (Coord)', "Ubah jadwal {$schedule->employee->name}", $userCompany->id, $coordinator->id);

        return $this->success(null, 'Jadwal berhasil diperbarui.');
    }

    public function coordinatorDestroySchedule(int $id, Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);
        $userCompany = $coordinator->compani;

        $schedule = Schedule::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->with('employee')
            ->first();

        if (! $schedule) {
            return $this->error('Jadwal tidak ditemukan.', null, 404);
        }

        if ($schedule->employee->branch_id != $coordinator->branch_id) {
            return $this->error('Akses ditolak.', null, 403);
        }

        $name = $schedule->employee->name;
        $date = $schedule->date;
        $schedule->delete();

        $this->logActivity('Delete Schedule (Coord)', "Hapus jadwal {$name} tgl {$date}", $userCompany->id, $coordinator->id);

        return $this->success(null, 'Jadwal berhasil dihapus.');
    }

    public function coordinatorLeave(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $leaves = Leave::with(['employee', 'employee.position'])
            ->whereHas('employee', fn($q) => $q->where('branch_id', $coordinator->branch_id))
            ->orderByRaw("FIELD(status, 'pending') DESC")
            ->latest('created_at')
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'employee'   => ['id' => $l->employee->id, 'name' => $l->employee->name, 'position' => $l->employee->position?->name],
                'start_date' => $l->start_date,
                'end_date'   => $l->end_date,
                'type'       => $l->type,
                'note'       => $l->note,
                'status'     => $l->status,
                'duration_days' => $l->duration_days,
                'created_at' => $l->created_at,
            ]);

        $employees = Employee::where('branch_id', $coordinator->branch_id)
            ->where('compani_id', $coordinator->compani_id)
            ->orderBy('name')
            ->get();

        $quotas = [];
        foreach ($employees as $emp) {
            $quota = LeaveQuota::getActiveQuota($emp);
            $quotas[$emp->id] = $quota ? [
                'total_quota'    => $quota->total_quota,
                'used_days'      => $quota->used_days,
                'remaining_days' => $quota->remaining_days,
            ] : null;
        }

        $employeeList = $employees->map(fn($e) => [
            'id'    => $e->id,
            'name'  => $e->name,
            'quota' => $quotas[$e->id] ?? null,
        ]);

        return $this->success(compact('leaves', 'employeeList'));
    }

    public function storeCoordinatorLeave(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'type'        => 'required|in:izin,sakit,cuti,meninggalkan_pekerjaan,tukar_shift,other',
            'note'        => 'required|string',
        ]);

        $targetEmp = Employee::findOrFail($request->employee_id);
        if ($targetEmp->branch_id != $coordinator->branch_id) {
            return $this->error('Karyawan beda cabang.', null, 403);
        }

        if ($request->type === 'cuti') {
            $validation = $this->quotaService->validatePersonalLeave($targetEmp, $request->start_date, $request->end_date);
            if (! $validation['allowed']) {
                return $this->error($validation['message'], null, 422);
            }
        }

        DB::beginTransaction();
        try {
            $overlap = Leave::where('employee_id', $request->employee_id)
                ->where('status', '!=', 'rejected')
                ->where('start_date', '<=', $request->end_date)
                ->where('end_date', '>=', $request->start_date)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                DB::rollBack();
                return $this->error('Karyawan sudah memiliki pengajuan cuti di periode tersebut.', null, 409);
            }

            $leave = Leave::create([
                'employee_id' => $request->employee_id,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'type'        => $request->type,
                'note'        => $request->note,
                'compani_id'  => $coordinator->compani_id,
                'status'      => 'pending',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menyimpan cuti.', $e->getMessage(), 500);
        }

        $this->logActivity('Create Leave Request (Coord)', "Koordinator mengajukan {$request->type} untuk {$targetEmp->name}", $coordinator->compani_id, $coordinator->id);
        Cache::forget("leaves_{$coordinator->compani_id}");

        return $this->success(['id' => $leave->id, 'status' => $leave->status], 'Permintaan cuti berhasil diajukan.', 201);
    }

    public function coordinatorUpdateLeave(Request $request, int $id): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $request->validate([
            'status'     => 'required|in:approved,rejected,pending',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|string',
            'note'       => 'nullable|string',
        ]);

        try {
            $leave = DB::transaction(function () use ($id, $request, $coordinator) {
                $leave = Leave::with('employee')->lockForUpdate()->findOrFail($id);

                if ($leave->employee->branch_id != $coordinator->branch_id) {
                    return $this->error('Akses ditolak.', null, 403);
                }

                if ($leave->status !== 'pending') {
                    return $this->error('Status cuti sudah diproses oleh Admin dan tidak dapat diubah.', null, 409);
                }

                if (in_array($request->status, ['approved', 'rejected'])) {
                    return $this->error('Anda tidak memiliki akses untuk menyetujui/menolak cuti. Silakan hubungi Admin.', null, 403);
                }

                if ($request->type === 'cuti') {
                    $validation = app(LeaveQuotaService::class)->validatePersonalLeave(
                        $leave->employee,
                        $request->start_date,
                        $request->end_date
                    );
                    if (! $validation['allowed']) {
                        throw new \Exception('QUOTA_ERROR: ' . $validation['message']);
                    }
                }

                $leave->update([
                    'status'     => 'pending',
                    'start_date' => $request->start_date,
                    'end_date'   => $request->end_date,
                    'type'       => $request->type,
                    'note'       => $request->note,
                ]);

                return $leave;
            });
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'QUOTA_ERROR:')) {
                return $this->error(str_replace('QUOTA_ERROR: ', '', $e->getMessage()), null, 422);
            }
            return $this->error('Terjadi kesalahan.', $e->getMessage(), 500);
        }

        if ($leave instanceof \Illuminate\Http\JsonResponse) {
            return $leave;
        }

        $this->logActivity('Update Leave Request', "Koordinator memperbarui status cuti {$leave->employee->name}", $coordinator->compani_id, $coordinator->id);
        Cache::forget("leaves_{$coordinator->compani_id}");

        return $this->success(null, 'Permintaan cuti berhasil diperbarui.');
    }

    public function coordinatorOvertime(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $rawOvertimes = Overtime::with(['employee', 'employee.position'])
            ->whereHas('employee', fn($q) => $q->where('branch_id', $coordinator->branch_id))
            ->orderBy('overtime_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        $grouped = $rawOvertimes->groupBy(fn($item) => $item->overtime_date . '|' . $item->start_time . '|' . $item->end_time)
            ->map(fn($group, $key) => [
                'key'          => $key,
                'overtime_date' => $group->first()->overtime_date,
                'start_time'   => $group->first()->start_time,
                'end_time'     => $group->first()->end_time,
                'employees'    => $group->map(fn($o) => [
                    'overtime_id' => $o->id,
                    'employee_id' => $o->employee_id,
                    'name'        => $o->employee->name,
                    'position'    => $o->employee->position?->name,
                    'status'      => $o->status,
                    'overtime_pay' => $o->overtime_pay,
                    'note'        => $o->note,
                ])->values(),
            ])->values();

        $employees = Employee::with('outlet')
            ->where('branch_id', $coordinator->branch_id)
            ->where('compani_id', $coordinator->compani_id)
            ->orderBy('name')
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'outlet' => $e->outlet?->name]);

        $outlets = Outlet::where('branch_id', $coordinator->branch_id)
            ->orderBy('name')
            ->get()
            ->map(fn($o) => ['id' => $o->id, 'name' => $o->name]);

        return $this->success(compact('grouped', 'employees', 'outlets'));
    }

    public function storeCoordinatorOvertime(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $request->validate([
            'employee_ids'   => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'overtime_date'  => 'required|date',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'note'           => 'nullable|string|max:1000',
        ]);

        $validEmployeeIds = Employee::whereIn('id', $request->employee_ids)
            ->where('branch_id', $coordinator->branch_id)
            ->pluck('id')
            ->toArray();

        DB::beginTransaction();
        try {
            $alreadyExistIds = Overtime::whereIn('employee_id', $validEmployeeIds)
                ->where('overtime_date', $request->overtime_date)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->lockForUpdate()
                ->pluck('employee_id')
                ->toArray();

            $toCreateIds = array_diff($validEmployeeIds, $alreadyExistIds);
            $count = 0;

            foreach ($toCreateIds as $empId) {
                Overtime::create([
                    'employee_id'   => $empId,
                    'overtime_date' => $request->overtime_date,
                    'start_time'    => $request->start_time,
                    'end_time'      => $request->end_time,
                    'overtime_pay'  => null,
                    'compani_id'    => $coordinator->compani_id,
                    'status'        => 'pending',
                    'note'          => $request->note,
                ]);
                $count++;
            }

            DB::commit();

            $this->logActivity('Create Overtime Request', "Koordinator mengajukan lembur untuk {$count} orang", $coordinator->compani_id, $coordinator->id);
            Cache::forget("overtimes_{$coordinator->compani_id}");

            return $this->success([
                'created' => $count,
                'skipped' => count($alreadyExistIds),
            ], "$count permintaan lembur berhasil dibuat.", 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal membuat lembur.', $e->getMessage(), 500);
        }
    }

    public function coordinatorBatchUpdate(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $request->validate([
            'original_date'  => 'required|date',
            'original_start' => 'required',
            'original_end'   => 'required',
            'overtime_date'  => 'required|date',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'employee_ids'   => 'array',
            'note'           => 'nullable|string|max:1000',
        ]);

        $newEmployeeIds = $request->employee_ids ?? [];

        DB::beginTransaction();
        try {
            $existingRecords = Overtime::where('compani_id', $coordinator->compani_id)
                ->whereHas('employee', fn($q) => $q->where('branch_id', $coordinator->branch_id))
                ->where('overtime_date', $request->original_date)
                ->where('start_time', $request->original_start)
                ->where('end_time', $request->original_end)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($existingRecords->isEmpty()) {
                DB::rollBack();
                return $this->error('Data tidak ditemukan atau sudah diproses Admin.', null, 404);
            }

            $existingEmployeeIds = $existingRecords->pluck('employee_id')->toArray();

            foreach ($existingRecords as $record) {
                if (in_array($record->employee_id, $newEmployeeIds)) {
                    $record->update([
                        'overtime_date' => $request->overtime_date,
                        'start_time'    => $request->start_time,
                        'end_time'      => $request->end_time,
                        'note'          => $request->note,
                    ]);
                } else {
                    $record->delete();
                }
            }

            foreach (array_diff($newEmployeeIds, $existingEmployeeIds) as $empId) {
                $emp = Employee::find($empId);
                if ($emp && $emp->branch_id == $coordinator->branch_id) {
                    $alreadyExists = Overtime::where('compani_id', $coordinator->compani_id)
                        ->where('employee_id', $empId)
                        ->where('overtime_date', $request->overtime_date)
                        ->where('start_time', $request->start_time)
                        ->where('end_time', $request->end_time)
                        ->lockForUpdate()->exists();

                    if (! $alreadyExists) {
                        Overtime::create([
                            'compani_id'    => $coordinator->compani_id,
                            'employee_id'   => $empId,
                            'overtime_date' => $request->overtime_date,
                            'start_time'    => $request->start_time,
                            'end_time'      => $request->end_time,
                            'status'        => 'pending',
                            'overtime_pay'  => null,
                            'note'          => $request->note,
                        ]);
                    }
                }
            }

            DB::commit();

            $this->logActivity('Batch Edit Overtime', "Koordinator mengubah kelompok lembur {$request->original_date}", $coordinator->compani_id, $coordinator->id);
            Cache::forget("overtimes_{$coordinator->compani_id}");

            return $this->success(null, 'Permintaan lembur berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal memperbarui lembur.', $e->getMessage(), 500);
        }
    }

    public function coordinatorBatchDelete(Request $request): JsonResponse
    {
        $coordinator = $this->getCoordinatorOrFail($request);

        $request->validate([
            'date'  => 'required|date',
            'start' => 'required',
            'end'   => 'required',
        ]);

        $deleted = Overtime::where('compani_id', $coordinator->compani_id)
            ->whereHas('employee', fn($q) => $q->where('branch_id', $coordinator->branch_id))
            ->where('overtime_date', $request->date)
            ->where('start_time', $request->start)
            ->where('end_time', $request->end)
            ->where('status', 'pending')
            ->delete();

        if (! $deleted) {
            return $this->error('Gagal menghapus atau data sudah diproses.', null, 409);
        }

        $this->logActivity('Batch Delete Overtime', "Koordinator membatalkan pengajuan lembur tgl {$request->date}", $coordinator->compani_id, $coordinator->id);
        Cache::forget("overtimes_{$coordinator->compani_id}");

        return $this->success(null, 'Pengajuan lembur berhasil dibatalkan.');
    }

    private function getCoordinatorOrFail(Request $request): Employee
    {
        $user = $request->user();

        if (! $user || ! $user->position?->is_head) {
            abort(response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya koordinator yang dapat mengakses fitur ini.',
                'errors'  => null,
            ], 403));
        }

        return $user;
    }

    public function gpsAttendance(Request $request): JsonResponse
    {
        $employee = $request->user();
        $today    = Carbon::today();

        $todayAttendance = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        $workLocation = $employee->outlet ?? $employee->branch;

        $todaySchedule = $employee->schedules()->where('date', $today)->with('shift')->first();

        $todayOvertime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        $recentLogs = GpsAttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [Carbon::now()->subDays(7), Carbon::now()])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(fn($log) => [
                'id'                  => $log->id,
                'attendance_date'     => $log->attendance_date,
                'check_in_time'       => $log->check_in_time,
                'check_in_latitude'   => $log->check_in_latitude,
                'check_in_longitude'  => $log->check_in_longitude,
                'check_in_address'    => $log->check_in_address,
                'check_in_distance'   => $log->check_in_distance,
                'check_in_photo'      => $log->check_in_photo
                    ? asset('storage/' . $log->check_in_photo)
                    : null,
                'check_out_time'      => $log->check_out_time,
                'check_out_latitude'  => $log->check_out_latitude,
                'check_out_longitude' => $log->check_out_longitude,
                'check_out_address'   => $log->check_out_address,
                'check_out_distance'  => $log->check_out_distance,
                'check_out_photo'     => $log->check_out_photo
                    ? asset('storage/' . $log->check_out_photo)
                    : null,
                'status'              => $log->status,
                'status_badge'        => $log->status_badge,
                'work_duration'       => $log->work_duration,
                'notes'               => $log->notes,
            ]);

        return $this->success([
            'today_attendance' => $todayAttendance ? [
                'id'                  => $todayAttendance->id,
                'attendance_date'     => $todayAttendance->attendance_date,
                'check_in_time'       => $todayAttendance->check_in_time,
                'check_in_latitude'   => $todayAttendance->check_in_latitude,
                'check_in_longitude'  => $todayAttendance->check_in_longitude,
                'check_in_address'    => $todayAttendance->check_in_address,
                'check_in_distance'   => $todayAttendance->check_in_distance,
                'check_in_photo'      => $todayAttendance->check_in_photo
                    ? asset('storage/' . $todayAttendance->check_in_photo)
                    : null,
                'check_out_time'      => $todayAttendance->check_out_time,
                'check_out_latitude'  => $todayAttendance->check_out_latitude,
                'check_out_longitude' => $todayAttendance->check_out_longitude,
                'check_out_address'   => $todayAttendance->check_out_address,
                'check_out_distance'  => $todayAttendance->check_out_distance,
                'check_out_photo'     => $todayAttendance->check_out_photo
                    ? asset('storage/' . $todayAttendance->check_out_photo)
                    : null,
                'status'              => $todayAttendance->status,
                'status_badge'        => $todayAttendance->status_badge,
                'work_duration'       => $todayAttendance->work_duration,
                'notes'               => $todayAttendance->notes,
            ] : null,
            'work_location' => $workLocation ? [
                'name'       => $workLocation->name,
                'latitude'   => $workLocation->latitude,
                'longitude'  => $workLocation->longitude,
                'gps_radius' => $workLocation->gps_radius ?? 1000,
            ] : null,
            'today_schedule' => $todaySchedule ? [
                'date'  => $todaySchedule->date,
                'shift' => $todaySchedule->shift ? [
                    'name'         => $todaySchedule->shift->name,
                    'start_time'   => $todaySchedule->shift->start_time,
                    'end_time'     => $todaySchedule->shift->end_time,
                    'is_cross_day' => $todaySchedule->shift->is_cross_day,
                ] : null,
            ] : null,
            'today_overtime' => $todayOvertime ? [
                'start_time' => $todayOvertime->start_time,
                'end_time'   => $todayOvertime->end_time,
            ] : null,
            'recent_logs' => $recentLogs,
        ]);
    }

    public function gpsCheckIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo'     => 'nullable|image|max:2048',
        ]);

        $employee = $request->user();
        $today    = Carbon::today();

        $schedule = $employee->schedules()->where('date', $today)->first();
        $overtime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        $hasSchedule = $schedule && $schedule->shift;
        $hasOvertime = $overtime !== null;

        if (!$hasSchedule && !$hasOvertime) {
            return $this->error('Anda tidak memiliki jadwal kerja atau lembur hari ini. Silakan hubungi Admin/Koordinator.', null, 422);
        }

        $existing = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return $this->error('Anda sudah check-in hari ini.', null, 409);
        }

        $workLocation = $employee->outlet ?? $employee->branch;

        if (!$workLocation || !$workLocation->latitude || !$workLocation->longitude) {
            return $this->error('Lokasi kerja belum diatur. Hubungi Admin/HRD.', null, 422);
        }

        $distance = $this->calculateGpsDistance(
            $request->latitude, $request->longitude,
            $workLocation->latitude, $workLocation->longitude
        );

        $radius = $workLocation->gps_radius ?? 1000;

        if ($distance > $radius) {
            return $this->error(
                'Anda berada di luar radius kerja. Jarak: ' . $this->formatGpsDistance($distance) . ' (Max: ' . $this->formatGpsDistance($radius) . ')',
                ['distance' => round($distance), 'radius' => $radius],
                422
            );
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        $status = 'present';
        $now    = Carbon::now();

        if ($hasSchedule) {
            $shiftStart = Carbon::parse($schedule->shift->start_time);
            if ($now->greaterThan($shiftStart->copy()->addMinutes(15))) {
                $status = 'late';
            }
        } elseif ($hasOvertime) {
            $overtimeStart = Carbon::parse($overtime->start_time);
            if ($now->greaterThan($overtimeStart->copy()->addMinutes(15))) {
                $status = 'late';
            }
        }

        $log = GpsAttendanceLog::create([
            'employee_id'        => $employee->id,
            'compani_id'         => $employee->compani_id,
            'attendance_date'    => $today,
            'check_in_time'      => $now,
            'check_in_latitude'  => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'check_in_address'   => "Lat: {$request->latitude}, Lon: {$request->longitude}",
            'check_in_distance'  => $distance,
            'check_in_photo'     => $photoPath,
            'status'             => $status,
        ]);

        return $this->success([
            'id'               => $log->id,
            'check_in_time'    => $log->check_in_time,
            'check_in_distance'=> round($distance),
            'status'           => $status,
            'check_in_photo'   => $photoPath ? asset('storage/' . $photoPath) : null,
        ], 'Check-in berhasil! Status: ' . ucfirst($status), 201);
    }

    public function gpsCheckOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo'     => 'nullable|image|max:2048',
            'notes'     => 'nullable|string|max:500',
        ]);

        $employee = $request->user();
        $today    = Carbon::today();

        $schedule = $employee->schedules()->where('date', $today)->first();
        $overtime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        $hasSchedule = $schedule && $schedule->shift;
        $hasOvertime = $overtime !== null;

        if (!$hasSchedule && !$hasOvertime) {
            return $this->error('Anda tidak memiliki jadwal kerja atau lembur hari ini. Tidak dapat check-out.', null, 422);
        }

        $attendance = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance) {
            return $this->error('Anda belum check-in hari ini.', null, 422);
        }

        if ($attendance->check_out_time) {
            return $this->error('Anda sudah check-out hari ini.', null, 409);
        }

        $workLocation = $employee->outlet ?? $employee->branch;
        $distance = $this->calculateGpsDistance(
            $request->latitude, $request->longitude,
            $workLocation->latitude, $workLocation->longitude
        );

        $radius = $workLocation->gps_radius ?? 1000;

        if ($distance > $radius) {
            return $this->error(
                'Anda berada di luar radius kerja. Jarak: ' . $this->formatGpsDistance($distance) . ' (Max: ' . $this->formatGpsDistance($radius) . ')',
                ['distance' => round($distance), 'radius' => $radius],
                422
            );
        }

        $isEarlyLeave     = false;
        $now              = Carbon::now();
        $toleranceMinutes = 15;

        if ($hasSchedule) {
            $shiftEnd = Carbon::parse($schedule->shift->end_time);
            if ($schedule->shift->is_cross_day) {
                $shiftEnd->addDay();
            }
            if ($now->lessThan($shiftEnd->copy()->subMinutes($toleranceMinutes))) {
                $isEarlyLeave = true;
            }
        } elseif ($hasOvertime) {
            $overtimeEnd = Carbon::parse($overtime->end_time);
            if ($now->lessThan($overtimeEnd->copy()->subMinutes($toleranceMinutes))) {
                $isEarlyLeave = true;
            }
        }

        if ($isEarlyLeave) {
            if (empty($request->notes) || strlen(trim($request->notes)) < 10) {
                return $this->error(
                    'Anda pulang lebih awal. Mohon berikan alasan di kolom notes (minimal 10 karakter).',
                    ['early_leave' => true],
                    422
                );
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        $updateData = [
            'check_out_time'      => $now,
            'check_out_latitude'  => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_address'   => "Lat: {$request->latitude}, Lon: {$request->longitude}",
            'check_out_distance'  => $distance,
            'check_out_photo'     => $photoPath,
        ];

        if ($isEarlyLeave) {
            $updateData['status'] = 'early_leave';
            $updateData['notes']  = $request->notes;
        } elseif ($request->filled('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $attendance->update($updateData);

        $message = $isEarlyLeave
            ? 'Check-out berhasil! Status: Pulang Awal (Admin akan memeriksa alasan Anda)'
            : 'Check-out berhasil!';

        return $this->success([
            'id'                => $attendance->id,
            'check_out_time'    => $attendance->check_out_time,
            'check_out_distance'=> round($distance),
            'status'            => $attendance->status,
            'work_duration'     => $attendance->work_duration,
            'check_out_photo'   => $photoPath ? asset('storage/' . $photoPath) : null,
            'early_leave'       => $isEarlyLeave,
        ], $message);
    }

    private function calculateGpsDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $latFrom  = deg2rad($lat1);
        $lonFrom  = deg2rad($lon1);
        $latTo    = deg2rad($lat2);
        $lonTo    = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function formatGpsDistance(float $meters): string
    {
        return $meters < 1000
            ? round($meters) . ' m'
            : round($meters / 1000, 2) . ' km';
    }

    private function logActivity(string $type, string $description, int $companyId, int $employeeId): void
    {
        ActivityLog::create([
            'employee_id'   => $employeeId,
            'compani_id'    => $companyId,
            'activity_type' => $type,
            'description'   => $description,
            'created_at'    => now(),
        ]);

        Cache::forget("activities_{$companyId}");
    }
}