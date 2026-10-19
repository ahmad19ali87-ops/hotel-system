<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Destination extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['name', 'image_url'];

    public function getNameAttribute(): string
    {
        return $this->title ?? '';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Direct external URL
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        if (Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . ltrim($this->image, '/'));
        }

        if (file_exists(public_path($this->image))) {
            return asset($this->image);
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}
