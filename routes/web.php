<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

// FORMA CORRECTA: Volt ya sabe que debe usar el método GET
Volt::route('/registrar', 'registrar-movimiento');
Volt::route('/historial', 'lista-movimientos');
Volt::route('/dashboard', 'dashboard');
