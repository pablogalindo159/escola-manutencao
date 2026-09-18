<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = $user->courses()->with(['videos' => function ($q) {
            $q->where('status', 'published');
        }])->get();

        $progressByCourse = [];
        foreach ($courses as $course) {
            $totalVideos = $course->videos->count();
            $completedVideos = UserProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('is_completed', true)
                ->count();

            $progressByCourse[$course->id] = $totalVideos > 0
                ? round(($completedVideos / $totalVideos) * 100)
                : 0;
        }

        return view('student.dashboard', [
            'courses' => $courses,
            'progressByCourse' => $progressByCourse,
        ]);
    }
}
