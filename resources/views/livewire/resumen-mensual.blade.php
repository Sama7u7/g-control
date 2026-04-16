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
        $mesActual = now()->month;
        $anioActual = now()->year;

        return [
            'totalIngresos' => Movimiento::where('tipo', 'ingreso')->whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->sum('monto'),
            'totalGastos' => Movimiento::where('tipo', 'gasto')->whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->sum('monto'),
            'movimientos' => Movimiento::whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->latest('fecha')->get(),
        ];
    }
}; ?>

<div class="space-y-8">
    <header>
        <h1 class="text-3xl font-black text-slate-900">Resumen Mensual</h1>
        <p class="text-slate-500 font-medium">Análisis de tus movimientos en {{ now()->translatedFormat('F') }}</p>
    </header>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-emerald-50 p-6 rounded-3xl border border-emerald-100">
            <p class="text-emerald-600 text-xs font-bold uppercase">Ingresos Totales</p>
            <h2 class="text-2xl font-black text-emerald-700 mt-1">${{ number_format($totalIngresos, 2) }}</h2>
        </div>
        <div class="bg-rose-50 p-6 rounded-3xl border border-rose-100">
            <p class="text-rose-600 text-xs font-bold uppercase">Gastos Totales</p>
            <h2 class="text-2xl font-black text-rose-700 mt-1">${{ number_format($totalGastos, 2) }}</h2>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Detalle del Mes</h3>
        </div>
        @livewire('lista-movimientos')
    </div>
</div>
