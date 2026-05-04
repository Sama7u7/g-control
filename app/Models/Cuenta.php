<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Cuenta extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'saldo_inicial',
        'color',
        'tasa_rendimiento',
        'tope_rendimiento',
        'tasa_excedente'
    ];

    /**
     * Esto hace que los cálculos aparezcan automáticamente
     * al convertir el modelo a JSON o Array.
     */
    protected $appends = [
        'saldo_actual',
        'rendimiento_mensual_estimado',
        'rendimiento_detallado'
    ];

    // 1. Relación polimórfica para gastos e ingresos
    public function movimientos(): MorphMany
    {
        return $this->morphMany(Movimiento::class, 'movible');
    }

    // 2. Cálculo del saldo real (Activos)
    public function getSaldoActualAttribute()
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastos = $this->movimientos()->where('tipo', 'gasto')->sum('monto');

        // Descontamos lo que salió para pagar tarjetas de crédito
        $pagosRealizados = Abono::where('cuenta_id', $this->id)->sum('monto');

        return ($this->saldo_inicial + $ingresos) - ($gastos + $pagosRealizados);
    }

    // 3. Rendimiento Mensual Total (La suma de base + excedente)
    public function getRendimientoMensualEstimadoAttribute()
    {
        $detalles = $this->rendimiento_detallado;
        return $detalles ? ($detalles['base'] + $detalles['excedente']) : 0;
    }

    // 4. Desglose para el Dashboard (Rendimiento por rangos)
    public function getRendimientoDetalladoAttribute()
    {
        if (!$this->tasa_rendimiento || $this->tasa_rendimiento <= 0) return null;

        $saldo = $this->saldo_actual;
        $tope = $this->tope_rendimiento ?? 0;

        $montoBase = ($tope > 0 && $saldo > $tope) ? $tope : $saldo;
        $montoExcedente = ($tope > 0 && $saldo > $tope) ? ($saldo - $tope) : 0;

        return [
            'base' => ($montoBase * ($this->tasa_rendimiento / 100)) / 12,
            'excedente' => ($montoExcedente * (($this->tasa_excedente ?? 0) / 100)) / 12,
        ];
    }
}
