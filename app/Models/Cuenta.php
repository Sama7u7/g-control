<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class Cuenta extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'entidad_financiera',
        'aplica_isr',
        'saldo_inicial',
        'color',
        'activo',
        'ultima_actualizacion',
        'tasa_rendimiento',
        'tope_rendimiento',
        'tasa_excedente',
        'tipo_interes',
    ];

    protected $casts = [
        'activo'               => 'boolean',
        'aplica_isr'           => 'boolean',
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
        'rendimiento_acumulado',
        'saldo_total',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function movimientos(): MorphMany
    {
        return $this->morphMany(Movimiento::class, 'movible');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getSaldoActualAttribute(): float
    {
        $ingresos       = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastos         = $this->movimientos()->where('tipo', 'gasto')->sum('monto');
        $pagosRealizados = Abono::where('cuenta_id', $this->id)->sum('monto') ?? 0;

        return (float) (($this->saldo_inicial + $ingresos) - ($gastos + $pagosRealizados));
    }

    public function getRendimientoMensualEstimadoAttribute(): float
    {
        $detalles = $this->rendimiento_detallado;
        return $detalles ? $detalles['neto'] : 0.0;
    }

    public function getRendimientoDetalladoAttribute(): ?array
    {
        if (!$this->tasa_rendimiento || $this->tasa_rendimiento <= 0) {
            return null;
        }

        $saldo = $this->saldo_actual;
        $tope  = $this->tope_rendimiento ?? 0;

        if ($this->tipo_interes === 'escalonado' && $tope > 0) {
            $montoBase      = min($saldo, $tope);
            $montoExcedente = max(0, $saldo - $tope);
            $tasaExc        = ($this->tasa_excedente ?? 0) / 100;
        } else {
            $montoBase      = $saldo;
            $montoExcedente = 0;
            $tasaExc        = 0;
        }

        $tasaBase = $this->tasa_rendimiento / 100;
        $rendimientoBrutoBase = ($montoBase * $tasaBase) / 12;
        $rendimientoBrutoExc  = ($montoExcedente * $tasaExc) / 12;

        $retencionISR = 0;
        if ($this->aplica_isr) {
            $tasaIsrAnual = 0.005;
            $retencionISR = ($saldo * $tasaIsrAnual) / 12;
        }

        return [
            'base'      => $rendimientoBrutoBase,
            'excedente' => $rendimientoBrutoExc,
            'isr'       => $retencionISR,
            'neto'      => max(0, ($rendimientoBrutoBase + $rendimientoBrutoExc) - $retencionISR),
        ];
    }

    public function getRendimientoAcumuladoAttribute(): float
    {
        if (!$this->tasa_rendimiento || !$this->ultima_actualizacion) {
            return 0.0;
        }

        // Cálculo a prueba de balas: obligamos a medir medianoche contra medianoche
        $fechaInicio = Carbon::parse($this->ultima_actualizacion)->startOfDay();
        $dias = abs(now()->startOfDay()->diffInDays($fechaInicio));

        if ($dias === 0) {
            return 0.0;
        }

        $saldo    = $this->saldo_actual;
        $tasa     = $this->tasa_rendimiento / 100;
        $tope     = $this->tope_rendimiento ?? 0;
        $tasaExc  = ($this->tasa_excedente ?? 0) / 100;

        $rendimientoBruto = 0;

        if ($this->tipo_interes === 'escalonado' && $tope > 0) {
            $base      = min($saldo, $tope);
            $excedente = max(0, $saldo - $tope);
            $rendimientoBruto = ($base * pow(1 + $tasa / 365, $dias)) + ($excedente * pow(1 + $tasaExc / 365, $dias)) - $saldo;
        } else {
            $rendimientoBruto = ($saldo * pow(1 + $tasa / 365, $dias)) - $saldo;
        }

        $retencionISR = 0;
        if ($this->aplica_isr) {
            $tasaIsrAnual = 0.005;
            $retencionISR = ($saldo * pow(1 + $tasaIsrAnual / 365, $dias)) - $saldo;
        }

        return max(0, $rendimientoBruto - $retencionISR);
    }

    public function getSaldoTotalAttribute(): float
    {
        return $this->saldo_actual + $this->rendimiento_acumulado;
    }
}
