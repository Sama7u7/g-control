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
        $cuentas_lista = Cuenta::all();
        $tarjetas_lista = TarjetaCredito::all();

        // Usamos saldo_total para que el resumen incluya rendimientos diarios
        $activos = $cuentas_lista->sum('saldo_total');
        $pasivos = $tarjetas_lista->sum->deuda_actual;
        $patrimonioNeto = $activos - $pasivos;

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
            <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Tu varo.</h1>
            <p class="font-body font-normal text-muted text-[0.95rem]">Así va el flujo del dinero hoy.</p>
        </div>
        <div class="px-5 py-2 bg-white rounded-sys-pill border border-border flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
            <span class="font-display font-bold uppercase text-[0.65rem] tracking-[0.14em] text-ink">
                {{ now()->timezone(auth()->user()->timezone ?? config('app.timezone'))->translatedFormat('d M Y | h:i A') }}
            </span>
        </div>
    </header>

    {{-- Tarjetas de Resumen Superior --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-7 rounded-sys-card border border-border">
            <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent mb-2">Patrimonio Neto
            </p>
            <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                ${{ number_format($patrimonioNeto, 2) }}</h2>
        </div>

        <div class="bg-white p-7 rounded-sys-card border border-border">
            <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-green mb-2">Activos Totales
            </p>
            <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                ${{ number_format($activos, 2) }}</h2>
        </div>

        <div class="bg-white p-7 rounded-sys-card border border-border">
            <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-rose mb-2">Pasivos Totales
            </p>
            <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                -${{ number_format($pasivos, 2) }}</h2>
        </div>
    </div>

    {{-- SECCIÓN DE DESGLOSE --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Desglose Cuentas --}}
        <div class="space-y-4">
            <h3 class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent ml-2">Desglose de
                Efectivo</h3>
            <div class="grid gap-3">
                @foreach ($cuentas_lista as $c)
                    <div
                        class="bg-white p-6 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface transition-colors cursor-default">
                        <div class="flex items-center gap-4">
                            <div class="w-3 h-12 rounded-sys-pill" style="background-color: {{ $c->color }}"></div>
                            <div>
                                <p class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                    {{ $c->nombre }}</p>
                                <p class="font-body text-[0.82rem] font-light text-muted">{{ $c->tipo }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-green">
                                ${{ number_format($c->saldo_total, 2) }}
                            </p>
                            @if ($c->tasa_rendimiento > 0)
                                <div class="mt-1">
                                    <span
                                        class="inline-block font-display font-bold text-[0.6rem] tracking-[0.1em] uppercase px-2 py-[0.2rem] rounded-sys-pill bg-green-light text-green">
                                        +${{ number_format($c->rendimiento_mensual_estimado, 2) }}/mes neto
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Desglose Tarjetas --}}
        <div class="space-y-4">
            <h3 class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent ml-2">Estado de
                Créditos</h3>
            <div class="grid gap-3">
                @foreach ($tarjetas_lista as $t)
                    <div
                        class="bg-white p-6 rounded-sys-card border border-border space-y-4 hover:bg-surface transition-colors cursor-default">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-12 rounded-sys-pill" style="background-color: {{ $t->color }}">
                                </div>
                                <div>
                                    <p class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                        {{ $t->nombre }}</p>
                                    <p class="font-body text-[0.82rem] font-light text-muted">Límite:
                                        ${{ number_format($t->limite_credito, 2) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-rose">
                                    -${{ number_format($t->deuda_actual, 2) }}</p>
                                <div class="mt-1">
                                    <span
                                        class="inline-block font-display font-bold text-[0.6rem] tracking-[0.1em] uppercase px-2 py-[0.2rem] rounded-sys-pill bg-surface text-muted">
                                        Corte: Día {{ $t->dia_corte }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @php $porcentaje = ($t->limite_credito > 0) ? ($t->deuda_actual / $t->limite_credito) * 100 : 0; @endphp
                        <div class="space-y-1.5">
                            <div class="flex justify-between font-body text-[0.7rem] text-hint px-1">
                                <span>Uso: {{ round($porcentaje) }}%</span>
                                <span>Disponible: ${{ number_format($t->disponible, 2) }}</span>
                            </div>
                            <div class="w-full h-2 bg-surface rounded-sys-pill overflow-hidden">
                                <div class="h-full rounded-sys-pill transition-all duration-1000"
                                    style="width: {{ $porcentaje }}%; background-color: {{ $porcentaje > 80 ? 'var(--rose)' : $t->color }}">
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
            <div class="bg-white p-8 rounded-sys-card border border-border">
                <h3 class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink mb-8">Distribución de
                    Gastos (Mes)</h3>
                <div class="flex flex-col md:flex-row items-center justify-around gap-8">
                    <div class="w-64 h-64">
                        <canvas id="gastosChart"></canvas>
                    </div>
                    <div class="flex-1 space-y-3 w-full">
                        @foreach ($labels as $index => $label)
                            <div
                                class="flex justify-between items-center p-4 rounded-sys-stat bg-surface border border-border">
                                <span class="flex items-center gap-3 font-body font-normal text-muted text-[0.95rem]">
                                    <span class="w-3 h-3 rounded-full"
                                        style="background-color: {{ ['#4F3FF0', '#16A34A', '#D97706', '#E11D48', '#8b5cf6', '#ec4899'][$index % 6] }}"></span>
                                    {{ $label }}
                                </span>
                                <span class="font-display font-bold text-ink text-[1rem] tracking-[-0.01em]">
                                    ${{ number_format($valores[$index], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-sys-card border border-border overflow-hidden">
                <div class="p-6 border-b border-border flex justify-between items-center">
                    <h3 class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink">Historial Reciente
                    </h3>
                    <a href="{{ route('historial') }}"
                        class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent hover:text-ink transition-colors">
                        Ver todo
                    </a>
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
                        backgroundColor: ['#4F3FF0', '#16A34A', '#D97706', '#E11D48', '#8b5cf6',
                            '#ec4899'
                        ],
                        hoverOffset: 10,
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '75%',
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
