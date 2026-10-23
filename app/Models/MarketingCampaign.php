<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'title',
        'code',
        'discount_percent',
        'discount_fixed',
        'currency',
        'type',
        'starts_at',
        'ends_at',
        'max_uses',
        'used_count',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_percent' => 'float',
        'discount_fixed' => 'float',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) {
            return false;
        }

        $today = now()->toDateString();
        if ($this->starts_at && $this->starts_at->toDateString() > $today) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->toDateString() < $today) {
            return false;
        }

        return true;
    }
}

