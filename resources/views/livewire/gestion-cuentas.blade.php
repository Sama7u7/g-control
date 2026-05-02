<?php

use Livewire\Volt\Component;
use App\Models\{Cuenta, TarjetaCredito, Categoria, Movimiento};
use Livewire\Attributes\{Title, Layout};

new #[Title('Ajustes - Mi Varo'), Layout('components.layouts.app')] class extends Component {
    public $tab = 'cuentas',
        $editando_id = null;

    public $timezone;
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

    public function mount()
    {
        // Cargamos la zona horaria actual del usuario
        $this->timezone = auth()->user()->timezone ?? config('app.timezone');
    }

    public function guardarPerfil()
    {
        if (auth()->check()) {
            // Si hay sesión, guardamos en la base de datos (PostgreSQL)
            auth()
                ->user()
                ->update(['timezone' => $this->timezone]);
        } else {
            // Si es invitado, guardamos en la sesión del navegador
            session(['user_timezone' => $this->timezone]);
        }

        session()->flash('ok', 'Zona horaria actualizada 🌍');
    }

    public function with()
    {
        // Filtramos para que solo aparezcan zonas de América y las de México al principio
        $allTimezones = \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA);

        // Zonas preferidas para que salgan hasta arriba del select
        $preferidas = ['America/Mexico_City', 'America/Monterrey', 'America/Merida', 'America/Tijuana', 'America/Cancun'];

        return [
            'cuentas' => Cuenta::all(),
            'tarjetas' => TarjetaCredito::all(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'timezones' => array_unique(array_merge($preferidas, $allTimezones)),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-10 pb-20">
    {{-- 1. HEADER --}}
    <header>
        <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Configuración</h1>
        <p class="text-slate-500 font-bold uppercase text-xs tracking-[0.2em] mt-1">Administra tus finanzas</p>
    </header>

    {{-- 2. TABS (Lo que te faltaba) --}}
    <div class="flex p-1.5 bg-slate-200/50 rounded-[2.5rem] shadow-inner">
        @foreach (['cuentas' => 'Cuentas', 'tarjetas' => 'Tarjetas', 'categorias' => 'Categorías', 'perfil' => 'Perfil'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 py-4 rounded-[2rem] font-black text-sm uppercase transition-all {{ $tab == $key ? 'bg-white shadow-xl text-indigo-600 scale-[1.02]' : 'text-slate-500' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- 3. GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        {{-- FORMULARIOS --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-[3rem] shadow-2xl border border-slate-100">
                <h2 class="text-xl font-black italic mb-6 text-slate-800 flex items-center gap-2">
                    <span class="w-2 h-6 {{ $tab == 'perfil' ? 'bg-amber-500' : 'bg-indigo-500' }} rounded-full"></span>
                    {{ $editando_id ? 'Editar' : ($tab == 'perfil' ? 'Ajustes de' : 'Nueva') }}
                    {{ match ($tab) {
                        'cuentas' => 'Cuenta',
                        'tarjetas' => 'Tarjeta',
                        'categorias' => 'Categoría',
                        'perfil' => 'Perfil',
                    } }}
                </h2>

                @if (session('ok'))
                    <div
                        class="mb-4 p-4 bg-emerald-500 text-white rounded-2xl text-center font-black animate-bounce shadow-lg">
                        {{ session('ok') }}
                    </div>
                @endif

                <form wire:submit.prevent="guardar" class="space-y-5">
                    @if ($tab == 'perfil')
                        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div
                                class="bg-amber-50/50 p-6 rounded-[2.5rem] border border-amber-100 flex flex-col items-center gap-2">
                                <span class="text-4xl">🌍</span>
                                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest text-center">
                                    Configura tu horario local</p>
                            </div>
                            <div class="space-y-2" wire:ignore> {{-- wire:ignore es CLAVE para que Livewire no borre el buscador --}}
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Zona
                                    Horaria</label>

                                <div x-data="{
                                    tsControl: null,
                                    init() {
                                        this.tsControl = new TomSelect($refs.selectTz, {
                                            create: false,
                                            sortField: { field: 'text', order: 'asc' },
                                            placeholder: 'Busca tu ciudad...',
                                            onChange: (value) => {
                                                @this.set('timezone', value);
                                                {{-- Sincroniza con Livewire --}}
                                            }
                                        });
                                    }
                                }">
                                    <select x-ref="selectTz"
                                        class="w-full mt-1 border-none bg-slate-50 rounded-2xl p-4 font-bold shadow-sm outline-none">
                                        <option value="">Selecciona una zona...</option>
                                        @foreach ($timezones as $tz)
                                            <option value="{{ $tz }}"
                                                {{ $timezone == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black text-lg shadow-xl hover:bg-amber-500 transition-all active:scale-95">
                                ACTUALIZAR PREFERENCIAS
                            </button>
                        </div>
                    @elseif ($tab == 'categorias')
                        {{-- Selector Emoji --}}
                        <div class="bg-indigo-50/50 p-6 rounded-[2.5rem] border border-indigo-100 flex flex-col items-center gap-4"
                            x-data="{ icono: @entangle('icono_categoria') }">
                            <div class="flex items-center gap-5">
                                <div class="w-20 h-20 bg-white rounded-full shadow-xl flex items-center justify-center text-5xl border-4 border-white"
                                    x-text="icono"></div>
                                <input type="text"
                                    class="w-20 bg-white border-2 border-indigo-100 rounded-2xl p-4 text-center text-2xl outline-none"
                                    x-bind:value="icono" @focus="$el.value = ''"
                                    @input="icono = $el.value; $wire.set('icono_categoria', $el.value)">
                            </div>
                        </div>
                        <input type="text" wire:model="nombre_categoria" placeholder="NOMBRE CATEGORÍA"
                            class="w-full border-none bg-slate-50 rounded-2xl p-5 font-black text-center uppercase focus:ring-4 focus:ring-indigo-100">
                        <button type="submit"
                            class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black text-lg shadow-xl hover:bg-indigo-600 transition-all">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                    @else
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
                        @endif
                        @if ($tab == 'tarjetas')
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Límite
                                de Crédito</label>
                            <input type="number" wire:model="limite_credito"
                                class="w-full bg-slate-50 border-none rounded-2xl p-4 font-black text-2xl text-rose-500">
                        @endif
                        <button type="submit"
                            class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black text-lg shadow-xl hover:bg-indigo-600 transition-all">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                    @endif

                    @if ($editando_id)
                        <button type="button" wire:click="limpiar"
                            class="w-full text-slate-400 font-bold text-xs uppercase tracking-widest">Cancelar</button>
                    @endif
                </form>
            </div>
        </div>

        {{-- LISTADOS --}}
        <div class="lg:col-span-7 space-y-4">
            @if ($tab == 'perfil')
                <div
                    class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center space-y-4">
                    <div class="w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center text-5xl">🕒</div>
                    <h3 class="font-black text-2xl text-slate-800">Zona Horaria Actual</h3>
                    <span
                        class="px-6 py-2 bg-slate-900 text-white rounded-full font-black text-sm">{{ $timezone }}</span>
                </div>
            @elseif ($tab == 'categorias')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($categorias as $cat)
                        <div wire:click="editarCategoria({{ $cat->id }})"
                            class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-indigo-400 cursor-pointer group transition-all">
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
                        class="bg-white p-6 rounded-[2.5rem] border-l-[18px] flex justify-between items-center shadow-sm hover:shadow-2xl cursor-pointer transition-all"
                        style="border-color: {{ $item->color }}">
                        <div>
                            <h4 class="font-black text-slate-800 text-xl">{{ $item->nombre }}</h4>
                            <p class="text-[10px] font-black text-slate-400 uppercase italic">
                                ${{ number_format($item->saldo_inicial ?? $item->limite_credito, 2) }}</p>
                        </div>
                        <p
                            class="font-black text-2xl {{ $tab == 'cuentas' ? 'text-emerald-600' : 'text-rose-500' }} italic">
                            ${{ number_format($item->saldo_actual ?? $item->deuda_actual, 2) }}
                        </p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
