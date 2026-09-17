<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'Senha@1234',
            'phone' => '11999999999',
            'professional_area' => 'Eletricista',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
        ]);
    }

    /**
     * Test register validation
     */
    public function test_register_requires_valid_email()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'João',
            'email' => 'invalid-email',
            'password' => 'Senha@1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /**
     * Test login success
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Senha@1234'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Senha@1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user',
        ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Credenciais inválidas']);
    }

    /**
     * Test password reset
     */
    public function test_user_can_request_password_reset()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Email enviado com sucesso']);
    }

    /**
     * Test token refresh
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    /**
     * Test logout
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }
}
