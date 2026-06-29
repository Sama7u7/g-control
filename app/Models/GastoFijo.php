<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GastoFijo extends Model
{
    protected $table = 'gastos_fijos';

    protected $fillable = [
        'nombre',
        'monto',
        'frecuencia',
        'dia_cobro',
        'proxima_fecha',
        'activo',
        'registro_automatico',
        'cobrable_id',
        'cobrable_type',
        'categoria_id',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'monto'               => 'decimal:2',
        'activo'              => 'boolean',
        'registro_automatico' => 'boolean',
        'proxima_fecha'       => 'date',
        'dia_cobro'           => 'integer',
    ];

    protected $appends = [
        'dias_para_cobro',
        'frecuencia_label',
        'monto_mensual_equivalente',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function cobrable(): MorphTo
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

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getDiasParaCobroAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->proxima_fecha, false);
    }

    public function getFrecuenciaLabelAttribute(): string
    {
        return match ($this->frecuencia) {
            'semanal'     => 'Semanal',
            'quincenal'   => 'Quincenal',
            'mensual'     => 'Mensual',
            'bimestral'   => 'Bimestral',
            'trimestral'  => 'Trimestral',
            'semestral'   => 'Semestral',
            'anual'       => 'Anual',
            default       => $this->frecuencia,
        };
    }

    public function getMontoMensualEquivalenteAttribute(): float
    {
        return match ($this->frecuencia) {
            'semanal'     => $this->monto * 4.33,
            'quincenal'   => $this->monto * 2,
            'mensual'     => $this->monto,
            'bimestral'   => $this->monto / 2,
            'trimestral'  => $this->monto / 3,
            'semestral'   => $this->monto / 6,
            'anual'       => $this->monto / 12,
            default       => $this->monto,
        };
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function calcularProximaFecha(string $frecuencia, int $diaCobro, ?Carbon $desde = null, bool $esRenovacion = false): Carbon
    {
        $base = ($desde ?? now())->startOfDay();

        // 1. Para frecuencias en días, la suma es directa
        if (in_array($frecuencia, ['semanal', 'quincenal'])) {
            return $frecuencia === 'semanal' ? $base->copy()->addWeek() : $base->copy()->addWeeks(2);
        }

        $mesesSuma = match ($frecuencia) {
            'mensual'    => 1,
            'bimestral'  => 2,
            'trimestral' => 3,
            'semestral'  => 6,
            'anual'      => 12,
            default      => 1,
        };

        // 2. Armamos la fecha objetivo en el mes y año actuales de la fecha base
        $fechaObjetivo = $base->copy()->setDay(min($diaCobro, $base->daysInMonth));

        // 3. Si esa fecha objetivo ya pasó en este mes (ej. hoy es 20 y cobro es 15)
        // O si estamos renovando un gasto porque el Scheduler acaba de registrar el pago de hoy...
        if ($fechaObjetivo->lessThan($base) || $esRenovacion) {
            // ...entonces lo mandamos al futuro sumando los meses correspondientes
            $fechaObjetivo->addMonthsNoOverflow($mesesSuma);
            // Reajustamos por si el mes futuro es más corto (ej. febrero)
            $fechaObjetivo->setDay(min($diaCobro, $fechaObjetivo->daysInMonth));
        }

        return $fechaObjetivo;
    }

    public function registrarMovimiento(): void
    {
        if ($this->cobrable_id && $this->cobrable_type) {
            Movimiento::create([
                'monto'        => $this->monto,
                'concepto'     => mb_strtoupper($this->nombre),
                'tipo'         => 'gasto',
                'fecha'        => now()->toDateString(),
                'movible_id'   => $this->cobrable_id,
                'movible_type' => $this->cobrable_type,
                'categoria_id' => $this->categoria_id,
            ]);
        }

        $this->update([
            // Pasamos "true" como cuarto parámetro para forzar el salto al siguiente ciclo
            'proxima_fecha' => self::calcularProximaFecha($this->frecuencia, $this->dia_cobro, now(), true),
        ]);
    }

    public function scopeProximos($query, int $dias = 7)
    {
        return $query->where('activo', true)
            ->whereDate('proxima_fecha', '<=', now()->addDays($dias))
            ->whereDate('proxima_fecha', '>=', now());
    }
}
