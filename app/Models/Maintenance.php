<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $table = 'maintenance';

    protected $fillable = [
        'hotel_id', 'room_id', 'issue_type', 'priority', 'notes', 'status', 'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}

