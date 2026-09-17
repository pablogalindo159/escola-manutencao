<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'video_id',
        'watched_seconds',
        'progress_percentage',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'watched_seconds' => 'integer',
        'progress_percentage' => 'float',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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

    public function video()
    {
        return $this->belongsTo(Video::class)->nullable();
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update([
            'is_completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    public function updateProgress($watchedSeconds, $videoDuration)
    {
        $percentage = ($watchedSeconds / $videoDuration) * 100;
        $isCompleted = $percentage >= 80; // 80% = completed

        $this->update([
            'watched_seconds' => $watchedSeconds,
            'progress_percentage' => min(100, $percentage),
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);
    }
}
