<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Catálogo com todos os cursos publicados, pra o aluno ver o que já
     * tem e o que ainda pode comprar/se inscrever.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Course::where('status', 'published')
            ->with('instructor:id,name,avatar_url')
            ->orderByDesc('created_at')
            ->get();

        $subscribedCourseIds = $user->courses()->pluck('courses.id')->toArray();

        return view('student.courses-index', [
            'courses' => $courses,
            'subscribedCourseIds' => $subscribedCourseIds,
        ]);
    }

    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['admin', 'instructor']);
        $isSubscribed = $user->courses()->where('course_id', $course->id)->exists();
        // Quem já tem acesso (comprou/se inscreveu) não perde o curso só
        // porque ele voltou pra rascunho pra edição - só "arquivado" tira
        // o acesso de quem já tinha. Curso ainda em rascunho continua
        // fora do alcance de quem nunca se inscreveu (mesmo se for grátis).
        $hasAccess = $isStaff
            || ($isSubscribed && $course->status !== 'archived')
            || ($course->type === 'free' && $course->status === 'published');

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
