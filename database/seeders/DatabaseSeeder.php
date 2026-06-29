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
        // 1. CATEGORÍAS GLOBALES DEL SISTEMA
        // Declaramos user_id como null para que todos los usuarios de la app puedan verlas
        $categorias = [
            ['nombre' => 'COMIDA', 'icono' => '🍔', 'user_id' => null],
            ['nombre' => 'TRANSPORTE', 'icono' => '🚗', 'user_id' => null],
            ['nombre' => 'SERVICIOS', 'icono' => '💡', 'user_id' => null],
            ['nombre' => 'SUSCRIPCIONES', 'icono' => '📺', 'user_id' => null],
            ['nombre' => 'SALUD', 'icono' => '💊', 'user_id' => null],
            ['nombre' => 'EDUCACIÓN', 'icono' => '📚', 'user_id' => null],
        ];

        foreach ($categorias as $cat) {
            Categoria::create($cat);
        }

        // 2. CUENTAS DE PRUEBA
        $usuario = \App\Models\User::factory()->create([
            'name' => 'Usuario 1',
            'last_name' => 'Prueba',
            'username' => 'user1',
            'password' => bcrypt('password'),
            'email' => 'user1@mail.com',
        ]);

                // 2. CUENTAS DE PRUEBA
        $usuario = \App\Models\User::factory()->create([
            'name' => 'Usuario 2',
            'last_name' => 'Prueba',
            'username' => 'user2',
            'password' => bcrypt('password'),
            'email' => 'user2@mail.com',
        ]);


    }
}
