<?php

use Livewire\Volt\Component;
use App\Models\Cuenta;
use App\Models\TarjetaCredito;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

new class extends Component {
    #[On('movimiento-registrado')]
    public function actualizarDatos() {}

    public function with()
    {
        // 1. Cargamos todas las entidades
        $cuentas_lista = Cuenta::all();
        $tarjetas_lista = TarjetaCredito::all();

        // 2. Totales para las tarjetas superiores
        $activos = $cuentas_lista->sum->saldo_actual;
        $pasivos = $tarjetas_lista->sum->deuda_actual;
        $patrimonioNeto = $activos - $pasivos;

        // 3. Datos para la gráfica (Gastos por categoría del mes actual)
        $gastosMes = Movimiento::query()->where('tipo', 'gasto')->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->select('categoria_id', DB::raw('SUM(monto) as total'))->groupBy('categoria_id')->with('categoria')->get();

        return [
            'patrimonioNeto' => $patrimonioNeto,
            'activos' => $activos,
            'pasivos' => $pasivos,
            'cuentas_lista' => $cuentas_lista,
            'tarjetas_lista' => $tarjetas_lista,
            'labels' => $gastosMes->map(fn($g) => $g->categoria->nombre ?? 'SIN CATEGORÍA'),
            'valores' => $gastosMes->pluck('total'),
        ];
    }

    public function rendering($view)
    {
        return $view->layout('components.layouts.app');
    }
}; ?>

<div class="space-y-8 pb-10">
    {{-- Header --}}
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 italic">¡Qué onda, Bro! 💸</h1>
            <p class="text-slate-500 font-medium">Así va el flujo del varo hoy.</p>
        </div>
        <div class="px-5 py-2 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
            <span class="text-slate-500 font-bold uppercase text-xs tracking-widest">
                Hoy es {{ now()->translatedFormat('d F, Y | h:i A') }}
            </span>
        </div>
    </header>

    {{-- Tarjetas de Resumen Superior --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
            class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-800 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full"></div>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Patrimonio Neto</p>
            <h2 class="text-4xl font-black tracking-tighter">${{ number_format($patrimonioNeto, 2) }}</h2>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <p class="text-emerald-500 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Activos Totales</p>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter">${{ number_format($activos, 2) }}</h2>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <p class="text-rose-500 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Pasivos Totales</p>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter">-${{ number_format($pasivos, 2) }}</h2>
        </div>
    </div>

    {{-- SECCIÓN DE DESGLOSE TIPO EXCEL --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Desglose Cuentas --}}
        <div class="space-y-4">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest ml-4 italic">Desglose de Efectivo y
                Débito</h3>
            <div class="grid gap-3">
                @foreach ($cuentas_lista as $c)
                    <div
                        class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm flex justify-between items-center hover:scale-[1.02] transition-transform">
                        <div class="flex items-center gap-4">
                            <div class="w-3 h-12 rounded-full" style="background-color: {{ $c->color }}"></div>
                            <div>
                                <p class="font-black text-slate-800 tracking-tight text-lg">{{ $c->nombre }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">
                                    {{ $c->tipo }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-xl text-emerald-600 tracking-tighter">
                                ${{ number_format($c->saldo_actual, 2) }}</p>
                            @if ($c->tasa_rendimiento > 0)
                                <p
                                    class="text-[10px] font-black text-indigo-400 bg-indigo-50 px-2 py-0.5 rounded-lg inline-block">
                                    +${{ number_format($c->rendimiento_mensual_estimado, 2) }} <span
                                        class="text-[8px]">est./mes</span>
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Desglose Tarjetas --}}
        <div class="space-y-4">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest ml-4 italic">Estado de Créditos</h3>
            <div class="grid gap-3">
                @foreach ($tarjetas_lista as $t)
                    <div
                        class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm space-y-4 hover:scale-[1.02] transition-transform">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-12 rounded-full" style="background-color: {{ $t->color }}"></div>
                                <div>
                                    <p class="font-black text-slate-800 tracking-tight text-lg">{{ $t->nombre }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Límite:
                                        ${{ number_format($t->limite_credito, 2) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-xl text-rose-500 tracking-tighter">
                                    -${{ number_format($t->deuda_actual, 2) }}</p>
                                <p
                                    class="text-[9px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-lg inline-block">
                                    Corte: Día {{ $t->dia_corte }}</p>
                            </div>
                        </div>

                        {{-- Barra de Progreso --}}
                        @php $porcentaje = ($t->limite_credito > 0) ? ($t->deuda_actual / $t->limite_credito) * 100 : 0; @endphp
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-tighter px-1">
                                <span class="text-slate-400">Uso: {{ round($porcentaje) }}%</span>
                                <span class="text-slate-600 italic">Disponible:
                                    ${{ number_format($t->disponible, 2) }}</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden p-0.5">
                                <div class="h-full rounded-full transition-all duration-1000"
                                    style="width: {{ $porcentaje }}%; background-color: {{ $t->color }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Gráfica y Registro --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 space-y-8">
            <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100">
                <h3 class="font-black text-slate-800 text-xl mb-10 italic">Distribución de Gastos (Mes)</h3>
                <div class="flex flex-col md:flex-row items-center justify-around gap-12">
                    <div class="w-72 h-72">
                        <canvas id="gastosChart"></canvas>
                    </div>
                    <div class="flex-1 space-y-3 w-full">
                        @foreach ($labels as $index => $label)
                            <div
                                class="flex justify-between items-center p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <span class="flex items-center gap-3 font-bold text-slate-600">
                                    <span class="w-3 h-3 rounded-full shadow-sm"
                                        style="background-color: {{ ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'][$index % 6] }}"></span>
                                    {{ $label }}
                                </span>
                                <span
                                    class="font-black text-slate-900 text-lg">${{ number_format($valores[$index], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 italic text-lg">Historial Reciente</h3>
                    <button class="text-xs font-black text-indigo-500 uppercase tracking-widest">Ver todo</button>
                </div>
                <div class="p-6">
                    @livewire('lista-movimientos')
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 lg:sticky lg:top-10">
            @livewire('registrar-movimiento')
        </div>
    </div>

    {{-- Script de Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            const ctx = document.getElementById('gastosChart');
            if (!ctx) return;
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        data: @json($valores),
                        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                            '#ec4899'
                        ],
                        hoverOffset: 20,
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '80%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</div>
