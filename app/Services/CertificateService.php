<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Regra única do certificado, usada pelo site e pelo app:
 * o certificado é liberado quando o aluno conclui 100% das aulas
 * publicadas do curso.
 */
class CertificateService
{
    /**
     * Progresso do aluno no curso: aulas publicadas x aulas concluídas.
     */
    public function completion(User $user, Course $course): array
    {
        $videoIds = $course->videos()->where('status', 'published')->pluck('id');
        $total = $videoIds->count();

        $completed = $total === 0 ? 0 : UserProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('video_id', $videoIds)
            ->where('is_completed', true)
            ->distinct()
            ->count('video_id');

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? (int) floor($completed / $total * 100) : 0,
        ];
    }

    /**
     * Devolve o certificado do aluno no curso, emitindo na hora se ele
     * acabou de chegar a 100%. Retorna null se ainda não concluiu.
     */
    public function issueIfEligible(User $user, Course $course): ?Certificate
    {
        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $completion = $this->completion($user, $course);
        if ($completion['total'] === 0 || $completion['completed'] < $completion['total']) {
            return null;
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'issued_at' => now(),
            'completion_percentage' => 100,
            'qr_code_data' => '',
        ]);
        $certificate->update(['qr_code_data' => $certificate->getVerificationUrl()]);

        return $certificate;
    }

    /**
     * Arte do certificado do curso (fundo, logo, assinatura) como data URI,
     * pronta para o dompdf. Campo vazio ou arquivo ausente = padrão.
     */
    public function assetsFor(Course $course): array
    {
        $toDataUri = function (?string $path): ?string {
            if (!$path) {
                return null;
            }
            $disk = Storage::disk('local');
            if (!$disk->exists($path)) {
                return null;
            }
            $mime = $disk->mimeType($path) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
        };

        return [
            'background' => $toDataUri($course->certificate_background),
            'logo' => $toDataUri($course->certificate_logo),
            'signature' => $toDataUri($course->certificate_signature),
            'hideFrame' => (bool) $course->certificate_hide_frame,
        ];
    }

    /**
     * Link temporário (assinado) para abrir o certificado sem login no
     * navegador - usado pelo app, que abre o link fora dele.
     */
    public function signedUrl(Certificate $certificate): string
    {
        return URL::temporarySignedRoute(
            'certificates.pdf',
            now()->addMinutes(30),
            ['certificate' => $certificate->id]
        );
    }
}
