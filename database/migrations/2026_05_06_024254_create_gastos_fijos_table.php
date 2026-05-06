<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                          // Netflix, Renta, Seguro auto...
            $table->decimal('monto', 15, 2);
            $table->enum('frecuencia', [
                'semanal',
                'quincenal',
                'mensual',
                'bimestral',
                'trimestral',
                'semestral',
                'anual',
            ]);
            $table->integer('dia_cobro');                      // Día del período en que se cobra (1-31)
            $table->date('proxima_fecha');                     // Calculada al guardar, usada para alertas y scheduler
            $table->boolean('activo')->default(true);
            $table->boolean('registro_automatico')->default(false); // Si true, el scheduler crea el movimiento solo

            // Asociación opcional a cuenta o tarjeta (polimórfica)
            $table->nullableMorphs('cobrable');                // cobrable_id + cobrable_type (Cuenta o TarjetaCredito)

            $table->foreignId('categoria_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            $table->string('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_fijos');
    }
};