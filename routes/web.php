<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

// El nuevo bloque de seguridad de Breeze para Alberto
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Cambiamos el Route::view de Breeze por tu componente Volt
    Volt::route('/dashboard', 'dashboard')->name('dashboard');
    
    // Tus rutas rescatadas
    Volt::route('/registrar', 'registrar-movimiento')->name('registrar');
    Volt::route('/historial', 'lista-movimientos')->name('historial');
    Volt::route('/configuracion', 'gestion-cuentas')->name('configuracion');
    Volt::route('/resumen', 'resumen-mensual')->name('resumen');
    Volt::route('/gastos-fijos', 'gastos-fijos')->name('gastos-fijos');

    // La ruta de perfil que Breeze sí debe mantener
    Route::view('/profile', 'profile')->name('profile');
});


require __DIR__.'/auth.php';