@component('mail::message')
# 👋 Bem-vindo à Escola da Manutenção!

Olá **{{ $user->name }}**,

Que alegria tê-lo conosco! Você se registrou com sucesso na plataforma de cursos de manutenção industrial.

## Próximos Passos

@component('mail::button', ['url' => config('app.url') . '/dashboard'])
Acessar Dashboard
@endcomponent

### O que você pode fazer agora:

1. **Explorar Cursos** - Navegue por nosso catálogo de cursos práticos
2. **Se Inscrever** - Escolha o curso que mais interessa e comece agora
3. **Registrar Reparos** - Documente seus trabalhos práticos com fotos e descrições
4. **Conectar-se** - Participe da comunidade e aprenda com outros alunos

## Conteúdo Gratuito

Para começar, temos alguns vídeos **gratuitos** disponíveis:

- 🔧 Introdução à Manutenção Básica
- ⚙️ Segurança no Trabalho
- 📋 Como Registrar um Reparo

## Perguntas?

Não hesite em nos contatar:
- 📧 support@escoladamanutencao.com.br
- 💬 Chat ao vivo no dashboard
- 📱 WhatsApp: (11) 99999-9999

Estamos aqui para ajudar! 😊

---

Bem-vindo a bordo,<br>
**Equipe Escola da Manutenção** 🔧

@component('mail::subcopy')
Se você não se registrou, pode ignorar este email.
@endcomponent

@endcomponent
