<?php

namespace App\Features;

use App\Models\User;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

/**
 * Sistema de Gamificação - Badges, Pontos e Leaderboards
 */
class GamificationFeature
{
    /**
     * Badges do sistema
     */
    const BADGES = [
        'first_repair' => [
            'name' => 'Primeiro Reparo',
            'description' => 'Completou seu primeiro reparo com sucesso',
            'icon' => '🎯',
            'points' => 50,
        ],
        'repair_master' => [
            'name' => 'Mestre do Reparo',
            'description' => '10 reparos aprovados com avaliação 5 estrelas',
            'icon' => '⭐',
            'points' => 500,
            'condition' => ['repairs_5_star' => 10],
        ],
        'fast_learner' => [
            'name' => 'Aprendiz Rápido',
            'description' => 'Completou 3 cursos em menos de 30 dias',
            'icon' => '⚡',
            'points' => 200,
        ],
        'community_star' => [
            'name' => 'Estrela da Comunidade',
            'description' => '20 posts úteis na comunidade',
            'icon' => '💫',
            'points' => 300,
            'condition' => ['community_posts' => 20],
        ],
        'certification_master' => [
            'name' => 'Mestre Certificado',
            'description' => '5 certificados obtidos',
            'icon' => '📜',
            'points' => 400,
            'condition' => ['certificates' => 5],
        ],
        'consistency_badge' => [
            'name' => 'Consistência',
            'description' => 'Ativo por 30 dias consecutivos',
            'icon' => '🔥',
            'points' => 250,
        ],
    ];

    /**
     * Atribuir badge quando reparo é aprovado
     */
    public static function awardBadgeForRepair(User $user): void
    {
        // First Repair Badge
        if ($user->repairs()->where('status', 'approved')->count() === 1) {
            self::awardBadge($user, 'first_repair');
        }

        // Repair Master Badge
        $five_star_repairs = $user->repairs()
            ->where('status', 'approved')
            ->where('rating', 5)
            ->count();

        if ($five_star_repairs >= 10) {
            self::awardBadge($user, 'repair_master');
        }

        // Incrementar pontos
        $user->increment('points', 50);
    }

    /**
     * Atribuir badge para conclusão de curso
     */
    public static function awardBadgeForCourse(User $user): void
    {
        $completed_courses = $user->progress()
            ->where('completed_at', '!=', null)
            ->distinct('course_id')
            ->count();

        // Fast Learner Badge
        if ($completed_courses === 3) {
            $first_course = $user->progress()
                ->where('completed_at', '!=', null)
                ->oldest('completed_at')
                ->first();

            if ($first_course && $first_course->completed_at->diffInDays(now()) <= 30) {
                self::awardBadge($user, 'fast_learner');
            }
        }

        // Incrementar pontos
        $user->increment('points', 100);
    }

    /**
     * Atribuir badge de certificado
     */
    public static function awardBadgeForCertificate(User $user): void
    {
        $certificates = $user->certificates()->count();

        if ($certificates >= 5) {
            self::awardBadge($user, 'certification_master');
        }

        $user->increment('points', 150);
    }

    /**
     * Atribuir badge de comunidade
     */
    public static function awardBadgeForCommunityPost(User $user): void
    {
        $useful_posts = $user->posts()
            ->where('helpful_count', '>=', 5)
            ->count();

        if ($useful_posts >= 20) {
            self::awardBadge($user, 'community_star');
        }

        $user->increment('points', 25);
    }

    /**
     * Atribuir badge manualmente
     */
    private static function awardBadge(User $user, string $badge_key): void
    {
        // Verificar se já tem
        if ($user->badges()->where('key', $badge_key)->exists()) {
            return;
        }

        $badge = Badge::firstOrCreate(
            ['key' => $badge_key],
            self::BADGES[$badge_key]
        );

        $user->badges()->attach($badge, [
            'awarded_at' => now(),
        ]);

        // Notificar usuário
        $user->notify(new BadgeAwardedNotification($badge));
    }

    /**
     * Obter leaderboard global
     */
    public static function getLeaderboard(int $limit = 10): array
    {
        return User::where('role', 'student')
            ->orderByDesc('points')
            ->take($limit)
            ->get(['id', 'name', 'avatar', 'points', 'email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'points' => $user->points,
                'rank' => self::getUserRank($user),
                'badge_count' => $user->badges()->count(),
                'repair_count' => $user->repairs()->where('status', 'approved')->count(),
            ])
            ->toArray();
    }

    /**
     * Obter leaderboard por curso
     */
    public static function getCourseLeaderboard(int $course_id, int $limit = 10): array
    {
        return User::whereHas('courses', function ($q) use ($course_id) {
            $q->where('courses.id', $course_id);
        })
        ->withCount([
            'progress' => function ($q) use ($course_id) {
                $q->where('course_id', $course_id)
                  ->where('completed_at', '!=', null);
            },
            'repairs' => function ($q) use ($course_id) {
                $q->where('course_id', $course_id)
                  ->where('status', 'approved');
            }
        ])
        ->orderByDesc('points')
        ->take($limit)
        ->get()
        ->toArray();
    }

    /**
     * Obter rank do usuário no leaderboard
     */
    public static function getUserRank(User $user): int
    {
        return User::where('points', '>', $user->points)->count() + 1;
    }

    /**
     * Calcular progresso para próxima badge
     */
    public static function getProgressToNextBadge(User $user): array
    {
        $next_badges = [];

        $repairs = $user->repairs()->where('status', 'approved')->count();
        $five_star_repairs = $user->repairs()
            ->where('status', 'approved')
            ->where('rating', 5)
            ->count();

        // Repair Master Progress
        if ($five_star_repairs < 10) {
            $next_badges[] = [
                'badge' => 'Mestre do Reparo',
                'current' => $five_star_repairs,
                'target' => 10,
                'progress' => ($five_star_repairs / 10) * 100,
            ];
        }

        $certificates = $user->certificates()->count();
        if ($certificates < 5) {
            $next_badges[] = [
                'badge' => 'Mestre Certificado',
                'current' => $certificates,
                'target' => 5,
                'progress' => ($certificates / 5) * 100,
            ];
        }

        return $next_badges;
    }

    /**
     * Streak de atividade (dias consecutivos)
     */
    public static function getActivityStreak(User $user): int
    {
        $streak = 0;
        $current_date = now()->startOfDay();

        while (true) {
            $activity = $user->activities()
                ->whereDate('created_at', $current_date)
                ->exists();

            if (!$activity) {
                break;
            }

            $streak++;
            $current_date->subDay();
        }

        return $streak;
    }

    /**
     * Atribuir badge de consistência se streak >= 30
     */
    public static function checkConsistencyBadge(User $user): void
    {
        $streak = self::getActivityStreak($user);

        if ($streak >= 30) {
            self::awardBadge($user, 'consistency_badge');
        }
    }
}
