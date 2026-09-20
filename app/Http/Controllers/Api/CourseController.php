<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Listar todos os cursos (paginado)
     * GET /api/courses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);
            $category = $request->query('category');
            $level = $request->query('level');
            $search = $request->query('search');

            $query = Course::active();

            // Filtro por categoria
            if ($category) {
                $query->where('category', $category);
            }

            // Filtro por nível
            if ($level) {
                $query->where('level', $level);
            }

            // Busca por título ou descrição
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                      ->orWhere('description', 'like', "%$search%");
                });
            }

            $courses = $query->with('instructor:id,name,avatar_url')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $courses->items(),
                'pagination' => [
                    'total' => $courses->total(),
                    'per_page' => $courses->perPage(),
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar cursos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter detalhes de um curso
     * GET /api/courses/{id}
     */
    public function show(Course $course): JsonResponse
    {
        try {
            $course->load([
                'instructor:id,name,avatar_url,bio',
                'videos:id,course_id,title,duration_seconds,order,thumbnail_url,status',
            ]);

            $user = auth('api')->user();
            $isSubscribed = false;
            $progressPercentage = 0;
            $isStaff = $user && in_array($user->role, ['admin', 'instructor']);

            if (!$isStaff && $course->status === 'archived') {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso não encontrado',
                ], 404);
            }

            $visibleVideos = $isStaff
                ? $course->videos
                : $course->videos->where('status', 'published')->values();

            if ($user) {
                $isSubscribed = $user->courses()
                    ->where('course_id', $course->id)
                    ->exists();

                $progressPercentage = $user->progress()
                    ->where('course_id', $course->id)
                    ->avg('progress_percentage') ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail_url' => $course->thumbnail_url,
                    'price' => $course->price,
                    'type' => $course->type,
                    'duration_minutes' => $course->duration_minutes,
                    'category' => $course->category,
                    'level' => $course->level,
                    'rating' => $course->rating,
                    'featured' => $course->featured,
                    'instructor' => $course->instructor,
                    'videos' => $visibleVideos->sortBy('order')->values(),
                    'student_count' => $course->getStudentCount(),
                    'is_subscribed' => $isSubscribed,
                    'progress_percentage' => $progressPercentage,
                    'created_at' => $course->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar cursos em destaque
     * GET /api/courses/featured
     */
    public function featured(): JsonResponse
    {
        try {
            $courses = Course::featured()
                ->with('instructor:id,name,avatar_url')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $courses,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar cursos em destaque',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar cursos do usuário autenticado
     * GET /api/courses/my-courses
     */
    public function myCourses(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $perPage = $request->query('per_page', 20);

            $courses = $user->courses()
                ->where('courses.status', '!=', 'archived')
                ->with('instructor:id,name,avatar_url')
                ->with('progress')
                ->paginate($perPage);

            // Adicionar progresso
            $coursesWithProgress = $courses->items();
            foreach ($coursesWithProgress as $course) {
                $progress = $user->progress()
                    ->where('course_id', $course->id)
                    ->avg('progress_percentage') ?? 0;
                $course->user_progress = $progress;
            }

            return response()->json([
                'success' => true,
                'data' => $coursesWithProgress,
                'pagination' => [
                    'total' => $courses->total(),
                    'per_page' => $courses->perPage(),
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar meus cursos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo curso (ADMIN)
     * POST /api/courses
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar se é admin ou instructor
            if (!in_array($user->role, ['admin', 'instructor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar cursos',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'type' => 'required|in:free,paid',
                'category' => 'required|string|max:100',
                'level' => 'required|in:beginner,intermediate,advanced',
                'thumbnail_url' => 'nullable|url',
            ]);

            // Gerar slug
            $validated['slug'] = str_slug($validated['title']);
            $validated['instructor_id'] = $user->id;
            $validated['status'] = 'draft';

            $course = Course::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Curso criado com sucesso',
                'data' => $course,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar curso (ADMIN)
     * PUT /api/courses/{id}
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar se é o criador ou admin
            if ($course->instructor_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este curso',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'string|max:255',
                'description' => 'string',
                'price' => 'numeric|min:0',
                'type' => 'in:free,paid',
                'category' => 'string|max:100',
                'level' => 'in:beginner,intermediate,advanced',
                'thumbnail_url' => 'nullable|url',
                'status' => 'in:draft,published,archived',
                'featured' => 'boolean',
            ]);

            if (isset($validated['title'])) {
                $validated['slug'] = str_slug($validated['title']);
            }

            $course->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Curso atualizado com sucesso',
                'data' => $course,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar curso (ADMIN)
     * DELETE /api/courses/{id}
     */
    public function destroy(Course $course): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Apenas admin pode deletar
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para deletar cursos',
                ], 403);
            }

            $course->delete();

            return response()->json([
                'success' => true,
                'message' => 'Curso deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Se inscrever em um curso
     * POST /api/courses/{id}/subscribe
     */
    public function subscribe(Course $course): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Inscrição direta só serve pra curso GRÁTIS. Curso pago tem
            // que passar pelo checkout do Mercado Pago - sem essa checagem,
            // esse endpoint liberava qualquer curso pago de graça.
            if ($course->type !== 'free' || $course->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este curso é pago. Use o checkout para adquiri-lo.',
                ], 422);
            }

            // Verificar se já está inscrito
            if ($user->courses()->where('course_id', $course->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já está inscrito neste curso',
                ], 400);
            }

            // Criar inscrição
            $subscription = $user->subscriptions()->create([
                'course_id' => $course->id,
                'type' => 'lifetime',
                'price' => 0,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscrição realizada com sucesso',
                'data' => $subscription,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao se inscrever no curso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
