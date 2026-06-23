self.addEventListener('install', (e) => {
    console.log('[Service Worker] Instalado correctamente');
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    console.log('[Service Worker] Activado y listo');
});

self.addEventListener('fetch', (e) => {
    // Por ahora, dejamos que el internet maneje todo normal.
    // Aquí después se puede programar el modo "Offline".
});
