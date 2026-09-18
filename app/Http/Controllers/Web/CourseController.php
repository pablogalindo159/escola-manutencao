<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function show(Request $request, Course $course)
    {
        abort_if($course->status !== 'published', 404);

        $isSubscribed = false;

        if (Auth::check()) {
            $isSubscribed = $request->user()->courses()->where('course_id', $course->id)->exists();
        }

        return view('courses.show', [
            'course' => $course,
            'isSubscribed' => $isSubscribed,
        ]);
    }
}
