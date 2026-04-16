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
    Schema::create('tarjetas_credito', function (Blueprint $table) {
    $table->id();
    $table->string('nombre'); // Ej: Nu, DiDi, BBVA Platino
    $table->decimal('limite_credito', 15, 2);
    $table->decimal('saldo_actual', 15, 2)->default(0); // Deuda actual (en negativo)
    $table->integer('dia_corte'); // Ej: 15
    $table->integer('dia_pago');  // Ej: 05
    $table->string('color')->nullable(); // Para el UI (ej: #004481)
    $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjetas_credito');
    }
};
