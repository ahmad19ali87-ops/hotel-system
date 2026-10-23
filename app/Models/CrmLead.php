<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    protected $fillable = [
        'name',
        'hotel_name',
        'phone',
        'email',
        'province',
        'city',
        'source',
        'status',
        'expected_revenue',
        'currency',
        'assigned_to',
        'hotel_id',
        'notes',
    ];

    protected $casts = [
        'expected_revenue' => 'float',
    ];

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedHotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'عميل محتمل جديد',
            'contacted' => 'تم التواصل الأولي',
            'demo_scheduled' => 'تم جدولة عرض توضيحي (Demo)',
            'proposal_sent' => 'تم إرسال العرض المالي',
            'won_converted' => 'تم إتمام الصفقة وتحويله لفندق مشترك 🎉',
            'lost' => 'صفقة خاسرة / غير مهتم',
            default => 'جديد',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'new' => 'bg-info text-dark',
            'contacted' => 'bg-primary text-white',
            'demo_scheduled' => 'bg-indigo-600 text-white',
            'proposal_sent' => 'bg-warning text-dark',
            'won_converted' => 'bg-success text-white',
            'lost' => 'bg-danger text-white',
            default => 'bg-secondary text-white',
        };
    }
}

