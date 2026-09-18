<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['admin', 'instructor']);
        $isSubscribed = $user->courses()->where('course_id', $course->id)->exists();
        $hasAccess = $isStaff || $course->type === 'free' || $isSubscribed;

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
}
