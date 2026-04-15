<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt; // <-- No olvides esta línea

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Esto le dice a Volt: "Busca aquí los componentes, no te hagas el sordo"
        Volt::mount([
            resource_path('views/livewire'),
        ]);
    }
}