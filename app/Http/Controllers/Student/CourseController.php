<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['admin', 'instructor']);
        $isSubscribed = $user->courses()->where('course_id', $course->id)->exists();
        $isPublished = $course->status === 'published';
        $hasAccess = $isStaff || ($isPublished && ($course->type === 'free' || $isSubscribed));

        if (!$hasAccess) {
            abort(403, 'Você precisa se inscrever neste curso para acessá-lo.');
        }

        $videos = $isStaff
            ? $course->videos()->orderBy('order')->get()
            : $course->videos()->where('status', 'published')->orderBy('order')->get();

        $completedVideoIds = UserProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->pluck('video_id')
            ->toArray();

        return view('student.course-show', [
            'course' => $course,
            'videos' => $videos,
            'completedVideoIds' => $completedVideoIds,
            'isSubscribed' => $isSubscribed,
        ]);
    }

    /**
     * Inscrição direta - só funciona pra cursos gratuitos. Cursos pagos
     * ainda dependem de integração com o Mercado Pago (nao implementada);
     * por enquanto, matricula em curso pago e feita manualmente pelo admin.
     */
    public function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->type !== 'free') {
            return back()->with('error', 'Este curso é pago. Entre em contato para saber como se inscrever.');
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return redirect()->route('student.courses.show', $course);
        }

        Subscription::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'lifetime',
            'price' => 0,
            'status' => 'active',
        ]);

        return redirect()->route('student.courses.show', $course)
            ->with('success', 'Inscrição realizada! Bons estudos 🎓');
    }
}
