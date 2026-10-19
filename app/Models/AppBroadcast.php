<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBroadcast extends Model
{
    protected $fillable = [
        'sender_id',
        'type',
        'title',
        'body',
        'target',
        'event_type',
        'icon',
        'link',
        'allow_reply',
        'recipients_count',
    ];

    protected $casts = [
        'allow_reply' => 'boolean',
        'recipients_count' => 'integer',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

