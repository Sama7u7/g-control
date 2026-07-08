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
        'transferencia_id', // ← agregado para la relación de transferencia
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];
    // ─── Accesors ───────────────────────────────────────────────────────────
    /**
     * Obtiene el nombre de la categoría para mostrar en el Dashboard.
     */
    public function getEtiquetaCategoriaAttribute()
    {
        // Si tiene un ID de transferencia, sabemos que es un traspaso
        if ($this->transferencia_id !== null) {
            return '🔄 Traspaso';
        }

        // Si tiene una categoría real asignada, devolvemos su nombre
        if ($this->categoria_id !== null && $this->categoria) {
            return $this->categoria->nombre;
        }

        // Si realmente no tiene nada
        return 'Sin categoría';
    }

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
