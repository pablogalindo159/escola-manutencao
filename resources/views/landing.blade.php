@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🔧</span>
                    <span class="text-xl font-bold text-blue-600">Escola da Manutenção</span>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#cursos" class="text-gray-700 hover:text-blue-600">Cursos</a>
                    <a href="#sobre" class="text-gray-700 hover:text-blue-600">Sobre</a>
                    <a href="#contato" class="text-gray-700 hover:text-blue-600">Contato</a>
                    <a href="#" class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Domine a Manutenção Industrial
                </h1>
                <p class="text-xl text-gray-600 mb-8">
                    Cursos práticos com certificados oficiais. Aprenda com os melhores professores da indústria.
                </p>
                <div class="flex gap-4">
                    <button class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Começar Agora
                    </button>
                    <button class="px-8 py-3 border-2 border-blue-600 text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition">
                        Saiba Mais
                    </button>
                </div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg h-96 flex items-center justify-center text-white">
                <div class="text-7xl">⚙️</div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-blue-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2">1.234+</div>
                    <div class="text-blue-100">Alunos Ativos</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">8</div>
                    <div class="text-blue-100">Cursos Disponíveis</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">72%</div>
                    <div class="text-blue-100">Taxa Conclusão</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">4.8/5</div>
                    <div class="text-blue-100">Avaliação Média</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses -->
    <section id="cursos" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h2 class="text-4xl font-bold text-gray-900 mb-12 text-center">
            Cursos em Destaque
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($featured_courses as $course)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-2xl transition">
                <div class="h-40 {{ $course['gradient'] }} flex items-center justify-center text-5xl">
                    {{ $course['icon'] }}
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $course['name'] }}</h3>
                    
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <span class="text-yellow-400">⭐</span>
                        @endfor
                        <span class="text-sm text-gray-600 ml-2">{{ $course['reviews'] }} avaliações</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 mb-4 text-sm text-gray-600">
                        <div>⏱️ {{ $course['hours'] }} horas</div>
                        <div>📹 {{ $course['videos'] }} aulas</div>
                    </div>
                    
                    <div class="border-t pt-4 flex justify-between items-center">
                        <span class="text-2xl font-bold text-blue-600">R$ {{ number_format($course['price'], 2, ',', '.') }}</span>
                        <a href="#" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                            Saiba Mais
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- Testimonials -->
    <section class="bg-gray-50 py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-12 text-center">
                O Que Nossos Alunos Dizem
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($testimonials as $testimonial)
                <div class="bg-white p-8 rounded-lg shadow">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
                            {{ substr($testimonial['name'], 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">{{ $testimonial['name'] }}</div>
                            <div class="text-sm text-gray-600">{{ $testimonial['course'] }}</div>
                        </div>
                    </div>
                    <div class="flex gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <span class="text-yellow-400">⭐</span>
                        @endfor
                    </div>
                    <p class="text-gray-700">{{ $testimonial['text'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-700 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6">Pronto para começar?</h2>
            <p class="text-xl mb-8 opacity-90">Junte-se a mais de 1.234 profissionais que já transformaram sua carreira</p>
            <button class="px-8 py-4 bg-white text-blue-600 rounded-lg font-bold text-lg hover:bg-gray-100 transition">
                Criar Conta Grátis
            </button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="text-white font-bold mb-4">Empresa</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Sobre Nós</a></li>
                        <li><a href="#" class="hover:text-white">Blog</a></li>
                        <li><a href="#" class="hover:text-white">Carreiras</a></li>
                    </ul>
                </div>
                <div>
                    <div class="text-white font-bold mb-4">Suporte</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Central de Ajuda</a></li>
                        <li><a href="#" class="hover:text-white">Contato</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <div class="text-white font-bold mb-4">Legal</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Termos</a></li>
                        <li><a href="#" class="hover:text-white">Privacidade</a></li>
                        <li><a href="#" class="hover:text-white">Cookies</a></li>
                    </ul>
                </div>
                <div>
                    <div class="text-white font-bold mb-4">Redes Sociais</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Facebook</a></li>
                        <li><a href="#" class="hover:text-white">Instagram</a></li>
                        <li><a href="#" class="hover:text-white">LinkedIn</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-sm">
                <p>&copy; 2026 Escola da Manutenção. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</div>
@endsection
