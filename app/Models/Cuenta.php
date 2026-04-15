<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    protected $fillable = ['nombre', 'tipo', 'saldo_inicial', 'color'];

    // Relación con Gastos e Ingresos
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    // Abonos que salieron de esta cuenta (ej: Pagaste la tarjeta desde aquí)
    public function abonosRealizados(): HasMany
    {
        return $this->hasMany(Abono::class, 'cuenta_origen_id');
    }

    // Abonos que entraron a esta cuenta (ej: El pago que recibió la tarjeta)
    public function abonosRecibidos(): HasMany
    {
        return $this->hasMany(Abono::class, 'cuenta_destino_id');
    }

    /**
     * ATRIBUTO DINÁMICO: Saldo Actual
     * Esto reemplaza tus fórmulas de Excel.
     */
    public function getSaldoActualAttribute()
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastos = $this->movimientos()->where('tipo', 'gasto')->sum('monto');
        $abonosEnviados = $this->abonosRealizados()->sum('monto');
        $abonosRecibidos = $this->abonosRecibidos()->sum('monto');

        return ($this->saldo_inicial + $ingresos + $abonosRecibidos) - ($gastos + $abonosEnviados);
    }
}
