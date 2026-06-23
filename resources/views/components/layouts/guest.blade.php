<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mi Varo') }}</title>

    <meta name="theme-color" content="#4F3FF0">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mi Varo">
    <link rel="apple-touch-icon" href="{{ asset('money-management.png') }}">

    <!-- Tipografías del Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Tailwind Config CDN (Mantenemos esto para que coincida con el app layout) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#0D0D0F',
                        muted: '#6B6B78',
                        hint: '#A8A8B0',
                        surface: '#F7F6F3',
                        accent: '#4F3FF0',
                        'accent-light': '#EAE8FF',
                        green: '#16A34A',
                        'green-light': '#DCFCE7',
                        amber: '#D97706',
                        'amber-light': '#FEF3C7',
                        rose: '#E11D48',
                        'rose-light': '#FFE4E6',
                        border: 'rgba(0,0,0,0.08)'
                    },
                    fontFamily: {
                        display: ['Syne', 'sans-serif'],
                        body: ['DM Sans', 'sans-serif'],
                    },
                    borderRadius: {
                        'sys-tag': '4px',
                        'sys-input': '8px',
                        'sys-stat': '12px',
                        'sys-card': '20px',
                        'sys-pill': '100px',
                    }
                }
            }
        }
    </script>
</head>

<body class="font-body text-ink antialiased bg-[#FAFAF8]">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 pb-10 sm:pt-0 px-4">
        <div>
            <!-- Reemplazamos el logo SVG por texto estilizado con tu marca -->
            <a href="/" wire:navigate
                class="font-display font-extrabold text-[2.5rem] tracking-[-0.04em] text-ink leading-none block text-center mb-6">
                Mi <span class="text-accent">Varo.</span>
            </a>
        </div>

        <div class="w-full sm:max-w-md px-8 py-10 bg-white border border-border overflow-hidden rounded-sys-card">
            {{ $slot }}
        </div>
    </div>
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
