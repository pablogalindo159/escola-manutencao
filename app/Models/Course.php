<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'thumbnail_url',
        'instructor_id',
        'price',
        'type',
        'duration_minutes',
        'category',
        'level',
        'rating',
        'status',
        'featured',
        'certificate_background',
        'certificate_logo',
        'certificate_signature',
        'certificate_hide_frame',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'float',
        'duration_minutes' => 'integer',
        'featured' => 'boolean',
        'certificate_hide_frame' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'subscriptions')
            ->withPivot('expires_at', 'status')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'subscriptions')
            ->withPivot('expires_at', 'status', 'type')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)->where('status', 'published');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Methods
    public function getTotalVideos()
    {
        return $this->videos()->count();
    }

    public function getTotalDuration()
    {
        return $this->videos()->sum('duration_seconds');
    }

    public function getStudentCount()
    {
        return $this->subscribers()
            ->where('subscriptions.status', 'active')
            ->count();
    }

    public function getAverageProgress($userId = null)
    {
        $query = $this->progress();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->avg('progress_percentage') ?? 0;
    }
}
