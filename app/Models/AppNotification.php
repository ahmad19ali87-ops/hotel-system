<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'hotel_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}

