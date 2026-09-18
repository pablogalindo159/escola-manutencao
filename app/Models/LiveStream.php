<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveStream extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'course_id',
        'youtube_video_id',
        'youtube_stream_url',
        'youtube_chat_url',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'viewers_count',
        'total_viewers',
        'likes',
        'allow_chat',
        'recorded',
        'thumbnail_url',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'allow_chat' => 'boolean',
        'recorded' => 'boolean',
    ];

    /**
     * Relacionamento com usuário (professor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com curso (opcional)
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Obter URL de embed do YouTube
     */
    public function getEmbedUrl(): string
    {
        if (!$this->youtube_video_id) {
            return '';
        }

        return "https://www.youtube.com/embed/{$this->youtube_video_id}?controls=1&modestbranding=1&rel=0";
    }

    /**
     * Obter URL completa do YouTube
     */
    public function getYoutubeUrl(): string
    {
        if (!$this->youtube_video_id) {
            return '';
        }

        return "https://www.youtube.com/watch?v={$this->youtube_video_id}";
    }

    /**
     * Verificar se está ao vivo agora
     */
    public function isLive(): bool
    {
        return $this->status === 'live' && 
               $this->started_at && 
               !$this->ended_at;
    }

    /**
     * Verificar se já começou
     */
    public function hasStarted(): bool
    {
        return in_array($this->status, ['live', 'ended', 'archived']) &&
               $this->started_at && 
               $this->started_at <= now();
    }

    /**
     * Verificar se pode começar
     */
    public function canStart(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now() &&
               $this->youtube_video_id;
    }

    /**
     * Marcar como ao vivo
     */
    public function markAsLive(): void
    {
        $this->update([
            'status' => 'live',
            'started_at' => now(),
        ]);
    }

    /**
     * Marcar como terminada
     */
    public function markAsEnded(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Marcar como arquivada (após processamento YouTube)
     */
    public function markAsArchived(): void
    {
        $this->update([
            'status' => 'archived',
        ]);
    }

    /**
     * Obter duração em minutos
     */
    public function getDurationMinutes(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->ended_at->diffInMinutes($this->started_at);
    }

    /**
     * Próximas transmissões agendadas
     */
    public static function upcoming($limit = 5)
    {
        return self::where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Transmissões ao vivo agora
     */
    public static function liveNow()
    {
        return self::where('status', 'live')
            ->where('started_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ended_at')
                      ->orWhere('ended_at', '>', now());
            })
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Transmissões recentes (últimos 7 dias)
     */
    public static function recent($limit = 10)
    {
        return self::whereIn('status', ['ended', 'archived'])
            ->where('ended_at', '>', now()->subDays(7))
            ->orderBy('ended_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
