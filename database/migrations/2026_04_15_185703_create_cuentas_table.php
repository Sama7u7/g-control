<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('cuentas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('color')->default('#6366f1');
        $table->enum('tipo', ['debito', 'efectivo', 'ahorro']);
        $table->decimal('saldo_inicial', 15, 2)->default(0);

        // Campos de rendimiento que añadimos después
        $table->decimal('tasa_rendimiento', 5, 2)->nullable();
        $table->decimal('tope_rendimiento', 15, 2)->nullable();
        $table->decimal('tasa_excedente', 5, 2)->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
