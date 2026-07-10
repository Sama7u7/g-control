<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Mi Varo') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    @livewireStyles
</head>

<body class="bg-[#FAFAF8] text-ink font-body pb-24">

    <main class="max-w-7xl mx-auto px-4 py-6 md:py-10">
        {{ $slot }}
    </main>

    @livewireScripts

    {{-- Navegación Inferior --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-border px-6 py-3 flex justify-between items-center z-50">

        <a href="/dashboard"
            class="flex flex-col items-center {{ request()->is('dashboard') ? 'text-accent' : 'text-hint hover:text-muted' }} transition-colors gap-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-display font-bold text-[0.6rem] uppercase tracking-[0.1em]">Inicio</span>
        </a>

        <a href="/resumen"
            class="flex flex-col items-center {{ request()->is('resumen') ? 'text-accent' : 'text-hint hover:text-muted' }} transition-colors gap-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="font-display font-bold text-[0.6rem] uppercase tracking-[0.1em]">Resumen</span>
        </a>
        <a href="/historial"
            class="flex flex-col items-center {{ request()->is('historial') ? 'text-accent' : 'text-hint hover:text-muted' }} transition-colors gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mb-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            <span class="font-display font-bold text-[0.6rem] uppercase tracking-[0.1em]">Historial</span>
        </a>

        <a href="/configuracion"
            class="flex flex-col items-center {{ request()->is('configuracion') ? 'text-accent' : 'text-hint hover:text-muted' }} transition-colors gap-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            <span class="font-display font-bold text-[0.6rem] uppercase tracking-[0.1em]">Ajustes</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="flex flex-col items-center">
            @csrf
            <button type="submit" class="flex flex-col items-center text-hint hover:text-rose transition-colors gap-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span class="font-display font-bold text-[0.6rem] uppercase tracking-[0.1em]">Salir</span>
            </button>
        </form>
    </nav>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PWA registrada exitosamente', reg))
                    .catch(err => console.log('Error al registrar PWA', err));
            });
        }
    </script>
</body>

</html>
