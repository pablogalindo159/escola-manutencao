<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\Course;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    /**
     * Listar transmissões (com abas por status)
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'scheduled');

        $stats = [
            'upcoming' => LiveStream::where('status', 'scheduled')
                ->where('scheduled_at', '>', now())->count(),
            'live' => LiveStream::where('status', 'live')->count(),
            'total_viewers' => LiveStream::sum('total_viewers'),
            'archived' => LiveStream::where('status', 'archived')->count(),
        ];

        $streams = LiveStream::where('status', $status)
            ->with('user')
            ->orderBy('scheduled_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.live-streams.index', [
            'streams' => $streams,
            'stats' => $stats,
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create()
    {
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.live-streams.form', [
            'courses' => $courses,
        ]);
    }

    /**
     * Salvar nova transmissão
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'nullable|integer|exists:courses,id',
            'scheduled_at' => 'required|date',
            'youtube_video_id' => 'nullable|string|max:50',
            'allow_chat' => 'nullable|boolean',
            'recorded' => 'nullable|boolean',
        ]);

        LiveStream::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'youtube_video_id' => $validated['youtube_video_id'] ?? null,
            'allow_chat' => $request->boolean('allow_chat', true),
            'recorded' => $request->boolean('recorded', true),
            'status' => 'scheduled',
        ]);

        return redirect()->route('admin.live-streams.index')
            ->with('success', 'Transmissão agendada com sucesso!');
    }

    /**
     * Formulário de edição
     */
    public function edit(LiveStream $liveStream)
    {
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.live-streams.form', [
            'stream' => $liveStream,
            'courses' => $courses,
        ]);
    }

    /**
     * Atualizar transmissão
     */
    public function update(Request $request, LiveStream $liveStream)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'nullable|integer|exists:courses,id',
            'scheduled_at' => 'required|date',
            'youtube_video_id' => 'nullable|string|max:50',
            'status' => 'required|in:scheduled,live,ended,archived',
            'allow_chat' => 'nullable|boolean',
            'recorded' => 'nullable|boolean',
        ]);

        $validated['allow_chat'] = $request->boolean('allow_chat');
        $validated['recorded'] = $request->boolean('recorded');

        $liveStream->update($validated);

        return redirect()->route('admin.live-streams.edit', $liveStream)
            ->with('success', 'Transmissão atualizada com sucesso!');
    }

    /**
     * Iniciar transmissão
     */
    public function start(LiveStream $liveStream)
    {
        if (!$liveStream->youtube_video_id) {
            return back()->with('error', 'Informe o YouTube Video ID antes de iniciar.');
        }

        $liveStream->markAsLive();

        return back()->with('success', 'Transmissão iniciada!');
    }

    /**
     * Finalizar transmissão
     */
    public function end(LiveStream $liveStream)
    {
        $liveStream->markAsEnded();

        return back()->with('success', 'Transmissão finalizada!');
    }

    /**
     * Deletar transmissão
     */
    public function destroy(LiveStream $liveStream)
    {
        $liveStream->delete();

        return redirect()->route('admin.live-streams.index')
            ->with('success', 'Transmissão removida.');
    }
}
