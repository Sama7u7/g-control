<?php

use Livewire\Volt\Component;
use App\Models\Movimiento;
use App\Models\Cuenta;
use App\Models\Categoria;

new class extends Component {
    // Aquí defines tus variables (lo que antes iba en el controlador)
    public $monto,
        $concepto,
        $tipo = 'gasto',
        $fecha,
        $cuenta_id,
        $categoria_id;

    // Se ejecuta al cargar el componente
    public function mount()
    {
        $this->fecha = date('Y-m-d');
    }

    // Tu lógica de guardado
    public function guardar()
    {
        $this->validate([
            'monto' => 'required|numeric',
            'concepto' => 'required|string',
            'cuenta_id' => 'required',
            'categoria_id' => 'required_if:tipo,gasto',
        ]);

        Movimiento::create([
            'monto' => $this->monto,
            'concepto' => $this->concepto,
            'tipo' => $this->tipo,
            'fecha' => $this->fecha,
            'cuenta_id' => $this->cuenta_id,
            'categoria_id' => $this->categoria_id,
        ]);

        session()->flash('ok', '¡Varo registrado! 💸');

        $this->reset(['monto', 'concepto']);

        // Esto sirve para avisar a otros componentes que se actualicen
        $this->dispatch('movimiento-registrado');
    }

    // Para pasar datos a la vista
    public function with()
    {
        return [
            'cuentas' => Cuenta::all(),
            'categorias' => Categoria::all(),
        ];
    }
};
?>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <form wire:submit="guardar" class="space-y-4">
        @if (session('ok'))
            <div class="bg-green-100 text-green-700 p-3 rounded-lg text-sm font-bold">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex gap-2 p-1 bg-gray-100 rounded-lg">
            <button type="button" wire:click="$set('tipo', 'gasto')"
                class="flex-1 py-2 rounded-md transition {{ $tipo == 'gasto' ? 'bg-white shadow text-red-600 font-bold' : 'text-gray-500' }}">
                Gasto
            </button>
            <button type="button" wire:click="$set('tipo', 'ingreso')"
                class="flex-1 py-2 rounded-md transition {{ $tipo == 'ingreso' ? 'bg-white shadow text-green-600 font-bold' : 'text-gray-500' }}">
                Ingreso
            </button>
        </div>

        <input type="number" step="0.01" wire:model="monto" placeholder="$ 0.00"
            class="w-full text-4xl font-black text-center border-none focus:ring-0 text-indigo-600">

        <input type="text" wire:model="concepto" placeholder="¿En qué se fue el dinero?"
            class="w-full border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500">

        <select wire:model="cuenta_id" class="w-full border-gray-200 rounded-xl">
            <option value="">¿De qué cuenta/tarjeta?</option>
            @foreach ($cuentas as $cuenta)
                <option value="{{ $cuenta->id }}">{{ $cuenta->nombre }}</option>
            @endforeach
        </select>

        @if ($tipo == 'gasto')
            <select wire:model="categoria_id" class="w-full border-gray-200 rounded-xl">
                <option value="">Selecciona Categoría</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
        @endif

        <input type="date" wire:model="fecha" class="w-full border-gray-200 rounded-xl">

        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-4 rounded-2xl font-bold transition shadow-lg shadow-indigo-100">
            Registrar Movimiento
        </button>
    </form>
</div>
