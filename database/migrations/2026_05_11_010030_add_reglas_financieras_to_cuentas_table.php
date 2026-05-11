<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->string('entidad_financiera')->default('banco')->after('tipo');
            $table->boolean('aplica_isr')->default(true)->after('entidad_financiera');
            $table->enum('tipo_interes', ['simple', 'escalonado'])->default('escalonado')->after('tasa_excedente');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropColumn(['entidad_financiera', 'aplica_isr', 'tipo_interes']);
        });
    }
};
