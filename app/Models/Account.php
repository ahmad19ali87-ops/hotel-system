<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'hotel_id', 'invoice_id', 'type', 'title', 'amount', 'currency', 'transaction_date', 'notes'
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
