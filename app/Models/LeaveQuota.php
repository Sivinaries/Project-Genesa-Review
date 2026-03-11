<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaveQuota extends Model
{
    protected $fillable = [
        'employee_id',
        'compani_id',
        'period_start',
        'period_end',
        'total_quota',
        'used_days',
    ];

    public function compani()
    {
        return $this->belongsTo(Compani::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, $this->total_quota - $this->used_days);
    }

    public static function getActiveQuota(Employee $employee): ?self
    {
        $joinDate = Carbon::parse($employee->join_date);
        $now      = Carbon::now();

        if ($now->lt($joinDate->copy()->addYear())) {
            return null;
        }

        $anniversaryThisYear = $joinDate->copy()->setYear($now->year);
        if ($anniversaryThisYear->isFuture()) {
            $anniversaryThisYear->subYear();
        }

        $periodStart = $anniversaryThisYear->toDateString();
        $periodEnd   = $anniversaryThisYear->copy()->addYear()->subDay()->toDateString();

        return self::firstOrCreate(
            [
                'employee_id'  => $employee->id,
                'period_start' => $periodStart,
            ],
            [
                'compani_id'  => $employee->compani_id,
                'period_end'  => $periodEnd,
                'total_quota' => 4,
                'used_days'   => 0,
            ]
        );
    }
}