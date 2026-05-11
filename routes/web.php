<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
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
    Volt::route('/historial', 'historial-movimientos')->name('historial');

    // La ruta de perfil que Breeze sí debe mantener
    Route::view('/profile', 'profile')->name('profile');

    Route::get('/plantilla-gastos', function () {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Mi_Varo_Plantilla.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para acentos en Excel
            fputcsv($file, ['Concepto', 'Monto', 'Fecha (YYYY-MM-DD)', 'ID_Cuenta', 'Categoria']); // Encabezados
            fputcsv($file, ['EJ. DESPENSA', '1200.50', now()->format('Y-m-d'), '1', 'Comida']); // Ejemplo
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    })->name('descargar.plantilla');
});


require __DIR__ . '/auth.php';
