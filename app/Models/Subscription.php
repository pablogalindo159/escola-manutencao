<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'type',
        'price',
        'expires_at',
        'mercado_pago_subscription_id',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('expires_at', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function isActive()
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    public function isExpired()
    {
        return !$this->isActive();
    }

    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    public function renew($newExpireDate = null)
    {
        $this->update([
            'status' => 'active',
            'expires_at' => $newExpireDate ?? now()->addMonth(),
        ]);
    }
}
