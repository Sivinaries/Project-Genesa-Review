<?php

namespace App\Http\Controllers;

use App\Models\GpsAttendanceLog;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GpsAttendanceController extends Controller
{
    public function index()
    {
        if (!Auth::guard('employee')->check()) {
            return redirect('/');
        }

        $employee = Auth::guard('employee')->user();
        $today = Carbon::today();

        $todayAttendance = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        $workLocation = $employee->outlet ?? $employee->branch;

        $recentLogs = GpsAttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [Carbon::now()->subDays(7), Carbon::now()])
            ->orderBy('attendance_date', 'desc')
            ->get();

        $todaySchedule = $employee->schedules()->where('date', $today)->first();

        $todayOvertime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        return view('ess.gpsAttendance', compact('employee', 'todayAttendance', 'workLocation', 'recentLogs', 'todaySchedule', 'todayOvertime'));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048',
        ]);

        $employee = Auth::guard('employee')->user();
        $today = Carbon::today();

        $schedule = $employee->schedules()->where('date', $today)->first();

        $overtime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        $hasSchedule = $schedule && $schedule->shift;
        $hasOvertime = $overtime !== null;

        if (!$hasSchedule && !$hasOvertime) {
            return back()->withErrors(['msg' => 'Anda tidak memiliki jadwal kerja atau lembur hari ini. Silakan hubungi Admin/Koordinator.']);
        }

        $existing = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return back()->withErrors(['msg' => 'Anda sudah check-in hari ini.']);
        }

        $workLocation = $employee->outlet ?? $employee->branch;

        if (!$workLocation || !$workLocation->latitude || !$workLocation->longitude) {
            return back()->withErrors(['msg' => 'Lokasi kerja belum diatur. Hubungi Admin/HRD.']);
        }

        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $workLocation->latitude,
            $workLocation->longitude
        );

        $radius = $workLocation->gps_radius ?? 1000;

        if ($distance > $radius) {
            return back()->withErrors([
                'msg' => "Anda berada di luar radius kerja. Jarak: " . $this->formatDistance($distance) . " (Max: " . $this->formatDistance($radius) . ")"
            ]);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        $status = 'present';
        $now = Carbon::now();

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

        GpsAttendanceLog::create([
            'employee_id' => $employee->id,
            'compani_id' => $employee->compani_id,
            'attendance_date' => $today,
            'check_in_time' => Carbon::now(),
            'check_in_latitude' => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'check_in_address' => "Lat: {$request->latitude}, Lon: {$request->longitude}",
            'check_in_distance' => $distance,
            'check_in_photo' => $photoPath,
            'status' => $status,
        ]);

        return redirect()->route('ess-gps-attendance')->with('success', 'Check-in berhasil! Status: ' . ucfirst($status));
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048',
        ]);

        $employee = Auth::guard('employee')->user();
        $today = Carbon::today();

        $schedule = $employee->schedules()->where('date', $today)->first();

        $overtime = Overtime::where('employee_id', $employee->id)
            ->where('overtime_date', $today)
            ->where('status', 'approved')
            ->first();

        $hasSchedule = $schedule && $schedule->shift;
        $hasOvertime = $overtime !== null;

        if (!$hasSchedule && !$hasOvertime) {
            return back()->withErrors(['msg' => 'Anda tidak memiliki jadwal kerja atau lembur hari ini. Tidak dapat check-out.']);
        }

        $attendance = GpsAttendanceLog::where('employee_id', $employee->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance) {
            return back()->withErrors(['msg' => 'Anda belum check-in hari ini.']);
        }

        if ($attendance->check_out_time) {
            return back()->withErrors(['msg' => 'Anda sudah check-out hari ini.']);
        }

        $workLocation = $employee->outlet ?? $employee->branch;
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $workLocation->latitude,
            $workLocation->longitude
        );

        $radius = $workLocation->gps_radius ?? 1000;

        if ($distance > $radius) {
            return back()->withErrors([
                'msg' => "Anda berada di luar radius kerja. Jarak: " . $this->formatDistance($distance) . " (Max: " . $this->formatDistance($radius) . ")"
            ]);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        $isEarlyLeave = false;
        $now = Carbon::now();
        $toleranceMinutes = 15;

        if ($hasSchedule) {
            $shiftEnd = Carbon::parse($schedule->shift->end_time);

            if ($schedule->shift->is_cross_day) {
                $shiftEnd->addDay();
            }

            $earliestAllowedCheckout = $shiftEnd->copy()->subMinutes($toleranceMinutes);

            if ($now->lessThan($earliestAllowedCheckout)) {
                $isEarlyLeave = true;
            }
        } elseif ($hasOvertime) {
            $overtimeEnd = Carbon::parse($overtime->end_time);
            $earliestAllowedCheckout = $overtimeEnd->copy()->subMinutes($toleranceMinutes);

            if ($now->lessThan($earliestAllowedCheckout)) {
                $isEarlyLeave = true;
            }
        }

        if ($isEarlyLeave) {
            $request->validate([
                'notes' => 'required|string|min:10|max:500'
            ], [
                'notes.required' => "Anda pulang lebih awal. Mohon berikan alasan di kolom catatan.",
                'notes.min' => 'Alasan minimal 10 karakter.',
                'notes.max' => 'Alasan maksimal 500 karakter.',
            ]);
        }

        $updateData = [
            'check_out_time' => Carbon::now(),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_address' => "Lat: {$request->latitude}, Lon: {$request->longitude}",
            'check_out_distance' => $distance,
            'check_out_photo' => $photoPath,
        ];

        if ($isEarlyLeave) {
            $updateData['status'] = 'early_leave';
            $updateData['notes'] = $request->notes;
        } else {
            if ($request->filled('notes')) {
                $updateData['notes'] = $request->notes;
            }
        }

        $attendance->update($updateData);

        $message = $isEarlyLeave
            ? 'Check-out berhasil! Status: Pulang Awal (Admin akan memeriksa alasan Anda)'
            : 'Check-out berhasil!';

        return redirect()->route('ess-gps-attendance')->with('success', $message);
    }

    public function adminIndex(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $userCompany = Auth::user()->compani;
        $startDate = $request->get('start_date', Carbon::today()->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());

        $logs = GpsAttendanceLog::with(['employee.branch', 'employee.outlet', 'employee.position'])
            ->where('compani_id', $userCompany->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time')
            ->get();

        return view('gpsAttendanceAdmin', compact('logs', 'startDate', 'endDate'));
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function formatDistance($meters)
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }
        return round($meters / 1000, 2) . ' km';
    }
}