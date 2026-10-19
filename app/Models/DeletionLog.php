<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'record_type',
        'record_id',
        'reference_number',
        'guest_name',
        'room_number',
        'amount',
        'currency',
        'payment_status',
        'reason',
        'snapshot',
        'deleted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deleted_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

