<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'subscription_id',
        'amount',
        'mercado_pago_payment_id',
        'mercado_pago_order_id',
        'mercado_pago_preference_id',
        'external_reference',
        'status',
        'qr_code',
        'qr_code_base64',
        'method',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'json',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subscription(): BelongsTo
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
    public function markAsApproved(): void
    {
        if ($this->status !== 'approved') {
            $this->update(['status' => 'approved', 'paid_at' => now()]);
        }

        if ($this->course_id && $this->user_id) {
            $this->user->courses()->syncWithoutDetaching([$this->course_id]);
            \Log::info('✅ Usuário adicionado ao curso', [
                'user_id' => $this->user_id,
                'course_id' => $this->course_id,
            ]);
        }
    }

    public function markAsRejected(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function markAsPending(): void
    {
        $this->update(['status' => 'pending']);
    }
}
