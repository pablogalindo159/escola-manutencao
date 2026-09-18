<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Course;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Detecta automaticamente titulo/duracao/thumbnail a partir da URL
     * (AJAX - chamado pelo botao "Detectar dados" no formulario)
     */
    public function detectMetadata(Request $request)
    {
        $validated = $request->validate([
            'video_url' => 'required|url',
        ]);

        $url = $validated['video_url'];

        // YouTube: usa oEmbed publico do proprio YouTube (sem precisar de chave de API)
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url)) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->get('https://www.youtube.com/oembed', [
                        'url' => $url,
                        'format' => 'json',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return response()->json([
                        'success' => true,
                        'title' => $data['title'] ?? null,
                        'thumbnail_url' => $data['thumbnail_url'] ?? null,
                        'duration_seconds' => null,
                        'note' => 'A duração de vídeos do YouTube não pode ser detectada automaticamente (precisaria de uma chave de API do Google). Preencha manualmente.',
                    ]);
                }
            } catch (\Exception $e) {
                // segue pro erro generico abaixo
            }

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível buscar os dados desse vídeo do YouTube. Confira se o link está certo.',
            ], 422);
        }

        // Arquivo direto (mp4, S3, CDN, etc): usa ffprobe pra ler a duracao
        // sem precisar baixar o arquivo inteiro (le so o cabecalho/indice)
        try {
            $process = new \Symfony\Component\Process\Process([
                'ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', $url,
            ]);
            $process->setTimeout(15);
            $process->run();

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput(), true);
                $duration = isset($data['format']['duration']) ? (int) round((float) $data['format']['duration']) : null;

                if ($duration) {
                    return response()->json([
                        'success' => true,
                        'duration_seconds' => $duration,
                        'title' => null,
                        'thumbnail_url' => null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // segue pro erro generico abaixo
        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível detectar os dados automaticamente pra esse link. Preencha manualmente.',
        ], 422);
    }

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
