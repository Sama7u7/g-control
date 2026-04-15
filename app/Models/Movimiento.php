<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $fillable = [
        'monto', 
        'concepto', 
        'tipo', 
        'fecha', 
        'cuenta_id', 
        'categoria_id'
    ];

    // Para que Laravel trate la fecha como objeto Carbon (facilita filtros mensuales)
    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}