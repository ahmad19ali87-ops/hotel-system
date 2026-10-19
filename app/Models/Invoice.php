<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'hotel_id',
        'booking_id',
        'room_id',
        'amount',
        'service_amount',
        'service_description',
        'tax',
        'tax_name',
        'tax_rate',
      'discount',
       'invoice_number',
        'total',
        'currency',
        'status',
        'pdf_path',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    protected static function boot()
{
    parent::boot();

    static::creating(function ($invoice) {
        $date = now()->format('Y-m-d');
        $nextNumber = Invoice::max('id') + 1;

        $invoice->invoice_number = "HOTEL-$date-$nextNumber";
    });
}
public function payments()
{
    return $this->hasMany(Payment::class);
}

public function accountEntry()
{
    return $this->hasOne(Account::class);
}
public function hotel()
{
    return $this->belongsTo(Hotel::class);
}

}
