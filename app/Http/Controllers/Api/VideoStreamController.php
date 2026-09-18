<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoStreamController extends Controller
{
    /**
     * Obter URL de stream segura (token expira em 1 hora)
     * GET /api/videos/{id}/stream-url
     */
    public function getStreamUrl($id, Request $request)
    {
        $user = $request->user();
        $video = Video::with('course')->findOrFail($id);

        // 1) Verificar acesso ao curso
        if (!$this->userHasAccess($user, $video)) {
            $this->logAccess($user->id, $video->id, $request, 'access_denied');
            Log::warning("Acesso negado ao vídeo {$id} para usuário {$user->id}");

            return response()->json([
                'success' => false,
                'message' => 'Você não tem acesso a este vídeo',
            ], 403);
        }

        // 2) Verificar IP suspeito (possível compartilhamento de conta)
        if (!$this->validateIp($user, $request->ip())) {
            $this->logAccess($user->id, $video->id, $request, 'blocked');

            return response()->json([
                'success' => false,
                'message' => 'Acesso bloqueado. Múltiplos IPs detectados. Tente novamente em alguns minutos.',
            ], 403);
        }

        // 3) Rate limit (máx 5 requisições/minuto)
        $rateLimitKey = "stream_rate_limit:{$user->id}";
        $attemptCount = (int) Cache::get($rateLimitKey, 0);

        if ($attemptCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Muitas requisições. Espere 1 minuto.',
            ], 429);
        }

        Cache::put($rateLimitKey, $attemptCount + 1, now()->addMinute());

        // 4) Gerar token de stream (expira em 1 hora)
        $streamToken = Str::random(60);
        $expiresAt = now()->addHour();

        Cache::put("stream_token:{$streamToken}", [
            'user_id' => $user->id,
            'video_id' => $video->id,
            'ip' => $request->ip(),
        ], $expiresAt);

        $this->logAccess($user->id, $video->id, $request, 'stream_url_requested');

        return response()->json([
            'success' => true,
            'data' => [
                'stream_url' => route('videos.stream', ['id' => $video->id, 'token' => $streamToken]),
                'expires_in' => 3600,
                'expires_at' => $expiresAt->toIso8601String(),
                'video_title' => $video->title,
                'video_duration' => $video->duration_seconds,
                'quality' => $video->quality,
            ],
        ]);
    }

    /**
     * Stream do vídeo: valida o token e redireciona pra URL real (CDN/S3/YouTube)
     * GET /videos/{id}/stream?token=xxx
     *
     * O vídeo em si não fica armazenado no nosso servidor (video_url aponta
     * pra fora), então em vez de servir bytes locais, validamos o token e
     * redirecionamos - o proprio destino (S3, CDN, etc) ja suporta range
     * requests nativamente para permitir avançar/voltar no vídeo.
     */
    public function stream($id, Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['error' => 'Token obrigatório'], 401);
        }

        $cacheKey = "stream_token:{$token}";
        $streamData = Cache::get($cacheKey);

        if (!$streamData || (int) $streamData['video_id'] !== (int) $id) {
            Log::warning("Token inválido ou expirado para vídeo {$id}");

            return response()->json(['error' => 'Token inválido ou expirado'], 401);
        }

        if ($streamData['ip'] !== $request->ip()) {
            Log::warning("IP diferente detectado no stream do vídeo {$id}", [
                'esperado' => $streamData['ip'],
                'recebido' => $request->ip(),
            ]);

            Cache::put("blocked_user:{$streamData['user_id']}", true, now()->addHour());

            return response()->json([
                'error' => 'IP diferente. Acesso bloqueado temporariamente.',
            ], 403);
        }

        if (Cache::has("blocked_user:{$streamData['user_id']}")) {
            return response()->json(['error' => 'Sua conta está temporariamente bloqueada.'], 403);
        }

        $video = Video::findOrFail($id);

        if (!$video->video_url) {
            return response()->json(['error' => 'Vídeo não disponível'], 404);
        }

        $this->logAccess($streamData['user_id'], $video->id, $request, 'stream_played');

        return redirect()->away($video->video_url);
    }

    /**
     * Verificar se usuário tem acesso ao vídeo
     */
    private function userHasAccess(User $user, Video $video): bool
    {
        if ($user->role === 'admin' || $user->role === 'instructor') {
            return true;
        }

        if ($video->course && $video->course->type === 'free') {
            return true;
        }

        return $user->subscriptions()
            ->where('course_id', $video->course_id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Validar IP (evitar compartilhamento de conta)
     * Permite 1 IP por vez; troca de IP em menos de 5 min é suspeita
     */
    private function validateIp(User $user, string $currentIp): bool
    {
        $ipKey = "user_stream_ip:{$user->id}";
        $lastIp = Cache::get($ipKey);

        if (!$lastIp || $lastIp === $currentIp) {
            Cache::put($ipKey, $currentIp, now()->addMinutes(5));
            return true;
        }

        Log::warning('IP alternado suspeito', [
            'user_id' => $user->id,
            'last_ip' => $lastIp,
            'current_ip' => $currentIp,
        ]);

        return false;
    }

    /**
     * Registrar acesso no log (visível pro admin)
     */
    private function logAccess(int $userId, int $videoId, Request $request, string $action): void
    {
        try {
            DB::table('video_access_logs')->insert([
                'user_id' => $userId,
                'video_id' => $videoId,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'action' => $action,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Falha ao registrar log de acesso a vídeo: ' . $e->getMessage());
        }
    }

    /**
     * Histórico de acessos a um vídeo (admin)
     * GET /api/admin/videos/{id}/access-log
     */
    public function accessLog($id, Request $request)
    {
        $logs = DB::table('video_access_logs')
            ->where('video_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Bloquear usuário suspeito (admin)
     * POST /api/admin/users/{id}/block-stream
     */
    public function blockUser($userId, Request $request)
    {
        $hours = (int) $request->input('hours', 1);
        $reason = $request->input('reason', 'Comportamento suspeito');

        Cache::put("blocked_user:{$userId}", true, now()->addHours($hours));

        Log::warning('Usuário bloqueado de streaming', [
            'user_id' => $userId,
            'hours' => $hours,
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Usuário bloqueado por {$hours} hora(s)",
        ]);
    }
}
