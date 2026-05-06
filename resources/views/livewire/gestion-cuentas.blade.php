<?php

use Livewire\Volt\Component;
use App\Models\{Cuenta, TarjetaCredito, Categoria, Movimiento, GastoFijo};
use Livewire\Attributes\{Title, Layout};

new #[Title('Ajustes - Mi Varo'), Layout('components.layouts.app')] class extends Component {
    public $tab = 'cuentas',
        $editando_id = null;

    public $timezone;

    // ── Campos Generales ─────────────────────────────────────────────────────
    public $nombre,
        $color = '#4F3FF0',
        $activo = true;

    // ── Campos Cuenta ────────────────────────────────────────────────────────
    public $tipo = 'debito',
        $saldo_inicial = 0,
        $mostrarRendimiento = false,
        $ultima_actualizacion,
        $tasa_rendimiento,
        $tope_rendimiento,
        $tasa_excedente;

    // ── Campos Tarjeta ───────────────────────────────────────────────────────
    public $limite_credito = 0,
        $dia_corte = 1,
        $dia_pago = 10;

    // ── Campos Categoría ─────────────────────────────────────────────────────
    public $nombre_categoria,
        $icono_categoria = '🏷️';

    // ── Campos Gasto Fijo ────────────────────────────────────────────────────
    public $gf_nombre = '',
        $gf_monto = 0,
        $gf_frecuencia = 'mensual',
        $gf_dia_cobro = 1,
        $gf_activo = true,
        $gf_registro_auto = false,
        $gf_categoria_id = '',
        $gf_notas = '',
        $gf_cobrable_tipo = '',
        $gf_cobrable_id = '';

    // ── Sincronización ───────────────────────────────────────────────────────
    public $nuevo_saldo_real;

    // ─── Lifecycle ───────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->timezone = auth()->user()->timezone ?? config('app.timezone');
        $this->ultima_actualizacion = now()->toDateString();
    }

    public function updatedTab(): void
    {
        $this->limpiar();
    }

    public function limpiar(): void
    {
        $this->reset(['nombre', 'color', 'activo', 'tipo', 'mostrarRendimiento', 'tasa_rendimiento', 'tope_rendimiento', 'tasa_excedente', 'editando_id', 'nuevo_saldo_real', 'nombre_categoria', 'gf_nombre', 'gf_monto', 'gf_activo', 'gf_registro_auto', 'gf_categoria_id', 'gf_notas', 'gf_cobrable_tipo', 'gf_cobrable_id']);
        $this->icono_categoria = '🏷️';
        $this->saldo_inicial = 0;
        $this->limite_credito = 0;
        $this->dia_corte = 1;
        $this->dia_pago = 10;
        $this->tipo = 'debito';
        $this->ultima_actualizacion = now()->toDateString();
        $this->gf_frecuencia = 'mensual';
        $this->gf_dia_cobro = 1;
        $this->resetValidation();
    }

    // ─── Cargar para editar ──────────────────────────────────────────────────

    public function editarCuenta($id): void
    {
        $c = Cuenta::find($id);
        $this->editando_id = $id;
        $this->nombre = $c->nombre;
        $this->color = $c->color;
        $this->tipo = $c->tipo;
        $this->saldo_inicial = $c->saldo_inicial;
        $this->activo = (bool) $c->activo;
        $this->ultima_actualizacion = $c->ultima_actualizacion?->toDateString() ?? now()->toDateString();
        $this->tasa_rendimiento = $c->tasa_rendimiento;
        $this->tope_rendimiento = $c->tope_rendimiento;
        $this->tasa_excedente = $c->tasa_excedente;
        $this->mostrarRendimiento = $c->tasa_rendimiento > 0;
    }

    public function editarTarjeta($id): void
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

    public function editarCategoria($id): void
    {
        $cat = Categoria::find($id);
        $this->editando_id = $id;
        $this->nombre_categoria = $cat->nombre;
        $this->icono_categoria = $cat->icono ?? '🏷️';
    }

    public function editarGastoFijo($id): void
    {
        $g = GastoFijo::find($id);
        $this->editando_id = $id;
        $this->gf_nombre = $g->nombre;
        $this->gf_monto = $g->monto;
        $this->gf_frecuencia = $g->frecuencia;
        $this->gf_dia_cobro = $g->dia_cobro;
        $this->gf_activo = $g->activo;
        $this->gf_registro_auto = $g->registro_automatico;
        $this->gf_categoria_id = $g->categoria_id ?? '';
        $this->gf_notas = $g->notas ?? '';

        if ($g->cobrable_type === \App\Models\Cuenta::class) {
            $this->gf_cobrable_tipo = 'cuenta';
            $this->gf_cobrable_id = $g->cobrable_id;
        } elseif ($g->cobrable_type === \App\Models\TarjetaCredito::class) {
            $this->gf_cobrable_tipo = 'tarjeta';
            $this->gf_cobrable_id = $g->cobrable_id;
        } else {
            $this->gf_cobrable_tipo = '';
            $this->gf_cobrable_id = '';
        }
    }

    // ─── Acciones ────────────────────────────────────────────────────────────

    public function eliminarCategoria($id): void
    {
        Categoria::destroy($id);
        session()->flash('ok', 'Categoría eliminada');
        $this->limpiar();
    }

    public function eliminarGastoFijo($id): void
    {
        GastoFijo::destroy($id);
        session()->flash('ok', 'Gasto fijo eliminado');
        $this->limpiar();
    }

    public function registrarGastoAhora($id): void
    {
        $gasto = GastoFijo::find($id);
        $gasto->registrarMovimiento();
        session()->flash('ok', $gasto->nombre . ' registrado como movimiento');
    }

    public function sincronizarSaldo($id): void
    {
        $this->validate(['nuevo_saldo_real' => 'required|numeric']);

        $c = Cuenta::find($id);
        $diferencia = $this->nuevo_saldo_real - $c->saldo_actual;

        if ($diferencia != 0) {
            Movimiento::create([
                'monto' => abs($diferencia),
                'concepto' => 'AJUSTE MANUAL DE SALDO',
                'tipo' => $diferencia > 0 ? 'ingreso' : 'gasto',
                'fecha' => now()->toDateString(),
                'movible_id' => $c->id,
                'movible_type' => Cuenta::class,
            ]);
        }

        session()->flash('ok', 'Saldo sincronizado');
        $this->limpiar();
    }

    public function guardar(): void
    {
        if ($this->tab === 'cuentas') {
            $this->validate([
                'nombre' => 'required|min:2',
                'tipo' => 'required|in:debito,efectivo,ahorro',
                'saldo_inicial' => 'required|numeric',
            ]);
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'tipo' => $this->tipo,
                'saldo_inicial' => $this->saldo_inicial,
                'color' => $this->color,
                'activo' => $this->activo,
                'ultima_actualizacion' => $this->mostrarRendimiento ? $this->ultima_actualizacion : null,
                'tasa_rendimiento' => $this->mostrarRendimiento ? $this->tasa_rendimiento : null,
                'tope_rendimiento' => $this->mostrarRendimiento ? $this->tope_rendimiento : null,
                'tasa_excedente' => $this->mostrarRendimiento ? $this->tasa_excedente : null,
            ];
            $this->editando_id ? Cuenta::find($this->editando_id)->update($data) : Cuenta::create($data);
        } elseif ($this->tab === 'tarjetas') {
            $this->validate([
                'nombre' => 'required|min:2',
                'limite_credito' => 'required|numeric|min:0',
                'dia_corte' => 'required|integer|min:1|max:31',
                'dia_pago' => 'required|integer|min:1|max:31',
            ]);
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'limite_credito' => $this->limite_credito,
                'dia_corte' => $this->dia_corte,
                'dia_pago' => $this->dia_pago,
                'color' => $this->color,
                'activo' => $this->activo,
            ];
            $this->editando_id ? TarjetaCredito::find($this->editando_id)->update($data) : TarjetaCredito::create($data);
        } elseif ($this->tab === 'categorias') {
            $this->validate([
                'nombre_categoria' => 'required|min:3',
                'icono_categoria' => 'required',
            ]);
            $data = [
                'nombre' => mb_strtoupper($this->nombre_categoria),
                'icono' => $this->icono_categoria,
            ];
            $this->editando_id ? Categoria::find($this->editando_id)->update($data) : Categoria::create($data);
        } elseif ($this->tab === 'gastos') {
            $this->validate([
                'gf_nombre' => 'required|min:2',
                'gf_monto' => 'required|numeric|min:0.01',
                'gf_frecuencia' => 'required|in:semanal,quincenal,mensual,bimestral,trimestral,semestral,anual',
                'gf_dia_cobro' => 'required|integer|min:1|max:31',
            ]);
            $cobrableType = match ($this->gf_cobrable_tipo) {
                'cuenta' => \App\Models\Cuenta::class,
                'tarjeta' => \App\Models\TarjetaCredito::class,
                default => null,
            };
            $data = [
                'nombre' => mb_strtoupper($this->gf_nombre),
                'monto' => $this->gf_monto,
                'frecuencia' => $this->gf_frecuencia,
                'dia_cobro' => $this->gf_dia_cobro,
                'activo' => $this->gf_activo,
                'registro_automatico' => $this->gf_registro_auto,
                'categoria_id' => $this->gf_categoria_id ?: null,
                'notas' => $this->gf_notas ?: null,
                'cobrable_id' => $cobrableType && $this->gf_cobrable_id ? $this->gf_cobrable_id : null,
                'cobrable_type' => $cobrableType && $this->gf_cobrable_id ? $cobrableType : null,
                'proxima_fecha' => GastoFijo::calcularProximaFecha($this->gf_frecuencia, $this->gf_dia_cobro),
            ];
            $this->editando_id ? GastoFijo::find($this->editando_id)->update($data) : GastoFijo::create($data);
        } elseif ($this->tab === 'perfil') {
            $this->guardarPerfil();
            return;
        }

        session()->flash('ok', 'Datos guardados correctamente');
        $this->limpiar();
    }

    public function guardarPerfil(): void
    {
        if (auth()->check()) {
            auth()
                ->user()
                ->update(['timezone' => $this->timezone]);
        } else {
            session(['user_timezone' => $this->timezone]);
        }
        session()->flash('ok', 'Zona horaria actualizada');
        $this->limpiar();
    }

    // ─── Data ────────────────────────────────────────────────────────────────

    public function with(): array
    {
        $allTimezones = \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA);
        $preferidas = ['America/Mexico_City', 'America/Monterrey', 'America/Merida', 'America/Tijuana', 'America/Cancun'];

        return [
            'cuentas' => Cuenta::all(),
            'tarjetas' => TarjetaCredito::all(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'gastos_fijos' => GastoFijo::with(['categoria', 'cobrable'])
                ->orderBy('proxima_fecha')
                ->get(),
            'timezones' => array_unique(array_merge($preferidas, $allTimezones)),
            'total_mensual' => GastoFijo::where('activo', true)->get()->sum->monto_mensual_equivalente,
            'proximos_siete' => GastoFijo::proximos(7)->count(),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-10 pb-20">

    {{-- HEADER --}}
    <header>
        <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Configuración</h1>
        <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent mt-1">Administra tus
            finanzas</p>
    </header>

    {{-- TABS --}}
    <div class="flex p-1.5 bg-surface rounded-sys-pill border border-border overflow-x-auto">
        @foreach ([
        'cuentas' => 'Cuentas',
        'tarjetas' => 'Tarjetas',
        'categorias' => 'Categorías',
        'gastos' => 'Gastos Fijos',
        'perfil' => 'Perfil',
    ] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 py-3 rounded-sys-pill font-display font-bold text-[0.7rem] uppercase tracking-[0.1em] transition-all whitespace-nowrap px-3
                    {{ $tab == $key ? 'bg-white shadow-sm text-accent' : 'text-hint hover:text-muted bg-transparent' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- RESUMEN GASTOS FIJOS --}}
    @if ($tab === 'gastos')
        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white p-7 rounded-sys-card border border-border">
                <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-rose mb-2">Compromiso
                    Mensual</p>
                <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                    ${{ number_format($total_mensual, 2) }}
                </h2>
                <p class="font-body text-[0.75rem] text-hint mt-1">Equivalente mensual de todos tus fijos activos</p>
            </div>
            <div class="bg-white p-7 rounded-sys-card border border-border">
                <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-amber mb-2">Próximos 7
                    días</p>
                <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                    {{ $proximos_siete }}
                </h2>
                <p class="font-body text-[0.75rem] text-hint mt-1">
                    {{ $proximos_siete == 1 ? 'gasto por vencer' : 'gastos por vencer' }}
                </p>
            </div>
        </div>
    @endif

    {{-- GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- ═══════════════ COLUMNA FORMULARIO ═══════════════ --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-sys-card border border-border">

                <h2
                    class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink mb-6 flex items-center gap-2">
                    <span class="w-2 h-6 {{ $tab == 'perfil' ? 'bg-amber' : 'bg-accent' }} rounded-sys-pill"></span>
                    {{ $editando_id ? 'Editar' : ($tab == 'perfil' ? 'Ajustes de' : 'Nuevo') }}
                    {{ match ($tab) {
                        'cuentas' => $editando_id ? 'Cuenta' : 'Cuenta',
                        'tarjetas' => $editando_id ? 'Tarjeta' : 'Tarjeta',
                        'categorias' => $editando_id ? 'Categoría' : 'Categoría',
                        'gastos' => $editando_id ? 'Gasto Fijo' : 'Gasto Fijo',
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

                    {{-- ══════════════ PERFIL ══════════════ --}}
                    @if ($tab === 'perfil')
                        <div class="space-y-6">
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
                                    init() {
                                        new TomSelect($refs.selectTz, {
                                            create: false,
                                            sortField: { field: 'text', order: 'asc' },
                                            placeholder: 'Busca tu ciudad...',
                                            onChange: (value) => { @this.set('timezone', value); }
                                        });
                                    }
                                }">
                                    <select x-ref="selectTz"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink">
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

                        {{-- ══════════════ CATEGORÍAS ══════════════ --}}
                    @elseif ($tab === 'categorias')
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
                            @error('nombre_categoria')
                                <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>

                        {{-- ══════════════ GASTOS FIJOS ══════════════ --}}
                    @elseif ($tab === 'gastos')
                        {{-- Nombre --}}
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                            <input type="text" wire:model="gf_nombre" placeholder="ej. Netflix, Renta, Seguro"
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            @error('gf_nombre')
                                <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Monto --}}
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Monto</label>
                            <input type="number" step="0.01" wire:model="gf_monto" placeholder="0.00"
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            @error('gf_monto')
                                <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Frecuencia --}}
                        <div class="flex flex-col gap-2">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Frecuencia</label>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ([
        'semanal' => ['📅', 'Sem'],
        'quincenal' => ['📅', 'Qna'],
        'mensual' => ['🗓️', 'Mens'],
        'bimestral' => ['🗓️', 'Bim'],
        'trimestral' => ['📆', 'Trim'],
        'semestral' => ['📆', '6m'],
        'anual' => ['🗓️', 'Anual'],
    ] as $val => [$emoji, $etiqueta])
                                    <button type="button" wire:click="$set('gf_frecuencia', '{{ $val }}')"
                                        class="flex flex-col items-center gap-0.5 p-2 rounded-sys-input border-2 transition-all font-display font-bold text-[0.6rem] uppercase tracking-[0.06em]
                                            {{ $gf_frecuencia == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted hover:border-accent/40' }}">
                                        <span class="text-base">{{ $emoji }}</span>
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Día de cobro --}}
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Día de Cobro</label>
                            <input type="number" wire:model="gf_dia_cobro" min="1" max="31"
                                placeholder="ej. 15"
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            <span class="font-body text-[0.7rem] text-hint">Día del período en que normalmente se
                                cobra</span>
                        </div>

                        {{-- Categoría --}}
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Categoría</label>
                            <select wire:model="gf_categoria_id"
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                <option value="">Sin categoría</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->icono }} {{ $cat->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Se carga en (cobrable) --}}
                        <div class="space-y-3">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Se carga en <span
                                    class="text-hint font-normal normal-case">(opcional)</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['' => ['➖', 'Ninguna'], 'cuenta' => ['🏦', 'Cuenta'], 'tarjeta' => ['💳', 'Tarjeta']] as $val => [$emoji, $etiqueta])
                                    <button type="button" wire:click="$set('gf_cobrable_tipo', '{{ $val }}')"
                                        class="flex flex-col items-center gap-0.5 p-2 rounded-sys-input border-2 transition-all font-display font-bold text-[0.65rem] uppercase tracking-[0.06em]
                                            {{ $gf_cobrable_tipo == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted hover:border-accent/40' }}">
                                        <span class="text-base">{{ $emoji }}</span>
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>
                            @if ($gf_cobrable_tipo === 'cuenta')
                                <select wire:model="gf_cobrable_id"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <option value="">Selecciona una cuenta</option>
                                    @foreach ($cuentas->where('activo', true) as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            @elseif ($gf_cobrable_tipo === 'tarjeta')
                                <select wire:model="gf_cobrable_id"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <option value="">Selecciona una tarjeta</option>
                                    @foreach ($tarjetas->where('activo', true) as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- Toggles --}}
                        <div class="space-y-3">
                            <div
                                class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                                <span
                                    class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Activo</span>
                                <button type="button" wire:click="$toggle('gf_activo')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $gf_activo ? 'bg-accent' : 'bg-hint' }}">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $gf_activo ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>
                            <div
                                class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                                <div>
                                    <span
                                        class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Registro
                                        Automático</span>
                                    <p class="font-body text-[0.72rem] text-hint mt-0.5">El sistema registra el
                                        movimiento al llegar la fecha</p>
                                </div>
                                <button type="button" wire:click="$toggle('gf_registro_auto')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $gf_registro_auto ? 'bg-accent' : 'bg-hint' }} ml-4 flex-shrink-0">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $gf_registro_auto ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Notas --}}
                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Notas <span
                                    class="text-hint font-normal normal-case">(opcional)</span></label>
                            <input type="text" wire:model="gf_notas"
                                placeholder="ej. Plan familiar, vence en dic."
                                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                        </div>

                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR GASTO' : 'GUARDAR GASTO' }}
                        </button>

                        {{-- ══════════════ CUENTAS ══════════════ --}}
                    @elseif ($tab === 'cuentas')
                        <div class="flex gap-4">
                            <div class="flex-1 flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                <input type="text" wire:model="nombre" placeholder="ej. Nu Débito"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                @error('nombre')
                                    <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Color</label>
                                <input type="color" wire:model="color"
                                    class="w-12 h-11 p-0.5 rounded-sys-input border border-border bg-white cursor-pointer">
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Tipo de Cuenta</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['debito' => ['🏦', 'Débito'], 'ahorro' => ['💰', 'Ahorro'], 'efectivo' => ['💵', 'Efectivo']] as $val => [$emoji, $etiqueta])
                                    <button type="button" wire:click="$set('tipo', '{{ $val }}')"
                                        class="flex flex-col items-center gap-1 p-3 rounded-sys-input border-2 transition-all font-display font-bold text-[0.65rem] uppercase tracking-[0.08em]
                                            {{ $tipo == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted hover:border-accent/40' }}">
                                        <span class="text-xl">{{ $emoji }}</span>
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="font-body text-[0.72rem] text-hint">Solo es una etiqueta — los rendimientos se
                                configuran abajo.</p>
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

                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Saldo Inicial</label>
                            <input type="number" step="0.01" wire:model="saldo_inicial" placeholder="0.00"
                                class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            @error('saldo_inicial')
                                <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                            @enderror
                        </div>

                        @if ($editando_id)
                            @php $cuenta = $cuentas->find($editando_id); @endphp
                            <div class="p-4 bg-amber-light border border-amber/20 rounded-sys-input space-y-3">
                                <div>
                                    <p
                                        class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-amber-dark">
                                        Corrección de Saldo</p>
                                    <p class="font-body text-[0.72rem] text-hint mt-0.5">
                                        Saldo calculado: <span
                                            class="font-bold text-ink">${{ number_format($cuenta?->saldo_actual ?? 0, 2) }}</span>
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" wire:model="nuevo_saldo_real"
                                        placeholder="Saldo real en la app/banco"
                                        class="flex-1 bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-amber focus:ring-4 focus:ring-amber/20 transition-all">
                                    <button type="button" wire:click="sincronizarSaldo({{ $editando_id }})"
                                        class="px-4 py-2 bg-amber text-white rounded-sys-input font-display font-bold text-[0.7rem] uppercase hover:opacity-90 transition-opacity whitespace-nowrap">
                                        Ajustar
                                    </button>
                                </div>
                                <p class="font-body text-[0.7rem] text-hint">Crea un movimiento de ajuste para mantener
                                    el historial.</p>
                            </div>
                        @endif

                        <div
                            class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                            <div>
                                <span
                                    class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Genera
                                    Rendimiento</span>
                                <p class="font-body text-[0.72rem] text-hint mt-0.5">Nu, Mercado Pago, CETES, etc.</p>
                            </div>
                            <button type="button" wire:click="$toggle('mostrarRendimiento')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $mostrarRendimiento ? 'bg-accent' : 'bg-hint' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $mostrarRendimiento ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        @if ($mostrarRendimiento)
                            <div
                                class="space-y-4 p-4 bg-surface rounded-sys-input border border-border animate-in fade-in slide-in-from-top-2 duration-300">
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Tasa de Rendimiento
                                        (%)</label>
                                    <input type="number" step="0.01" wire:model="tasa_rendimiento"
                                        placeholder="ej. 15.00"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <span class="font-body text-[0.7rem] text-hint">Tasa cuando cumples las condiciones
                                        del mes</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Tope de Rendimiento
                                        ($)</label>
                                    <input type="number" step="0.01" wire:model="tope_rendimiento"
                                        placeholder="ej. 50,000.00"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <span class="font-body text-[0.7rem] text-hint">Monto máximo que genera la tasa
                                        preferente. Vacío si no aplica.</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Tasa sobre Excedente
                                        (%)</label>
                                    <input type="number" step="0.01" wire:model="tasa_excedente"
                                        placeholder="ej. 9.00"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <span class="font-body text-[0.7rem] text-hint">Tasa para el saldo que supera el
                                        tope</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Última Actualización
                                        de Tasa</label>
                                    <input type="date" wire:model="ultima_actualizacion"
                                        class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                    <span class="font-body text-[0.7rem] text-hint">Fecha desde la que corre el
                                        rendimiento actual</span>
                                </div>
                            </div>
                        @endif

                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR CUENTA' : 'GUARDAR CUENTA' }}
                        </button>

                        {{-- ══════════════ TARJETAS ══════════════ --}}
                    @else
                        <div class="flex gap-4">
                            <div class="flex-1 flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                <input type="text" wire:model="nombre" placeholder="ej. Nu Crédito"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                @error('nombre')
                                    <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                                @enderror
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

                        <div class="flex flex-col gap-1">
                            <label class="font-display font-bold text-[0.7rem] text-ink">Límite de Crédito</label>
                            <input type="number" step="0.01" wire:model="limite_credito" placeholder="0.00"
                                class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                            @error('limite_credito')
                                <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Día de Corte</label>
                                <input type="number" wire:model="dia_corte" min="1" max="31"
                                    placeholder="ej. 15"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                @error('dia_corte')
                                    <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Día de Pago</label>
                                <input type="number" wire:model="dia_pago" min="1" max="31"
                                    placeholder="ej. 5"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all">
                                @error('dia_pago')
                                    <span class="text-rose text-[0.75rem]">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                            {{ $editando_id ? 'ACTUALIZAR TARJETA' : 'GUARDAR TARJETA' }}
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

        {{-- ═══════════════ COLUMNA LISTADOS ═══════════════ --}}
        <div class="lg:col-span-7 space-y-4">

            {{-- PERFIL --}}
            @if ($tab === 'perfil')
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

                {{-- CATEGORÍAS --}}
            @elseif ($tab === 'categorias')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @forelse ($categorias as $cat)
                        <div wire:click="editarCategoria({{ $cat->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex flex-col items-center gap-3 hover:bg-surface cursor-pointer group transition-colors">
                            <span
                                class="text-3xl group-hover:scale-110 transition-transform">{{ $cat->icono ?? '🏷️' }}</span>
                            <span
                                class="font-display font-bold text-ink text-[0.6rem] uppercase tracking-[0.1em] text-center">{{ $cat->nombre }}</span>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-10 text-hint font-body text-[0.85rem]">Aún no hay
                            categorías.</div>
                    @endforelse
                </div>

                {{-- GASTOS FIJOS --}}
            @elseif ($tab === 'gastos')
                <div class="space-y-3">
                    @forelse ($gastos_fijos as $gasto)
                        @php
                            $dias = $gasto->dias_para_cobro;
                            $urgente = $dias <= 3;
                            $proximo = $dias <= 7;
                            $borde = $urgente ? 'border-l-rose' : ($proximo ? 'border-l-amber' : 'border-l-border');
                        @endphp
                        <div
                            class="bg-white rounded-sys-card border border-border border-l-4 {{ $borde }} p-5 space-y-4 {{ !$gasto->activo ? 'opacity-50' : '' }}">

                            <div class="flex justify-between items-start">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-lg flex-shrink-0">
                                        {{ $gasto->categoria?->icono ?? '📋' }}
                                    </div>
                                    <div>
                                        <h4 class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                            {{ $gasto->nombre }}</h4>
                                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span
                                                class="font-body text-[0.72rem] text-hint px-2 py-0.5 bg-surface rounded-full border border-border">
                                                {{ $gasto->frecuencia_label }}
                                            </span>
                                            @if ($gasto->cobrable)
                                                <span
                                                    class="font-body text-[0.72rem] text-hint px-2 py-0.5 bg-surface rounded-full border border-border">
                                                    {{ $gasto->cobrable->nombre }}
                                                </span>
                                            @endif
                                            @if ($gasto->registro_automatico)
                                                <span
                                                    class="font-body text-[0.72rem] text-accent px-2 py-0.5 bg-accent-light rounded-full border border-accent/20">Auto</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-4">
                                    <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-rose">
                                        -${{ number_format($gasto->monto, 2) }}
                                    </p>
                                    <p class="font-body text-[0.72rem] text-hint">
                                        ${{ number_format($gasto->monto_mensual_equivalente, 2) }}/mes
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between p-3 rounded-sys-input
                                {{ $urgente ? 'bg-rose-light border border-rose/20' : ($proximo ? 'bg-amber-light border border-amber/20' : 'bg-surface border border-border') }}">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ $urgente ? '🔴' : ($proximo ? '🟡' : '🟢') }}</span>
                                    <span
                                        class="font-body text-[0.8rem] {{ $urgente ? 'text-rose font-bold' : ($proximo ? 'text-amber-dark font-bold' : 'text-muted') }}">
                                        {{ $gasto->proxima_fecha->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                                <span
                                    class="font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] {{ $urgente ? 'text-rose' : ($proximo ? 'text-amber-dark' : 'text-hint') }}">
                                    @if ($dias == 0)
                                        ¡Hoy!
                                    @elseif ($dias == 1)
                                        Mañana
                                    @elseif ($dias < 0)
                                        Vencido hace {{ abs($dias) }}d
                                    @else
                                        En {{ $dias }} días
                                    @endif
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <button wire:click="editarGastoFijo({{ $gasto->id }})"
                                    class="flex-1 py-2 border border-border rounded-sys-input font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] text-muted hover:text-ink hover:border-ink transition-all">
                                    Editar
                                </button>
                                @if ($gasto->cobrable_id)
                                    <button wire:click="registrarGastoAhora({{ $gasto->id }})"
                                        wire:confirm="Registrar {{ $gasto->nombre }} como movimiento ahora?"
                                        class="flex-1 py-2 border border-accent/30 bg-accent-light rounded-sys-input font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] text-accent hover:bg-accent hover:text-white transition-all">
                                        Registrar
                                    </button>
                                @endif
                                <button wire:click="eliminarGastoFijo({{ $gasto->id }})"
                                    wire:confirm="Eliminar {{ $gasto->nombre }}?"
                                    class="px-4 py-2 border border-rose/30 bg-rose-light rounded-sys-input font-display font-bold text-[0.65rem] text-rose hover:bg-rose hover:text-white transition-all">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    @empty
                        <div
                            class="bg-white rounded-sys-card border border-border p-16 flex flex-col items-center gap-4 text-center">
                            <span class="text-5xl">📋</span>
                            <h3 class="font-display font-bold text-[1.1rem] tracking-[-0.02em] text-ink">Sin gastos
                                fijos aún</h3>
                            <p class="font-body text-[0.85rem] text-hint max-w-xs">Agrega tus suscripciones, servicios
                                y pagos recurrentes.</p>
                        </div>
                    @endforelse
                </div>

                {{-- CUENTAS --}}
            @elseif ($tab === 'cuentas')
                <div class="grid gap-3">
                    @forelse ($cuentas as $cuenta)
                        <div wire:click="editarCuenta({{ $cuenta->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface cursor-pointer transition-colors {{ !$cuenta->activo ? 'opacity-50' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-10 rounded-sys-pill flex-shrink-0"
                                    style="background-color: {{ $cuenta->color }}"></div>
                                <div>
                                    <h4 class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                        {{ $cuenta->nombre }}</h4>
                                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                        <span
                                            class="font-body text-[0.72rem] text-hint px-2 py-0.5 bg-surface rounded-full border border-border">
                                            {{ match ($cuenta->tipo) {'debito' => '🏦 Débito','ahorro' => '💰 Ahorro','efectivo' => '💵 Efectivo',default => $cuenta->tipo} }}
                                        </span>
                                        @if ($cuenta->tasa_rendimiento)
                                            <span
                                                class="font-body text-[0.72rem] text-green px-2 py-0.5 bg-green-light rounded-full border border-green/20">
                                                {{ $cuenta->tasa_rendimiento }}% rdto.
                                            </span>
                                        @endif
                                        @if (!$cuenta->activo)
                                            <span
                                                class="font-body text-[0.72rem] text-hint px-2 py-0.5 bg-surface rounded-full border border-border">Inactiva</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-green">
                                    ${{ number_format($cuenta->saldo_actual, 2) }}
                                </p>
                                @if ($cuenta->rendimiento_mensual_estimado > 0)
                                    <span
                                        class="inline-block font-display font-bold text-[0.6rem] tracking-[0.1em] uppercase px-2 py-[0.2rem] rounded-sys-pill bg-green-light text-green">
                                        +${{ number_format($cuenta->rendimiento_mensual_estimado, 2) }}/mes
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-hint font-body text-[0.85rem]">Aún no hay cuentas.</div>
                    @endforelse
                </div>

                {{-- TARJETAS --}}
            @else
                <div class="grid gap-3">
                    @forelse ($tarjetas as $tarjeta)
                        <div wire:click="editarTarjeta({{ $tarjeta->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface cursor-pointer transition-colors {{ !$tarjeta->activo ? 'opacity-50' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-10 rounded-sys-pill flex-shrink-0"
                                    style="background-color: {{ $tarjeta->color }}"></div>
                                <div>
                                    <h4 class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink">
                                        {{ $tarjeta->nombre }}</h4>
                                    <p class="font-body text-[0.82rem] font-light text-muted">
                                        Corte: día {{ $tarjeta->dia_corte }} · Pago: día {{ $tarjeta->dia_pago }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-rose">
                                    -${{ number_format($tarjeta->deuda_actual, 2) }}
                                </p>
                                <p class="font-body text-[0.72rem] text-hint">límite:
                                    ${{ number_format($tarjeta->limite_credito, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-hint font-body text-[0.85rem]">Aún no hay tarjetas.</div>
                    @endforelse
                </div>
            @endif

        </div>
    </div>
</div>
