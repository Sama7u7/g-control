<?php

use Livewire\Volt\Component;
use App\Models\Movimiento;

new class extends Component {
    public function rendering($view)
    {
        return $view->layout('components.layouts.app');
    }

    public function with()
    {
        $user = auth()->user();

        // Creamos las fechas exactas de inicio y fin de mes (Formato: YYYY-MM-DD)
        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();

        return [
            // Usamos whereBetween para un filtrado seguro y universal
            'totalIngresos' =>
                $user
                    ->movimientos()
                    ->where('tipo', 'ingreso')
                    ->whereBetween('fecha', [$inicioMes, $finMes])
                    ->sum('monto') ?? 0,

            'totalGastos' =>
                $user
                    ->movimientos()
                    ->where('tipo', 'gasto')
                    ->whereBetween('fecha', [$inicioMes, $finMes])
                    ->sum('monto') ?? 0,

            'movimientos' => $user
                ->movimientos()
                ->whereBetween('fecha', [$inicioMes, $finMes])
                ->latest('fecha')
                ->get(),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-8 pb-20">
    {{-- Header --}}
    <header>
        <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Resumen Mensual
        </h1>
        <p class="font-body text-muted text-[0.95rem] mt-1">Análisis de tus movimientos en
            {{ now()->translatedFormat('F') }}</p>
    </header>

    {{-- Tarjetas de Ingresos / Egresos --}}
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-green-light p-7 rounded-sys-card border border-border">
            <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-green mb-2">Ingresos Totales
            </p>
            <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-green">
                ${{ number_format($totalIngresos, 2) }}</h2>
        </div>
        <div class="bg-rose-light p-7 rounded-sys-card border border-border">
            <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-rose mb-2">Gastos Totales
            </p>
            <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-rose">
                ${{ number_format($totalGastos, 2) }}</h2>
        </div>
    </div>

    {{-- Lista de Movimientos --}}
    <div class="bg-white rounded-sys-card border border-border overflow-hidden">
        <div class="p-6 border-b border-border flex justify-between items-center">
            <h3 class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink">Detalle del Mes</h3>
        </div>
        <div class="p-6">
            @livewire('lista-movimientos')
        </div>
    </div>
</div>
