<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveStream;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LiveStreamController extends Controller
{
    /**
     * Listar transmissões ao vivo
     * GET /api/live-streams
     */
    public function index(Request $request)
    {
        $status = $request->query('status', null);
        
        $query = LiveStream::with('user');

        if ($status) {
            $query->where('status', $status);
        }

        $streams = $query->orderBy('scheduled_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $streams,
        ]);
    }

    /**
     * Obter transmissões ao vivo agora
     * GET /api/live-streams/live-now
     */
    public function liveNow()
    {
        $streams = LiveStream::liveNow()
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'count' => count($streams),
            'data' => $streams,
        ]);
    }

    /**
     * Obter próximas transmissões agendadas
     * GET /api/live-streams/upcoming
     */
    public function upcoming(Request $request)
    {
        $limit = $request->query('limit', 5);
        $streams = LiveStream::upcoming($limit)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'count' => count($streams),
            'data' => $streams,
        ]);
    }

    /**
     * Obter transmissões recentes (últimos 7 dias)
     * GET /api/live-streams/recent
     */
    public function recent(Request $request)
    {
        $limit = $request->query('limit', 10);
        $streams = LiveStream::recent($limit)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'count' => count($streams),
            'data' => $streams,
        ]);
    }

    /**
     * Obter detalhe de uma transmissão
     * GET /api/live-streams/{id}
     */
    public function show($id)
    {
        $stream = LiveStream::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $stream,
            'embed_url' => $stream->getEmbedUrl(),
            'youtube_url' => $stream->getYoutubeUrl(),
            'is_live' => $stream->isLive(),
            'duration_minutes' => $stream->getDurationMinutes(),
        ]);
    }

    /**
     * Criar nova transmissão agendada
     * POST /api/live-streams
     * 
     * Requer:
     * - title: string
     * - description: string (opcional)
     * - course_id: string (opcional)
     * - scheduled_at: datetime (ISO 8601)
     * - youtube_video_id: string (opcional, pode ser preenchido depois)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Apenas professores podem criar transmissões
        if ($user->role !== 'instructor' && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas professores podem criar transmissões ao vivo',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'nullable|integer|exists:courses,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s|after:now',
            'youtube_video_id' => 'nullable|string|max:50',
            'allow_chat' => 'nullable|boolean',
            'recorded' => 'nullable|boolean',
        ]);

        $stream = LiveStream::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'youtube_video_id' => $validated['youtube_video_id'] ?? null,
            'allow_chat' => $validated['allow_chat'] ?? true,
            'recorded' => $validated['recorded'] ?? true,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transmissão agendada com sucesso!',
            'data' => $stream,
        ], 201);
    }

    /**
     * Atualizar transmissão
     * PUT /api/live-streams/{id}
     */
    public function update(Request $request, $id)
    {
        $stream = LiveStream::findOrFail($id);
        $user = Auth::user();

        // Apenas o criador ou admin pode atualizar
        if ($stream->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para atualizar esta transmissão',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'nullable|integer|exists:courses,id',
            'scheduled_at' => 'nullable|date_format:Y-m-d H:i:s',
            'youtube_video_id' => 'nullable|string|max:50',
            'status' => 'nullable|in:scheduled,live,ended,archived',
            'allow_chat' => 'nullable|boolean',
            'recorded' => 'nullable|boolean',
            'thumbnail_url' => 'nullable|url',
        ]);

        $stream->update(array_filter($validated));

        return response()->json([
            'success' => true,
            'message' => 'Transmissão atualizada com sucesso!',
            'data' => $stream,
        ]);
    }

    /**
     * Iniciar transmissão (marcar como ao vivo)
     * POST /api/live-streams/{id}/start
     */
    public function start($id)
    {
        $stream = LiveStream::findOrFail($id);
        $user = Auth::user();

        if ($stream->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão',
            ], 403);
        }

        if (!$stream->youtube_video_id) {
            return response()->json([
                'success' => false,
                'message' => 'YouTube Video ID é obrigatório para iniciar',
            ], 400);
        }

        $stream->markAsLive();

        return response()->json([
            'success' => true,
            'message' => 'Transmissão iniciada!',
            'data' => $stream,
        ]);
    }

    /**
     * Finalizar transmissão
     * POST /api/live-streams/{id}/end
     */
    public function end($id)
    {
        $stream = LiveStream::findOrFail($id);
        $user = Auth::user();

        if ($stream->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão',
            ], 403);
        }

        $stream->markAsEnded();

        return response()->json([
            'success' => true,
            'message' => 'Transmissão finalizada!',
            'data' => $stream,
        ]);
    }

    /**
     * Arquivar transmissão (após YouTube processar)
     * POST /api/live-streams/{id}/archive
     */
    public function archive($id)
    {
        $stream = LiveStream::findOrFail($id);
        $user = Auth::user();

        if ($stream->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão',
            ], 403);
        }

        $stream->markAsArchived();

        return response()->json([
            'success' => true,
            'message' => 'Transmissão arquivada!',
            'data' => $stream,
        ]);
    }

    /**
     * Deletar transmissão
     * DELETE /api/live-streams/{id}
     */
    public function destroy($id)
    {
        $stream = LiveStream::findOrFail($id);
        $user = Auth::user();

        if ($stream->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão',
            ], 403);
        }

        $stream->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transmissão deletada!',
        ]);
    }

    /**
     * Atualizar contagem de visualizadores
     * POST /api/live-streams/{id}/viewers
     */
    public function updateViewers($id, Request $request)
    {
        $stream = LiveStream::findOrFail($id);

        $validated = $request->validate([
            'viewers_count' => 'required|integer|min:0',
        ]);

        $stream->update([
            'viewers_count' => $validated['viewers_count'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $stream,
        ]);
    }

    /**
     * Obter estatísticas de uma transmissão
     * GET /api/live-streams/{id}/stats
     */
    public function stats($id)
    {
        $stream = LiveStream::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $stream->title,
                'status' => $stream->status,
                'viewers_count' => $stream->viewers_count,
                'total_viewers' => $stream->total_viewers,
                'likes' => $stream->likes,
                'duration_minutes' => $stream->getDurationMinutes(),
                'started_at' => $stream->started_at,
                'ended_at' => $stream->ended_at,
                'url' => $stream->getYoutubeUrl(),
            ],
        ]);
    }
}
