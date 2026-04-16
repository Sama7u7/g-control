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
    Schema::create('movimientos', function (Blueprint $table) {
        $table->id();
        $table->decimal('monto', 15, 2);
        $table->string('concepto');
        $table->enum('tipo', ['ingreso', 'gasto']);
        $table->date('fecha');
        
        // ESTA LÍNEA ES LA MAGIA (Crea movible_id y movible_type automáticamente)
        $table->morphs('movible'); 
        
        $table->foreignId('categoria_id')->nullable()->constrained()->onDelete('set null');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
