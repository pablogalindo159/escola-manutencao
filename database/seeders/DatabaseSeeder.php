<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuário admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Senha123!'),
                'role' => 'admin',  // ✅ ADICIONA ROLE ADMIN
                'status' => 'active',
            ]
        );

        // Criar cursos
        $this->call(CourseSeeder::class);
    }
}
