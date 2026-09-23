{{-- Aviso de instalação só no iPhone/iPad pelo Safari (o iOS não tem botão
     automático de instalar). Some se já estiver instalado ou se o aluno fechar. --}}
<div id="pwa-ios-banner" style="display:none; position:fixed; left:12px; right:12px; bottom:12px; z-index:9999;
     background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.18); padding:14px 44px 14px 14px;
     font-family:-apple-system, Segoe UI, Roboto, Arial, sans-serif; font-size:14px; color:#1f2937; line-height:1.45;">
    <button type="button" id="pwa-ios-close" aria-label="Fechar"
            style="position:absolute; top:6px; right:8px; background:none; border:0; font-size:22px; color:#9ca3af;">×</button>
    <div style="display:flex; gap:12px; align-items:center;">
        <img src="/icons/icon-192.png" alt="" style="width:44px; height:44px; border-radius:10px;">
        <div>
            <strong>Instale o app da Escola</strong><br>
            Toque em <strong>Compartilhar</strong>
            <span style="display:inline-block; border:1.5px solid #0066FF; border-radius:4px; padding:0 4px; color:#0066FF; font-weight:bold;">↑</span>
            e depois em <strong>Adicionar à Tela de Início</strong>.
        </div>
    </div>
</div>
<script>
    (function () {
        try {
            var ua = navigator.userAgent || '';
            var isIOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            var isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|GSA/.test(ua);
            var installed = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
            var dismissed = localStorage.getItem('pwaIosBannerDismissed') === '1';
            if (!isIOS || !isSafari || installed || dismissed) return;
            var el = document.getElementById('pwa-ios-banner');
            el.style.display = 'block';
            document.getElementById('pwa-ios-close').addEventListener('click', function () {
                el.style.display = 'none';
                localStorage.setItem('pwaIosBannerDismissed', '1');
            });
        } catch (e) {}
    })();
</script>
