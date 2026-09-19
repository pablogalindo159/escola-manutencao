<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $courseIds = $user->courses()->where('courses.status', 'published')->pluck('courses.id');
        $selectedCourseId = $request->query('course_id');

        $query = Post::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->with('author:id,name,avatar_url')
            ->withCount('comments')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');

        if ($selectedCourseId) {
            $query->where('course_id', $selectedCourseId);
        }

        $posts = $query->paginate(15)->withQueryString();
        $courses = $user->courses()->where('courses.status', 'published')->get();

        return view('student.community-index', [
            'posts' => $posts,
            'courses' => $courses,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $courses = $user->courses()->where('courses.status', 'published')->get();

        return view('student.community-form', [
            'courses' => $courses,
            'selectedCourseId' => $request->query('course_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $this->authorizeCourseAccess($request->user(), $validated['course_id']);

        $post = Post::create([
            'course_id' => $validated['course_id'],
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => 'published',
        ]);

        return redirect()->route('student.community.show', $post)
            ->with('success', 'Post publicado!');
    }

    public function show(Request $request, Post $post)
    {
        $this->authorizeCourseAccess($request->user(), $post->course_id);

        $post->load([
            'author:id,name,avatar_url',
            'comments' => function ($q) {
                $q->with('author:id,name,avatar_url')->orderBy('created_at');
            },
        ]);

        return view('student.community-show', [
            'post' => $post,
        ]);
    }

    public function comment(Request $request, Post $post)
    {
        $this->authorizeCourseAccess($request->user(), $post->course_id);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Comentário adicionado!');
    }

    public function like(Request $request, Post $post)
    {
        $user = $request->user();
        $this->authorizeCourseAccess($user, $post->course_id);

        if (!$post->likes()->where('user_id', $user->id)->exists()) {
            $post->likes()->attach($user->id);
            $post->increment('likes_count');
        }

        return back();
    }

    public function unlike(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->likes()->where('user_id', $user->id)->exists()) {
            $post->likes()->detach($user->id);
            $post->decrement('likes_count');
        }

        return back();
    }

    private function authorizeCourseAccess(User $user, int $courseId): void
    {
        if (in_array($user->role, ['admin', 'instructor'])) {
            return;
        }

        $hasAccess = $user->courses()
            ->where('courses.status', 'published')
            ->where('course_id', $courseId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Você precisa estar inscrito no curso pra participar da comunidade.');
        }
    }
}
