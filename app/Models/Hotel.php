<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Hotel extends Model
{
    use SoftDeletes;

    protected $table = 'hotels';


    protected $fillable = [
        'name',
        'slug',
        'logo',
        'stars',
        'is_visible',
        'phone',
        'phones',
        'email',
        'address',
        'google_maps_url',
        'description',
        'tagline',
        'amenities',
        'province',
        'images',
        'printer_settings',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function getPrinterSettingsListAttribute(): array
    {
        if (empty($this->printer_settings)) {
            return [
                'invoice_printer' => 'طابعة الفواتير الافتراضية',
                'report_printer' => 'طابعة التقارير الافتراضية',
                'invoice_format' => 'thermal_80mm',
                'scanner_source' => 'camera',
                'auto_print' => false,
            ];
        }

        $decoded = json_decode($this->printer_settings, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getAmenitiesListAttribute(): array
    {
        if (empty($this->amenities)) {
            return ['واي فاي مجاني', 'تكييف مركزي', 'إفطار فاخر', 'مواقف سيارات'];
        }

        $decoded = json_decode($this->amenities, true);
        if (is_array($decoded)) {
            return array_filter(array_map('trim', $decoded));
        }

        return array_filter(array_map('trim', explode(',', $this->amenities)));
    }

    public function scopeForSlug(Builder $query, string $slug): Builder
    {
        return $query->whereRaw('LOWER(RTRIM(slug)) = ?', [strtolower(rtrim($slug))]);
    }

    public function rooms()
{
    return $this->hasMany(Room::class, 'hotel_id');
}


    public function bookings()
    {
        return $this->hasMany(Booking::class, 'hotel_id');
    }

    public function guests()
    {
        return $this->hasMany(Guest::class, 'hotel_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'hotel_id');
    }

    public function maintenance()
    {
        return $this->hasMany(Maintenance::class, 'hotel_id'        );
    }

    public function reviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . ltrim($this->logo, '/'));
        }

        $legacyPath = public_path('uploads/hotels/' . $this->logo);

        return file_exists($legacyPath) ? asset('uploads/hotels/' . $this->logo) : null;
    }

    public function getImageUrlsAttribute(): array
    {
        $images = $this->images ? (json_decode($this->images, true) ?: []) : [];

        return collect($images)->map(function ($image) {
            if (Storage::disk('public')->exists($image)) {
                return asset('storage/' . ltrim($image, '/'));
            }

            return file_exists(public_path('uploads/hotels/' . $image))
                ? asset('uploads/hotels/' . $image)
                : null;
        })->filter()->values()->all();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->image_urls[0] ?? $this->logo_url;
    }

    public function subscriptions()
    {
        return $this->hasMany(HotelSubscription::class, 'hotel_id')->latest('id');
    }

    public function currentSubscription()
    {
        return $this->hasOne(HotelSubscription::class, 'hotel_id')->latestOfMany();
    }

    public function subscriptionInvoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'hotel_id')->latest('id');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'hotel_id')->latest('id');
    }

    /**
     * Get active subscription or provision/fallback to default plan
     */
    public function getActiveSubscriptionAttribute(): ?HotelSubscription
    {
        $sub = $this->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trial', 'grace_period'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->first();

        if ($sub) {
            return $sub;
        }

        // Return latest subscription even if expired so we have its data
        return $this->subscriptions()->with('plan')->first();
    }
}


