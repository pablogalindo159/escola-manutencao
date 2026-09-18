<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Video;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function watch(Request $request, Video $video)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $video);

        $video->loadMissing('course');

        $progress = UserProgress::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->first();

        // Próximo e vídeo anterior, pra navegar sem voltar pro curso
        $siblingVideos = $video->course->videos()
            ->where('status', 'published')
            ->orderBy('order')
            ->get();

        $currentIndex = $siblingVideos->search(fn ($v) => $v->id === $video->id);
        $nextVideo = $currentIndex !== false ? $siblingVideos->get($currentIndex + 1) : null;
        $previousVideo = $currentIndex !== false ? $siblingVideos->get($currentIndex - 1) : null;

        return view('student.video-watch', [
            'video' => $video,
            'progress' => $progress,
            'nextVideo' => $nextVideo,
            'previousVideo' => $previousVideo,
        ]);
    }

    /**
     * Redireciona pra URL real do vídeo, depois de checar acesso.
     * Protegido por sessão (cookie), diferente do endpoint da API
     * (que usa token porque o app não tem sessão de navegador).
     */
    public function stream(Request $request, Video $video)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $video);

        if (!$video->video_url) {
            abort(404, 'Vídeo não disponível');
        }

        return redirect()->away($video->video_url);
    }

    public function markComplete(Request $request, Video $video)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $video);

        UserProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $video->course_id,
                'video_id' => $video->id,
            ],
            [
                'watched_seconds' => $video->duration_seconds,
                'progress_percentage' => 100,
                'is_completed' => true,
                'completed_at' => now(),
            ]
        );

        return back()->with('success', 'Aula marcada como concluída!');
    }

    private function authorizeAccess(User $user, Video $video): void
    {
        if (in_array($user->role, ['admin', 'instructor'])) {
            return;
        }

        if ($video->status !== 'published') {
            abort(404);
        }

        $video->loadMissing('course');
        $hasAccess = $video->course && $video->course->type === 'free';

        if (!$hasAccess) {
            $hasAccess = $user->subscriptions()
                ->where('course_id', $video->course_id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();
        }

        if (!$hasAccess) {
            abort(403, 'Você não tem acesso a este vídeo.');
        }
    }
}
