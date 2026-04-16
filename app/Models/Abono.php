<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abono extends Model
{
    // 1. Actualizamos el fillable con los nombres de las nuevas tablas
    protected $fillable = [
        'monto', 
        'fecha', 
        'cuenta_id',    // La que suelta el varo (BBVA)
        'tarjeta_id',   // La que recibe el pago (NU Crédito)
        'notas'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];

    // 2. Relación con la Cuenta (De donde sale el dinero)
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    // 3. Relación con la Tarjeta (A donde entra el pago)
    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(TarjetaCredito::class);
    }
}