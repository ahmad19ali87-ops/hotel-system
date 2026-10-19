<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'hotel_id', 'guest_id', 'booking_id', 'room_id',
        'title', 'message', 'status', 'response', 'handled_by'
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
