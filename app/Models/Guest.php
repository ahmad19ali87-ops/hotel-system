<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'hotel_id',
        'user_id',
        'name',
        'first_name',
        'father_name',
        'last_name',
        'nationality',
        'birth_date',
        'id_type',
        'id_number',
        'phone',
        'email',
        'national_id',
        'address',
        'notes',
        'attachments',
    ];

    public function getAttachmentsListAttribute(): array
    {
        if (empty($this->attachments)) {
            return [];
        }

        $decoded = json_decode($this->attachments, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_map(function ($item) {
            if (is_string($item)) {
                $path = $item;
                $name = basename($item);
                $type = pathinfo($item, PATHINFO_EXTENSION);
            } else {
                $path = $item['path'] ?? '';
                $name = $item['name'] ?? basename($path);
                $type = $item['type'] ?? pathinfo($path, PATHINFO_EXTENSION);
            }

            return [
                'path' => $path,
                'name' => $name,
                'type' => $type,
                'url' => \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
                    ? asset('storage/' . ltrim($path, '/'))
                    : asset($path),
            ];
        }, $decoded);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->father_name} {$this->last_name}");
        }
        return $this->name ?? '—';
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'guest_id');
    }
}




