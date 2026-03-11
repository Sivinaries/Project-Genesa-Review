<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\Overtime;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoRejectExpiredRequests extends Command
{
    protected $signature = 'requests:auto-reject';

    protected $description = 'Otomatis menolak pengajuan cuti dan lembur yang masih pending tapi tanggalnya sudah terlewat.';

    public function handle(): void
    {
        $this->info('Memulai auto-reject pengajuan yang sudah terlewat...');

        [$leaveResults, $overtimeResults] = DB::transaction(function () {
            return [
                $this->autoRejectLeaves(),
                $this->autoRejectOvertimes(),
            ];
        });

        if (empty($leaveResults)) {
            $this->line('  [Leave] Tidak ada pengajuan cuti yang perlu di-reject.');
        } else {
            foreach ($leaveResults as $companyId => $count) {
                $this->line("  [Leave] Company ID {$companyId}: {$count} pengajuan di-reject.");
            }
        }

        if (empty($overtimeResults)) {
            $this->line('  [Overtime] Tidak ada pengajuan lembur yang perlu di-reject.');
        } else {
            foreach ($overtimeResults as $companyId => $count) {
                $this->line("  [Overtime] Company ID {$companyId}: {$count} pengajuan di-reject.");
            }
        }

        $this->info('Selesai.');
    }

    private function autoRejectLeaves(): array
    {
        $expiredLeaves = Leave::where('status', 'pending')
            ->where('end_date', '<', Carbon::today())
            ->get();

        if ($expiredLeaves->isEmpty()) {
            return [];
        }

        $results = [];
        $groupedByCompany = $expiredLeaves->groupBy('compani_id');

        foreach ($groupedByCompany as $companyId => $leaves) {
            $ids   = $leaves->pluck('id')->toArray();
            $count = count($ids);

            Leave::whereIn('id', $ids)->update([
                'status'     => 'rejected',
                'updated_at' => now(),
            ]);

            ActivityLog::create([
                'user_id'       => null,
                'staff_id'      => null,
                'employee_id'   => null,
                'compani_id'    => $companyId,
                'activity_type' => 'Auto-Reject Leave',
                'description'   => "Sistem otomatis menolak {$count} pengajuan cuti yang sudah terlewat.",
                'created_at'    => now(),
            ]);

            Cache::forget("leaves_{$companyId}");
            Cache::forget("activities_{$companyId}");

            $results[$companyId] = $count;
        }
        return $results;
    }

    private function autoRejectOvertimes(): array
    {
        $now = Carbon::now();

        $expiredOvertimes = Overtime::where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->where('overtime_date', '<', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->where('overtime_date', $now->toDateString())
                            ->where('start_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->get();

        if ($expiredOvertimes->isEmpty()) {
            return [];
        }

        $results = [];
        $groupedByCompany = $expiredOvertimes->groupBy('compani_id');

        foreach ($groupedByCompany as $companyId => $overtimes) {
            $ids   = $overtimes->pluck('id')->toArray();
            $count = count($ids);

            Overtime::whereIn('id', $ids)->update([
                'status'     => 'rejected',
                'updated_at' => now(),
            ]);

            ActivityLog::create([
                'user_id'       => null,
                'staff_id'      => null,
                'employee_id'   => null,
                'compani_id'    => $companyId,
                'activity_type' => 'Auto-Reject Overtime',
                'description'   => "Sistem otomatis menolak {$count} pengajuan lembur yang sudah terlewat.",
                'created_at'    => now(),
            ]);

            Cache::forget("overtimes_{$companyId}");
            Cache::forget("activities_{$companyId}");

            $results[$companyId] = $count;
        }
        return $results;
    }
}