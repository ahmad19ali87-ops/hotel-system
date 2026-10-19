<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cleaning extends Model
{
    protected $fillable = [
        'hotel_id', 'room_id', 'status', 'requested_at', 'notes'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}



