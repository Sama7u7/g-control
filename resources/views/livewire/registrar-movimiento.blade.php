<?php

use Livewire\Volt\Component;
use App\Models\Movimiento;
use App\Models\Cuenta;
use App\Models\TarjetaCredito;
use App\Models\Categoria;

new class extends Component {
    public $monto,
        $concepto,
        $tipo = 'gasto',
        $fecha,
        $destino_id,
        $categoria_id;

    public function mount()
    {
        $this->fecha = date('Y-m-d');
    }

    public function guardar()
    {
        $this->validate([
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:100',
            'destino_id' => 'required',
            'fecha' => 'required|date',
            'categoria_id' => $this->tipo === 'gasto' ? 'required|exists:categorias,id' : 'nullable',
        ]);

        // Lógica Polimórfica
        [$tipoSeleccionado, $id] = explode('-', $this->destino_id);
        $modelo = $tipoSeleccionado === 'cuenta' ? Cuenta::class : TarjetaCredito::class;

        Movimiento::create([
            'monto' => $this->monto,
            'concepto' => mb_strtoupper($this->concepto),
            'tipo' => $this->tipo,
            'fecha' => $this->fecha,
            'movible_id' => $id,
            'movible_type' => $modelo,
            'categoria_id' => $this->tipo === 'gasto' ? $this->categoria_id : null,
        ]);

        session()->flash('ok', '¡Varo registrado! 💸');
        $this->reset(['monto', 'concepto', 'categoria_id', 'destino_id']);
        $this->dispatch('movimiento-registrado');
    }

    public function with()
    {
        return [
            'cuentas' => Cuenta::orderBy('nombre')->get(),
            'tarjetas' => TarjetaCredito::orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ];
    }
}; ?>

<div class="bg-indigo-50 border border-indigo-100 rounded-[2.5rem] p-3 shadow-2xl shadow-indigo-100/50">
    <div class="p-6">
        <h3 class="font-black text-indigo-900 text-xl mb-1 tracking-tight italic">Registrar</h3>
        <p class="text-indigo-600/60 text-sm font-bold uppercase tracking-widest">Movimiento rápido</p>
    </div>

    <form wire:submit.prevent="guardar" class="space-y-4 px-3 pb-6">
        @if (session('ok'))
            <div
                class="bg-emerald-500 text-white p-4 rounded-2xl text-sm font-black text-center animate-bounce shadow-lg shadow-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Switch Gasto/Ingreso --}}
        <div class="flex gap-2 p-1.5 bg-indigo-100/50 rounded-2xl">
            <button type="button" wire:click="$set('tipo', 'gasto')"
                class="flex-1 py-3 rounded-xl transition-all {{ $tipo == 'gasto' ? 'bg-white shadow-md text-rose-600 font-black' : 'text-indigo-400 font-bold' }}">
                GASTO
            </button>
            <button type="button" wire:click="$set('tipo', 'ingreso')"
                class="flex-1 py-3 rounded-xl transition-all {{ $tipo == 'ingreso' ? 'bg-white shadow-md text-emerald-600 font-black' : 'text-indigo-400 font-bold' }}">
                INGRESO
            </button>
        </div>

        <div class="bg-white rounded-3xl p-6 space-y-5 border border-indigo-100/50">
            {{-- Monto --}}
            <input type="number" step="0.01" wire:model.blur="monto" placeholder="0.00"
                class="w-full text-5xl font-black text-center border-none focus:ring-0 text-indigo-600 placeholder-indigo-100 bg-transparent">

            {{-- Concepto --}}
            <input type="text" wire:model.blur="concepto" placeholder="¿En qué se fue?"
                class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all">

            {{-- Destino (Polimórfico) --}}
            <select wire:model="destino_id"
                class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all appearance-none">
                <option value="">¿De dónde sale / entra?</option>
                <optgroup label="💰 CUENTAS (ACTIVO)">
                    @foreach ($cuentas as $c)
                        <option value="cuenta-{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="💳 TARJETAS (PASIVO)">
                    @foreach ($tarjetas as $t)
                        <option value="tarjeta-{{ $t->id }}">{{ $t->nombre }}</option>
                    @endforeach
                </optgroup>
            </select>

            {{-- Categoría --}}
            @if ($tipo == 'gasto')
                <select wire:model="categoria_id"
                    class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all appearance-none">
                    <option value="">Selecciona Categoría</option>
                    @foreach ($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            @endif

            {{-- Fecha --}}
            <input type="date" wire:model="fecha"
                class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-500 font-bold focus:ring-4 focus:ring-indigo-50 outline-none">
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 text-white py-5 rounded-3xl font-black text-xl hover:bg-indigo-700 transition shadow-xl shadow-indigo-200 active:scale-95">
            REGISTRAR
        </button>
    </form>
</div>
