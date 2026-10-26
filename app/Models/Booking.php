<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'hotel_id', 'room_id', 'guest_id', 'created_by',
        'guest_name', 'guest_phone',
        'check_in', 'check_out',
        'actual_check_in_at', 'actual_check_out_at',
        'total_price', 'total_price_usd', 'currency', 'status'
    ];

    protected $casts = [
        'actual_check_in_at' => 'datetime',
        'actual_check_out_at' => 'datetime',
    ];


    public function room()
    {
        return $this->belongsTo(Room::class);
    }
public function guest()
{
    return $this->belongsTo(Guest::class);
}

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }


}
