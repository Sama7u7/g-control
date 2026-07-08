<?php

use Livewire\Volt\Component;
use App\Models\Movimiento;
use App\Models\Cuenta;
use App\Models\TarjetaCredito;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component {
    public $monto,
        $concepto,
        $tipo = 'gasto',
        $fecha,
        $origen_id, // Nuevo campo para traspasos
        $destino_id,
        $categoria_id;

    public function mount()
    {
        $this->fecha = date('Y-m-d');
    }

    public function guardar()
    {
        // 1. Reglas de validación dinámicas según el tipo
        $reglas = [
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:100',
            'destino_id' => 'required',
            'fecha' => 'required|date',
        ];

        if ($this->tipo === 'gasto') {
            $reglas['categoria_id'] = 'required|exists:categorias,id';
        } elseif ($this->tipo === 'transferencia') {
            // Si es traspaso, el origen es obligatorio y no puede ser igual al destino
            $reglas['origen_id'] = 'required|different:destino_id';
        }

        $this->validate($reglas);

        // 2. Transacción de Base de Datos para asegurar que no haya errores a medias
        DB::transaction(function () {
            if ($this->tipo === 'transferencia') {
                // LÓGICA DE TRASPASO (Doble registro)
                // Generar un ID único para vincular ambos movimientos (opcional, pero recomendado)
                $transferId = Str::uuid();

                // A) Registro de salida (Origen -> Gasto)
                [$tipoOrigen, $idOrigen] = explode('-', $this->origen_id);
                auth()->user()->movimientos()->create([
                    'monto' => $this->monto,
                    'concepto' => 'TRASPASO: ' . mb_strtoupper($this->concepto),
                    'tipo' => 'gasto',
                    'fecha' => $this->fecha,
                    'movible_id' => $idOrigen,
                    'movible_type' => $tipoOrigen === 'cuenta' ? Cuenta::class : TarjetaCredito::class,
            'transferencia_id' => $transferId, // Descomenta esto cuando agregues la columna a tu BD
                ]);

                // B) Registro de entrada (Destino -> Ingreso)
                [$tipoDestino, $idDestino] = explode('-', $this->destino_id);
                auth()->user()->movimientos()->create([
                    'monto' => $this->monto,
                    'concepto' => 'TRASPASO: ' . mb_strtoupper($this->concepto),
                    'tipo' => 'ingreso',
                    'fecha' => $this->fecha,
                    'movible_id' => $idDestino,
                    'movible_type' => $tipoDestino === 'cuenta' ? Cuenta::class : TarjetaCredito::class,
                    // 'transferencia_id' => $transferId, // Descomenta esto cuando agregues la columna a tu BD
                ]);

            } else {
                // LÓGICA NORMAL (Gasto o Ingreso simple)
                [$tipoSeleccionado, $id] = explode('-', $this->destino_id);
                $modelo = $tipoSeleccionado === 'cuenta' ? Cuenta::class : TarjetaCredito::class;

                auth()->user()->movimientos()->create([
                    'monto' => $this->monto,
                    'concepto' => mb_strtoupper($this->concepto),
                    'tipo' => $this->tipo,
                    'fecha' => $this->fecha,
                    'movible_id' => $id,
                    'movible_type' => $modelo,
                    'categoria_id' => $this->tipo === 'gasto' ? $this->categoria_id : null,
                ]);
            }
        });

        session()->flash('ok', '¡Varo registrado! 💸');
        // Limpiamos también el origen_id
        $this->reset(['monto', 'concepto', 'categoria_id', 'destino_id', 'origen_id']);
        $this->dispatch('movimiento-registrado');
    }

    public function with()
    {
        $user = auth()->user();
        return [
            'cuentas' => $user->cuentas()->orderBy('nombre')->get(),
            'tarjetas' => $user->tarjetasCredito()->orderBy('nombre')->get(),
            'categorias' => Categoria::whereNull('user_id')->orWhere('user_id', $user->id)->orderBy('nombre')->get(),
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

        {{-- Switch Gasto/Ingreso/Traspaso --}}
        <div class="flex gap-1 p-1.5 bg-indigo-100/50 rounded-2xl text-xs sm:text-sm">
            <button type="button" wire:click="$set('tipo', 'gasto')"
                class="flex-1 py-3 rounded-xl transition-all {{ $tipo == 'gasto' ? 'bg-white shadow-md text-rose-600 font-black' : 'text-indigo-400 font-bold' }}">
                GASTO
            </button>
            <button type="button" wire:click="$set('tipo', 'ingreso')"
                class="flex-1 py-3 rounded-xl transition-all {{ $tipo == 'ingreso' ? 'bg-white shadow-md text-emerald-600 font-black' : 'text-indigo-400 font-bold' }}">
                INGRESO
            </button>
            <button type="button" wire:click="$set('tipo', 'transferencia')"
                class="flex-1 py-3 rounded-xl transition-all {{ $tipo == 'transferencia' ? 'bg-white shadow-md text-blue-600 font-black' : 'text-indigo-400 font-bold' }}">
                TRASPASO
            </button>
        </div>

        <div class="bg-white rounded-3xl p-6 space-y-5 border border-indigo-100/50">
            {{-- Monto --}}
            <input type="number" step="0.01" wire:model.blur="monto" placeholder="0.00"
                class="w-full text-5xl font-black text-center border-none focus:ring-0 text-indigo-600 placeholder-indigo-100 bg-transparent">

            {{-- Concepto --}}
            <input type="text" wire:model.blur="concepto"
                placeholder="{{ $tipo == 'transferencia' ? 'Motivo del traspaso' : '¿En qué se fue?' }}"
                class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all">

            {{-- Cuenta Origen (Solo aparece si es traspaso) --}}
            @if ($tipo == 'transferencia')
                <select wire:model="origen_id"
                    class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all appearance-none">
                    <option value="">¿De qué cuenta SALE el dinero?</option>
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
            @endif

            {{-- Cuenta Destino (Polimórfico) --}}
            <select wire:model="destino_id"
                class="w-full border-slate-100 bg-slate-50/50 rounded-2xl p-4 text-slate-700 font-bold focus:ring-4 focus:ring-indigo-50 outline-none transition-all appearance-none">
                <option value="">{{ $tipo == 'transferencia' ? '¿A qué cuenta ENTRA el dinero?' : '¿De dónde sale / entra?' }}</option>
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

            {{-- Categoría (Se oculta en traspasos e ingresos) --}}
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
