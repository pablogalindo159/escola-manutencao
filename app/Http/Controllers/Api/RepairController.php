<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    /**
     * Listar reparos do usuário
     * GET /api/repairs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $perPage = $request->query('per_page', 20);
            $status = $request->query('status');

            $query = $user->repairs();

            if ($status) {
                $query->where('status', $status);
            }

            $repairs = $query->with('photos')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $repairs->items(),
                'pagination' => [
                    'total' => $repairs->total(),
                    'per_page' => $repairs->perPage(),
                    'current_page' => $repairs->currentPage(),
                    'last_page' => $repairs->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar reparos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter detalhes de um reparo
     * GET /api/repairs/{id}
     */
    public function show(Repair $repair): JsonResponse
    {
        try {
            // Verificar permissão
            $user = auth('api')->user();
            if ($repair->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para ver este reparo',
                ], 403);
            }

            $repair->load('photos', 'user:id,name,avatar_url');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $repair->id,
                    'user' => $repair->user,
                    'equipment_type' => $repair->equipment_type,
                    'customer_name' => $repair->customer_name,
                    'defect_description' => $repair->defect_description,
                    'diagnosis' => $repair->diagnosis,
                    'measurements' => $repair->measurements,
                    'components_replaced' => $repair->components_replaced,
                    'solution' => $repair->solution,
                    'notes' => $repair->notes,
                    'status' => $repair->status,
                    'rating' => $repair->rating,
                    'instructor_feedback' => $repair->instructor_feedback,
                    'photos' => $repair->photos,
                    'created_at' => $repair->created_at,
                    'updated_at' => $repair->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do reparo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo reparo
     * POST /api/repairs
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'equipment_type' => 'required|string|max:255',
                'customer_name' => 'string|nullable|max:255',
                'defect_description' => 'required|string',
                'diagnosis' => 'string|nullable',
                'measurements' => 'json|nullable',
                'components_replaced' => 'json|nullable',
                'solution' => 'string|nullable',
                'notes' => 'string|nullable',
                'course_id' => 'integer|nullable|exists:courses,id',
            ]);

            $validated['user_id'] = $user->id;
            $validated['status'] = 'draft';

            $repair = Repair::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reparo criado com sucesso',
                'data' => $repair,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar reparo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar reparo
     * PUT /api/repairs/{id}
     */
    public function update(Request $request, Repair $repair): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($repair->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este reparo',
                ], 403);
            }

            // Se não está em draft, não pode editar
            if ($repair->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível editar um reparo que já foi enviado',
                ], 400);
            }

            $validated = $request->validate([
                'equipment_type' => 'string|max:255',
                'customer_name' => 'string|nullable|max:255',
                'defect_description' => 'string',
                'diagnosis' => 'string|nullable',
                'measurements' => 'json|nullable',
                'components_replaced' => 'json|nullable',
                'solution' => 'string|nullable',
                'notes' => 'string|nullable',
            ]);

            $repair->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reparo atualizado com sucesso',
                'data' => $repair,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar reparo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload de foto do reparo
     * POST /api/repairs/{id}/photos
     */
    public function uploadPhoto(Request $request, Repair $repair): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($repair->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para adicionar fotos a este reparo',
                ], 403);
            }

            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'stage' => 'required|in:before,during,after,diagnostic',
                'description' => 'string|nullable|max:255',
            ]);

            // Upload da foto
            $path = $request->file('photo')->store('repairs/' . $repair->id, 'public');

            // Criar registro da foto
            $photo = $repair->photos()->create([
                'photo_url' => '/storage/' . $path,
                'stage' => $validated['stage'],
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto adicionada com sucesso',
                'data' => $photo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar reparo para análise do professor
     * POST /api/repairs/{id}/submit
     */
    public function submitForReview(Repair $repair): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($repair->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para enviar este reparo',
                ], 403);
            }

            // Verificar se tem fotos
            if ($repair->photos()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adicione ao menos uma foto antes de enviar',
                ], 400);
            }

            $repair->submitForReview();

            return response()->json([
                'success' => true,
                'message' => 'Reparo enviado para análise com sucesso',
                'data' => $repair,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar reparo para análise',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar reparo
     * DELETE /api/repairs/{id}
     */
    public function destroy(Repair $repair): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Verificar permissão
            if ($repair->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para deletar este reparo',
                ], 403);
            }

            // Deletar fotos
            foreach ($repair->photos as $photo) {
                Storage::delete('public/repairs/' . $repair->id);
            }

            $repair->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reparo deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar reparo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprovar reparo (ADMIN/INSTRUCTOR)
     * POST /api/repairs/{id}/approve
     */
    public function approve(Request $request, Repair $repair): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!in_array($user->role, ['admin', 'instructor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para aprovar reparos',
                ], 403);
            }

            $validated = $request->validate([
                'feedback' => 'string|nullable|max:1000',
                'rating' => 'numeric|nullable|between:0,5',
            ]);

            $repair->approve();

            if ($validated['feedback'] ?? false) {
                $repair->addInstructorFeedback(
                    $validated['feedback'],
                    $validated['rating'] ?? null
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Reparo aprovado com sucesso',
                'data' => $repair,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aprovar reparo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
