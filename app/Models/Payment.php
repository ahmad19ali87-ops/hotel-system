<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function hotel()
{
    return $this->belongsTo(Hotel::class);
}

}

