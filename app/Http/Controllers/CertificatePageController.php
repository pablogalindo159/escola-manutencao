<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Services\CertificateService;

class CertificatePageController extends Controller
{
    /**
     * GET /certificados/{certificate}/pdf (link assinado e temporário).
     * Gera PDF se o dompdf estiver instalado; senão, página para imprimir.
     */
    public function pdf(Certificate $certificate)
    {
        $certificate->loadMissing('user:id,name', 'course.instructor:id,name');

        return $this->render($certificate, 'certificado-' . $certificate->certificate_number . '.pdf');
    }

    /**
     * Prévia no admin: certificado de exemplo (não salvo) com a arte do curso.
     */
    public function preview(Course $course)
    {
        $course->loadMissing('instructor:id,name');
        $certificate = new Certificate([
            'certificate_number' => 'CERT-PREVIA-EXEMPLO',
            'issued_at' => now(),
            'completion_percentage' => 100,
        ]);
        $certificate->setRelation('user', auth()->user());
        $certificate->setRelation('course', $course);

        return $this->render($certificate, 'previa-certificado.pdf');
    }

    /**
     * Gera PDF se o dompdf estiver instalado; senão, página para imprimir.
     */
    private function render(Certificate $certificate, string $filename)
    {
        $data = [
            'certificate' => $certificate,
            'assets' => app(CertificateService::class)->assetsFor($certificate->course),
        ];

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.certificate', $data + ['isPdf' => true])
                ->setPaper('a4', 'landscape')
                ->stream($filename);
        }

        return view('certificates.certificate', $data + ['isPdf' => false]);
    }

    /**
     * GET /certificado/verificar/{number} — verificação pública.
     */
    public function verify(string $number)
    {
        $certificate = Certificate::with('user:id,name', 'course:id,title')
            ->where('certificate_number', $number)
            ->first();

        return view('certificates.verify', compact('certificate', 'number'));
    }
}
