<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Repair;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepairControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $teacher;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'student']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->course = Course::factory()->create(['instructor_id' => $this->teacher->id]);
    }

    /**
     * Test create repair
     */
    public function test_student_can_create_repair()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/repairs', [
            'course_id' => $this->course->id,
            'equipment' => 'Compressor Industrial',
            'client_name' => 'Empresa XYZ',
            'problem_description' => 'Vazamento de ar comprimido',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'student_id',
            'course_id',
            'equipment',
            'status',
        ]);

        $this->assertDatabaseHas('repairs', [
            'student_id' => $this->user->id,
            'equipment' => 'Compressor Industrial',
        ]);
    }

    /**
     * Test update repair
     */
    public function test_student_can_update_repair()
    {
        $repair = Repair::factory()->create([
            'student_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/repairs/{$repair->id}",
            [
                'diagnosis' => 'Válvula de retenção desgastada',
                'status' => 'pending_review',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('repairs', [
            'id' => $repair->id,
            'diagnosis' => 'Válvula de retenção desgastada',
        ]);
    }

    /**
     * Test upload repair photos
     */
    public function test_student_can_upload_repair_photos()
    {
        $repair = Repair::factory()->create(['student_id' => $this->user->id]);

        // Simular upload de arquivo
        $file = \Illuminate\Http\UploadedFile::fake()->image('repair.jpg', 800, 600);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/repairs/{$repair->id}/photos",
            [
                'photo' => $file,
                'stage' => 'before',
            ]
        );

        $response->assertStatus(200);
    }

    /**
     * Test teacher can review repair
     */
    public function test_teacher_can_approve_repair()
    {
        $repair = Repair::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->teacher)->putJson(
            "/api/v1/repairs/{$repair->id}/approve",
            [
                'feedback' => 'Excelente trabalho!',
                'rating' => 5,
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('repairs', [
            'id' => $repair->id,
            'status' => 'approved',
            'rating' => 5,
        ]);
    }

    /**
     * Test teacher can reject repair
     */
    public function test_teacher_can_reject_repair()
    {
        $repair = Repair::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->teacher)->putJson(
            "/api/v1/repairs/{$repair->id}/reject",
            [
                'feedback' => 'Faltaram detalhes',
                'improvements_needed' => 'Descrever melhor cada etapa',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('repairs', [
            'id' => $repair->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Test list my repairs
     */
    public function test_student_can_list_their_repairs()
    {
        Repair::factory(3)->create(['student_id' => $this->user->id]);
        Repair::factory(2)->create(); // Outros alunos

        $response = $this->actingAs($this->user)->getJson('/api/v1/repairs');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test unauthorized teacher cannot review another's repair
     */
    public function test_teacher_cannot_review_other_course_repair()
    {
        $other_teacher = User::factory()->create(['role' => 'teacher']);
        $other_course = Course::factory()->create(['instructor_id' => $other_teacher->id]);
        
        $repair = Repair::factory()->create(['course_id' => $other_course->id]);

        $response = $this->actingAs($this->teacher)->putJson(
            "/api/v1/repairs/{$repair->id}/approve",
            ['feedback' => 'test', 'rating' => 5]
        );

        $response->assertStatus(403);
    }

    /**
     * Test repair status flow
     */
    public function test_repair_follows_correct_status_flow()
    {
        $repair = Repair::factory()->create(['student_id' => $this->user->id]);
        $this->assertEquals('draft', $repair->status);

        // Update to pending review
        $repair->update(['status' => 'pending_review']);
        $this->assertEquals('pending_review', $repair->fresh()->status);

        // Approve
        $repair->update(['status' => 'approved']);
        $this->assertEquals('approved', $repair->fresh()->status);
    }
}
