<?php

namespace App\Features;

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Repair;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Sistema de Analytics - Métricas de negócio e insights
 */
class AnalyticsFeature
{
    /**
     * Dashboard Principal
     */
    public static function getDashboardMetrics(): array
    {
        return [
            'revenue' => self::getRevenueMetrics(),
            'users' => self::getUserMetrics(),
            'courses' => self::getCourseMetrics(),
            'repairs' => self::getRepairMetrics(),
            'retention' => self::getRetentionMetrics(),
        ];
    }

    /**
     * Métricas de Receita
     */
    public static function getRevenueMetrics(): array
    {
        $total_revenue = Payment::where('status', 'completed')->sum('amount');

        $revenue_today = Payment::where('status', 'completed')
            ->whereDate('created_at', now())
            ->sum('amount');

        $revenue_this_month = Payment::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $revenue_this_year = Payment::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Receita por método
        $by_method = Payment::where('status', 'completed')
            ->groupBy('payment_method')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->get()
            ->pluck('total', 'payment_method')
            ->toArray();

        // Receita por curso
        $by_course = Payment::where('status', 'completed')
            ->groupBy('course_id')
            ->select('course_id', DB::raw('SUM(amount) as total'))
            ->with('course:id,title')
            ->get();

        // Trend (últimos 30 dias)
        $trend = self::getRevenueTrend(30);

        // MRR (Monthly Recurring Revenue)
        $mrr = Payment::where('status', 'completed')
            ->where('expires_at', '>', now())
            ->sum('amount');

        // Churn rate
        $churned = Payment::where('status', 'completed')
            ->where('expires_at', '<', now())
            ->where('expires_at', '>', now()->subMonth())
            ->count();

        $active = Payment::where('status', 'completed')
            ->where('expires_at', '>', now())
            ->count();

        $churn_rate = $active > 0 ? ($churned / $active) * 100 : 0;

        return [
            'total' => $total_revenue,
            'today' => $revenue_today,
            'this_month' => $revenue_this_month,
            'this_year' => $revenue_this_year,
            'by_method' => $by_method,
            'by_course' => $by_course,
            'trend' => $trend,
            'mrr' => $mrr,
            'churn_rate' => round($churn_rate, 2),
        ];
    }

    /**
     * Métricas de Usuários
     */
    public static function getUserMetrics(): array
    {
        $total = User::count();
        $active_students = User::where('role', 'student')
            ->where('last_login', '>', now()->subDays(7))
            ->count();

        $new_this_month = User::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $growth_rate = self::getMonthlyGrowthRate();

        // Breakdown by role
        $by_role = User::groupBy('role')
            ->select('role', DB::raw('COUNT(*) as count'))
            ->get()
            ->pluck('count', 'role')
            ->toArray();

        // Engagement
        $highly_engaged = User::where('role', 'student')
            ->where('points', '>', 500)
            ->count();

        return [
            'total' => $total,
            'active_students' => $active_students,
            'new_this_month' => $new_this_month,
            'growth_rate' => round($growth_rate, 2),
            'by_role' => $by_role,
            'highly_engaged_count' => $highly_engaged,
            'engagement_rate' => round(($highly_engaged / $total) * 100, 2),
        ];
    }

    /**
     * Métricas de Cursos
     */
    public static function getCourseMetrics(): array
    {
        return Course::with('students')
            ->get()
            ->map(function ($course) {
                $students = $course->students()->count();
                $completed = $course->progress()
                    ->where('completed_at', '!=', null)
                    ->count();

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'enrolled' => $students,
                    'completed' => $completed,
                    'completion_rate' => $students > 0 ? round(($completed / $students) * 100, 2) : 0,
                    'revenue' => Payment::where('course_id', $course->id)
                        ->where('status', 'completed')
                        ->sum('amount'),
                    'avg_rating' => round($course->reviews()->avg('rating'), 1),
                    'total_reviews' => $course->reviews()->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->toArray();
    }

    /**
     * Métricas de Reparos
     */
    public static function getRepairMetrics(): array
    {
        $total = Repair::count();
        $approved = Repair::where('status', 'approved')->count();
        $pending = Repair::where('status', 'pending_review')->count();
        $rejected = Repair::where('status', 'rejected')->count();

        $avg_rating = Repair::whereNotNull('rating')->avg('rating');
        $time_to_review = self::getAvgTimeToReview();

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 2) : 0,
            'avg_rating' => round($avg_rating, 1),
            'avg_review_time_hours' => round($time_to_review, 1),
        ];
    }

    /**
     * Métricas de Retenção
     */
    public static function getRetentionMetrics(): array
    {
        $cohorts = self::getCohortRetention();

        return [
            'day_1' => self::getRetentionRate(1),
            'day_7' => self::getRetentionRate(7),
            'day_30' => self::getRetentionRate(30),
            'day_90' => self::getRetentionRate(90),
            'cohort_analysis' => $cohorts,
        ];
    }

    /**
     * Trend de receita (últimos N dias)
     */
    private static function getRevenueTrend(int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $revenue = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');
            $data[$date] = $revenue;
        }
        return $data;
    }

    /**
     * Taxa de crescimento mensal
     */
    private static function getMonthlyGrowthRate(): float
    {
        $this_month = User::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $last_month = User::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        return $last_month > 0 ? (($this_month - $last_month) / $last_month) * 100 : 0;
    }

    /**
     * Tempo médio para revisar reparos (em horas)
     */
    private static function getAvgTimeToReview(): float
    {
        $repairs = Repair::where('status', '!=', 'pending_review')
            ->whereNotNull('reviewed_at')
            ->get();

        if ($repairs->isEmpty()) {
            return 0;
        }

        $total_hours = 0;
        foreach ($repairs as $repair) {
            $total_hours += $repair->created_at->diffInHours($repair->reviewed_at);
        }

        return $total_hours / $repairs->count();
    }

    /**
     * Taxa de retenção por dias
     */
    private static function getRetentionRate(int $days): float
    {
        $start_date = now()->subDays($days);

        $users_registered = User::where('created_at', '>', $start_date)->count();
        $users_active = User::where('created_at', '>', $start_date)
            ->where('last_login', '>', $start_date)
            ->count();

        return $users_registered > 0 ? ($users_active / $users_registered) * 100 : 0;
    }

    /**
     * Análise de Coorte (cohort retention)
     */
    private static function getCohortRetention(): array
    {
        $cohorts = [];

        for ($i = 12; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $month_key = $month->format('Y-m');

            $users_in_cohort = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            if ($users_in_cohort === 0) continue;

            $retention = [];
            for ($week = 1; $week <= 4; $week++) {
                $target_date = $month->copy()->addWeeks($week);
                $active = User::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->where('last_login', '>=', $target_date->subDays(7))
                    ->where('last_login', '<=', $target_date)
                    ->count();

                $retention["week_$week"] = round(($active / $users_in_cohort) * 100, 1);
            }

            $cohorts[$month_key] = $retention;
        }

        return $cohorts;
    }

    /**
     * Exportar relatório em PDF
     */
    public static function exportToPDF(): string
    {
        $metrics = self::getDashboardMetrics();

        // Usar library como Dompdf ou TCPDF
        // TODO: Implementar export
        return '';
    }
}
