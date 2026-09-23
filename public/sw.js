// Service worker do PWA - Escola da Manutenção.
// Propositalmente mínimo e seguro: NÃO guarda páginas, login, pagamentos
// nem API em cache. Só intercepta a abertura de páginas (GET) e, se não
// houver internet, mostra /offline.html. Todo o resto vai direto à rede.
const CACHE = 'escola-pwa-v1';
const PRECACHE = ['/offline.html', '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET' || req.mode !== 'navigate') return; // rede normal
  event.respondWith(
    fetch(req).catch(() => caches.match('/offline.html'))
  );
});
