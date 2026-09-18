<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Repair;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // Métricas principais
        $metrics = [
            'total_users' => User::count(),
            'total_courses' => Course::count(),
            'active_subscriptions' => Subscription::where('status', 'active')
                ->where('expires_at', '>', now())
                ->count(),
            'total_revenue' => Payment::where('status', 'approved')
                ->sum('amount'),
            'completion_rate' => $this->getCompletionRate(),
            'active_students' => User::where('last_login_at', '>', now()->subDays(7))->count(),
        ];

        // Inscrições últimos 7 dias
        $inscriptions_7d = $this->getInscriptions7Days();

        // Cursos populares
        $popular_courses = Course::withCount('students')
            ->orderByDesc('students_count')
            ->take(5)
            ->get();

        // Últimas transações
        $recent_payments = Payment::with('user')
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Receita por método de pagamento
        $revenue_by_method = Payment::where('status', 'approved')
            ->whereNotNull('method')
            ->groupBy('method')
            ->select('method', DB::raw('SUM(amount) as total'))
            ->get();

        // Taxa de retenção
        $retention_rate = $this->getRetentionRate();

        return view('dashboard.admin-dashboard', [
            'metrics' => $metrics,
            'inscriptions_7d' => $inscriptions_7d,
            'popular_courses' => $popular_courses,
            'recent_payments' => $recent_payments,
            'revenue_by_method' => $revenue_by_method,
            'retention_rate' => $retention_rate,
        ]);
    }

    /**
     * Gerenciamento de cursos
     */
    public function courses()
    {
        $courses = Course::withCount('students')
            ->with('instructor')
            ->paginate(15);

        return view('admin.courses.index', [
            'courses' => $courses
        ]);
    }

    /**
     * Editar curso
     */
    public function editCourse(Course $course)
    {
        return view('admin.courses.edit', [
            'course' => $course
        ]);
    }

    /**
     * Atualizar curso
     */
    public function updateCourse(Course $course)
    {
        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,archived',
        ]);

        $course->update($validated);

        return redirect()->route('admin.courses.edit', $course)
            ->with('success', 'Curso atualizado com sucesso!');
    }

    /**
     * Gerenciamento de usuários
     */
    public function users()
    {
        $users = User::paginate(15);

        return view('admin.users.index', [
            'users' => $users
        ]);
    }

    /**
     * Detalhes do usuário
     */
    public function userDetails(User $user)
    {
        $user_courses = $user->courses()->with('progress')->paginate(10);
        $payments = $user->payments()->orderByDesc('created_at')->take(10)->get();
        $repairs = $user->repairs()->orderByDesc('created_at')->take(10)->get();

        return view('admin.users.details', [
            'user' => $user,
            'courses' => $user_courses,
            'payments' => $payments,
            'repairs' => $repairs,
        ]);
    }

    /**
     * Gerenciamento de pagamentos
     */
    public function payments()
    {
        $payments = Payment::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.payments.index', [
            'payments' => $payments
        ]);
    }

    /**
     * Refund
     */
    public function refund(Payment $payment)
    {
        if ($payment->status !== 'completed') {
            return redirect()->back()->with('error', 'Apenas pagamentos completos podem ser reembolsados');
        }

        // Integração com Mercado Pago API
        // TODO: Implementar chamada à API do Mercado Pago

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => request('reason'),
        ]);

        return redirect()->back()->with('success', 'Reembolso processado com sucesso!');
    }

    /**
     * Relatórios
     */
    public function reports()
    {
        $period = request('period', '30'); // days

        $report_data = [
            'period' => $period,
            'total_revenue' => $this->getTotalRevenue($period),
            'new_users' => User::where('created_at', '>', now()->subDays($period))->count(),
            'completed_courses' => DB::table('user_progress')
                ->where('completed_at', '>', now()->subDays($period))
                ->count(),
            'avg_session_duration' => $this->getAvgSessionDuration($period),
            'churn_rate' => $this->getChurnRate($period),
        ];

        return view('admin.reports.index', [
            'report_data' => $report_data
        ]);
    }

    /**
     * Helper functions
     */
    private function getCompletionRate()
    {
        $total = DB::table('user_progress')->count();
        $completed = DB::table('user_progress')
            ->where('completed_at', '!=', null)
            ->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    private function getInscriptions7Days()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = User::whereDate('created_at', $date)->count();
            $data[$date] = $count;
        }
        return $data;
    }

    private function getRetentionRate()
    {
        $active_last_month = User::where('last_login_at', '>', now()->subDays(30))->count();
        $total_users = User::count();

        return $total_users > 0 ? round(($active_last_month / $total_users) * 100, 2) : 0;
    }

    private function getTotalRevenue($days)
    {
        return Payment::where('status', 'completed')
            ->where('created_at', '>', now()->subDays($days))
            ->sum('amount');
    }

    private function getAvgSessionDuration($days)
    {
        // TODO: Implementar tracking de sessões
        return 0;
    }

    private function getChurnRate($days)
    {
        $inactive = User::where('last_login_at', '<', now()->subDays(30))->count();
        $total = User::count();

        return $total > 0 ? round(($inactive / $total) * 100, 2) : 0;
    }
}
