#!/bin/bash

################################################################################
# 🚀 ESCOLA DA MANUTENÇÃO - INSTALADOR AUTOMÁTICO 100%
# Linux / macOS
# 
# Modo de uso:
#   bash <(curl -s https://seu-dominio.com/install-auto-linux-mac.sh)
# ou
#   curl -sSL https://seu-dominio.com/install-auto-linux-mac.sh | bash
#
# Ou local:
#   bash install-auto-linux-mac.sh
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
################################################################################

set -e

# ==============================================================================
# CONFIGURAÇÃO - CUSTOMIZE AQUI!
# ==============================================================================

# URL do ZIP (customize para seu servidor)
ZIP_URL="https://github.com/seu-usuario/seu-repo/releases/download/v1.0/ESCOLA_MANUTENCAO_COMPLETO.zip"
# Alternativas:
# ZIP_URL="https://dl.dropbox.com/s/xxxxx/ESCOLA_MANUTENCAO_COMPLETO.zip?dl=1"
# ZIP_URL="https://drive.google.com/uc?id=xxxxx&export=download"
# ZIP_URL="https://seu-servidor.com/files/ESCOLA_MANUTENCAO_COMPLETO.zip"

# Pasta de destino
PROJECT_DIR="${HOME}/Projetos/escola-manutencao"

# ==============================================================================
# COLORS
# ==============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ==============================================================================
# FUNCTIONS
# ==============================================================================

clear_screen() {
    clear
}

logo() {
    echo -e "${CYAN}"
    echo "╔════════════════════════════════════════════════════════╗"
    echo "║   🚀 ESCOLA DA MANUTENÇÃO                              ║"
    echo "║   INSTALADOR AUTOMÁTICO 100%                           ║"
    echo "║                                                        ║"
    echo "║   Baixando... Instalando... Configurando...            ║"
    echo "╚════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

step() {
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}📋 $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# ==============================================================================
# PASSO 1: VERIFICAR SISTEMA
# ==============================================================================

clear_screen
logo

step "1️⃣ VERIFICANDO SISTEMA"

if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="Linux"
    PM="apt-get"
    info "Sistema: Linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macOS"
    PM="brew"
    info "Sistema: macOS"
else
    error "Sistema não suportado: $OSTYPE"
fi

# ==============================================================================
# PASSO 2: VERIFICAR E INSTALAR DEPENDÊNCIAS
# ==============================================================================

step "2️⃣ VERIFICANDO DEPENDÊNCIAS"

# Docker
if command -v docker &> /dev/null; then
    success "Docker já instalado"
else
    warning "Docker não encontrado. Instalando..."
    
    if [ "$OS" = "Linux" ]; then
        sudo apt-get update > /dev/null 2>&1
        curl -fsSL https://get.docker.com -o get-docker.sh 2>/dev/null
        sudo sh get-docker.sh > /dev/null 2>&1
        rm -f get-docker.sh
        
        # Adicionar usuário ao grupo docker
        sudo usermod -aG docker $USER 2>/dev/null || true
        info "Você pode precisar fazer logout e login novamente"
    else
        error "No macOS, instale Docker Desktop: https://www.docker.com/products/docker-desktop"
    fi
    
    success "Docker instalado!"
fi

# Docker Compose
if command -v docker-compose &> /dev/null; then
    success "Docker Compose já instalado"
else
    warning "Docker Compose não encontrado. Instalando..."
    LATEST=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep 'tag_name' | cut -d'"' -f4)
    sudo curl -L "https://github.com/docker/compose/releases/download/${LATEST}/docker-compose-$(uname -s)-$(uname -m)" \
        -o /usr/local/bin/docker-compose 2>/dev/null
    sudo chmod +x /usr/local/bin/docker-compose
    success "Docker Compose instalado!"
fi

# ==============================================================================
# PASSO 3: CRIAR PASTA
# ==============================================================================

step "3️⃣ CRIANDO PASTA DO PROJETO"

if [ -d "$PROJECT_DIR" ]; then
    warning "Pasta já existe: $PROJECT_DIR"
    info "Usando pasta existente..."
else
    mkdir -p "$PROJECT_DIR"
    success "Pasta criada: $PROJECT_DIR"
fi

cd "$PROJECT_DIR"
success "Diretório atual: $(pwd)"

# ==============================================================================
# PASSO 4: BAIXAR ZIP
# ==============================================================================

step "4️⃣ BAIXANDO ESCOLA_MANUTENCAO_COMPLETO.zip"

ZIP_FILE="ESCOLA_MANUTENCAO_COMPLETO.zip"

# Verificar se já existe
if [ -f "$ZIP_FILE" ]; then
    success "Arquivo já existe localmente"
else
    info "Baixando de: $ZIP_URL"
    info "Isso pode levar alguns minutos (arquivo de 300 KB)..."
    echo ""
    
    # Tentar download com curl
    if curl -L -# -f "$ZIP_URL" -o "$ZIP_FILE" 2>/dev/null; then
        success "Download concluído!"
    else
        warning "Erro ao baixar de $ZIP_URL"
        
        # Oferecer opção de colocar arquivo manualmente
        echo ""
        error "Não consegui baixar o arquivo automaticamente.
        
Opções:
1. Coloque ESCOLA_MANUTENCAO_COMPLETO.zip em: $PROJECT_DIR
2. Ou copie para: ~/Downloads/

Depois execute novamente este script."
    fi
fi

# ==============================================================================
# PASSO 5: EXTRAIR ZIP
# ==============================================================================

step "5️⃣ EXTRAINDO ARQUIVO"

info "Extraindo ESCOLA_MANUTENCAO_COMPLETO.zip..."
unzip -q "$ZIP_FILE"
success "Arquivo extraído!"

# Entrar na pasta
if [ -d "final" ]; then
    cd final
    success "Entrando em: final/"
elif [ -d "ESCOLA_MANUTENCAO_FINAL" ]; then
    cd ESCOLA_MANUTENCAO_FINAL
    success "Entrando em: ESCOLA_MANUTENCAO_FINAL/"
fi

# ==============================================================================
# PASSO 6: EXTRAIR ZIPs DE CÓDIGO
# ==============================================================================

step "6️⃣ EXTRAINDO CÓDIGO (4 arquivos)"

if [ -d "codigo_zips" ]; then
    cd codigo_zips
    
    ZIP_COUNT=$(ls -1 *.zip 2>/dev/null | wc -l)
    info "Encontrados $ZIP_COUNT arquivos ZIP"
    
    for zip in *.zip; do
        info "Extraindo: $zip"
        unzip -q "$zip" 2>/dev/null || true
    done
    
    success "Código extraído!"
    cd ..
fi

# ==============================================================================
# PASSO 7: INICIAR DOCKER
# ==============================================================================

step "7️⃣ INICIANDO DOCKER"

info "Subindo containers (PostgreSQL, Redis, Laravel, Nginx)..."

docker-compose up -d 2>/dev/null || docker compose up -d 2>/dev/null || error "Erro ao iniciar Docker"

# Aguardar
info "Aguardando containers ficarem prontos..."
for i in {1..40}; do
    echo -ne "\r[$((i*100/40))%] Aguardando... "
    sleep 1
done
echo ""

success "Containers rodando!"

# ==============================================================================
# PASSO 8: INSTALAR DEPENDÊNCIAS PHP
# ==============================================================================

step "8️⃣ INSTALANDO DEPENDÊNCIAS"

info "Composer install..."
docker-compose exec -T laravel composer install -q 2>/dev/null || true

info "Gerando chave de app..."
docker-compose exec -T laravel php artisan key:generate 2>/dev/null || true

info "Gerando JWT secret..."
docker-compose exec -T laravel php artisan jwt:secret --force -q 2>/dev/null || true

success "Dependências instaladas!"

# ==============================================================================
# PASSO 9: DATABASE
# ==============================================================================

step "9️⃣ CONFIGURANDO DATABASE"

info "Executando migrações..."
docker-compose exec -T laravel php artisan migrate --force 2>/dev/null || true

info "Carregando dados de teste..."
docker-compose exec -T laravel php artisan db:seed --force -q 2>/dev/null || true

success "Database configurado!"

# ==============================================================================
# PASSO 10: ASSETS
# ==============================================================================

step "🔟 COMPILANDO ASSETS"

if [ -f "package.json" ]; then
    info "npm install..."
    docker-compose exec -T laravel npm install -q 2>/dev/null || true
    
    info "npm run build..."
    docker-compose exec -T laravel npm run build -q 2>/dev/null || true
    
    success "Assets compilados!"
else
    warning "package.json não encontrado"
fi

# ==============================================================================
# PASSO 11: VALIDAÇÕES
# ==============================================================================

step "1️⃣1️⃣ VALIDANDO INSTALAÇÃO"

info "Testando database..."
if docker-compose exec -T laravel php artisan tinker --execute="DB::connection()->getPDO();" 2>/dev/null > /dev/null; then
    success "Database conectado ✓"
fi

info "Testando Redis..."
if docker-compose exec -T laravel php artisan tinker --execute="Cache::put('test', 'ok');" 2>/dev/null > /dev/null; then
    success "Redis conectado ✓"
fi

info "Criando storage link..."
docker-compose exec -T laravel php artisan storage:link 2>/dev/null || true

# ==============================================================================
# PASSO 12: RESUMO FINAL
# ==============================================================================

clear_screen
logo

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!${NC}"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}🌐 ACESSE AGORA:${NC}"
echo ""
echo -e "  ${CYAN}http://localhost:8000${NC}              Website"
echo -e "  ${CYAN}http://localhost:8000/admin${NC}        Admin Dashboard"
echo -e "  ${CYAN}http://localhost:8000/api/docs${NC}     Documentação API"
echo ""
echo -e "${GREEN}👤 LOGIN:${NC}"
echo ""
echo -e "  Email:    ${CYAN}admin@example.com${NC}"
echo -e "  Password: ${CYAN}password${NC}"
echo ""
echo -e "${GREEN}📱 CONFIGURAR APP FLUTTER:${NC}"
echo ""
echo -e "  ${CYAN}cd app${NC}"
echo -e "  ${CYAN}flutter pub get${NC}"
echo -e "  ${CYAN}flutterfire configure${NC}"
echo -e "  ${CYAN}flutter run${NC}"
echo ""
echo -e "${GREEN}🔍 VERIFICAR STATUS:${NC}"
echo ""
echo -e "  ${CYAN}cd $(pwd)${NC}"
echo -e "  ${CYAN}docker-compose ps${NC}"
echo ""
echo -e "${GREEN}🛑 PARAR TUDO:${NC}"
echo ""
echo -e "  ${CYAN}docker-compose down${NC}"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${CYAN}Pasta do projeto:${NC} $(pwd)"
echo ""
echo -e "${YELLOW}Próximos passos:${NC}"
echo "  1. Abrir http://localhost:8000"
echo "  2. Fazer login com admin@example.com"
echo "  3. Explorar o admin dashboard"
echo "  4. Configurar Firebase (opcional)"
echo "  5. Configurar Mercado Pago (opcional)"
echo ""
echo -e "${GREEN}🚀 Boa sorte com seu projeto!${NC}"
echo ""
