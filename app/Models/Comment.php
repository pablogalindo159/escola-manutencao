<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'content',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parentComment()
    {
        return $this->belongsTo(Comment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_comment_id');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'comment_likes')
            ->withTimestamps();
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_comment_id');
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

    public function reply($userId, $content)
    {
        return $this->replies()->create([
            'user_id' => $userId,
            'content' => $content,
            'post_id' => $this->post_id,
        ]);
    }
}
