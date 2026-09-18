<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CourseController extends Controller
{
    public function show(Course $course)
    {
        abort_if($course->status !== 'published', 404);

        return view('courses.show', [
            'course' => $course,
        ]);
    }
}
