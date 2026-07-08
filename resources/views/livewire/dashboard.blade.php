<?php

use Livewire\Volt\Component;
use App\Models\Cuenta;
use App\Models\TarjetaCredito;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

new class extends Component {
    // 1. NUEVAS VARIABLES PARA LOS FILTROS
    public $mesFiltro;
    public $anioFiltro;

    // 2. INICIALIZAMOS LOS FILTROS AL MES/AÑO ACTUAL
    public function mount()
    {
        $this->mesFiltro = now()->month;
        $this->anioFiltro = now()->year;
    }

    #[On('movimiento-registrado')]
    public function actualizarDatos() {}

    // 3. SEGURIDAD: SI CAMBIA EL AÑO AL ACTUAL, LIMITAMOS EL MES FUTURO
    public function updatedAnioFiltro()
    {
        if ($this->anioFiltro == now()->year && $this->mesFiltro > now()->month) {
            $this->mesFiltro = now()->month;
        }
    }

    public function with()
    {
        $usuario = auth()->user();

        // ------------------ CUENTAS Y TARJETAS ------------------
        $cuentas_lista = $usuario->cuentas()->get();
        $tarjetas_lista = $usuario->tarjetasCredito()->get();
        $activos = $cuentas_lista->sum('saldo_total');
        $pasivos = $tarjetas_lista->sum->deuda_actual;
        $patrimonioNeto = $activos - $pasivos;

        // ------------------ LÓGICA DE FILTROS ------------------
        // Años disponibles (Compatible con SQLite y MySQL)
        $aniosDisponibles = $usuario->movimientos()->select('fecha')->get()->map(fn($m) => \Carbon\Carbon::parse($m->fecha)->year)->unique()->sortDesc()->values()->toArray();
        // Aseguramos que el año actual exista en el selector
        if (!in_array(now()->year, $aniosDisponibles)) {
            array_unshift($aniosDisponibles, now()->year);
        }

        // Lógica Híbrida: Meses a mostrar (1-12 o hasta el mes actual)
        $mesLimite = $this->anioFiltro == now()->year ? now()->month : 12;
        $mesesParaMostrar = range(1, $mesLimite);

        $nombresMeses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        // ------------------ GRÁFICA 1: MES (PASTEL) ------------------
        $gastosMes = $usuario
            ->movimientos()
            ->where('tipo', 'gasto')
            ->whereMonth('fecha', $this->mesFiltro)
            ->whereYear('fecha', $this->anioFiltro)
            ->whereNotNull('categoria_id') // Ignoramos traspasos
            ->select('categoria_id', DB::raw('SUM(monto) as total'))
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get();

        $labelsDoughnut = $gastosMes->map(fn($g) => $g->categoria->nombre ?? 'SIN CATEGORÍA')->toArray();
        $valoresDoughnut = $gastosMes->pluck('total')->toArray();

        // ------------------ GRÁFICA 2: AÑO (BARRAS) (Compatible con SQLite y MySQL) ------------------
        $gastosAnioBD = $usuario->movimientos()->where('tipo', 'gasto')->whereYear('fecha', $this->anioFiltro)->whereNotNull('categoria_id')->get()->groupBy(fn($m) => \Carbon\Carbon::parse($m->fecha)->month)->map(fn($group) => $group->sum('monto'));

        $valoresBar = [];
        for ($i = 1; $i <= 12; $i++) {
            $valoresBar[] = $gastosAnioBD->get($i, 0); // Rellena con 0 si no hay gastos en ese mes
        }

        // ------------------ ACTUALIZACIÓN EN VIVO ------------------
        // Esto le avisa a JS que los datos cambiaron para que redibuje las gráficas
        $this->dispatch('actualizar-graficas', labelsDoughnut: $labelsDoughnut, valoresDoughnut: $valoresDoughnut, valoresBar: $valoresBar);

        return [
            'patrimonioNeto' => $patrimonioNeto,
            'activos' => $activos,
            'pasivos' => $pasivos,
            'cuentas_lista' => $cuentas_lista,
            'tarjetas_lista' => $tarjetas_lista,
            'labels' => $labelsDoughnut,
            'valores' => $valoresDoughnut,
            'valoresBar' => $valoresBar,
            'aniosDisponibles' => $aniosDisponibles,
            'mesesParaMostrar' => $mesesParaMostrar,
            'nombresMeses' => $nombresMeses,
        ];
    }

    public function rendering($view)
    {
        return $view->layout('components.layouts.app');
    }
}; ?>

<div class="max-w-7xl mx-auto space-y-4 pb-8 px-4">
    {{-- Header --}}
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h1 class="font-display font-bold text-2xl tracking-tight text-ink leading-tight">Hola
                {{ auth()->user()->name }}</h1>
            <p class="font-body font-normal text-muted text-sm mt-0.5">Así va el flujo del dinero hoy.</p>
        </div>
        <div class="px-3 py-1.5 bg-white rounded-sys-pill border border-border flex items-center gap-2 shadow-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
            <span class="font-display font-bold uppercase text-[0.65rem] tracking-wider text-ink">
                {{ now()->timezone(auth()->user()->timezone ?? config('app.timezone'))->translatedFormat('d M Y | h:i A') }}
            </span>
        </div>
    </header>

    {{-- ARQUITECTURA BENTO BOX (3 COLUMNAS) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

        {{-- ========================================== --}}
        {{-- PILAR 1 (IZQUIERDA - 3/12): LO QUE TENGO   --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-3 space-y-4">

            {{-- Tarjeta Maestra de Saldos --}}
            <div class="bg-white p-4 rounded-xl border border-border shadow-sm flex flex-col gap-3">
                <div>
                    <p class="font-display font-bold text-[0.65rem] tracking-wider uppercase text-accent mb-0.5">
                        Patrimonio Neto</p>
                    <h2 class="font-display font-extrabold text-2xl tracking-tight text-ink">
                        ${{ number_format($patrimonioNeto, 2) }}
                    </h2>
                </div>
                <div class="w-full h-px bg-border"></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-display font-bold text-[0.6rem] tracking-wider uppercase text-green mb-0.5">
                            Activos</p>
                        <h2 class="font-display font-bold text-sm tracking-tight text-ink">
                            ${{ number_format($activos, 2) }}</h2>
                    </div>
                    <div class="text-right">
                        <p class="font-display font-bold text-[0.6rem] tracking-wider uppercase text-rose mb-0.5">
                            Pasivos</p>
                        <h2 class="font-display font-bold text-sm tracking-tight text-ink">
                            -${{ number_format($pasivos, 2) }}</h2>
                    </div>
                </div>
            </div>

            {{-- Desglose Cuentas --}}
            <div class="space-y-2">
                <h3 class="font-display font-bold text-[0.65rem] tracking-wider uppercase text-accent ml-1">Efectivo
                </h3>
                <div class="grid gap-2 max-h-[220px] overflow-y-auto pr-1 custom-scrollbar">
                    @foreach ($cuentas_lista as $c)
                        <div
                            class="bg-white p-3 rounded-xl border border-border flex justify-between items-center shadow-sm">
                            <div class="flex items-center gap-2.5">
                                <div class="w-1.5 h-7 rounded-full" style="background-color: {{ $c->color }}"></div>
                                <div>
                                    <p class="font-display font-bold text-[0.85rem] text-ink">{{ $c->nombre }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[0.95rem] text-green">
                                    ${{ number_format($c->saldo_total, 2) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Desglose Tarjetas --}}
            <div class="space-y-2">
                <h3 class="font-display font-bold text-[0.65rem] tracking-wider uppercase text-accent ml-1">Créditos
                </h3>
                <div class="grid gap-2 max-h-[220px] overflow-y-auto pr-1 custom-scrollbar">
                    @foreach ($tarjetas_lista as $t)
                        <div class="bg-white p-3 rounded-xl border border-border space-y-2 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-1.5 h-7 rounded-full" style="background-color: {{ $t->color }}">
                                    </div>
                                    <div>
                                        <p class="font-display font-bold text-[0.85rem] text-ink">{{ $t->nombre }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-display font-extrabold text-[0.95rem] text-rose">
                                        -${{ number_format($t->deuda_actual, 2) }}
                                    </p>
                                </div>
                            </div>
                            @php $porcentaje = ($t->limite_credito > 0) ? ($t->deuda_actual / $t->limite_credito) * 100 : 0; @endphp
                            <div class="w-full h-1.5 bg-surface rounded-full overflow-hidden mt-1">
                                <div class="h-full rounded-full transition-all duration-1000"
                                    style="width: {{ $porcentaje }}%; background-color: {{ $porcentaje > 80 ? 'var(--rose)' : $t->color }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- PILAR 2 (CENTRO - 6/12): LO QUE HICE       --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-6 space-y-4">

            {{-- TARJETA: Gráfica del Mes --}}
            <div class="bg-white p-4 rounded-xl border border-border shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h3 class="font-display font-bold text-base text-ink">Distribución de Gastos</h3>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="mesFiltro"
                            class="bg-surface border border-border text-ink text-xs rounded-lg focus:ring-accent focus:border-accent block p-1.5 outline-none font-bold transition-all">
                            @foreach ($mesesParaMostrar as $m)
                                <option value="{{ $m }}">{{ $nombresMeses[$m] }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="anioFiltro"
                            class="bg-surface border border-border text-ink text-xs rounded-lg focus:ring-accent focus:border-accent block p-1.5 outline-none font-bold transition-all">
                            @foreach ($aniosDisponibles as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col xl:flex-row items-center justify-center gap-6">
                    <div class="w-36 h-36 shrink-0" wire:ignore>
                        <canvas id="gastosChart"></canvas>
                    </div>
                    <div class="flex-1 space-y-2 w-full max-h-[144px] overflow-y-auto pr-1 custom-scrollbar">
                        @if (count($labels) > 0)
                            @foreach ($labels as $index => $label)
                                <div
                                    class="flex justify-between items-center p-2 rounded bg-surface border border-border">
                                    <span class="flex items-center gap-2 font-body text-xs text-muted">
                                        <span class="w-2 h-2 rounded-full"
                                            style="background-color: {{ ['#4F3FF0', '#16A34A', '#D97706', '#E11D48', '#8b5cf6', '#ec4899'][$index % 6] }}"></span>
                                        {{ $label }}
                                    </span>
                                    <span class="font-display font-bold text-ink text-sm">
                                        ${{ number_format($valores[$index], 2) }}
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted font-bold text-xs">No hay gastos este mes.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TARJETA: Gráfica Anual --}}
            <div class="bg-white p-4 rounded-xl border border-border shadow-sm">
                <h3 class="font-display font-bold text-base text-ink mb-3">Resumen del Año ({{ $anioFiltro }})</h3>
                <div class="w-full h-32" wire:ignore>
                    <canvas id="anualChart"></canvas>
                </div>
            </div>

            {{-- TARJETA: Historial --}}
            <div class="bg-white rounded-xl border border-border overflow-hidden shadow-sm">
                <div class="p-3 border-b border-border flex justify-between items-center bg-surface/50">
                    <h3 class="font-display font-bold text-sm text-ink">Historial Reciente</h3>
                    <a href="{{ route('historial') }}"
                        class="font-display font-bold text-[0.6rem] tracking-wider uppercase text-accent hover:text-ink transition-colors">
                        Ver todo
                    </a>
                </div>
                <div class="p-3">
                    @livewire('lista-movimientos')
                </div>
            </div>

        </div>

        {{-- ========================================== --}}
        {{-- PILAR 3 (DERECHA - 3/12): LO QUE HARÉ      --}}
        {{-- ========================================== --}}
        <div class="lg:col-span-3 lg:sticky lg:top-4">
            @livewire('registrar-movimiento')
        </div>

    </div>

    {{-- ESTILOS PARA SCROLLBARS INTERNOS --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: var(--border);
            border-radius: 20px;
        }
    </style>

    {{-- SCRIPT: Lógica de Gráficas Reactivas --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            const ctxDoughnut = document.getElementById('gastosChart');
            let doughnutChart = null;
            if (ctxDoughnut) {
                doughnutChart = new Chart(ctxDoughnut, {
                    type: 'doughnut',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            data: @json($valores),
                            backgroundColor: ['#4F3FF0', '#16A34A', '#D97706', '#E11D48', '#8b5cf6',
                                '#ec4899'
                            ],
                            hoverOffset: 4,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        maintainAspectRatio: false
                    }
                });
            }

            const ctxBar = document.getElementById('anualChart');
            let barChart = null;
            if (ctxBar) {
                barChart = new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct',
                            'Nov', 'Dic'
                        ],
                        datasets: [{
                            label: 'Total Gastado',
                            data: @json($valoresBar),
                            backgroundColor: '#4F3FF0',
                            borderRadius: 4,
                            barThickness: 'flex',
                            maxBarThickness: 12
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 9
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 9
                                    }
                                }
                            }
                        }
                    }
                });
            }

            window.addEventListener('actualizar-graficas', (event) => {
                let info = event.detail;
                if (doughnutChart) {
                    doughnutChart.data.labels = info.labelsDoughnut;
                    doughnutChart.data.datasets[0].data = info.valoresDoughnut;
                    doughnutChart.update();
                }
                if (barChart) {
                    barChart.data.datasets[0].data = info.valoresBar;
                    barChart.update();
                }
            });
        });
    </script>
</div>
