<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cuenta;
use App\Models\TarjetaCredito;
use App\Models\Categoria;
use App\Models\Movimiento;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CATEGORÍAS CON ICONOS
        $categorias = [
            ['nombre' => 'COMIDA Y ANTOJITOS', 'icono' => '🍔'],
            ['nombre' => 'TRANSPORTE / DIDI', 'icono' => '🚗'],
            ['nombre' => 'SERVICIOS (LUZ, AGUA)', 'icono' => '💡'],
            ['nombre' => 'SUSCRIPCIONES', 'icono' => '📺'],
            ['nombre' => 'SALUD', 'icono' => '💊'],
            ['nombre' => 'EDUCACIÓN', 'icono' => '📚'],
        ];

        foreach ($categorias as $cat) {
            Categoria::create($cat);
        }

        $comida = Categoria::where('nombre', 'COMIDA Y ANTOJITOS')->first();
        $transporte = Categoria::where('nombre', 'TRANSPORTE / DIDI')->first();

        // 2. CUENTAS DE DÉBITO (Aspecto: Rendimientos Fintech)
        // Cuenta tipo Nu/Cajita con tope de rendimiento
        $nu = Cuenta::create([
            'nombre' => 'NU (CAJITA)',
            'tipo' => 'debito',
            'saldo_inicial' => 20000,
            'color' => '#9333ea', // Morado Nu
            'tasa_rendimiento' => 15.0, // 15% anual
            'tope_rendimiento' => 23000,
            'tasa_excedente' => 7.5,
            'activo' => true
        ]);

        $efectivo = Cuenta::create([
            'nombre' => 'EFECTIVO',
            'tipo' => 'debito',
            'saldo_inicial' => 1500,
            'color' => '#10b981', // Verde
            'activo' => true
        ]);

        // 3. TARJETAS DE CRÉDITO (Aspecto: Deuda y Límites)
        $nuCredit = TarjetaCredito::create([
            'nombre' => 'NU CRÉDITO',
            'limite_credito' => 12000,
            'dia_corte' => 15,
            'dia_pago' => 5,
            'color' => '#7e22ce',
            'activo' => true
        ]);

        $didiCard = TarjetaCredito::create([
            'nombre' => 'DIDI CARD',
            'limite_credito' => 5000,
            'dia_corte' => 1,
            'dia_pago' => 20,
            'color' => '#f97316', // Naranja DiDi
            'activo' => true
        ]);

        // 4. MOVIMIENTOS PARA LA GRÁFICA Y EL HISTORIAL
        // Gasto en cuenta de débito
        Movimiento::create([
            'monto' => 450.50,
            'concepto' => 'Cena Tacos el Inge',
            'tipo' => 'gasto',
            'fecha' => now(),
            'categoria_id' => $comida->id,
            'movible_id' => $nu->id,
            'movible_type' => Cuenta::class,
        ]);

        // Gasto en tarjeta de crédito (Para ver la barra de progreso)
        Movimiento::create([
            'monto' => 3200,
            'concepto' => 'Supermercado Mensual',
            'tipo' => 'gasto',
            'fecha' => now(),
            'categoria_id' => $comida->id,
            'movible_id' => $nuCredit->id,
            'movible_type' => TarjetaCredito::class,
        ]);

        Movimiento::create([
            'monto' => 120.00,
            'concepto' => 'Viaje al COBACH',
            'tipo' => 'gasto',
            'fecha' => now(),
            'categoria_id' => $transporte->id,
            'movible_id' => $didiCard->id,
            'movible_type' => TarjetaCredito::class,
        ]);

        // Un ingreso para ver el balance positivo
        Movimiento::create([
            'monto' => 5000,
            'concepto' => 'Depósito de Proyecto PHP',
            'tipo' => 'ingreso',
            'fecha' => now(),
            'movible_id' => $nu->id,
            'movible_type' => Cuenta::class,
        ]);
    }
}