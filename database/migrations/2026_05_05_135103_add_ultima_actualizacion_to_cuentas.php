<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega ultima_actualizacion a cuentas.
 * Este campo permite calcular el rendimiento acumulado por días
 * usando la fórmula: saldo × (1 + tasa/365)^días
 *
 * NOTA: saldo_actual NO se agrega como columna porque se calcula
 * dinámicamente en el modelo (saldo_inicial + movimientos - abonos).
 * Para correcciones manuales se usa sincronizarSaldo() que crea
 * un movimiento de ajuste, manteniendo el historial íntegro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->date('ultima_actualizacion')->nullable()->after('tasa_excedente');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropColumn('ultima_actualizacion');
        });
    }
};