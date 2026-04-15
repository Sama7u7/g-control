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
    $table->string('nombre'); // BBVA, Nu, Stori
    $table->enum('tipo', ['debito', 'credito']);
    $table->decimal('saldo_inicial', 15, 2)->default(0);
    $table->string('color')->nullable(); // Para el UI (ej: #004481)
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
