<?php

namespace App\Services;

use App\Models\CollectiveLeave;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveQuota;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveQuotaService
{
    /**
     * Validasi apakah karyawan eligible untuk cuti mandiri.
     * Dipanggil oleh coordinator sebelum submit.
     */
    public function validatePersonalLeave(Employee $employee, string $startDate, string $endDate): array
    {
        $joinDate = Carbon::parse($employee->join_date);

        // Cek 1 tahun masa kerja
        if (Carbon::now()->lt($joinDate->copy()->addYear())) {
            $eligibleDate = $joinDate->copy()->addYear()->format('d M Y');
            return [
                'allowed' => false,
                'message' => "Karyawan {$employee->name} belum memenuhi masa kerja 1 tahun. Eligible mulai {$eligibleDate}.",
                'quota'   => null,
            ];
        }

        $quota = LeaveQuota::getActiveQuota($employee);

        if (! $quota) {
            return [
                'allowed' => false,
                'message' => 'Kuota cuti tidak ditemukan.',
                'quota'   => null,
            ];
        }

        $duration = $this->countLeaveDays($employee->compani_id, $startDate, $endDate);

        if ($quota->remaining_days < $duration) {
            return [
                'allowed'  => false,
                'message'  => "Kuota cuti {$employee->name} tidak mencukupi. Sisa: {$quota->remaining_days} hari, dibutuhkan: {$duration} hari.",
                'quota'    => $quota,
            ];
        }

        return [
            'allowed'  => true,
            'message'  => '',
            'quota'    => $quota,
            'duration' => $duration,
        ];
    }

    /**
     * Potong kuota saat leave type 'cuti' di-approve.
     */
    public function deductQuota(Leave $leave): void
    {
        if ($leave->type !== 'cuti') return;

        $quota = LeaveQuota::getActiveQuota($leave->employee);
        if (! $quota) return;

        $duration = $this->countLeaveDays($leave->compani_id, $leave->start_date, $leave->end_date);
        $quota->increment('used_days', min($duration, $quota->remaining_days));
    }

    /**
     * Kembalikan kuota saat leave di-reject/delete setelah sebelumnya approved.
     */
    public function restoreQuota(Leave $leave): void
    {
        if ($leave->type !== 'cuti') return;

        $quota = LeaveQuota::getActiveQuota($leave->employee);
        if (! $quota) return;

        $duration = $this->countLeaveDays($leave->compani_id, $leave->start_date, $leave->end_date);
        $quota->decrement('used_days', min($duration, $quota->used_days));
    }

    /**
     * Hitung hari cuti, mengecualikan tanggal yang sudah masuk cuti bersama.
     * (Hari cuti bersama tidak memotong kuota mandiri karyawan)
     */
    public function countLeaveDays(int $companiId, $startDate, $endDate): int
    {
        $collectiveDates = CollectiveLeave::where('compani_id', $companiId)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $count = 0;
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            if (! in_array($date->toDateString(), $collectiveDates)) {
                $count++;
            }
        }

        return $count;
    }
}