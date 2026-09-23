{{-- PWA: ícones, manifest e service worker (site "instalável" no celular) --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0066FF">
<link rel="icon" type="image/png" href="/favicon.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Escola">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>
