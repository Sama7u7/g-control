<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade; // <-- Añade esta
use Livewire\Volt\Volt;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 1. Esto ya lo tenías para los componentes Volt
        Volt::mount([
            resource_path('views/livewire'),
        ]);

        // 2. ESTO ES LO QUE FALTA: Registrar el path de los componentes de Blade
        // para que Laravel entienda qué significa "[layouts]"
        Blade::componentNamespace('App\\Views\\Components', 'layouts');
        
        // O más directo para este error específico:
        $this->loadViewsFrom(resource_path('views/components'), 'layouts');
    }
}