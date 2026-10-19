<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
    'hotel_id', 'room_id', 'guest_id', 'created_by',
    'guest_name', 'guest_phone',
    'check_in', 'check_out',
    'total_price', 'total_price_usd', 'currency', 'status'
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
