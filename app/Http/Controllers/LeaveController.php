<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\CollectiveLeave;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function __construct(private LeaveQuotaService $quotaService) {}

    public function index()
    {
        if (! Auth::check()) {
            return redirect('/');
        }

        $userCompany = Auth::user()->compani;

        if (! $userCompany) {
            return redirect()->route('addcompany');
        }

        $status = $userCompany->status;

        if ($status !== 'Settlement') {
            return redirect()->route('login');
        }

        $cacheKey = "leaves_{$userCompany->id}";

        $leaves = Cache::remember($cacheKey, 180, function () use ($userCompany) {
            return $userCompany->leaves()->with(['employee', 'employee.position'])->get();
        });

        $employee = $userCompany->employees()
            ->with(['position', 'branch'])
            ->orderBy('name')
            ->get();

        $quotas = collect();
        foreach ($employee as $emp) {
            $quota = LeaveQuota::getActiveQuota($emp);
            if ($quota) {
                $quotas->put($emp->id, $quota);
            }
        }

        $currentYear     = now()->year;
        $collectiveLeaves = CollectiveLeave::where('compani_id', $userCompany->id)
            ->whereYear('date', $currentYear)
            ->orderBy('date')
            ->get();

        $maxCollective = $userCompany->max_collective_leave;

        return view('leave', compact(
            'leaves',
            'employee',
            'quotas',
            'collectiveLeaves',
            'currentYear',
            'maxCollective'
        ));
    }

    public function store(Request $request)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'note' => 'required|string',
            'status' => 'required|string',
        ]);

        if ($data['type'] === 'cuti') {
            $employee   = Employee::findOrFail($data['employee_id']);
            $validation = $this->quotaService->validatePersonalLeave(
                $employee,
                $data['start_date'],
                $data['end_date']
            );

            if (! $validation['allowed']) {
                return back()->withErrors(['quota' => $validation['message']])->withInput();
            }
        }

        $leave = Leave::create([
            'employee_id' => $data['employee_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'type' => $data['type'],
            'note' => $data['note'],
            'status' => $data['status'],
            'compani_id' => $userCompany->id,
        ]);

        if ($data['type'] === 'cuti' && $data['status'] === 'approved') {
            $leave->load('employee');
            $this->quotaService->deductQuota($leave);
        }

        $leave->load('employee');

        $this->logActivity(
            'Create Leave',
            "Membuat leave '{$leave->employee->name}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('leave'))->with('success', 'Cuti berhasil dibuat!');
    }

    public function update(Request $request, $id)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'employee_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'type' => 'required',
            'note' => 'required',
            'status' => 'required',
        ]);

        $leave = Leave::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->firstOrFail();

        $oldContent = $leave->employee->name;

        $wasApproved    = $leave->status === 'approved' && $leave->type === 'cuti';
        $willBeApproved = $data['status'] === 'approved' && $data['type'] === 'cuti';

        if ($willBeApproved && ! $wasApproved) {
            $employee   = Employee::findOrFail($data['employee_id']);
            $validation = $this->quotaService->validatePersonalLeave(
                $employee,
                $data['start_date'],
                $data['end_date']
            );

            if (! $validation['allowed']) {
                return back()->withErrors(['quota' => $validation['message']])->withInput();
            }
        }

        if ($wasApproved) {
            $this->quotaService->restoreQuota($leave);
        }

        $leave->update([
            'employee_id' => $data['employee_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'type' => $data['type'],
            'note' => $data['note'],
            'status' => $data['status'],
        ]);

        if ($willBeApproved) {
            $leave->load('employee');
            $this->quotaService->deductQuota($leave);
        }

        if ($leave->wasChanged('employee_id')) {
            $leave->load('employee');
        }

        $this->logActivity(
            'Update Leave',
            "Mengubah leave '{$oldContent}' menjadi '{$leave->status}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('leave'))->with('success', 'Cuti berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $userCompany = auth()->user()->compani;

        $leave = Leave::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->with('employee')
            ->first();

        if ($leave->type === 'cuti' && $leave->status === 'approved') {
            $this->quotaService->restoreQuota($leave);
        }

        $oldContent = $leave->employee->name;

        $leave->delete();

        $this->logActivity(
            'Delete Leave',
            "Menghapus leave '{$oldContent}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('leave'))->with('success', 'Cuti berhasil dihapus!');
    }

    public function storeCollective(Request $request)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:100',
        ]);

        $year  = Carbon::parse($data['date'])->year;
        $count = CollectiveLeave::where('compani_id', $userCompany->id)
            ->whereYear('date', $year)
            ->count();

        if ($count >= $userCompany->max_collective_leave) {
            return back()->withErrors([
                'collective' => "Batas maksimal {$userCompany->max_collective_leave} hari cuti bersama tahun {$year} sudah tercapai."
            ]);
        }

        CollectiveLeave::create([
            'compani_id' => $userCompany->id,
            'date'       => $data['date'],
            'name'       => $data['name'],
        ]);

        $this->clearCache($userCompany->id);

        return back()->with('success', 'Cuti bersama berhasil ditambahkan!');
    }

    public function destroyCollective($id)
    {
        $userCompany = auth()->user()->compani;

        CollectiveLeave::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->delete();

        $this->clearCache($userCompany->id);

        return back()->with('success', 'Cuti bersama berhasil dihapus!');
    }

    public function adjustQuota(Request $request)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'action'      => 'required|in:deduct,restore',
            'days'        => 'required|integer|min:1|max:365',
            'reason'      => 'nullable|string|max:255',
        ]);

        $employee = Employee::where('id', $data['employee_id'])
            ->where('compani_id', $userCompany->id)
            ->firstOrFail();

        $quota = LeaveQuota::getActiveQuota($employee);

        if (! $quota) {
            return back()->withErrors([
                'quota_adjust' => "Karyawan {$employee->name} belum memiliki kuota aktif (belum 1 tahun masa kerja)."
            ]);
        }

        if ($data['action'] === 'deduct') { 
            $newTotal = max(0, $quota->total_quota - $data['days']);
            if ($newTotal < $quota->used_days) {
                return back()->withErrors([
                    'quota_adjust' => "Tidak bisa mengurangi {$data['days']} hari. Karyawan sudah memakai {$quota->used_days} hari, sehingga total minimal adalah {$quota->used_days} hari."
                ]);
            }

            $quota->update(['total_quota' => $newTotal]);
            $desc = "Admin mengurangi kuota cuti {$employee->name} sebesar {$data['days']} hari. Alasan: {$data['reason']}";
            $msg  = "Kuota cuti {$employee->name} berhasil dikurangi {$data['days']} hari. Sisa kuota: {$quota->remaining_days} hari.";
        } else {
            $maxQuota  = $quota->total_quota + $data['days'];
            $quota->update(['total_quota' => $maxQuota]);
            $desc = "Admin menambah kuota cuti {$employee->name} sebesar {$data['days']} hari. Alasan: {$data['reason']}";
            $msg  = "Kuota cuti {$employee->name} berhasil ditambah {$data['days']} hari. Total kuota: {$quota->total_quota} hari.";
        }

        $this->logActivity('Adjust Leave Quota', $desc, $userCompany->id);

        return back()->with('success', $msg);
    }


    private function clearCache($companyId)
    {
        Cache::forget("leaves_{$companyId}");
    }

    private function logActivity($type, $description, $companyId)
    {
        $userId  = null;
        $staffId = null;

        if (Auth::guard('staff')->check()) {
            $staffId = Auth::guard('staff')->id();
        } elseif (Auth::check()) {
            $userId = Auth::id();
        }

        ActivityLog::create([
            'user_id'       => $userId,
            'staff_id'      => $staffId,
            'compani_id'    => $companyId,
            'activity_type' => $type,
            'description'   => $description,
            'created_at'    => now(),
        ]);

        Cache::forget("activities_{$companyId}");
    }
}