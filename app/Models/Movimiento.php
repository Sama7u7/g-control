<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Movimiento extends Model
{
    protected $fillable = [
        'monto',
        'concepto',
        'tipo',
        'fecha',
        'movible_id',    // ← corregido (antes tenía 'cuenta_id' que no existe)
        'movible_type',  // ← corregido
        'categoria_id',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /**
     * Relación polimórfica: puede pertenecer a Cuenta o TarjetaCredito.
     */
    public function movible(): MorphTo
    {
        return $this->morphTo();
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
