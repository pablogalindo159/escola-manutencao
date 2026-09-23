<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificatePageController extends Controller
{
    /**
     * GET /certificados/{certificate}/pdf (link assinado e temporário).
     * Gera PDF se o dompdf estiver instalado; senão, página para imprimir.
     */
    public function pdf(Certificate $certificate)
    {
        $certificate->loadMissing('user:id,name', 'course.instructor:id,name');
        $filename = 'certificado-' . $certificate->certificate_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.certificate', [
                'certificate' => $certificate,
                'isPdf' => true,
            ])->setPaper('a4', 'landscape')->stream($filename);
        }

        return view('certificates.certificate', [
            'certificate' => $certificate,
            'isPdf' => false,
        ]);
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
