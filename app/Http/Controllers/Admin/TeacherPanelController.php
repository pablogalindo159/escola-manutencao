<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class TeacherPanelController extends Controller
{
    /**
     * Display teacher dashboard
     */
    public function index()
    {
        $teacher = auth()->user();

        // Reparos para avaliar
        $pending_repairs = Repair::where('instructor_id', $teacher->id)
            ->where('status', 'pending_review')
            ->with('student', 'course')
            ->orderByDesc('created_at')
            ->paginate(10);

        // Estatísticas
        $stats = [
            'total_repairs_reviewed' => Repair::where('instructor_id', $teacher->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->count(),
            'avg_rating' => Repair::where('instructor_id', $teacher->id)
                ->whereNotNull('rating')
                ->avg('rating'),
            'pending_count' => Repair::where('instructor_id', $teacher->id)
                ->where('status', 'pending_review')
                ->count(),
            'this_month_reviews' => Repair::where('instructor_id', $teacher->id)
                ->where('status', 'approved')
                ->where('created_at', '>', now()->startOfMonth())
                ->count(),
        ];

        // Gráfico de atividade
        $activity = $this->getMonthlyActivity($teacher->id);

        // Courses taught
        $courses = $teacher->courses()->withCount('students')->get();

        return view('teacher.dashboard', [
            'pending_repairs' => $pending_repairs,
            'stats' => $stats,
            'activity' => $activity,
            'courses' => $courses,
        ]);
    }

    /**
     * Show repair detail for review
     */
    public function repairDetail(Repair $repair)
    {
        // Autorizar apenas professor do curso
        if ($repair->course->instructor_id !== auth()->id()) {
            abort(403);
        }

        return view('teacher.repair-detail', [
            'repair' => $repair->load('student', 'course', 'photos'),
            'comments' => $repair->comments()->orderByDesc('created_at')->paginate(20),
        ]);
    }

    /**
     * Approve repair
     */
    public function approveRepair(Repair $repair)
    {
        // Validação
        if ($repair->course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = request()->validate([
            'feedback' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'certificate' => 'boolean',
        ]);

        $repair->update([
            'status' => 'approved',
            'instructor_feedback' => $validated['feedback'],
            'rating' => $validated['rating'],
            'reviewed_at' => now(),
        ]);

        // Gerar certificado se solicitado
        if ($validated['certificate']) {
            $repair->generateCertificate();
        }

        // Notificar aluno
        $repair->student->notify(new RepairApprovedNotification($repair));

        return redirect()->back()->with('success', 'Reparo aprovado! Certificado gerado.');
    }

    /**
     * Reject repair
     */
    public function rejectRepair(Repair $repair)
    {
        if ($repair->course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = request()->validate([
            'feedback' => 'required|string|max:1000',
            'improvements_needed' => 'required|string',
        ]);

        $repair->update([
            'status' => 'rejected',
            'instructor_feedback' => $validated['feedback'],
            'improvements_needed' => $validated['improvements_needed'],
            'reviewed_at' => now(),
        ]);

        // Notificar aluno
        $repair->student->notify(new RepairRejectedNotification($repair));

        return redirect()->back()->with('success', 'Feedback enviado ao aluno.');
    }

    /**
     * Add comment to repair
     */
    public function addComment(Repair $repair)
    {
        if ($repair->course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = request()->validate([
            'comment' => 'required|string|max:500',
        ]);

        $repair->comments()->create([
            'user_id' => auth()->id(),
            'body' => $validated['comment'],
        ]);

        return redirect()->back()->with('success', 'Comentário adicionado.');
    }

    /**
     * Export repairs as CSV
     */
    public function exportRepairs()
    {
        $teacher = auth()->user();
        $repairs = Repair::where('instructor_id', $teacher->id)
            ->with('student', 'course')
            ->get();

        $filename = "reparos-" . now()->format('Y-m-d') . ".csv";
        $handle = fopen('php://memory', 'w');

        // Headers
        fputcsv($handle, [
            'ID',
            'Aluno',
            'Curso',
            'Equipamento',
            'Data',
            'Status',
            'Rating',
            'Feedback'
        ]);

        // Data
        foreach ($repairs as $repair) {
            fputcsv($handle, [
                $repair->id,
                $repair->student->name,
                $repair->course->title,
                $repair->equipment,
                $repair->created_at->format('d/m/Y'),
                $repair->status,
                $repair->rating,
                substr($repair->instructor_feedback ?? '', 0, 100),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Get my students
     */
    public function students()
    {
        $teacher = auth()->user();
        $students = User::whereHas('courses', function ($q) use ($teacher) {
            $q->where('instructor_id', $teacher->id);
        })
        ->with('courses')
        ->paginate(20);

        return view('teacher.students', [
            'students' => $students,
        ]);
    }

    /**
     * Student detail
     */
    public function studentDetail(User $student)
    {
        $teacher = auth()->user();

        // Verificar se o professor tem relação com este aluno
        if (!$teacher->courses()->whereHas('students', function ($q) use ($student) {
            $q->where('users.id', $student->id);
        })->exists()) {
            abort(403);
        }

        $repairs = $student->repairs()
            ->whereHas('course', function ($q) use ($teacher) {
                $q->where('instructor_id', $teacher->id);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('teacher.student-detail', [
            'student' => $student,
            'repairs' => $repairs,
        ]);
    }

    /**
     * Helper functions
     */
    private function getMonthlyActivity($teacher_id)
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Repair::where('instructor_id', $teacher_id)
                ->where('status', 'approved')
                ->whereDate('reviewed_at', $date)
                ->count();
            $data[$date] = $count;
        }
        return $data;
    }
}
