<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Offer extends Model
{
    protected $fillable = [
        'hotel_id',
        'title',
        'description',
        'discount_percentage',
        'special_price',
        'special_price_usd',
        'start_date',
        'end_date',
        'image',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'special_price' => 'float',
        'special_price_usd' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . ltrim($this->image, '/'));
        }
        return $this->hotel?->cover_image_url ?? asset('images/default-offer.jpg');
    }
}

