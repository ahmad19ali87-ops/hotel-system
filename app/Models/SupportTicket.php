<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'hotel_id',
        'user_id',
        'assigned_to',
        'subject',
        'department',
        'priority',
        'status',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportTicketMessage::class, 'ticket_id')->latestOfMany();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $next = self::max('id') + 1;
                $ticket->ticket_number = "TCK-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
            if (empty($ticket->last_reply_at)) {
                $ticket->last_reply_at = now();
            }
        });
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'طوارئ عاجلة 🔥',
            'high' => 'أولوية مرتفعة',
            'medium' => 'أولوية متوسطة',
            'low' => 'أولوية عادية',
            default => 'متوسطة',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'bg-danger text-white',
            'high' => 'bg-warning text-dark',
            'medium' => 'bg-primary text-white',
            'low' => 'bg-secondary text-white',
            default => 'bg-info text-white',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'جديدة ومفتوحة',
            'in_progress' => 'قيد المعالجة من الدعم',
            'waiting_customer' => 'بانتظار رد العميل',
            'resolved' => 'تم الحل بنجاح ✔',
            'closed' => 'مغلقة ومؤرشفة',
            default => 'مفتوحة',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-emerald-500 text-white',
            'in_progress' => 'bg-blue-600 text-white',
            'waiting_customer' => 'bg-amber-500 text-dark',
            'resolved' => 'bg-teal-600 text-white',
            'closed' => 'bg-slate-400 text-white',
            default => 'bg-slate-500 text-white',
        };
    }

    public function getDepartmentLabelAttribute(): string
    {
        return match ($this->department) {
            'technical' => 'الدعم التقني والتشغيلي',
            'billing' => 'الاشتراكات والفوترة',
            'sales' => 'المبيعات وتطوير الأعمال',
            'feature_request' => 'اقتراحات وتطوير النظام',
            default => 'استفسارات عامة',
        };
    }
}

