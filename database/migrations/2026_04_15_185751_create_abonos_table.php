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
    Schema::create('abonos', function (Blueprint $table) {
        $table->id();
        // De qué cuenta salió el dinero (BBVA, Nu Débito, etc.)
        $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('cascade');
        // A qué tarjeta entró el dinero (Nu Crédito, ML, etc.)
        $table->foreignId('tarjeta_id')->constrained('tarjetas_credito')->onDelete('cascade');
        $table->decimal('monto', 15, 2);
        $table->date('fecha');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
