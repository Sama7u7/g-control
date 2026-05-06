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
        'activo',                 // ← agregado
        'ultima_actualizacion',   // ← agregado (para rendimiento por días)
        'tasa_rendimiento',
        'tope_rendimiento',
        'tasa_excedente',
    ];

    protected $casts = [
        'activo'               => 'boolean',
        'ultima_actualizacion' => 'date',
        'saldo_inicial'        => 'decimal:2',
        'tasa_rendimiento'     => 'decimal:2',
        'tope_rendimiento'     => 'decimal:2',
        'tasa_excedente'       => 'decimal:2',
    ];

    protected $appends = [
        'saldo_actual',
        'rendimiento_mensual_estimado',
        'rendimiento_detallado',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function movimientos(): MorphMany
    {
        return $this->morphMany(Movimiento::class, 'movible');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Saldo real = saldo_inicial + ingresos − gastos − abonos realizados.
     * No hay columna saldo_actual en la tabla; siempre se calcula.
     * Para correcciones manuales se crea un movimiento de ajuste
     * desde gestion-cuentas (sincronizarSaldo).
     */
    public function getSaldoActualAttribute(): float
    {
        $ingresos       = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastos         = $this->movimientos()->where('tipo', 'gasto')->sum('monto');
        $pagosRealizados = Abono::where('cuenta_id', $this->id)->sum('monto');

        return (float) (($this->saldo_inicial + $ingresos) - ($gastos + $pagosRealizados));
    }

    /**
     * Rendimiento mensual estimado = base + excedente.
     */
    public function getRendimientoMensualEstimadoAttribute(): float
    {
        $detalles = $this->rendimiento_detallado;
        return $detalles ? ($detalles['base'] + $detalles['excedente']) : 0.0;
    }

    /**
     * Desglose del rendimiento usando la fórmula del Sheets:
     *   - Si hay tope: MIN(saldo, tope) × tasa  +  MAX(0, saldo−tope) × tasa_excedente
     *   - Sin tope:    saldo × tasa
     * Devuelve rendimiento mensual (÷ 12) para mostrar en dashboard.
     */
    public function getRendimientoDetalladoAttribute(): ?array
    {
        if (!$this->tasa_rendimiento || $this->tasa_rendimiento <= 0) {
            return null;
        }

        $saldo = $this->saldo_actual;
        $tope  = $this->tope_rendimiento ?? 0;

        // Si existe tope (Nu, Mercado Pago, etc.)
        if ($tope > 0) {
            $montoBase      = min($saldo, $tope);
            $montoExcedente = max(0, $saldo - $tope);
            $tasaExc        = ($this->tasa_excedente ?? 0) / 100;
        } else {
            $montoBase      = $saldo;
            $montoExcedente = 0;
            $tasaExc        = 0;
        }

        $tasaBase = $this->tasa_rendimiento / 100;

        return [
            'base'      => ($montoBase      * $tasaBase) / 12,
            'excedente' => ($montoExcedente * $tasaExc)  / 12,
        ];
    }

    /**
     * Rendimiento diario acumulado desde ultima_actualizacion (fórmula exacta del Sheets).
     * Útil si quieres mostrar cuánto llevas ganado en el período actual.
     */
    public function getRendimientoAcumuladoAttribute(): float
    {
        if (!$this->tasa_rendimiento || !$this->ultima_actualizacion) {
            return 0.0;
        }

        $dias     = now()->diffInDays($this->ultima_actualizacion);
        $saldo    = $this->saldo_actual;
        $tasa     = $this->tasa_rendimiento / 100;
        $tope     = $this->tope_rendimiento ?? 0;
        $tasaExc  = ($this->tasa_excedente ?? 0) / 100;

        if ($tope > 0) {
            $base      = min($saldo, $tope);
            $excedente = max(0, $saldo - $tope);
            return ($base * pow(1 + $tasa / 365, $dias))
                 + ($excedente * pow(1 + $tasaExc / 365, $dias))
                 - $saldo; // solo el rendimiento, no el principal
        }

        return ($saldo * pow(1 + $tasa / 365, $dias)) - $saldo;
    }
}