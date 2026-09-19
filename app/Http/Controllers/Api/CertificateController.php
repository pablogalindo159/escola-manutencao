<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Listar certificados do usuário
     * GET /api/certificates
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $perPage = $request->query('per_page', 20);

            $certificates = $user->certificates()
                ->with('course:id,title,category')
                ->orderBy('issued_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $certificates->items(),
                'pagination' => [
                    'total' => $certificates->total(),
                    'per_page' => $certificates->perPage(),
                    'current_page' => $certificates->currentPage(),
                    'last_page' => $certificates->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar certificados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter detalhes de um certificado
     * GET /api/certificates/{id}
     */
    public function show(Certificate $certificate): JsonResponse
    {
        try {
            // Verificar permissão
            $user = auth('api')->user();
            if ($certificate->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para ver este certificado',
                ], 403);
            }

            $certificate->load('user:id,name,cpf', 'course:id,title,category,duration_minutes');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $certificate->id,
                    'user_id' => $certificate->user_id,
                    'course_id' => $certificate->course_id,
                    'certificate_number' => $certificate->certificate_number,
                    'user' => $certificate->user,
                    'course' => $certificate->course,
                    'issued_at' => $certificate->issued_at,
                    'completion_percentage' => $certificate->completion_percentage,
                    'grade' => $certificate->grade,
                    'is_valid' => $certificate->isValid(),
                    'verification_url' => $certificate->getVerificationUrl(),
                    'download_url' => $certificate->downloadUrl(),
                    'created_at' => $certificate->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter detalhes do certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gerar certificado após conclusão do curso
     * POST /api/certificates
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);

            // Verificar se usuário completou o curso (80%+)
            $progress = $user->progress()
                ->where('course_id', $validated['course_id'])
                ->avg('progress_percentage') ?? 0;

            if ($progress < 80) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você precisa completar 80% do curso para gerar o certificado',
                ], 400);
            }

            // Verificar se já existe certificado
            $existing = Certificate::where('user_id', $user->id)
                ->where('course_id', $validated['course_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já tem um certificado para este curso',
                ], 400);
            }

            // Criar certificado
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'course_id' => $validated['course_id'],
                'certificate_number' => Certificate::generateCertificateNumber(),
                'issued_at' => now(),
                'completion_percentage' => $progress,
                'qr_code_data' => '',
            ]);

            // Gerar QR Code
            $qrData = $certificate->generateQRCode();
            $certificate->update(['qr_code_data' => $qrData]);

            // TODO: Gerar PDF do certificado

            return response()->json([
                'success' => true,
                'message' => 'Certificado gerado com sucesso',
                'data' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'issued_at' => $certificate->issued_at,
                    'download_url' => $certificate->downloadUrl(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar certificado por número
     * GET /api/certificates/verify/{number}
     */
    public function verify($certificateNumber): JsonResponse
    {
        try {
            $certificate = Certificate::where('certificate_number', $certificateNumber)->first();

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado não encontrado',
                ], 404);
            }

            $certificate->load('user:id,name', 'course:id,title');

            return response()->json([
                'success' => true,
                'data' => [
                    'certificate_number' => $certificate->certificate_number,
                    'student_name' => $certificate->user->name,
                    'course_title' => $certificate->course->title,
                    'issued_at' => $certificate->issued_at,
                    'is_valid' => $certificate->isValid(),
                    'completion_percentage' => $certificate->completion_percentage,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download do certificado em PDF
     * GET /api/certificates/{id}/download
     */
    public function download(Certificate $certificate)
    {
        try {
            // Verificar permissão
            $user = auth('api')->user();
            if ($certificate->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para fazer download deste certificado',
                ], 403);
            }

            // TODO: Gerar PDF e retornar
            // Por enquanto, retornar mensagem
            return response()->json([
                'success' => true,
                'message' => 'PDF será gerado em breve',
                'certificate_number' => $certificate->certificate_number,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer download do certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar certificado manualmente (ADMIN)
     * POST /api/admin/certificates
     */
    public function adminCreate(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar certificados',
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'completion_percentage' => 'required|numeric|between:0,100',
                'grade' => 'numeric|nullable|between:0,10',
            ]);

            $certificate = Certificate::create([
                'user_id' => $validated['user_id'],
                'course_id' => $validated['course_id'],
                'certificate_number' => Certificate::generateCertificateNumber(),
                'issued_at' => now(),
                'completion_percentage' => $validated['completion_percentage'],
                'grade' => $validated['grade'] ?? null,
                'qr_code_data' => '',
            ]);

            // Gerar QR Code
            $qrData = $certificate->generateQRCode();
            $certificate->update(['qr_code_data' => $qrData]);

            return response()->json([
                'success' => true,
                'message' => 'Certificado criado manualmente com sucesso',
                'data' => $certificate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
