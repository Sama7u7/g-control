<?php

use Livewire\Volt\Component;
use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public function with()
    {
        $cuentas = Cuenta::all();

        // 1. Activos: Suma de saldos de cuentas tipo DEBITO
        $activos = $cuentas->where('tipo', 'debito')->sum(fn($c) => $c->saldo_actual);

        // 2. Pasivos: Suma de saldos de cuentas tipo CREDITO (deudas)
        $pasivos = $cuentas->where('tipo', 'credito')->sum(fn($c) => $c->saldo_actual);

        // 3. Patrimonio Neto
        $patrimonioNeto = $activos + $pasivos;

        // 4. Datos para la gráfica (Gastos por categoría del mes actual)
        $gastosMes = Movimiento::query()->where('tipo', 'gasto')->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->select('categoria_id', DB::raw('SUM(monto) as total'))->groupBy('categoria_id')->with('categoria')->get();

        return [
            'patrimonioNeto' => $patrimonioNeto,
            'activos' => $activos,
            'pasivos' => $pasivos,
            'labels' => $gastosMes->map(fn($g) => $g->categoria->nombre ?? 'Sin categoría'),
            'valores' => $gastosMes->pluck('total'),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 text-white p-6 rounded-3xl shadow-lg border border-slate-800">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Patrimonio Neto</p>
            <h2 class="text-3xl font-black mt-1">${{ number_format($patrimonioNeto, 2) }}</h2>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Activos</p>
            <h2 class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($activos, 2) }}</h2>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Pasivos</p>
            <h2 class="text-2xl font-bold text-rose-500 mt-1">${{ number_format($pasivos, 2) }}</h2>
        </div>
    </div>

    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <h3 class="text-gray-800 font-extrabold text-lg mb-6">Gastos por Categoría</h3>

        <div class="flex flex-col md:flex-row items-center justify-around gap-8">
            <div class="w-64 h-64">
                <canvas id="gastosChart"></canvas>
            </div>

            <div class="flex-1 space-y-2">
                @foreach ($labels as $index => $label)
                    <div class="flex justify-between items-center text-sm">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full"
                                style="background-color: {{ ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'][$index % 5] }}"></span>
                            {{ $label }}
                        </span>
                        <span class="font-bold text-gray-700">${{ number_format($valores[$index], 2) }}</span>
                    </div>
                @endforeach
            </div>
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
                        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        hoverOffset: 4,
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
