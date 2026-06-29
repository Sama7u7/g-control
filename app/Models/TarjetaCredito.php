<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TarjetaCredito extends Model
{
    protected $fillable = [
        'nombre',
        'limite_credito',
        'dia_corte',
        'dia_pago',
        'color',
        'activo',
        'user_id',
    ];

    protected $table = 'tarjetas_credito';

    protected $casts = [
        'activo'         => 'boolean',
        'limite_credito' => 'decimal:2',
    ];

    protected $appends = [
        'deuda_actual',
        'disponible',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function movimientos(): MorphMany
    {
        return $this->morphMany(Movimiento::class, 'movible');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Deuda real = gastos con tarjeta − abonos realizados.
     * Igual que Cuenta: siempre calculado, sin columna saldo_actual.
     * Para correcciones manuales se edita directamente en gestion-cuentas
     * creando un movimiento de ajuste.
     */
    public function getDeudaActualAttribute(): float
    {
        $gastos  = $this->movimientos()->where('tipo', 'gasto')->sum('monto');
        $abonos  = Abono::where('tarjeta_id', $this->id)->sum('monto');

        return (float) max(0, $gastos - $abonos);
    }

    /**
     * Crédito disponible = límite − deuda.
     */
    public function getDisponibleAttribute(): float
    {
        return (float) max(0, $this->limite_credito - $this->deuda_actual);
    }

    /**
     * Porcentaje de uso del crédito (0–100).
     */
    public function getPorcentajeUsoAttribute(): float
    {
        if ($this->limite_credito <= 0) return 0.0;
        return min(100, ($this->deuda_actual / $this->limite_credito) * 100);
    }
}
