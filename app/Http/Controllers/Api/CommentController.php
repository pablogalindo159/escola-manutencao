<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Listar comentários de um post
     * GET /api/posts/{postId}/comments
     */
    public function indexByPost(Request $request, $postId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 50);

            $comments = Comment::topLevel()
                ->where('post_id', $postId)
                ->with('author:id,name,avatar_url')
                ->with('replies.author:id,name,avatar_url')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $comments->items(),
                'pagination' => [
                    'total' => $comments->total(),
                    'per_page' => $comments->perPage(),
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar comentários',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter detalhes de um comentário
     * GET /api/comments/{id}
     */
    public function show(Comment $comment): JsonResponse
    {
        try {
            $comment->load('author:id,name,avatar_url');
            $comment->load('replies.author:id,name,avatar_url');

            $user = auth('api')->user();
            $isLiked = false;

            if ($user) {
                $isLiked = $comment->likes()->where('user_id', $user->id)->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'content' => $comment->content,
                    'author' => $comment->author,
                    'likes_count' => $comment->likes_count,
                    'is_liked_by_user' => $isLiked,
                    'replies' => $comment->replies,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do comentário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo comentário
     * POST /api/comments
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'post_id' => 'required|exists:posts,id',
                'content' => 'required|string|max:1000',
                'parent_comment_id' => 'integer|nullable|exists:comments,id',
            ]);

            $validated['user_id'] = $user->id;

            $comment = Comment::create($validated);
            $comment->load('author:id,name,avatar_url');

            // Notificar autor do post ou comentário pai
            if ($validated['parent_comment_id'] ?? false) {
                // TODO: Notificar autor do comentário pai
            } else {
                // TODO: Notificar autor do post
            }

            return response()->json([
                'success' => true,
                'message' => 'Comentário criado com sucesso',
                'data' => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar comentário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar comentário
     * PUT /api/comments/{id}
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($comment->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este comentário',
                ], 403);
            }

            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            $comment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Comentário atualizado com sucesso',
                'data' => $comment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar comentário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar comentário
     * DELETE /api/comments/{id}
     */
    public function destroy(Comment $comment): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($comment->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para deletar este comentário',
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentário deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar comentário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Curtir comentário
     * POST /api/comments/{id}/like
     */
    public function like(Comment $comment): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $comment->addLike($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Comentário curtido com sucesso',
                'likes_count' => $comment->likes_count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao curtir comentário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover curtida do comentário
     * DELETE /api/comments/{id}/like
     */
    public function unlike(Comment $comment): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $comment->removeLike($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Curtida removida com sucesso',
                'likes_count' => $comment->likes_count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover curtida',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
