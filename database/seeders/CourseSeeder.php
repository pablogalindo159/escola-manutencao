<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\User;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::first();

        if (!$instructor) {
            return;
        }

        $courses = [
            [
                'title' => 'Manutenção de Notebooks',
                'description' => 'Aprenda a diagnosticar e reparar notebooks do zero, desde troca de componentes até formatação e otimização.',
                'slug' => 'manutencao-de-notebooks',
                'instructor_id' => $instructor->id,
                'price' => 297.00,
                'type' => 'paid',
                'duration_minutes' => 1440,
                'category' => 'Notebooks',
                'level' => 'beginner',
                'rating' => 4.8,
                'status' => 'published',
                'featured' => true,
            ],
            [
                'title' => 'Reparo de Smartphones',
                'description' => 'Curso completo de reparo de smartphones: troca de tela, bateria, placa e diagnóstico avançado.',
                'slug' => 'reparo-de-smartphones',
                'instructor_id' => $instructor->id,
                'price' => 247.00,
                'type' => 'paid',
                'duration_minutes' => 1080,
                'category' => 'Smartphones',
                'level' => 'intermediate',
                'rating' => 4.6,
                'status' => 'published',
                'featured' => true,
            ],
            [
                'title' => 'Diagnóstico Eletrônico',
                'description' => 'Técnicas avançadas de diagnóstico eletrônico com testadores profissionais e leitura de esquemas.',
                'slug' => 'diagnostico-eletronico',
                'instructor_id' => $instructor->id,
                'price' => 347.00,
                'type' => 'paid',
                'duration_minutes' => 1800,
                'category' => 'Eletrônica',
                'level' => 'advanced',
                'rating' => 4.9,
                'status' => 'published',
                'featured' => true,
            ],
        ];

        foreach ($courses as $courseData) {
            Course::firstOrCreate(
                ['slug' => $courseData['slug']],
                $courseData
            );
        }
    }
}
