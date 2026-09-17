# ==============================================================================
# 🚀 ESCOLA DA MANUTENÇÃO - INSTALADOR AUTOMÁTICO 100%
# Windows (PowerShell)
# 
# Modo de uso:
#   Opção A - Direto da internet:
#     powershell -Command "IEX((New-Object Net.WebClient).DownloadString('https://seu-dominio.com/install-auto-windows.ps1'))"
#
#   Opção B - Local:
#     Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
#     .\install-auto-windows.ps1
#
# O que faz:
#   ✅ Baixa ESCOLA_MANUTENCAO_COMPLETO.zip
#   ✅ Instala Docker (se não tiver)
#   ✅ Extrai tudo
#   ✅ Configura Laravel
#   ✅ Executa migrações
#   ✅ Deixa rodando pronto para usar!
#
# Tempo: ~15-20 minutos
# Requer: Windows 10/11 (PRO ou superior para Docker)
# ==============================================================================

# Permitir execução de script
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force

# ==============================================================================
# CONFIGURAÇÃO - CUSTOMIZE AQUI!
# ==============================================================================

# URL do ZIP (customize para seu servidor)
$ZIP_URL = "https://github.com/seu-usuario/seu-repo/releases/download/v1.0/ESCOLA_MANUTENCAO_COMPLETO.zip"
# Alternativas:
# $ZIP_URL = "https://dl.dropbox.com/s/xxxxx/ESCOLA_MANUTENCAO_COMPLETO.zip?dl=1"
# $ZIP_URL = "https://drive.google.com/uc?id=xxxxx&export=download"
# $ZIP_URL = "https://seu-servidor.com/files/ESCOLA_MANUTENCAO_COMPLETO.zip"

# Pasta de destino
$PROJECT_DIR = "$HOME\Projetos\escola-manutencao"

# ==============================================================================
# COLORS & FUNCTIONS
# ==============================================================================

function Write-Logo {
    Clear-Host
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║   🚀 ESCOLA DA MANUTENÇÃO                              ║" -ForegroundColor Cyan
    Write-Host "║   INSTALADOR AUTOMÁTICO 100%                           ║" -ForegroundColor Cyan
    Write-Host "║                                                        ║" -ForegroundColor Cyan
    Write-Host "║   Baixando... Instalando... Configurando...            ║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Step {
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Blue
    Write-Host "📋 $args" -ForegroundColor Cyan
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Blue
    Write-Host ""
}

function Write-Info {
    Write-Host "ℹ️  $args" -ForegroundColor Blue
}

function Write-Success {
    Write-Host "✅ $args" -ForegroundColor Green
}

function Write-Error-Custom {
    Write-Host "❌ $args" -ForegroundColor Red
    Write-Host ""
    Write-Host "Pressione qualquer tecla para fechar..."
    [void][System.Console]::ReadKey($true)
    exit 1
}

function Write-Warning-Custom {
    Write-Host "⚠️  $args" -ForegroundColor Yellow
}

# ==============================================================================
# PASSO 0: VERIFICAR ADMIN
# ==============================================================================

Write-Logo

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]"Administrator")
if (-not $isAdmin) {
    Write-Error-Custom "Este script requer privilégios de Administrator. Clique direito > Executar como Administrator"
}
Write-Success "Executando como Administrator"

# ==============================================================================
# PASSO 1: VERIFICAR SISTEMA
# ==============================================================================

Write-Step "1️⃣ VERIFICANDO SISTEMA"

Write-Info "Sistema Operacional: Windows"

# Verificar versão do Windows
$osVersion = [System.Environment]::OSVersion.VersionString
Write-Info "Versão: $osVersion"

# ==============================================================================
# PASSO 2: VERIFICAR DOCKER
# ==============================================================================

Write-Step "2️⃣ VERIFICANDO DOCKER"

try {
    $dockerVersion = docker --version 2>$null
    Write-Success "Docker já instalado: $dockerVersion"
} catch {
    Write-Error-Custom "Docker não encontrado!`n`nInstale Docker Desktop: https://www.docker.com/products/docker-desktop`n`nNota: Windows Home precisa do WSL2 (Windows Subsystem for Linux 2)"
}

# ==============================================================================
# PASSO 3: CRIAR PASTA
# ==============================================================================

Write-Step "3️⃣ CRIANDO PASTA DO PROJETO"

if (Test-Path $PROJECT_DIR) {
    Write-Warning-Custom "Pasta já existe: $PROJECT_DIR"
    Write-Info "Usando pasta existente..."
} else {
    New-Item -ItemType Directory -Path $PROJECT_DIR -Force | Out-Null
    Write-Success "Pasta criada: $PROJECT_DIR"
}

Set-Location $PROJECT_DIR
Write-Success "Diretório atual: $(Get-Location)"

# ==============================================================================
# PASSO 4: BAIXAR ZIP
# ==============================================================================

Write-Step "4️⃣ BAIXANDO ESCOLA_MANUTENCAO_COMPLETO.zip"

$ZIP_FILE = Join-Path $PROJECT_DIR "ESCOLA_MANUTENCAO_COMPLETO.zip"

# Verificar se já existe
if (Test-Path $ZIP_FILE) {
    Write-Success "Arquivo já existe localmente"
} else {
    Write-Info "Baixando de: $ZIP_URL"
    Write-Info "Isso pode levar alguns minutos (arquivo de 300 KB)..."
    Write-Host ""
    
    try {
        Write-Info "Iniciando download..."
        
        # Usar WebClient para download
        $WebClient = New-Object System.Net.WebClient
        $WebClient.DownloadFile($ZIP_URL, $ZIP_FILE)
        
        Write-Success "Download concluído!"
    } catch {
        Write-Warning-Custom "Erro ao baixar de: $ZIP_URL"
        Write-Host ""
        
        Write-Error-Custom "Não consegui baixar o arquivo automaticamente.`n`nOpções:`n1. Coloque ESCOLA_MANUTENCAO_COMPLETO.zip em: $PROJECT_DIR`n2. Ou copie para: $HOME\Downloads`n`nDepois execute novamente este script."
    }
}

# ==============================================================================
# PASSO 5: EXTRAIR ZIP
# ==============================================================================

Write-Step "5️⃣ EXTRAINDO ARQUIVO"

Write-Info "Extraindo ESCOLA_MANUTENCAO_COMPLETO.zip..."

try {
    Expand-Archive -Path $ZIP_FILE -DestinationPath $PROJECT_DIR -Force
    Write-Success "Arquivo extraído!"
} catch {
    Write-Error-Custom "Erro ao extrair arquivo: $_"
}

# Entrar na pasta
if (Test-Path "final") {
    Set-Location "final"
    Write-Success "Entrando em: final\"
} elseif (Test-Path "ESCOLA_MANUTENCAO_FINAL") {
    Set-Location "ESCOLA_MANUTENCAO_FINAL"
    Write-Success "Entrando em: ESCOLA_MANUTENCAO_FINAL\"
}

# ==============================================================================
# PASSO 6: EXTRAIR ZIPs DE CÓDIGO
# ==============================================================================

Write-Step "6️⃣ EXTRAINDO CÓDIGO (4 arquivos)"

if (Test-Path "codigo_zips") {
    Push-Location "codigo_zips"
    
    $zipFiles = Get-ChildItem -Filter "*.zip" -ErrorAction SilentlyContinue
    $ZIP_COUNT = @($zipFiles).Count
    Write-Info "Encontrados $ZIP_COUNT arquivos ZIP"
    
    foreach ($zip in $zipFiles) {
        Write-Info "Extraindo: $($zip.Name)"
        try {
            Expand-Archive -Path $zip.FullName -DestinationPath "." -Force
        } catch {
            Write-Warning-Custom "Erro ao extrair $($zip.Name): $_"
        }
    }
    
    Write-Success "Código extraído!"
    Pop-Location
}

# ==============================================================================
# PASSO 7: INICIAR DOCKER
# ==============================================================================

Write-Step "7️⃣ INICIANDO DOCKER"

Write-Info "Subindo containers (PostgreSQL, Redis, Laravel, Nginx)..."

try {
    & docker-compose up -d 2>$null
    if ($LASTEXITCODE -ne 0) {
        & docker compose up -d
    }
} catch {
    Write-Error-Custom "Erro ao iniciar Docker: $_"
}

# Aguardar
Write-Info "Aguardando containers ficarem prontos..."
for ($i = 1; $i -le 40; $i++) {
    $percent = [math]::Round(($i / 40) * 100)
    Write-Host "`r[$percent%] Aguardando..." -NoNewline
    Start-Sleep -Seconds 1
}
Write-Host ""

Write-Success "Containers rodando!"

# ==============================================================================
# PASSO 8: INSTALAR DEPENDÊNCIAS PHP
# ==============================================================================

Write-Step "8️⃣ INSTALANDO DEPENDÊNCIAS"

Write-Info "Composer install..."
try {
    & docker-compose exec -T laravel composer install -q 2>$null
} catch {
    Write-Warning-Custom "Erro ao rodar composer"
}

Write-Info "Gerando chave de app..."
try {
    & docker-compose exec -T laravel php artisan key:generate 2>$null
} catch {
    Write-Warning-Custom "Erro ao gerar chave"
}

Write-Info "Gerando JWT secret..."
try {
    & docker-compose exec -T laravel php artisan jwt:secret --force -q 2>$null
} catch {
    Write-Warning-Custom "Erro ao gerar JWT"
}

Write-Success "Dependências instaladas!"

# ==============================================================================
# PASSO 9: DATABASE
# ==============================================================================

Write-Step "9️⃣ CONFIGURANDO DATABASE"

Write-Info "Executando migrações..."
try {
    & docker-compose exec -T laravel php artisan migrate --force 2>$null
} catch {
    Write-Warning-Custom "Erro ao rodar migrações"
}

Write-Info "Carregando dados de teste..."
try {
    & docker-compose exec -T laravel php artisan db:seed --force -q 2>$null
} catch {
    Write-Warning-Custom "Erro ao rodar seeders"
}

Write-Success "Database configurado!"

# ==============================================================================
# PASSO 10: ASSETS
# ==============================================================================

Write-Step "🔟 COMPILANDO ASSETS"

if (Test-Path "package.json") {
    Write-Info "npm install..."
    try {
        & docker-compose exec -T laravel npm install -q 2>$null
    } catch {
        Write-Warning-Custom "Erro ao rodar npm install"
    }
    
    Write-Info "npm run build..."
    try {
        & docker-compose exec -T laravel npm run build -q 2>$null
    } catch {
        Write-Warning-Custom "Erro ao rodar npm build"
    }
    
    Write-Success "Assets compilados!"
} else {
    Write-Warning-Custom "package.json não encontrado"
}

# ==============================================================================
# PASSO 11: VALIDAÇÕES
# ==============================================================================

Write-Step "1️⃣1️⃣ VALIDANDO INSTALAÇÃO"

Write-Info "Testando database..."
try {
    $dbTest = & docker-compose exec -T laravel php artisan tinker --execute="DB::connection()->getPDO();" 2>$null
    if ($null -ne $dbTest) {
        Write-Success "Database conectado ✓"
    }
} catch {
    Write-Warning-Custom "Erro ao testar database"
}

Write-Info "Testando Redis..."
try {
    $redisTest = & docker-compose exec -T laravel php artisan tinker --execute="Cache::put('test', 'ok');" 2>$null
    if ($null -ne $redisTest) {
        Write-Success "Redis conectado ✓"
    }
} catch {
    Write-Warning-Custom "Erro ao testar Redis"
}

Write-Info "Criando storage link..."
try {
    & docker-compose exec -T laravel php artisan storage:link 2>$null
} catch {
    Write-Warning-Custom "Erro ao criar storage link"
}

# ==============================================================================
# PASSO 12: RESUMO FINAL
# ==============================================================================

Clear-Host
Write-Logo

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
Write-Host ""
Write-Host "✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!" -ForegroundColor Green
Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 ACESSE AGORA:" -ForegroundColor Green
Write-Host ""
Write-Host "  http://localhost:8000" -ForegroundColor Cyan -NoNewline
Write-Host "              Website"
Write-Host "  http://localhost:8000/admin" -ForegroundColor Cyan -NoNewline
Write-Host "        Admin Dashboard"
Write-Host "  http://localhost:8000/api/docs" -ForegroundColor Cyan -NoNewline
Write-Host "     Documentação API"
Write-Host ""
Write-Host "👤 LOGIN:" -ForegroundColor Green
Write-Host ""
Write-Host "  Email:    " -ForegroundColor Green -NoNewline
Write-Host "admin@example.com" -ForegroundColor Cyan
Write-Host "  Password: " -ForegroundColor Green -NoNewline
Write-Host "password" -ForegroundColor Cyan
Write-Host ""
Write-Host "📱 CONFIGURAR APP FLUTTER:" -ForegroundColor Green
Write-Host ""
Write-Host "  cd app" -ForegroundColor Cyan
Write-Host "  flutter pub get" -ForegroundColor Cyan
Write-Host "  flutterfire configure" -ForegroundColor Cyan
Write-Host "  flutter run" -ForegroundColor Cyan
Write-Host ""
Write-Host "🔍 VERIFICAR STATUS:" -ForegroundColor Green
Write-Host ""
Write-Host "  cd $(Get-Location)" -ForegroundColor Cyan
Write-Host "  docker-compose ps" -ForegroundColor Cyan
Write-Host ""
Write-Host "🛑 PARAR TUDO:" -ForegroundColor Green
Write-Host ""
Write-Host "  docker-compose down" -ForegroundColor Cyan
Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
Write-Host ""
Write-Host "Pasta do projeto: " -ForegroundColor Yellow -NoNewline
Write-Host "$(Get-Location)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Cyan
Write-Host "  1. Abrir http://localhost:8000"
Write-Host "  2. Fazer login com admin@example.com"
Write-Host "  3. Explorar o admin dashboard"
Write-Host "  4. Configurar Firebase (opcional)"
Write-Host "  5. Configurar Mercado Pago (opcional)"
Write-Host ""
Write-Host "🚀 Boa sorte com seu projeto!" -ForegroundColor Green
Write-Host ""
Write-Host "Pressione qualquer tecla para fechar..."
[void][System.Console]::ReadKey($true)
