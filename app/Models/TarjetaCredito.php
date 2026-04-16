<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TarjetaCredito extends Model
{
    protected $fillable = ['nombre', 'limite_credito', 'dia_corte', 'dia_pago', 'color'];
    protected $table = 'tarjetas_credito';

    public function movimientos(): MorphMany
    {
        return $this->morphMany(Movimiento::class, 'movible');
    }

    // Deuda actual (Suma de lo que has gastado menos lo que has pagado)
    public function getDeudaActualAttribute()
    {
        $gastos = $this->movimientos()->where('tipo', 'gasto')->sum('monto');
        $abonos = Abono::where('tarjeta_id', $this->id)->sum('monto');

        return $gastos - $abonos;
    }

    // CRÉDITO DISPONIBLE: Lo que realmente te importa ver
    public function getDisponibleAttribute()
    {
        return $this->limite_credito - $this->deuda_actual;
    }
}