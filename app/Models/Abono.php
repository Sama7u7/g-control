<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abono extends Model
{
    protected $fillable = [
        'monto',
        'fecha',
        'cuenta_id',   // De donde sale el dinero (cuenta débito)
        'tarjeta_id',  // A donde va el pago (tarjeta crédito)
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(TarjetaCredito::class, 'tarjeta_id');
    }
}