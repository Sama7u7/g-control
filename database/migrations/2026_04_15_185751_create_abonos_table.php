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
    $table->decimal('monto', 15, 2);
    $table->date('fecha');
    // De dónde sale y a dónde va
    $table->foreignId('cuenta_origen_id')->constrained('cuentas');
    $table->foreignId('cuenta_destino_id')->constrained('cuentas');
    $table->string('notas')->nullable();
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
