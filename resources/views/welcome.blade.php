<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Varo | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-[#F8FAFC] antialiased text-slate-900">
    <div class="max-w-7xl mx-auto px-4 py-6 md:py-10">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">¡Qué onda, Mano! 👋</h1>
                <p class="text-slate-500 font-medium">Aquí está el resumen de tus varos hoy.</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="text-xs font-bold px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full uppercase tracking-wider">
                    {{ now()->translatedFormat('F Y') }}
                </span>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-8 space-y-8">
                @livewire('dashboard')

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 italic">Historial reciente</h3>
                    </div>
                    <div class="p-2">
                        @livewire('lista-movimientos')
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 lg:sticky lg:top-10">
                <div class="bg-indigo-50 border border-indigo-100 rounded-3xl p-2">
                    <div class="p-4">
                        <h3 class="font-extrabold text-indigo-900 text-lg mb-1">Registrar</h3>
                        <p class="text-indigo-600/70 text-sm mb-4">Anota tus gastos al momento</p>
                    </div>
                    @livewire('registrar-movimiento')
                </div>
            </div>
        </div>
    </div>
    @livewireScripts
</body>

</html>
