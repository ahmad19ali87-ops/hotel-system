<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelReview extends Model
{
    protected $fillable = ['hotel_id', 'guest_name', 'rating', 'comment', 'hotel_reply', 'replied_at'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'replied_at' => 'datetime',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}