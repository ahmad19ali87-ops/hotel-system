<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
         'name',
        'room_number',
        'room_type_id',
        'price',
        'price_usd',
        'status',
        'needs_cleaning',
        'maintenance_notes',
        'image',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function type()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function getTypeAttribute($value)
    {
        if ($this->relationLoaded('roomType')) {
            return $this->getRelation('roomType');
        }

        if ($this->relationLoaded('type')) {
            return $this->getRelation('type');
        }

        if ($this->room_type_id) {
            $resolved = $this->roomType;
            if ($resolved) {
                return $resolved;
            }
        }

        return $value;
    }
public function hotel()
{
    return $this->belongsTo(Hotel::class, 'hotel_id');
}

public function bookings()
{
    return $this->hasMany(Booking::class, 'room_id');
}

public function getImageUrlAttribute(): ?string
{
    if (! $this->image) {
        return null;
    }

    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->image)) {
        return asset('storage/' . ltrim($this->image, '/'));
    }

    if (file_exists(public_path('room_images/' . $this->image))) {
        return asset('room_images/' . $this->image);
    }

    if (file_exists(public_path('uploads/rooms/' . $this->image))) {
        return asset('uploads/rooms/' . $this->image);
    }

    return asset('storage/' . ltrim($this->image, '/'));
}



}
