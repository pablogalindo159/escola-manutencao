{{-- Certificado: usado no PDF (dompdf) e como página para imprimir. Layout com tabelas/CSS simples para o dompdf. --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado - {{ $certificate->course->title }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; background: #ffffff; }
        .page { width: 297mm; height: 210mm; padding: 14mm; }
        .frame { width: 100%; height: 100%; border: 6px solid #0066FF; padding: 6mm; }
        .inner { width: 100%; height: 100%; border: 1px solid #93c5fd; text-align: center; padding: 14mm 18mm; }
        .brand { font-size: 14pt; letter-spacing: 3px; color: #0066FF; font-weight: bold; }
        .title { font-size: 34pt; font-weight: bold; margin: 8mm 0 4mm; letter-spacing: 2px; }
        .text { font-size: 13pt; line-height: 1.6; }
        .name { font-size: 26pt; font-weight: bold; color: #0066FF; margin: 5mm 0; }
        .course { font-size: 18pt; font-weight: bold; margin: 3mm 0 6mm; }
        .footer { width: 100%; margin-top: 12mm; font-size: 10pt; color: #4b5563; }
        .footer td { width: 50%; vertical-align: top; }
        .line { border-top: 1px solid #6b7280; width: 70%; margin: 0 auto 2mm; }
        .no-print { text-align: center; padding: 12px; background: #eff6ff; font-family: Arial, sans-serif; }
        .no-print button { background: #0066FF; color: #fff; border: 0; padding: 10px 18px; border-radius: 8px; font-size: 15px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
@unless ($isPdf)
    <div class="no-print">
        <button onclick="window.print()">Imprimir / Salvar como PDF</button>
    </div>
@endunless
<div class="page">
    <div class="frame">
        <div class="inner">
            <div class="brand">ESCOLA DA MANUTENÇÃO</div>
            <div class="title">CERTIFICADO DE CONCLUSÃO</div>
            <div class="text">Certificamos que</div>
            <div class="name">{{ $certificate->user->name }}</div>
            <div class="text">concluiu com êxito o curso</div>
            <div class="course">{{ $certificate->course->title }}</div>
            <div class="text">
                @if ($certificate->course->duration_minutes)
                    com carga horária de {{ max(1, (int) round($certificate->course->duration_minutes / 60)) }} hora(s),
                @endif
                em {{ $certificate->issued_at->format('d/m/Y') }}.
            </div>
            <table class="footer">
                <tr>
                    <td>
                        <div class="line"></div>
                        {{ $certificate->course->instructor->name ?? 'Escola da Manutenção' }}<br>Instrutor
                    </td>
                    <td>
                        Certificado nº {{ $certificate->certificate_number }}<br>
                        Verifique a autenticidade em:<br>
                        {{ $certificate->getVerificationUrl() }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
