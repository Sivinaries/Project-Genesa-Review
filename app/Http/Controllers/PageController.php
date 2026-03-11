<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Note;
use App\Models\Leave;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\Attendance;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function dashboard()
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

        //CARD
        $totalEmployees = Employee::where('compani_id', $userCompany->id)->count();

        $newEmployeesThisMonth = Employee::where('compani_id', $userCompany->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $totalLeaves = Leave::where('compani_id', $userCompany->id)->where('status', 'pending')->count();

        $newLeavesThisMonth = Leave::where('compani_id', $userCompany->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $totalOvertime = Overtime::where('compani_id', $userCompany->id)->where('status', 'pending')->count();

        $newOvertimesThisMonth = Overtime::where('compani_id', $userCompany->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $overtimePay = Overtime::where('compani_id', $userCompany->id)->sum('overtime_pay');

        //CHART
        $attendance = Attendance::orderBy('period_start', 'asc')->where('compani_id', $userCompany->id)
            ->take(6) // ambil 6 periode terakhir
            ->get();

        $labels = $attendance->map(function ($item) {
            return Carbon::parse($item->period_start)->format('M Y');
        });

        $present = $attendance->pluck('total_present');
        $late    = $attendance->pluck('total_late');
        $alpha   = $attendance->pluck('total_alpha');
        $leave   = $attendance->pluck('total_leave');

        $batches = $userCompany->payrolls()
            ->join('employees', 'payrolls.employee_id', '=', 'employees.id')
            ->select(
                'payrolls.pay_period_start',
                'payrolls.pay_period_end',
                DB::raw('SUM(payrolls.net_salary) as total_spent'),
                DB::raw('MAX(payrolls.created_at) as created_at')
            )
            ->groupBy('payrolls.pay_period_start', 'payrolls.pay_period_end')
            ->orderBy(DB::raw('MAX(payrolls.created_at)'), 'asc')
            ->get();

        // 👉 DATA UNTUK CHART
        $payrollLabels = $batches->map(function ($item) {
            return Carbon::parse($item->pay_period_start)->format('M Y');
        });

        $payrollExpense = $batches->map(function ($item) {
            return (int) $item->total_spent;
        });

        return view('dashboard', compact(
            'totalEmployees',
            'newEmployeesThisMonth',
            'totalLeaves',
            'newLeavesThisMonth',
            'totalOvertime',
            'newOvertimesThisMonth',
            'overtimePay',
            'attendance',
            // chart
            'labels',
            'present',
            'late',
            'alpha',
            'leave',
            'batches',
            'payrollLabels',
            'payrollExpense'
        ));
    }

    public function profile()
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

        return view('profil', compact('userCompany'));
    }

    public function search(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $company = Auth::user()->compani;

        if (!$company) {
            return redirect()->route('addcompany');
        }

        if ($company->status !== 'Settlement') {
            return redirect()->route('login');
        }

        $search = $request->input('search');

        $branches = Branch::where('compani_id', $company->id);
        $positions = Position::where('compani_id', $company->id);
        $announcements = Announcement::where('compani_id', $company->id);
        $employees = Employee::where('compani_id', $company->id);
        $overtimes = Overtime::with([
            'employee.branch',
            'employee.position',
            'employee.outlet',
        ])
            ->where('compani_id', $company->id);
        $leaves = Leave::where('compani_id', $company->id);
        $notes = Note::where('compani_id', $company->id);

        if ($search) {

            $branches->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });

            $positions->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });

            $announcements->where('content', 'like', "%{$search}%");

            $employees->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });

            $overtimes->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });

            $leaves->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });

            $notes->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $overtimes = $overtimes
            ->get()
            ->groupBy(
                fn($item) =>
                $item->overtime_date . '|' .
                    $item->start_time   . '|' .
                    $item->end_time
            );

        return view('search', [
            'branches'      => $branches->get(),
            'positions'     => $positions->get(),
            'announcements' => $announcements->get(),
            'employees'     => $employees->get(),
            'overtimes'     => $overtimes,
            'leaves'        => $leaves->get(),
            'notes'         => $notes->get(),
        ]);
    }
}
