<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'content',
        'likes_count',
        'is_pinned',
        'status',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'is_pinned' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'post_likes')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public function addLike($userId)
    {
        if (!$this->isLikedBy($userId)) {
            $this->likes()->attach($userId);
            $this->increment('likes_count');
        }
    }

    public function removeLike($userId)
    {
        if ($this->isLikedBy($userId)) {
            $this->likes()->detach($userId);
            $this->decrement('likes_count');
        }
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function pin()
    {
        $this->update(['is_pinned' => true]);
    }

    public function unpin()
    {
        $this->update(['is_pinned' => false]);
    }
}
