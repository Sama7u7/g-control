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
        $color = '#4F3FF0', // Cambiado al color accent por defecto
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
        } elseif ($this->tab == 'perfil') {
            $this->guardarPerfil();
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
        $this->timezone = auth()->user()->timezone ?? config('app.timezone');
    }

    public function guardarPerfil()
    {
        if (auth()->check()) {
            auth()
                ->user()
                ->update(['timezone' => $this->timezone]);
        } else {
            session(['user_timezone' => $this->timezone]);
        }

        session()->flash('ok', 'Zona horaria actualizada 🌍');
    }

    public function with()
    {
        $allTimezones = \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA);
        $preferidas = ['America/Mexico_City', 'America/Monterrey', 'America/Merida', 'America/Tijuana', 'America/Cancun'];

        return [
            'cuentas' => Cuenta::all(),
            'tarjetas' => TarjetaCredito::all(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'timezones' => array_unique(array_merge($preferidas, $allTimezones)),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-10 pb-20">
    {{-- 1. HEADER --}}
    <header>
        <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Configuración</h1>
        <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent mt-1">Administra tus
            finanzas</p>
    </header>

    {{-- 2. TABS --}}
    <div class="flex p-1.5 bg-surface rounded-sys-pill border border-border">
        @foreach (['cuentas' => 'Cuentas', 'tarjetas' => 'Tarjetas', 'categorias' => 'Categorías', 'perfil' => 'Perfil'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 py-3 rounded-sys-pill font-display font-bold text-[0.7rem] uppercase tracking-[0.1em] transition-all {{ $tab == $key ? 'bg-white shadow-sm text-accent' : 'text-hint hover:text-muted bg-transparent' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- 3. GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- FORMULARIOS --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-sys-card border border-border">
                <h2
                    class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink mb-6 flex items-center gap-2">
                    <span class="w-2 h-6 {{ $tab == 'perfil' ? 'bg-amber' : 'bg-accent' }} rounded-sys-pill"></span>
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
                        class="mb-6 p-4 bg-green-light border border-green/20 text-green rounded-sys-input text-center font-body text-[0.85rem]">
                        {{ session('ok') }}
                    </div>
                @endif

                <form wire:submit.prevent="guardar" class="space-y-5">
                    @if ($tab == 'perfil')
                        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div
                                class="bg-surface p-6 rounded-sys-card border border-border flex flex-col items-center gap-2">
                                <span class="text-3xl">🌍</span>
                                <p
                                    class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-ink text-center">
                                    Configura tu horario local
                                </p>
                            </div>

                            <div class="space-y-2 flex flex-col" wire:ignore>
                                <label class="font-display font-bold text-[0.7rem] text-ink">Zona Horaria</label>
                                <div x-data="{
                                    tsControl: null,
                                    init() {
                                        this.tsControl = new TomSelect($refs.selectTz, {
                                            create: false,
                                            sortField: { field: 'text', order: 'asc' },
                                            placeholder: 'Busca tu ciudad...',
                                            onChange: (value) => {
                                                @this.set('timezone', value);
                                            }
                                        });
                                    }
                                }">
                                    <select x-ref="selectTz"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                        <option value="">Selecciona una zona...</option>
                                        @foreach ($timezones as $tz)
                                            <option value="{{ $tz }}"
                                                {{ $timezone == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                                ACTUALIZAR PREFERENCIAS
                            </button>
                        </div>
                    @elseif ($tab == 'categorias')
                        <div class="bg-surface p-6 rounded-sys-card border border-border flex flex-col items-center gap-4"
                            x-data="{ icono: @entangle('icono_categoria') }">
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl border border-border"
                                    x-text="icono"></div>
                                <input type="text"
                                    class="w-16 bg-white border border-border rounded-sys-input p-3 text-center text-xl outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all"
                                    x-bind:value="icono" @focus="$el.value = ''"
                                    @input="icono = $el.value; $wire.set('icono_categoria', $el.value)">
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Nombre de la Categoría</label>
                            <input type="text" wire:model="nombre_categoria" placeholder="ej. Supermercado"
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                        </div>
                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                    @else
                        {{-- Formularios de Cuentas y Tarjetas --}}
                        <div class="flex gap-4">
                            <div class="flex-1 flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                <input type="text" wire:model="nombre" placeholder="ej. BBVA Débito"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Color</label>
                                <input type="color" wire:model="color"
                                    class="w-12 h-11 p-0.5 rounded-sys-input border border-border bg-white cursor-pointer">
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                            <span
                                class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Estatus
                                Activo</span>
                            <button type="button" wire:click="$toggle('activo')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $activo ? 'bg-accent' : 'bg-hint' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $activo ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        @if ($tab == 'cuentas')
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Saldo Inicial</label>
                                <input type="number" step="0.01" wire:model="saldo_inicial" placeholder="0.00"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            </div>
                        @endif

                        @if ($tab == 'tarjetas')
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Límite de Crédito</label>
                                <input type="number" step="0.01" wire:model="limite_credito" placeholder="0.00"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            </div>
                        @endif

                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                    @endif

                    @if ($editando_id)
                        <button type="button" wire:click="limpiar"
                            class="w-full text-muted hover:text-ink font-display font-bold text-[0.65rem] uppercase tracking-[0.14em] transition-colors mt-2">
                            Cancelar Edición
                        </button>
                    @endif
                </form>
            </div>
        </div>

        {{-- LISTADOS --}}
        <div class="lg:col-span-7 space-y-4">
            @if ($tab == 'perfil')
                <div
                    class="bg-white p-10 rounded-sys-card border border-border flex flex-col items-center justify-center text-center space-y-4">
                    <div
                        class="w-20 h-20 bg-surface rounded-full flex items-center justify-center text-4xl border border-border">
                        🕒</div>
                    <h3 class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink">Zona Horaria Actual
                    </h3>
                    <span
                        class="px-5 py-2 bg-accent-light text-accent rounded-sys-pill font-display font-bold text-[0.65rem] tracking-[0.1em] uppercase">
                        {{ $timezone }}
                    </span>
                </div>
            @elseif ($tab == 'categorias')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($categorias as $cat)
                        <div wire:click="editarCategoria({{ $cat->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex flex-col items-center gap-3 hover:bg-surface cursor-pointer group transition-colors">
                            <span
                                class="text-3xl group-hover:scale-110 transition-transform">{{ $cat->icono ?? '🏷️' }}</span>
                            <span
                                class="font-display font-bold text-ink text-[0.6rem] uppercase tracking-[0.1em] text-center">{{ $cat->nombre }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Listado de Cuentas y Tarjetas --}}
                <div class="grid gap-3">
                    @foreach ($tab == 'cuentas' ? $cuentas : $tarjetas as $item)
                        <div wire:click="{{ $tab == 'cuentas' ? 'editarCuenta' : 'editarTarjeta' }}({{ $item->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface cursor-pointer transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-10 rounded-sys-pill flex-shrink-0"
                                    style="background-color: {{ $item->color }}"></div>
                                <div>
                                    <h4 class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                        {{ $item->nombre }}</h4>
                                    <p class="font-body text-[0.82rem] font-light text-muted">
                                        {{ $tab == 'cuentas' ? 'Inicial:' : 'Límite:' }}
                                        ${{ number_format($item->saldo_inicial ?? $item->limite_credito, 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] {{ $tab == 'cuentas' ? 'text-green' : 'text-rose' }}">
                                    {{ $tab == 'tarjetas' ? '-' : '' }}${{ number_format($item->saldo_actual ?? $item->deuda_actual, 2) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
