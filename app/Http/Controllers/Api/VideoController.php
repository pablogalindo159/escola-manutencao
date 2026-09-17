<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Obter detalhes de um vídeo
     * GET /api/videos/{id}
     */
    public function show(Video $video): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $progress = null;

            if ($user) {
                $progress = $user->progress()
                    ->where('video_id', $video->id)
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $video->id,
                    'course_id' => $video->course_id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'video_url' => $video->video_url,
                    'duration_seconds' => $video->duration_seconds,
                    'order' => $video->order,
                    'quality' => $video->quality,
                    'thumbnail_url' => $video->thumbnail_url,
                    'material_url' => $video->material_url,
                    'status' => $video->status,
                    'progress' => $progress ? [
                        'watched_seconds' => $progress->watched_seconds,
                        'progress_percentage' => $progress->progress_percentage,
                        'is_completed' => $progress->is_completed,
                        'completed_at' => $progress->completed_at,
                    ] : null,
                    'created_at' => $video->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do vídeo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar progresso do vídeo
     * POST /api/videos/{id}/progress
     */
    public function updateProgress(Request $request, Video $video): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'watched_seconds' => 'required|integer|min:0',
            ]);

            // Verificar se usuário está inscrito no curso
            $isSubscribed = $user->courses()
                ->where('course_id', $video->course_id)
                ->exists();

            if (!$isSubscribed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está inscrito neste curso',
                ], 403);
            }

            // Criar ou atualizar progresso
            $progress = UserProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'video_id' => $video->id,
                    'course_id' => $video->course_id,
                ],
                [
                    'watched_seconds' => 0,
                    'progress_percentage' => 0,
                    'is_completed' => false,
                ]
            );

            // Atualizar progresso
            $watchedSeconds = $validated['watched_seconds'];
            $percentage = ($watchedSeconds / $video->duration_seconds) * 100;
            $isCompleted = $percentage >= 80; // 80% = completo

            $progress->update([
                'watched_seconds' => $watchedSeconds,
                'progress_percentage' => min(100, $percentage),
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Progresso atualizado com sucesso',
                'data' => [
                    'watched_seconds' => $progress->watched_seconds,
                    'progress_percentage' => $progress->progress_percentage,
                    'is_completed' => $progress->is_completed,
                    'completed_at' => $progress->completed_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar progresso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter progresso do curso
     * GET /api/courses/{courseId}/progress
     */
    public function courseProgress($courseId): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $progress = $user->progress()
                ->where('course_id', $courseId)
                ->with('video:id,title,duration_seconds,order')
                ->get();

            // Calcular progresso geral
            $averageProgress = $user->progress()
                ->where('course_id', $courseId)
                ->avg('progress_percentage') ?? 0;

            $videosCompleted = $user->progress()
                ->where('course_id', $courseId)
                ->where('is_completed', true)
                ->count();

            $totalVideos = \App\Models\Video::where('course_id', $courseId)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $courseId,
                    'overall_progress' => $averageProgress,
                    'videos_completed' => $videosCompleted,
                    'total_videos' => $totalVideos,
                    'videos' => $progress,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter progresso do curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo vídeo (ADMIN)
     * POST /api/videos
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!in_array($user->role, ['admin', 'instructor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar vídeos',
                ], 403);
            }

            $validated = $request->validate([
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'description' => 'string|nullable',
                'video_url' => 'required|url',
                'duration_seconds' => 'required|integer|min:0',
                'order' => 'required|integer|min:0',
                'quality' => 'in:480p,720p,1080p',
                'thumbnail_url' => 'nullable|url',
                'material_url' => 'nullable|url',
            ]);

            $validated['status'] = 'draft';

            $video = Video::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vídeo criado com sucesso',
                'data' => $video,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar vídeo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar vídeo (ADMIN)
     * PUT /api/videos/{id}
     */
    public function update(Request $request, Video $video): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!in_array($user->role, ['admin', 'instructor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar vídeos',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'string|max:255',
                'description' => 'string|nullable',
                'video_url' => 'url',
                'duration_seconds' => 'integer|min:0',
                'order' => 'integer|min:0',
                'quality' => 'in:480p,720p,1080p',
                'thumbnail_url' => 'nullable|url',
                'material_url' => 'nullable|url',
                'status' => 'in:draft,published,archived',
            ]);

            $video->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vídeo atualizado com sucesso',
                'data' => $video,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar vídeo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar vídeo (ADMIN)
     * DELETE /api/videos/{id}
     */
    public function destroy(Video $video): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para deletar vídeos',
                ], 403);
            }

            $video->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vídeo deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar vídeo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
