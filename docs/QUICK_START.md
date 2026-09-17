# ⚡ QUICK START - INSTALADOR AUTOMÁTICO

**Tempo**: 5 minutos para começar  
**Instalação completa**: ~15-20 minutos  
**Resultado**: Sistema 100% pronto para usar

---

## 🐧 LINUX / 🍎 macOS

Abra o terminal e execute:

```bash
bash <(curl -s https://raw.githubusercontent.com/seu-usuario/seu-repo/main/install-auto-linux-mac.sh)
```

**Pronto!** Deixa rodando e vai fazer tudo automaticamente.

---

## 🪟 WINDOWS

Abra PowerShell como **Administrator** e execute:

```powershell
IEX((New-Object Net.WebClient).DownloadString('https://raw.githubusercontent.com/seu-usuario/seu-repo/main/install-auto-windows.ps1'))
```

**Pronto!** Deixa rodando e vai fazer tudo automaticamente.

---

## ✅ O QUE FAZER DURANTE A INSTALAÇÃO

Enquanto aguarda (~15-20 minutos):

1. ☕ Tomar um café
2. 📖 Ler documentação (pasta `docs/`)
3. 🔐 Preparar credenciais (Mercado Pago, Firebase)
4. 📋 Preparar dados que vai usar

---

## 🎉 QUANDO TERMINAR

Seu navegador mostrará:

```
✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!

🌐 ACESSE AGORA:
   http://localhost:8000              Website
   http://localhost:8000/admin        Admin Dashboard
   http://localhost:8000/api/docs     Documentação API

👤 LOGIN:
   Email:    admin@example.com
   Password: password
```

---

## 🚀 PRIMEIROS PASSOS

### 1. Abrir no navegador
```
http://localhost:8000
```

### 2. Fazer login
```
Email: admin@example.com
Password: password
```

### 3. Explorar admin dashboard
Veja cursos, reparos, usuários, etc

### 4. Testar app Flutter (opcional)
```bash
cd app
flutter pub get
flutterfire configure
flutter run
```

---

## ❌ SE DER ERRO

### "Arquivo ZIP não encontrado"

**Solução**: Editar o script e trocar a URL

```bash
# Abrir arquivo install-auto-linux-mac.sh
# Procurar por: ZIP_URL="https://..."
# Trocar para sua URL correta
# Executar novamente
```

---

### "Docker não encontrado" (Windows)

**Solução**: Instalar Docker Desktop

1. https://www.docker.com/products/docker-desktop
2. Reiniciar computador
3. Executar instalador novamente

---

### "Sem permissão" (Linux)

**Solução**:
```bash
chmod +x install-auto-linux-mac.sh
./install-auto-linux-mac.sh
```

---

## 📞 PRÓXIMAS ETAPAS

Depois que tudo estiver pronto:

1. **Configurar Mercado Pago** (para aceitar pagamentos)
2. **Configurar Firebase** (para notificações push)
3. **Customizar** (seu logo, cores, textos)
4. **Adicionar seus cursos** (ao invés dos de teste)
5. **Deploy** (colocar em produção)

---

## 💡 DICAS

✅ **Sempre rode no Docker**  
❌ Não instale tudo manualmente

✅ **Mantenha o código versionado**  
Coloque em GitHub

✅ **Teste localmente antes de deploy**  
Nunca vá ao ar sem testar

---

## 🎯 RESUMO

```
1. Copiar comando (Linux/Mac ou Windows)
2. Abrir terminal/PowerShell
3. Colar comando
4. Apertar Enter
5. Aguardar ~20 minutos
6. Acessar http://localhost:8000
7. Pronto! 🎉
```

---

**Dúvidas? Veja `GUIA_INSTALADORES_AUTO.md`**

**Boa sorte! 🚀**
