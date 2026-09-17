<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'video_url',
        'duration_seconds',
        'order',
        'quality',
        's3_key',
        'thumbnail_url',
        'status',
        'material_url',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByQuality($query, $quality)
    {
        return $query->where('quality', $quality);
    }

    // Methods
    public function getProgress($userId)
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->first();
    }

    public function isWatchedBy($userId)
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->exists();
    }

    public function getViewCount()
    {
        return $this->progress()
            ->where('watched_seconds', '>', 0)
            ->distinct('user_id')
            ->count();
    }
}
