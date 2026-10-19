<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleaningLog extends Model
{
    protected $table = 'cleaning_logs';

    protected $fillable = ['hotel_id', 'room_id', 'user_id', 'cleaned_at'];

    protected function casts(): array
    {
        return ['cleaned_at' => 'datetime'];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function cleaner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}