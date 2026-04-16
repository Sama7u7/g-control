<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/registrar', 'registrar-movimiento');
Volt::route('/historial', 'lista-movimientos');
Volt::route('/', 'dashboard');
Volt::route('/configuracion', 'gestion-cuentas');
Volt::route('/resumen', 'resumen-mensual');
