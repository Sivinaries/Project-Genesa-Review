<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectiveLeave extends Model
{
    protected $fillable = [
        'compani_id', 
        'date', 
        'name'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function compani()
    {
        return $this->belongsTo(Compani::class);
    }
}