<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'equipment_type',
        'customer_name',
        'defect_description',
        'diagnosis',
        'measurements',
        'components_replaced',
        'solution',
        'notes',
        'status',
        'rating',
        'instructor_feedback',
    ];

    protected $casts = [
        'measurements' => 'json',
        'components_replaced' => 'json',
        'rating' => 'float',
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

    public function photos()
    {
        return $this->hasMany(RepairPhoto::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public function addPhoto($path, $stage = 'before')
    {
        return $this->photos()->create([
            'photo_url' => $path,
            'stage' => $stage, // before, during, after, diagnostic
        ]);
    }

    public function submitForReview()
    {
        $this->update([
            'status' => 'pending_review',
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
        ]);
    }

    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'instructor_feedback' => $reason,
        ]);
    }

    public function addInstructorFeedback($feedback, $rating = null)
    {
        $this->update([
            'instructor_feedback' => $feedback,
            'rating' => $rating,
            'status' => 'reviewed',
        ]);
    }

    public function getPhotosByStage($stage)
    {
        return $this->photos()->where('stage', $stage)->get();
    }
}
