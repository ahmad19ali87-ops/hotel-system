<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'hotel_id',
        'guest_id',
        'subject',
        'body',
        'attachment',
        'sender_type',
        'is_read',
        'is_broadcast',
        'allow_reply',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_broadcast' => 'boolean',
        'allow_reply' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }
        return Storage::disk('public')->exists($this->attachment)
            ? asset('storage/' . ltrim($this->attachment, '/'))
            : asset($this->attachment);
    }
}
