@component('mail::message')
# ✅ Seu Reparo foi Aprovado!

Excelente notícia, **{{ $repair->student->name }}**!

Seu reparo de **{{ $repair->equipment }}** foi avaliado pelo professor e **APROVADO** com sucesso! 🎉

## Detalhes do Reparo

| Campo | Detalhes |
|-------|----------|
| **Equipamento** | {{ $repair->equipment }} |
| **Cliente** | {{ $repair->client_name }} |
| **Avaliação** | ⭐ @for($i = 0; $i < $repair->rating; $i++) ★ @endfor ({{ $repair->rating }}/5) |
| **Data de Submissão** | {{ $repair->created_at->format('d/m/Y H:i') }} |

## Feedback do Professor

> {{ $repair->instructor_feedback }}

@if($repair->certificate)

## 🏆 Certificado Gerado

Parabéns! Um certificado foi gerado e está disponível no seu dashboard.

@component('mail::button', ['url' => config('app.url') . '/certificates/' . $repair->certificate->id])
Ver Certificado
@endcomponent

@endif

## Próximos Passos

1. Você pode **baixar o certificado** no seu dashboard
2. Compartilhe em suas redes sociais ou LinkedIn
3. Continue aprendendo com outros cursos
4. Registre mais reparos para ganhar badges!

## Ganhos de Pontos

Você ganhou **50 pontos** de experiência! 🎯

---

Continue sua jornada de aprendizado!<br>
**Equipe Escola da Manutenção** 🔧

@component('mail::subcopy')
Este reparo foi revisado em {{ $repair->reviewed_at->format('d/m/Y H:i') }}
@endcomponent

@endcomponent
