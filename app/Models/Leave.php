<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'compani_id',
            'employee_id',
            'start_date',
            'end_date',
            'type',
            'note',
            'status',
        ];

    public function compani()
    {
        return $this->belongsTo(Compani::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

     public function getDurationDaysAttribute(): int
    {
        return \Carbon\Carbon::parse($this->start_date)
            ->diffInDays(\Carbon\Carbon::parse($this->end_date)) + 1;
    }
}
