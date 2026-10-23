<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'hotel_id',
        'subscription_id',
        'plan_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'payment_method',
        'paid_at',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function subscription()
    {
        return $this->belongsTo(HotelSubscription::class, 'subscription_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inv) {
            if (empty($inv->invoice_number)) {
                $year = date('Y');
                $next = self::max('id') + 1;
                $inv->invoice_number = "SUB-{$year}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}

