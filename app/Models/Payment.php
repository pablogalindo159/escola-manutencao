<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'subscription_id',
        'amount',
        'mercado_pago_payment_id',
        'mercado_pago_preference_id',
        'mercado_pago_order_id',         // ✅ NOVO: para PIX Transparente
        'external_reference',             // ✅ NOVO: para webhook tracking
        'status',
        'method',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'json',
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

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    // Methods
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function markAsApproved()
    {
        $this->update([
            'status' => 'approved',
            'paid_at' => now(),
        ]);

        // Activate subscription
        if ($this->subscription) {
            $this->subscription->update(['status' => 'active']);
        }
    }

    public function markAsRejected($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['rejection_reason'] = $reason;

        $this->update([
            'status' => 'rejected',
            'metadata' => $metadata,
        ]);
    }
}
