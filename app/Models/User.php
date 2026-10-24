<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'father_name',
        'last_name',
        'email',
        'google_id',
        'avatar',
        'phone',
        'bio',
        'job_title',
        'nationality',
        'birth_date',
        'id_type',
        'id_number',
        'national_id',
        'address',
        'attachments',
        'verification_code',
        'verification_code_sent_at',
        'verification_attempts',
        'is_verified',
        'password',
        'locked_until',
        'role',
        'hotel_id',
        'permissions',
    ];

    public function setPermissionsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['permissions'] = json_encode(array_values($value));
        } else {
            $this->attributes['permissions'] = $value;
        }
    }

    public function getPermissionsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getPermissionsListAttribute(): array
    {
        return $this->permissions ?? [];
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }
        return in_array($permission, $this->permissions_list, true);
    }

    /**
     * Check if user account is currently locked due to anti-brute force enforcement.
     */
    public function isLocked(): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'locked_until')) {
            return false;
        }
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Lock user account for specified duration and revoke all active tokens.
     */
    public function lockFor(int $minutes = 60, ?string $reason = null): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'locked_until')) {
            $this->update(['locked_until' => now()->addMinutes($minutes)]);
        }
        $this->tokens()->delete();
    }

    /**
     * Unlock user account immediately.
     */
    public function unlock(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'locked_until')) {
            $this->update(['locked_until' => null]);
        }
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . ltrim($this->avatar, '/'));
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=1d4ed8&color=ffffff&bold=true';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_sent_at' => 'datetime',
            'verification_attempts' => 'integer',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'is_verified' => 'boolean',
            'birth_date' => 'date',
        ];
    }

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

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function bookingsCreated()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function cleaningLogs()
    {
        return $this->hasMany(CleaningLog::class, 'user_id');
    }

    public function guestProfiles()
    {
        return $this->hasMany(Guest::class, 'user_id');
    }

    public function notifications()
    {
        $relation = $this->hasMany(AppNotification::class, 'user_id');

        // Isolation: Super admin & Platform admin only view platform-level notifications
        if (in_array($this->role, ['super_admin', 'platform_admin'], true)) {
            return $relation->where(function ($q) {
                $q->whereNull('hotel_id')
                  ->orWhereIn('type', [
                      'security_alert',
                      'crm_lead',
                      'subscription_request',
                      'support_ticket',
                      'broadcast_message',
                      'system',
                      'platform_announcement',
                  ]);
            });
        }

        // Isolation: Hotel staff and managers only view notifications for their hotel or platform broadcasts
        if ($this->hotel_id && !in_array($this->role, ['guest'], true)) {
            return $relation->where(function ($q) {
                $q->where('hotel_id', $this->hotel_id)
                  ->orWhereNull('hotel_id');
            });
        }

        return $relation;
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function unreadNotificationsCount(): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
            return 0;
        }

        return $this->unreadNotifications()->count();
    }

    public function isHotelOwner(): bool
    {
        return $this->role === 'hotel_owner';
    }

    public function unreadMessagesCount(): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('messages')) {
            return 0;
        }

        return \App\Models\Message::where('receiver_id', $this->id)
            ->where('is_read', false)
            ->count();
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }
}
