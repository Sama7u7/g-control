<?php

use Livewire\Volt\Component;
use App\Models\{Cuenta, TarjetaCredito, Categoria, Movimiento};
use Livewire\Attributes\{Title, Layout};

new #[Title('Ajustes - Mi Varo'), Layout('components.layouts.app')] class extends Component {
    public $tab = 'cuentas',
        $editando_id = null;

    // Campos Generales
    public $nombre,
        $color = '#6366f1',
        $saldo_inicial = 0,
        $activo = true;

    // Campos Cuenta
    public $mostrarRendimiento = false,
        $tasa_rendimiento,
        $tope_rendimiento,
        $tasa_excedente;

    // Campos Tarjeta
    public $limite_credito = 0,
        $dia_corte = 1,
        $dia_pago = 10;

    // Campos Categoría
    public $nombre_categoria,
        $icono_categoria = '🏷️';

    // Sincronización
    public $nuevo_saldo_real;

    public function updatedTab()
    {
        $this->limpiar();
    }

    public function limpiar()
    {
        $this->reset(['nombre', 'color', 'mostrarRendimiento', 'tasa_rendimiento', 'tope_rendimiento', 'tasa_excedente', 'editando_id', 'nuevo_saldo_real', 'nombre_categoria', 'activo']);
        $this->icono_categoria = '🏷️';
        $this->saldo_inicial = 0;
        $this->limite_credito = 0;
        $this->dia_corte = 1;
        $this->dia_pago = 10;
        $this->resetValidation();
    }

    public function editarCuenta($id)
    {
        $c = Cuenta::find($id);
        $this->editando_id = $id;
        $this->nombre = $c->nombre;
        $this->color = $c->color;
        $this->saldo_inicial = $c->saldo_inicial;
        $this->activo = (bool) $c->activo;
        $this->tasa_rendimiento = $c->tasa_rendimiento;
        $this->tope_rendimiento = $c->tope_rendimiento;
        $this->tasa_excedente = $c->tasa_excedente;
        $this->mostrarRendimiento = $c->tasa_rendimiento > 0;
    }

    public function editarTarjeta($id)
    {
        $t = TarjetaCredito::find($id);
        $this->editando_id = $id;
        $this->nombre = $t->nombre;
        $this->color = $t->color;
        $this->limite_credito = $t->limite_credito;
        $this->activo = (bool) $t->activo;
        $this->dia_corte = $t->dia_corte;
        $this->dia_pago = $t->dia_pago;
    }

    public function editarCategoria($id)
    {
        $cat = Categoria::find($id);
        $this->editando_id = $id;
        $this->nombre_categoria = $cat->nombre;
        $this->icono_categoria = $cat->icono ?? '🏷️';
    }

    public function eliminarCategoria($id)
    {
        Categoria::destroy($id);
        session()->flash('ok', 'Categoría eliminada 🗑️');
        $this->limpiar();
    }

    public function guardar()
    {
        if ($this->tab == 'cuentas') {
            $this->validate(['nombre' => 'required|min:2', 'saldo_inicial' => 'required|numeric']);
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'saldo_inicial' => $this->saldo_inicial,
                'color' => $this->color,
                'activo' => $this->activo,
                'tasa_rendimiento' => $this->mostrarRendimiento ? $this->tasa_rendimiento : null,
                'tope_rendimiento' => $this->mostrarRendimiento ? $this->tope_rendimiento : null,
                'tasa_excedente' => $this->mostrarRendimiento ? $this->tasa_excedente : null,
            ];
            $this->editando_id ? Cuenta::find($this->editando_id)->update($data) : Cuenta::create($data);
        } elseif ($this->tab == 'tarjetas') {
            $this->validate(['nombre' => 'required|min:2', 'limite_credito' => 'required|numeric']);
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'limite_credito' => $this->limite_credito,
                'dia_corte' => $this->dia_corte,
                'dia_pago' => $this->dia_pago,
                'color' => $this->color,
                'activo' => $this->activo,
            ];
            $this->editando_id ? TarjetaCredito::find($this->editando_id)->update($data) : TarjetaCredito::create($data);
        } elseif ($this->tab == 'categorias') {
            $this->validate(['nombre_categoria' => 'required|min:3', 'icono_categoria' => 'required']);
            $data = [
                'nombre' => mb_strtoupper($this->nombre_categoria),
                'icono' => $this->icono_categoria,
            ];
            $this->editando_id ? Categoria::find($this->editando_id)->update($data) : Categoria::create($data);
        }
        session()->flash('ok', '¡Datos guardados correctamente!');
        $this->limpiar();
    }

    public function sincronizarSaldo($id)
    {
        $c = Cuenta::find($id);
        $diferencia = $this->nuevo_saldo_real - $c->saldo_actual;
        if ($diferencia != 0) {
            Movimiento::create([
                'monto' => abs($diferencia),
                'concepto' => 'AJUSTE MANUAL DE SALDO',
                'tipo' => $diferencia > 0 ? 'ingreso' : 'gasto',
                'fecha' => now(),
                'movible_id' => $c->id,
                'movible_type' => Cuenta::class,
            ]);
        }
        $this->limpiar();
        session()->flash('ok', 'Saldo sincronizado ✅');
    }

    public function with()
    {
        return [
            'cuentas' => Cuenta::all(),
            'tarjetas' => TarjetaCredito::all(),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-10 pb-20">
    <header>
        <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Configuración</h1>
        <p class="text-slate-500 font-bold uppercase text-xs tracking-[0.2em] mt-1">Administra tus finanzas</p>
    </header>

    {{-- Tabs --}}
    <div class="flex p-1.5 bg-slate-200/50 rounded-[2.5rem] shadow-inner">
        @foreach (['cuentas' => 'Cuentas', 'tarjetas' => 'Tarjetas', 'categorias' => 'Categorías'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 py-4 rounded-[2rem] font-black text-sm uppercase transition-all {{ $tab == $key ? 'bg-white shadow-xl text-indigo-600 scale-[1.02]' : 'text-slate-500' }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        {{-- FORMULARIOS --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-[3rem] shadow-2xl border border-slate-100">
                <h2 class="text-xl font-black italic mb-6 text-slate-800 flex items-center gap-2">
                    <span class="w-2 h-6 bg-indigo-500 rounded-full"></span>
                    {{ $editando_id ? 'Editar' : 'Nueva' }}
                    {{ match ($tab) {'cuentas' => 'Cuenta','tarjetas' => 'Tarjeta','categorias' => 'Categoría'} }}
                </h2>

                @if (session('ok'))
                    <div
                        class="mb-4 p-4 bg-emerald-500 text-white rounded-2xl text-center font-black animate-bounce shadow-lg">
                        {{ session('ok') }}</div>
                @endif

                <form wire:submit.prevent="guardar" class="space-y-5">
                    @if ($tab == 'categorias')
                        {{-- Selector Emoji con Ayuda y Autolimpieza --}}
                        <div class="bg-indigo-50/50 p-6 rounded-[2.5rem] border border-indigo-100 flex flex-col items-center gap-4"
                            x-data="{ icono: @entangle('icono_categoria') }">
                            <div class="flex items-center gap-5">
                                <div class="w-20 h-20 bg-white rounded-full shadow-xl flex items-center justify-center text-5xl border-4 border-white"
                                    x-text="icono"></div>
                                <input type="text" placeholder="😀"
                                    class="w-20 bg-white border-2 border-indigo-100 rounded-2xl p-4 text-center text-2xl outline-none focus:ring-4 focus:ring-indigo-200 transition-all"
                                    x-bind:value="icono" @focus="$el.value = ''"
                                    @input="icono = $el.value; $wire.set('icono_categoria', $el.value)"
                                    @change="$wire.set('icono_categoria', $el.value)">
                            </div>
                            <div class="text-center space-y-1">
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Atajos de
                                    teclado:</p>
                                <p class="text-[9px] font-bold text-slate-500 italic">
                                    <span class="bg-white px-1.5 py-0.5 rounded border shadow-sm">Win + .</span> en PC
                                    <span class="mx-1">/</span>
                                    <span class="bg-white px-1.5 py-0.5 rounded border shadow-sm">Cmd + Ctrl +
                                        Espacio</span> en Mac
                                </p>
                            </div>
                        </div>
                        <input type="text" wire:model="nombre_categoria" placeholder="NOMBRE CATEGORÍA"
                            class="w-full border-none bg-slate-50 rounded-2xl p-5 font-black text-center uppercase focus:ring-4 focus:ring-indigo-100">
                    @else
                        {{-- Campos Cuenta/Tarjeta --}}
                        <div class="flex gap-4">
                            <input type="text" wire:model="nombre" placeholder="NOMBRE"
                                class="flex-1 border-none bg-slate-50 rounded-2xl p-4 font-bold shadow-sm">
                            <input type="color" wire:model="color"
                                class="w-16 h-14 p-1 rounded-2xl border-none bg-slate-50 cursor-pointer shadow-sm">
                        </div>

                        <div
                            class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Estatus
                                Activo</span>
                            <button type="button" wire:click="$toggle('activo')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $activo ? 'bg-indigo-600' : 'bg-slate-300' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $activo ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        @if ($tab == 'cuentas')
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Saldo
                                Inicial</label>
                            <input type="number" step="0.01" wire:model="saldo_inicial"
                                class="w-full bg-slate-50 border-none rounded-2xl p-4 font-black text-2xl text-emerald-600">

                            <div
                                class="flex items-center gap-4 p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 border-dashed">
                                <input type="checkbox" wire:model.live="mostrarRendimiento" id="chk"
                                    class="w-6 h-6 rounded-lg text-indigo-600 border-none shadow-sm">
                                <label for="chk" class="text-xs font-black text-indigo-900 cursor-pointer">¿GENERA
                                    RENDIMIENTOS?</label>
                            </div>

                            @if ($mostrarRendimiento)
                                <div
                                    class="p-5 bg-indigo-600 rounded-[2rem] text-white space-y-3 animate-in zoom-in duration-300 shadow-xl">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[8px] font-black opacity-60 uppercase ml-1">Tasa %
                                                Anual</label>
                                            <input type="number" step="0.1" wire:model="tasa_rendimiento"
                                                class="w-full bg-white/10 border-none rounded-xl p-3 font-black text-white outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[8px] font-black opacity-60 uppercase ml-1">Tope Rend.
                                                $</label>
                                            <input type="number" wire:model="tope_rendimiento"
                                                class="w-full bg-white/10 border-none rounded-xl p-3 font-black text-white outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[8px] font-black opacity-60 uppercase ml-1">Tasa Excedente
                                            %</label>
                                        <input type="number" step="0.1" wire:model="tasa_excedente"
                                            class="w-full bg-white/10 border-none rounded-xl p-3 font-black text-white outline-none">
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if ($tab == 'tarjetas')
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Límite
                                de Crédito</label>
                            <input type="number" wire:model="limite_credito"
                                class="w-full bg-slate-50 border-none rounded-2xl p-4 font-black text-2xl text-rose-500">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Día Corte</label>
                                    <input type="number" wire:model="dia_corte"
                                        class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold">
                                </div>
                                <div>
                                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Día Pago</label>
                                    <input type="number" wire:model="dia_pago"
                                        class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold">
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-slate-900 text-white py-5 rounded-[2rem] font-black text-lg shadow-xl hover:bg-indigo-600 transition-all active:scale-95">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                        @if ($editando_id && $tab == 'categorias')
                            <button type="button" wire:confirm="¿Borrar categoría?"
                                wire:click="eliminarCategoria({{ $editando_id }})"
                                class="bg-rose-100 text-rose-600 px-6 rounded-[2rem] hover:bg-rose-600 hover:text-white transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif
                    </div>
                    @if ($editando_id)
                        <button type="button" wire:click="limpiar"
                            class="w-full text-slate-400 font-bold text-xs uppercase tracking-widest">Cancelar</button>
                    @endif
                </form>
            </div>

            @if ($editando_id && $tab == 'cuentas')
                <div class="bg-emerald-600 p-8 rounded-[3rem] text-white shadow-xl animate-in fade-in duration-500">
                    <h3 class="font-black italic text-xl mb-4">Sincronizar Saldo Real</h3>
                    <div class="flex gap-3">
                        <input type="number" step="0.01" wire:model="nuevo_saldo_real"
                            class="flex-1 bg-white/20 border-none rounded-2xl p-4 font-black text-white placeholder-white/40"
                            placeholder="Saldo actual">
                        <button wire:click="sincronizarSaldo({{ $editando_id }})"
                            class="bg-white text-emerald-700 px-8 rounded-2xl font-black shadow-lg">OK</button>
                    </div>
                </div>
            @endif
        </div>

        {{-- LISTADOS --}}
        <div class="lg:col-span-7 space-y-4">
            @if ($tab == 'categorias')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($categorias as $cat)
                        <div wire:click="editarCategoria({{ $cat->id }})"
                            class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-indigo-400 hover:shadow-xl transition-all group cursor-pointer">
                            <span
                                class="text-4xl group-hover:scale-125 transition-transform">{{ $cat->icono ?? '🏷️' }}</span>
                            <span
                                class="font-black text-slate-700 text-[10px] uppercase tracking-widest">{{ $cat->nombre }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                @foreach ($tab == 'cuentas' ? $cuentas : $tarjetas as $item)
                    <div wire:click="{{ $tab == 'cuentas' ? 'editarCuenta' : 'editarTarjeta' }}({{ $item->id }})"
                        class="bg-white p-6 rounded-[2.5rem] border-l-[18px] flex justify-between items-center shadow-sm hover:shadow-2xl transition-all cursor-pointer group {{ !$item->activo ? 'opacity-50 grayscale' : '' }}"
                        style="border-color: {{ $item->color }}">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-black text-slate-800 text-xl tracking-tight">{{ $item->nombre }}</h4>
                                @if (!$item->activo)
                                    <span
                                        class="bg-slate-200 text-slate-500 text-[8px] px-2 py-1 rounded-full font-black uppercase tracking-tighter italic">Inactivo</span>
                                @endif
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase italic">
                                ${{ number_format($item->saldo_inicial ?? $item->limite_credito, 2) }} inicial/límite
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                class="font-black text-2xl {{ $tab == 'cuentas' ? 'text-emerald-600' : 'text-rose-500' }} tracking-tighter italic">
                                ${{ number_format($item->saldo_actual ?? $item->deuda_actual, 2) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
