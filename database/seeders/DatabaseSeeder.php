<?php

namespace Database\Seeders;

use App\Models\Cuenta;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tus Cuentas
        Cuenta::create(['nombre' => 'BBVA', 'tipo' => 'credito', 'saldo_inicial' => 0, 'color' => '#004481']);
        Cuenta::create(['nombre' => 'MLCREDITO', 'tipo' => 'credito', 'saldo_inicial' => 0, 'color' => '#ffe600']);
        Cuenta::create(['nombre' => 'NU', 'tipo' => 'debito', 'saldo_inicial' => 25000, 'color' => '#820ad1']);
        Cuenta::create(['nombre' => 'DIDI', 'tipo' => 'debito', 'saldo_inicial' => 37000, 'color' => '#ff8800']);

        // Tus Categorías
        $cats = ['Deportes', 'Hogar y Despensa', 'Comida y Antojos', 'Transporte', 'Varios', 'Cuidado Personal'];
        foreach ($cats as $cat) {
            Categoria::create(['nombre' => $cat]);
        }
    }
}