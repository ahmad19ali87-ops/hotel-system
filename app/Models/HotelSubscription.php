<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HotelSubscription extends Model
{
    protected $fillable = [
        'hotel_id',
        'plan_id',
        'status',
        'billing_cycle',
        'currency',
        'price_paid',
        'starts_at',
        'expires_at',
        'trial_ends_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'trial_ends_at' => 'date',
        'price_paid' => 'float',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'subscription_id');
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at) {
            return 999;
        }

        $now = Carbon::today();
        $diff = (int)$now->diffInDays($this->expires_at, false);
        return $diff;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->days_remaining < 0 || in_array($this->status, ['expired', 'cancelled']);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $remaining = $this->days_remaining;
        return $remaining >= 0 && $remaining <= 7;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_expired) {
            return 'منتهي الصلاحية';
        }

        return match ($this->status) {
            'active' => 'نشط وساري',
            'trial' => 'فترة تجريبية',
            'grace_period' => 'فترة سماح',
            'cancelled' => 'ملغي',
            default => 'ساري',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->is_expired) {
            return 'bg-danger text-white';
        }

        if ($this->is_expiring_soon) {
            return 'bg-warning text-dark';
        }

        return match ($this->status) {
            'active' => 'bg-success text-white',
            'trial' => 'bg-info text-white',
            'grace_period' => 'bg-warning text-dark',
            'cancelled' => 'bg-secondary text-white',
            default => 'bg-success text-white',
        };
    }
}

