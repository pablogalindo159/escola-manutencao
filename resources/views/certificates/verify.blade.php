<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação de certificado</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #1f2937; }
        .card { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .ok { color: #15803d; } .bad { color: #b91c1c; }
        h1 { font-size: 20px; margin-top: 0; }
        dt { font-size: 12px; color: #6b7280; margin-top: 12px; } dd { margin: 2px 0 0; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <p style="color:#0066FF;font-weight:bold;letter-spacing:2px;margin:0 0 12px">ESCOLA DA MANUTENÇÃO</p>
    @if ($certificate)
        <h1 class="ok">✔ Certificado válido</h1>
        <dl>
            <dt>Aluno</dt><dd>{{ $certificate->user->name }}</dd>
            <dt>Curso</dt><dd>{{ $certificate->course->title }}</dd>
            <dt>Emitido em</dt><dd>{{ $certificate->issued_at->format('d/m/Y') }}</dd>
            <dt>Número</dt><dd>{{ $certificate->certificate_number }}</dd>
        </dl>
    @else
        <h1 class="bad">✖ Certificado não encontrado</h1>
        <p>Nenhum certificado com o número <strong>{{ $number }}</strong> foi emitido por esta escola.</p>
    @endif
</div>
</body>
</html>
