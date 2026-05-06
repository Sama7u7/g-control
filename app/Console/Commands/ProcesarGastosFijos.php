<?php

namespace App\Console\Commands;

use App\Models\GastoFijo;
use Illuminate\Console\Command;

class ProcesarGastosFijos extends Command
{
    protected $signature   = 'gastos:procesar';
    protected $description = 'Registra automáticamente los gastos fijos que vencen hoy';

    public function handle(): void
    {
        $hoy = now()->toDateString();

        $gastos = GastoFijo::where('activo', true)
                           ->where('registro_automatico', true)
                           ->whereDate('proxima_fecha', '<=', $hoy)
                           ->get();

        if ($gastos->isEmpty()) {
            $this->info('Sin gastos fijos que procesar hoy.');
            return;
        }

        foreach ($gastos as $gasto) {
            $gasto->registrarMovimiento();
            $this->info("✅ Registrado: {$gasto->nombre} — $" . number_format($gasto->monto, 2));
        }

        $this->info("Total procesados: {$gastos->count()}");
    }
}