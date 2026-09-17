#!/bin/bash

#
# 🚀 ESCOLA DA MANUTENÇÃO - Script Automático VPS Linux
#
# Instala o sistema completo em uma VPS Linux
# Uso: bash install-vps-linux.sh
#
# Requerimentos:
# - Ubuntu 20.04+ ou Debian
# - Acesso SSH como root
# - 2GB RAM (mínimo)
# - 10GB espaço em disco
#

set -e  # Sair se algum comando falhar

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # Sem cor

# Função de log
log() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Banner
echo ""
echo -e "${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║   🎓 ESCOLA DA MANUTENÇÃO - Instalação Automática VPS       ║
║                                                              ║
║   Este script vai instalar o sistema completo em ~5min      ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# Verificar se é root
if [[ $EUID -ne 0 ]]; then
    error "Este script precisa ser executado como root!"
    echo "Use: sudo bash install-vps-linux.sh"
    exit 1
fi

# Verificar OS
if ! [[ -f /etc/os-release ]]; then
    error "Sistema operacional não detectado!"
    exit 1
fi

log "Detectando sistema operacional..."
. /etc/os-release

if [[ $ID != "ubuntu" && $ID != "debian" ]]; then
    error "Este script suporta apenas Ubuntu/Debian"
    exit 1
fi

success "Sistema: $PRETTY_NAME"

# ============================================================================
# PASSO 1: ATUALIZAR SISTEMA
# ============================================================================
echo ""
log "PASSO 1: Atualizando sistema (${BLUE}~30seg${NC})..."

apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl git wget apt-transport-https ca-certificates software-properties-common

success "Sistema atualizado!"

# ============================================================================
# PASSO 2: INSTALAR DOCKER
# ============================================================================
echo ""
log "PASSO 2: Instalando Docker (${BLUE}~1min${NC})..."

# Verificar se Docker já está instalado
if command -v docker &> /dev/null; then
    success "Docker já está instalado!"
else
    # Instalar Docker
    curl -fsSL https://download.docker.com/linux/$(lsb_release -is | tr '[:upper:]' '[:lower:]')/gpg | apt-key add -
    add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/$(lsb_release -is | tr '[:upper:]' '[:lower:]') $(lsb_release -cs) stable" -y
    
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose
    
    systemctl enable docker -q
    systemctl start docker -q
    
    success "Docker instalado!"
fi

# ============================================================================
# PASSO 3: CLONAR REPOSITÓRIO
# ============================================================================
echo ""
log "PASSO 3: Clonando repositório (${BLUE}~30seg${NC})..."

# Limpar instalação anterior
if [ -d "/var/www/escola-manutencao" ]; then
    warn "Encontrada instalação anterior em /var/www/escola-manutencao"
    read -p "Deseja substituir? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf /var/www/escola-manutencao
    else
        error "Operação cancelada"
        exit 1
    fi
fi

mkdir -p /var/www
cd /var/www

git clone -q https://github.com/pablogalindo159/escola-manutencao.git
cd escola-manutencao

success "Repositório clonado!"

# ============================================================================
# PASSO 4: CONFIGURAR DOCKER
# ============================================================================
echo ""
log "PASSO 4: Iniciando containers Docker (${BLUE}~2min${NC})..."

# Verificar se docker-compose.yml existe
if [ ! -f "docker-compose.yml" ]; then
    error "docker-compose.yml não encontrado!"
    exit 1
fi

# Iniciar containers
docker-compose up -d

# Aguardar serviços iniciarem
log "Aguardando serviços iniciarem..."
sleep 10

success "Containers iniciados!"

# ============================================================================
# PASSO 5: INSTALAR DEPENDÊNCIAS PHP
# ============================================================================
echo ""
log "PASSO 5: Instalando dependências PHP (${BLUE}~1min${NC})..."

docker-compose exec -T app composer install --no-interaction -q

success "Dependências PHP instaladas!"

# ============================================================================
# PASSO 6: CONFIGURAR AMBIENTE
# ============================================================================
echo ""
log "PASSO 6: Configurando ambiente..."

docker-compose exec -T app cp .env.example .env
docker-compose exec -T app php artisan key:generate -q
docker-compose exec -T app php artisan jwt:secret -q

success "Ambiente configurado!"

# ============================================================================
# PASSO 7: BANCO DE DADOS
# ============================================================================
echo ""
log "PASSO 7: Criando banco de dados (${BLUE}~30seg${NC})..."

# Aguardar PostgreSQL estar pronto
log "Aguardando PostgreSQL inicializar..."
for i in {1..30}; do
    if docker-compose exec -T postgres pg_isready -q 2>/dev/null; then
        break
    fi
    sleep 1
done

# Rodar migrações
docker-compose exec -T app php artisan migrate --force -q

success "Banco de dados criado!"

# ============================================================================
# PASSO 8: CRIAR USUÁRIO ADMIN
# ============================================================================
echo ""
log "PASSO 8: Criando usuário admin..."

# Criar usuário via Tinker
docker-compose exec -T app php artisan tinker --execute="
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('Senha123!')
]);
" -q 2>/dev/null || true

success "Usuário admin criado!"
success "Email: admin@example.com"
success "Senha: Senha123!"

# ============================================================================
# PASSO 9: COMPILAR ASSETS
# ============================================================================
echo ""
log "PASSO 9: Compilando assets (${BLUE}~1min${NC})..."

docker-compose exec -T app npm install -q
docker-compose exec -T app npm run build -q

success "Assets compilados!"

# ============================================================================
# FINALIZAÇÃO
# ============================================================================
echo ""
echo -e "${GREEN}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║         ✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!                ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# Obter IP da VPS
SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo -e "${BLUE}📋 INFORMAÇÕES DO ACESSO:${NC}"
echo ""
echo -e "  🌐 URL:        ${YELLOW}http://${SERVER_IP}:8000${NC}"
echo -e "  📧 Email:      ${YELLOW}admin@example.com${NC}"
echo -e "  🔐 Senha:      ${YELLOW}Senha123!${NC}"
echo ""
echo -e "${BLUE}📚 PRÓXIMOS PASSOS:${NC}"
echo ""
echo "  1. Acesse o sistema:"
echo "     ${YELLOW}http://${SERVER_IP}:8000${NC}"
echo ""
echo "  2. Faça login com as credenciais acima"
echo ""
echo "  3. Altere a senha padrão imediatamente!"
echo ""
echo "  4. Configure seu domínio (opcional)"
echo ""
echo -e "${BLUE}🔗 URLS ÚTEIS:${NC}"
echo ""
echo -e "  API REST:      ${YELLOW}http://${SERVER_IP}:8000/api${NC}"
echo -e "  Admin:         ${YELLOW}http://${SERVER_IP}:8000/admin${NC}"
echo -e "  Health Check:  ${YELLOW}http://${SERVER_IP}:8000/health${NC}"
echo ""
echo -e "${BLUE}📝 COMANDOS ÚTEIS:${NC}"
echo ""
echo "  Ver logs:"
echo "    ${YELLOW}docker-compose -f /var/www/escola-manutencao/docker-compose.yml logs -f app${NC}"
echo ""
echo "  Reiniciar sistema:"
echo "    ${YELLOW}docker-compose -f /var/www/escola-manutencao/docker-compose.yml restart${NC}"
echo ""
echo "  Parar sistema:"
echo "    ${YELLOW}docker-compose -f /var/www/escola-manutencao/docker-compose.yml down${NC}"
echo ""
echo -e "${BLUE}📞 SUPORTE:${NC}"
echo ""
echo "  GitHub: https://github.com/pablogalindo159/escola-manutencao"
echo "  Issues: https://github.com/pablogalindo159/escola-manutencao/issues"
echo ""

echo -e "${GREEN}Obrigado por usar Escola da Manutenção! 🎓${NC}"
echo ""
