<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Posts de todos os cursos do aluno (igual à comunidade do site).
     * GET /api/posts?course_id=opcional
     * Também devolve "courses" (id, title) para o filtro e o formulário do app.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $courses = $user->courses()
                ->where('courses.status', '!=', 'archived')
                ->get(['courses.id', 'courses.title']);
            $courseIds = $courses->pluck('id');

            $perPage = (int) $request->query('per_page', 20);
            $selectedCourseId = $request->query('course_id');

            $query = Post::active()
                ->whereIn('course_id', $courseIds)
                ->with('author:id,name,avatar_url')
                ->with('comments')
                ->orderBy('is_pinned', 'desc')
                ->orderBy('created_at', 'desc');

            if ($selectedCourseId) {
                $query->where('course_id', $selectedCourseId);
            }

            $posts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $posts->items(),
                'pagination' => [
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                ],
                'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar posts de um curso
     * GET /api/posts/course/{courseId}
     */
    public function indexByCourse(Request $request, $courseId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 20);
            $sortBy = $request->query('sort_by', 'recent'); // recent, popular

            $query = Post::active()
                ->where('course_id', $courseId)
                ->with('author:id,name,avatar_url')
                ->with('comments');

            // Ordenação
            if ($sortBy === 'popular') {
                $query->orderBy('likes_count', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Posts fixados primeiro
            $query->orderBy('is_pinned', 'desc');

            $posts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $posts->items(),
                'pagination' => [
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter detalhes de um post
     * GET /api/posts/{id}
     */
    public function show(Post $post): JsonResponse
    {
        try {
            $post->load([
                'author:id,name,avatar_url',
                'comments' => function ($query) {
                    $query->topLevel()->with('author:id,name,avatar_url')->with('replies');
                },
            ]);

            $user = auth('api')->user();
            $isLiked = false;

            if ($user) {
                $isLiked = $post->likes()->where('user_id', $user->id)->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $post->id,
                    'course_id' => $post->course_id,
                    'user_id' => $post->user_id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'author' => $post->author,
                    'likes_count' => $post->likes_count,
                    'is_liked_by_user' => $isLiked,
                    'comments_count' => $post->comments()->count(),
                    'comments' => $post->comments,
                    'is_pinned' => $post->is_pinned,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo post
     * POST /api/posts
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $validated['user_id'] = $user->id;
            $validated['status'] = 'published';

            $post = Post::create($validated);
            $post->load('author:id,name,avatar_url');

            return response()->json([
                'success' => true,
                'message' => 'Post criado com sucesso',
                'data' => $post,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar post
     * PUT /api/posts/{id}
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($post->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este post',
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'string|max:255',
                'content' => 'string',
            ]);

            $post->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Post atualizado com sucesso',
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar post
     * DELETE /api/posts/{id}
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($post->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para deletar este post',
                ], 403);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Curtir post
     * POST /api/posts/{id}/like
     */
    public function like(Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $post->addLike($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Post curtido com sucesso',
                'likes_count' => $post->likes_count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao curtir post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover curtida do post
     * DELETE /api/posts/{id}/like
     */
    public function unlike(Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $post->removeLike($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Curtida removida com sucesso',
                'likes_count' => $post->likes_count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover curtida',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fixar post (ADMIN)
     * POST /api/posts/{id}/pin
     */
    public function pin(Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para fixar posts',
                ], 403);
            }

            $post->pin();

            return response()->json([
                'success' => true,
                'message' => 'Post fixado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fixar post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover fixação do post (ADMIN)
     * DELETE /api/posts/{id}/pin
     */
    public function unpin(Post $post): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para desafixar posts',
                ], 403);
            }

            $post->unpin();

            return response()->json([
                'success' => true,
                'message' => 'Fixação do post removida com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover fixação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
