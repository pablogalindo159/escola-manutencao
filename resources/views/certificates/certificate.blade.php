{{-- Certificado: usado no PDF (dompdf) e como página para imprimir.
     A4 deitada = 297x210mm. Margem de 10mm no body (o dompdf ignora margem do @page) => área útil 277x190mm.
     Alturas fixas somando menos que 190mm para caber sempre em 1 página.
     $assets (por curso, opcional): background, logo, signature (data URI) e hideFrame. Sem nada = padrão. --}}
@php
    $assets = $assets ?? ['background' => null, 'logo' => null, 'signature' => null, 'hideFrame' => false];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado - {{ $certificate->course->title }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        html { margin: 0; padding: 0; }
        body { margin: 10mm; padding: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; background: #ffffff; }
        .bg { position: absolute; left: 0; top: 0; width: 297mm; height: 210mm; z-index: -1; }
        .frame { border: 4px solid #0066FF; padding: 4mm; height: 176mm; page-break-inside: avoid; }
        .inner { border: 1px solid #93c5fd; height: 166mm; padding: 12mm 12mm 0 12mm; text-align: center; }
        .frame.noframe { border-color: transparent; }
        .frame.noframe .inner { border-color: transparent; }
        .brand { font-size: 13pt; letter-spacing: 3px; color: #0066FF; font-weight: bold; }
        .logo { max-height: 18mm; max-width: 80mm; }
        .title { font-size: 28pt; font-weight: bold; margin: 7mm 0 5mm; letter-spacing: 1px; }
        .with-logo .title { margin: 4mm 0 3mm; }
        .text { font-size: 13pt; line-height: 1.5; }
        .name { font-size: 24pt; font-weight: bold; color: #0066FF; margin: 4mm 0; }
        .course { font-size: 17pt; font-weight: bold; margin: 3mm 0 5mm; }
        /* Textos longos: fonte menor para continuar cabendo em 1 página */
        .name.long { font-size: 17pt; }
        .course.long { font-size: 13pt; }
        .sign { width: 90mm; margin: 16mm auto 0; border-top: 1px solid #6b7280; padding-top: 2mm; font-size: 10pt; color: #374151; }
        .sign-img-box { margin: 5mm auto 0; height: 16mm; }
        .sign-img { max-height: 16mm; max-width: 70mm; }
        .sign-img-box + .sign { margin-top: 1mm; }
        .verify { margin-top: 10mm; font-size: 8pt; color: #6b7280; line-height: 1.5; }
        .with-logo .verify, .with-sign .verify { margin-top: 6mm; }
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
@if ($assets['background'])
    <img class="bg" src="{{ $assets['background'] }}" alt="">
@endif
<div class="frame {{ $assets['hideFrame'] ? 'noframe' : '' }}">
    <div class="inner {{ $assets['logo'] ? 'with-logo' : '' }} {{ $assets['signature'] ? 'with-sign' : '' }}">
        @if ($assets['logo'])
            <img class="logo" src="{{ $assets['logo'] }}" alt="">
        @else
            <div class="brand">ESCOLA DA MANUTENÇÃO</div>
        @endif
        <div class="title">CERTIFICADO DE CONCLUSÃO</div>
        <div class="text">Certificamos que</div>
        <div class="name {{ mb_strlen($certificate->user->name) > 32 ? 'long' : '' }}">{{ $certificate->user->name }}</div>
        <div class="text">concluiu com êxito o curso</div>
        <div class="course {{ mb_strlen($certificate->course->title) > 45 ? 'long' : '' }}">{{ $certificate->course->title }}</div>
        <div class="text">
            @if ($certificate->course->duration_minutes)
                com carga horária de {{ max(1, (int) round($certificate->course->duration_minutes / 60)) }} hora(s),
            @endif
            em {{ $certificate->issued_at->format('d/m/Y') }}.
        </div>
        @if ($assets['signature'])
            <div class="sign-img-box"><img class="sign-img" src="{{ $assets['signature'] }}" alt=""></div>
        @endif
        <div class="sign">
            {{ $certificate->course->instructor->name ?? 'Escola da Manutenção' }}<br>Instrutor
        </div>
        <div class="verify">
            Certificado nº {{ $certificate->certificate_number }}<br>
            Verifique a autenticidade em: {{ $certificate->getVerificationUrl() }}
        </div>
    </div>
</div>
</body>
</html>
