<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Course;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Listar vídeos de um curso
     */
    public function index(Course $course)
    {
        $videos = $course->videos()->orderBy('order')->get();

        return view('admin.videos.index', [
            'course' => $course,
            'videos' => $videos,
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(Course $course)
    {
        $nextOrder = $course->videos()->max('order') + 1;

        return view('admin.videos.form', [
            'course' => $course,
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Salvar novo vídeo
     */
    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'duration_seconds' => 'required|integer|min:0',
            'order' => 'required|integer|min:0',
            'quality' => 'required|in:480p,720p,1080p',
            'thumbnail_url' => 'nullable|url',
            'material_url' => 'nullable|url',
        ]);

        $validated['course_id'] = $course->id;
        $validated['status'] = 'draft';

        Video::create($validated);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'Vídeo adicionado com sucesso! Publique-o quando estiver pronto.');
    }

    /**
     * Formulário de edição
     */
    public function edit(Course $course, Video $video)
    {
        return view('admin.videos.form', [
            'course' => $course,
            'video' => $video,
        ]);
    }

    /**
     * Atualizar vídeo
     */
    public function update(Request $request, Course $course, Video $video)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'duration_seconds' => 'required|integer|min:0',
            'order' => 'required|integer|min:0',
            'quality' => 'required|in:480p,720p,1080p',
            'thumbnail_url' => 'nullable|url',
            'material_url' => 'nullable|url',
            'status' => 'required|in:draft,published,archived',
        ]);

        $video->update($validated);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'Vídeo atualizado com sucesso!');
    }

    /**
     * Remover vídeo
     */
    public function destroy(Course $course, Video $video)
    {
        $video->delete();

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'Vídeo removido.');
    }
}
