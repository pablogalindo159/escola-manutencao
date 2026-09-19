<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Criar usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'cpf' => $request->cpf,
                'role' => 'student',
                'status' => 'active',
            ]);

            // Gerar token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'cpf' => $user->cpf,
                    'bio' => $user->bio,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar_url' => $user->avatar_url,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login do usuário
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            // Tentar autenticar
            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha inválidos',
                ], 401);
            }

            // Atualizar last_login_at
            $user = auth('api')->user();
            $user->update(['last_login_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'cpf' => $user->cpf,
                    'bio' => $user->bio,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar_url' => $user->avatar_url,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer login',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Renovar token JWT
     * POST /api/auth/refresh-token
     */
    public function refreshToken(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token renovado com sucesso',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao renovar token',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Verificar quanto tempo falta pro token expirar
     * GET /api/auth/token-status
     */
    public function tokenStatus(): JsonResponse
    {
        try {
            $payload = auth('api')->payload();
            $expiresAt = $payload->get('exp');
            $secondsLeft = $expiresAt - now()->timestamp;

            return response()->json([
                'success' => true,
                'expires_in' => max(0, $secondsLeft),
                'expires_at' => \Carbon\Carbon::createFromTimestamp($expiresAt)->toIso8601String(),
                'should_refresh' => $secondsLeft < 300,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar token',
            ], 500);
        }
    }

    /**
     * Logout do usuário
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter dados do usuário autenticado
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'cpf' => $user->cpf,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter dados do usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar perfil do usuário
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $validated = $request->validate([
                'name' => 'string|max:255',
                'phone' => 'string|max:20',
                'bio' => 'string|nullable|max:500',
                'avatar_url' => 'string|nullable|url',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_url,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alterar senha
     * PUT /api/auth/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth('api')->user();

            // Verificar senha atual
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual inválida',
                ], 401);
            }

            // Atualizar senha
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
