<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'compani_id',
        'branch_id',
        'name',
        'start_time',
        'end_time',
        'is_cross_day',
        'color',
    ];

    public function getDurationAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        if ($this->is_cross_day) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
    
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} jam {$minutes} menit";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        } elseif ($minutes > 0) {
            return "{$minutes} menit";
        } else {
            return "0 jam";
        }
    }

    public function compani()
    {
        return $this->belongsTo(Compani::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}