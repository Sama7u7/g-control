<?php

use Livewire\Volt\Component;
use App\Models\{Cuenta, TarjetaCredito, Categoria, Movimiento, GastoFijo};
use Livewire\Attributes\{Title, Layout};
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

new #[Title('Ajustes - Mi Varo'), Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;
    public $tab = 'cuentas',
        $editando_id = null;

    public $timezone;
    public $archivo_gastos;

    // ── Campos Generales ─────────────────────────────────────────────────────
    public $nombre,
        $color = '#4F3FF0',
        $activo = true;

    // ── Campos Cuenta (Ampliados) ────────────────────────────────────────────
    public $tipo = 'debito',
        $entidad_financiera = 'banco',
        $aplica_isr = true,
        $saldo_inicial = 0,
        $mostrarRendimiento = false,
        $ultima_actualizacion,
        $tasa_rendimiento,
        $tope_rendimiento,
        $tasa_excedente,
        $tipo_interes = 'escalonado';

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
        $this->reset(['nombre', 'color', 'activo', 'tipo', 'entidad_financiera', 'aplica_isr', 'tipo_interes', 'mostrarRendimiento', 'tasa_rendimiento', 'tope_rendimiento', 'tasa_excedente', 'editando_id', 'nuevo_saldo_real', 'nombre_categoria', 'gf_nombre', 'gf_monto', 'gf_activo', 'gf_registro_auto', 'gf_categoria_id', 'gf_notas', 'gf_cobrable_tipo', 'gf_cobrable_id']);
        $this->icono_categoria = '🏷️';
        $this->saldo_inicial = 0;
        $this->limite_credito = 0;
        $this->dia_corte = 1;
        $this->dia_pago = 10;
        $this->tipo = 'debito';
        $this->entidad_financiera = 'banco';
        $this->aplica_isr = true;
        $this->tipo_interes = 'escalonado';
        $this->ultima_actualizacion = now()->toDateString();
        $this->gf_frecuencia = 'mensual';
        $this->gf_dia_cobro = 1;
        $this->resetValidation();
    }

    public function editarCuenta($id): void
    {
        $c = auth()->user()->cuentas()->findOrFail($id);
        $this->editando_id = $id;
        $this->nombre = $c->nombre;
        $this->color = $c->color;
        $this->tipo = $c->tipo;
        $this->entidad_financiera = $c->entidad_financiera ?? 'banco';
        $this->aplica_isr = (bool) $c->aplica_isr;
        $this->tipo_interes = $c->tipo_interes ?? 'escalonado';
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
        $t = auth()->user()->tarjetasCredito()->findOrFail($id);
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
        // Buscamos primero en todo el sistema (globales + personales)
        $cat = Categoria::whereNull('user_id')
            ->orWhere('user_id', auth()->id())
            ->where('id', $id) // Buscamos por el ID específico
            ->first();

        // Si no existe, lanzamos el error manualmente para entender por qué
        if (!$cat) {
            abort(404, "La categoría con ID $id no pertenece a tu usuario o no es global.");
        }

        $this->editando_id = $id;
        $this->nombre_categoria = $cat->nombre;
        $this->icono_categoria = $cat->icono ?? '🏷️';
    }

    public function editarGastoFijo($id): void
    {
        $g = auth()->user()->gastosFijos()->findOrFail($id);
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

    public function eliminarCategoria($id): void
    {
        auth()->user()->categorias()->findOrFail($id)->delete();
        session()->flash('ok', 'Categoría eliminada');
        $this->limpiar();
    }

    public function eliminarGastoFijo($id): void
    {
        auth()->user()->gastosFijos()->findOrFail($id)->delete();
        session()->flash('ok', 'Gasto fijo eliminado');
        $this->limpiar();
    }

    public function registrarGastoAhora($id): void
    {
        $gasto = auth()->user()->gastosFijos()->findOrFail($id);
        $gasto->registrarMovimiento();
        session()->flash('ok', $gasto->nombre . ' registrado como movimiento');
    }
    public function sincronizarSaldo($id): void
    {
        $this->validate(['nuevo_saldo_real' => 'required|numeric']);
        $c = auth()->user()->cuentas()->findOrFail($id);

        // Cambiamos saldo_total por saldo_actual
        $diferencia = $this->nuevo_saldo_real - $c->saldo_actual;

        if ($diferencia != 0) {
            Movimiento::create([
                'monto' => abs($diferencia),
                'concepto' => 'AJUSTE / CAPITALIZACIÓN',
                'tipo' => $diferencia > 0 ? 'ingreso' : 'gasto',
                'fecha' => now()->toDateString(),
                'movible_id' => $c->id,
                'movible_type' => Cuenta::class,
            ]);
        }

        // El reloj se reinicia, pero ahora el dinero ya está consolidado en el historial
        $c->update(['ultima_actualizacion' => now()->toDateString()]);

        session()->flash('ok', 'Saldo sincronizado y rendimientos capitalizados');
        $this->limpiar();
    }

    public function guardar(): void
    {
        $user = auth()->user();

        if ($this->tab === 'cuentas') {
            $this->validate([
                'nombre' => 'required|min:2',
                'tipo' => 'required|in:debito,efectivo,ahorro',
                'entidad_financiera' => 'required|in:banco,sofipo,fintech,cetes',
                'saldo_inicial' => 'required|numeric',
            ]);
            $data = [
                'nombre' => mb_strtoupper($this->nombre),
                'tipo' => $this->tipo,
                'entidad_financiera' => $this->entidad_financiera,
                'aplica_isr' => $this->aplica_isr,
                'tipo_interes' => $this->mostrarRendimiento ? $this->tipo_interes : 'escalonado',
                'saldo_inicial' => $this->saldo_inicial,
                'color' => $this->color,
                'activo' => $this->activo,
                'ultima_actualizacion' => $this->mostrarRendimiento ? $this->ultima_actualizacion : null,
                'tasa_rendimiento' => $this->mostrarRendimiento ? $this->tasa_rendimiento : null,
                'tope_rendimiento' => $this->mostrarRendimiento ? $this->tope_rendimiento : null,
                'tasa_excedente' => $this->mostrarRendimiento ? $this->tasa_excedente : null,
            ];

            // Corrección aquí: Usamos la relación del usuario para crear o editar
            $this->editando_id ? $user->cuentas()->findOrFail($this->editando_id)->update($data) : $user->cuentas()->create($data);
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

            // Corrección aquí
            $this->editando_id ? $user->tarjetasCredito()->findOrFail($this->editando_id)->update($data) : $user->tarjetasCredito()->create($data);
        } elseif ($this->tab === 'categorias') {
            // 1. Normalizamos a mayúsculas ANTES de validar
            $this->nombre_categoria = mb_strtoupper($this->nombre_categoria);

            // 2. Ahora validamos con el nombre ya en mayúsculas
            $this->validate(
                [
                    'nombre_categoria' => [
                        'required',
                        'min:3',
                        Rule::unique('categorias', 'nombre')
                            ->where(function ($query) {
                                return $query->where('user_id', auth()->id());
                            })
                            ->ignore($this->editando_id),
                    ],
                    'icono_categoria' => 'required',
                ],
                [
                    // Aquí es donde personalizas el mensaje
                    'nombre_categoria.unique' => 'Ya tienes una categoría con este nombre.',
                    'nombre_categoria.required' => 'El nombre es obligatorio.',
                    'nombre_categoria.min' => 'El nombre debe tener al menos 3 caracteres.',
                ],
            );
            // Si es edición, verificamos si es global
            if ($this->editando_id) {
                $cat = Categoria::findOrFail($this->editando_id);
                if (is_null($cat->user_id)) {
                    session()->flash('ok', 'No puedes editar categorías globales.');
                    $this->limpiar();
                    return;
                }
            }

            $data = [
                'nombre' => $this->nombre_categoria, // Ya está en mayúsculas
                'icono' => $this->icono_categoria,
                'user_id' => auth()->id(),
            ];

            $this->editando_id ? auth()->user()->categorias()->findOrFail($this->editando_id)->update($data) : auth()->user()->categorias()->create($data);
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

            // Corrección aquí
            $this->editando_id ? $user->gastosFijos()->findOrFail($this->editando_id)->update($data) : $user->gastosFijos()->create($data);
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

    public function with(): array
    {
        $allTimezones = \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA);
        $preferidas = ['America/Mexico_City', 'America/Monterrey', 'America/Merida', 'America/Tijuana', 'America/Cancun'];

        $user = auth()->user();

        return [
            'cuentas' => $user->cuentas,
            'tarjetas' => $user->tarjetasCredito,
            'categorias' => Categoria::whereNull('user_id')->orWhere('user_id', $user->id)->orderBy('nombre')->get(),
            'gastos_fijos' => $user
                ->gastosFijos()
                ->with(['categoria', 'cobrable'])
                ->orderBy('proxima_fecha')
                ->get(),
            'timezones' => array_unique(array_merge($preferidas, $allTimezones)),
            'total_mensual' => $user->gastosFijos()->where('activo', true)->get()->sum->monto_mensual_equivalente,
            'proximos_siete' => $user->gastosFijos()->proximos(7)->count(),
        ];
    }
    public function importarGastos(): void
    {
        $this->validate([
            'archivo_gastos' => 'required|mimes:csv,txt|max:5120',
        ]);

        $path = $this->archivo_gastos->getRealPath();
        $archivo = fopen($path, 'r');

        fgetcsv($archivo);

        $contador = 0;

        while (($fila = fgetcsv($archivo)) !== false) {
            if (count($fila) >= 4) {
                \App\Models\Movimiento::create([
                    'concepto' => mb_strtoupper(trim($fila[0])),
                    'monto' => (float) $fila[1],
                    'fecha' => \Carbon\Carbon::parse($fila[2])->toDateString(),
                    'tipo' => 'gasto',
                    'movible_type' => \App\Models\Cuenta::class,
                    'movible_id' => (int) $fila[3],
                ]);
                $contador++;
            }
        }

        fclose($archivo);

        $this->reset('archivo_gastos');
        session()->flash('ok', "¡Éxito! Se importaron {$contador} gastos desde el archivo.");
        $this->limpiar();
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-10 pb-20">

    <header>
        <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Configuración</h1>
        <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-accent mt-1">Administra tus
            finanzas</p>
    </header>

    <div class="flex p-1.5 bg-surface rounded-sys-pill border border-border overflow-x-auto">
        @foreach (['cuentas' => 'Cuentas', 'tarjetas' => 'Tarjetas', 'categorias' => 'Categorías', 'gastos' => 'Gastos Fijos', 'perfil' => 'Perfil'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="flex-1 py-3 rounded-sys-pill font-display font-bold text-[0.7rem] uppercase tracking-[0.1em] transition-all whitespace-nowrap px-3 {{ $tab == $key ? 'bg-white shadow-sm text-accent' : 'text-hint hover:text-muted bg-transparent' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'gastos')
        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white p-7 rounded-sys-card border border-border">
                <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-rose mb-2">Compromiso
                    Mensual</p>
                <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">
                    ${{ number_format($total_mensual, 2) }}</h2>
                <p class="font-body text-[0.75rem] text-hint mt-1">Equivalente mensual de todos tus fijos activos</p>
            </div>
            <div class="bg-white p-7 rounded-sys-card border border-border">
                <p class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-amber mb-2">Próximos 7
                    días</p>
                <h2 class="font-display font-extrabold text-[1.8rem] tracking-[-0.04em] text-ink">{{ $proximos_siete }}
                </h2>
                <p class="font-body text-[0.75rem] text-hint mt-1">
                    {{ $proximos_siete == 1 ? 'gasto por vencer' : 'gastos por vencer' }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-sys-card border border-border">
                <h2
                    class="font-display font-bold text-[1.25rem] tracking-[-0.02em] text-ink mb-6 flex items-center gap-2">
                    <span class="w-2 h-6 {{ $tab == 'perfil' ? 'bg-amber' : 'bg-accent' }} rounded-sys-pill"></span>
                    {{ $editando_id ? 'Editar' : ($tab == 'perfil' ? 'Ajustes de' : 'Nuevo') }}
                    {{ match ($tab) {'cuentas' => 'Cuenta','tarjetas' => 'Tarjeta','categorias' => 'Categoría','gastos' => 'Gasto Fijo','perfil' => 'Perfil'} }}
                </h2>

                @if (session('ok'))
                    <div
                        class="mb-6 p-4 bg-green-light border border-green/20 text-green rounded-sys-input text-center font-body text-[0.85rem]">
                        {{ session('ok') }}
                    </div>
                @endif

                <form wire:submit.prevent="guardar">
                    @if ($tab === 'perfil')
                        <div wire:key="form-perfil" class="space-y-6">
                            <div
                                class="bg-surface p-6 rounded-sys-card border border-border flex flex-col items-center gap-2">
                                <span class="text-3xl">🌍</span>
                                <p
                                    class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-ink text-center">
                                    Configura tu horario local</p>
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

                            {{-- Sección de Importación Masiva en Perfil --}}
                            <div
                                class="bg-surface p-6 rounded-sys-card border border-border flex flex-col items-center gap-3 mt-6">
                                <div
                                    class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-2xl border border-border">
                                    📊
                                </div>
                                <div class="text-center">
                                    <p
                                        class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-ink">
                                        Importación Masiva
                                    </p>
                                    <p class="font-body text-[0.75rem] text-hint mt-1 max-w-[250px]">
                                        Descarga la plantilla oficial, llénala en Excel y súbela para registrar todo el
                                        historial.
                                    </p>
                                </div>

                                <div class="w-full flex gap-2 mt-2">
                                    <a href="{{ route('descargar.plantilla') }}"
                                        class="flex-1 text-center bg-white border border-border text-ink py-3 rounded-sys-pill font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] hover:border-accent hover:text-accent transition-all">
                                        1. Bajar CSV
                                    </a>

                                    <label
                                        class="flex-1 text-center bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] hover:opacity-90 transition-all cursor-pointer">
                                        2. Subir CSV
                                        <input type="file" wire:model="archivo_gastos" class="hidden" accept=".csv">
                                    </label>
                                </div>

                                <div wire:loading wire:target="archivo_gastos"
                                    class="text-[0.65rem] text-hint font-display tracking-[0.1em] uppercase animate-pulse mt-2">
                                    Cargando archivo...
                                </div>

                                @if ($archivo_gastos)
                                    <div
                                        class="w-full mt-2 p-4 bg-amber-light border border-amber/20 rounded-sys-input text-center animate-in fade-in slide-in-from-top-2">
                                        <p class="font-body text-[0.75rem] text-amber-dark mb-3">
                                            Archivo detectado: <span
                                                class="font-bold">{{ $archivo_gastos->getClientOriginalName() }}</span>
                                        </p>
                                        <button type="button" wire:click="importarGastos"
                                            class="w-full bg-amber text-white py-3 rounded-sys-pill font-display font-bold text-[0.7rem] uppercase tracking-[0.1em] hover:opacity-90 transition-opacity">
                                            Procesar Todos los Gastos
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif ($tab === 'categorias')
                        <div wire:key="form-categorias" class="space-y-5">
                            <div class="bg-surface p-6 rounded-sys-card border border-border flex flex-col items-center gap-4"
                                x-data="{ icono: @entangle('icono_categoria') }">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl border border-border"
                                        x-text="icono"></div>
                                    <input type="text"
                                        class="w-16 bg-white border border-border rounded-sys-input p-3 text-center text-xl outline-none focus:border-accent transition-all"
                                        x-bind:value="icono" @focus="$el.value = ''"
                                        @input="icono = $el.value; $wire.set('icono_categoria', $el.value)">
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Nombre de la
                                    Categoría</label>
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
                        </div>
                    @elseif ($tab === 'gastos')
                        <div wire:key="form-gastos" class="space-y-5">
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                <input type="text" wire:model="gf_nombre" placeholder="ej. Netflix, Renta, Seguro"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent transition-all">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Monto</label>
                                <input type="number" step="0.01" wire:model="gf_monto" placeholder="0.00"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent transition-all">
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Frecuencia</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach (['semanal' => 'Sem', 'quincenal' => 'Qna', 'mensual' => 'Mens', 'bimestral' => 'Bim', 'trimestral' => 'Trim', 'semestral' => '6m', 'anual' => 'Anual'] as $val => $etiqueta)
                                        <button type="button"
                                            wire:click="$set('gf_frecuencia', '{{ $val }}')"
                                            class="p-2 rounded-sys-input border-2 font-display font-bold text-[0.6rem] uppercase {{ $gf_frecuencia == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted' }}">
                                            {{ $etiqueta }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Día de Cobro</label>
                                <input type="number" wire:model="gf_dia_cobro" min="1" max="31"
                                    class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem]">
                            </div>
                            <div
                                class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                                <span
                                    class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Registro
                                    Automático</span>
                                <button type="button" wire:click="$toggle('gf_registro_auto')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $gf_registro_auto ? 'bg-accent' : 'bg-hint' }}">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $gf_registro_auto ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>
                            <button type="submit"
                                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem]">
                                {{ $editando_id ? 'ACTUALIZAR GASTO' : 'GUARDAR GASTO' }}
                            </button>
                        </div>
                    @elseif ($tab === 'cuentas')
                        <div wire:key="form-cuentas" class="space-y-5">
                            <div class="flex gap-4">
                                <div class="flex-1 flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                    <input type="text" wire:model="nombre" placeholder="ej. Nu Débito"
                                        class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent transition-all">
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
                                            class="flex flex-col items-center gap-1 p-3 rounded-sys-input border-2 transition-all font-display font-bold text-[0.65rem] uppercase {{ $tipo == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted hover:border-accent/40' }}">
                                            <span class="text-xl">{{ $emoji }}</span>{{ $etiqueta }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Entidad Financiera</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach (['banco' => 'Banco', 'sofipo' => 'SOFIPO', 'fintech' => 'Fintech', 'cetes' => 'Cetes'] as $val => $etiqueta)
                                        <button type="button"
                                            wire:click="$set('entidad_financiera', '{{ $val }}')"
                                            class="p-2 rounded-sys-input border-2 transition-all font-display font-bold text-[0.65rem] uppercase {{ $entidad_financiera == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-surface text-muted' }}">
                                            {{ $etiqueta }}
                                        </button>
                                    @endforeach
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
                                <label class="font-display font-bold text-[0.7rem] text-ink">Saldo Inicial</label>
                                <input type="number" step="0.01" wire:model="saldo_inicial" placeholder="0.00"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem] text-ink outline-none focus:border-accent transition-all">
                            </div>

                            @if ($editando_id)
                                @php $cuenta = $cuentas->find($editando_id); @endphp
                                <div class="p-4 bg-amber-light border border-amber/20 rounded-sys-input space-y-3">
                                    <div>
                                        <p
                                            class="font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-amber-dark">
                                            Corrección de Saldo</p>
                                        <p class="font-body text-[0.72rem] text-hint mt-0.5">Saldo real actual (con
                                            rendimientos): <span
                                                class="font-bold text-ink">${{ number_format($cuenta?->saldo_total ?? 0, 2) }}</span>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.01" wire:model="nuevo_saldo_real"
                                            placeholder="Saldo en tu banco hoy"
                                            class="flex-1 bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                                        <button type="button" wire:click="sincronizarSaldo({{ $editando_id }})"
                                            class="px-4 py-2 bg-amber text-white rounded-sys-input font-display font-bold text-[0.7rem] uppercase">Ajustar</button>
                                    </div>
                                </div>
                            @endif

                            <div
                                class="flex items-center justify-between p-4 bg-surface rounded-sys-input border border-border">
                                <div><span
                                        class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Genera
                                        Rendimiento</span></div>
                                <button type="button" wire:click="$toggle('mostrarRendimiento')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $mostrarRendimiento ? 'bg-accent' : 'bg-hint' }}">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $mostrarRendimiento ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>

                            @if ($mostrarRendimiento)
                                <div class="space-y-4 p-4 bg-surface rounded-sys-input border border-border">
                                    <div
                                        class="flex items-center justify-between p-3 bg-white rounded-sys-input border border-border">
                                        <div>
                                            <span
                                                class="font-display font-bold text-[0.65rem] tracking-[0.14em] text-ink uppercase">Retención
                                                ISR</span>
                                            <p class="font-body text-[0.65rem] text-hint mt-0.5">Aplica retención
                                                automática (0.50% anual).</p>
                                        </div>
                                        <button type="button" wire:click="$toggle('aplica_isr')"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $aplica_isr ? 'bg-accent' : 'bg-hint' }}">
                                            <span
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $aplica_isr ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                        </button>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label class="font-display font-bold text-[0.7rem] text-ink">Tipo de
                                            Interés</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach (['simple' => 'Simple', 'escalonado' => 'Escalonado'] as $val => $etiqueta)
                                                <button type="button"
                                                    wire:click="$set('tipo_interes', '{{ $val }}')"
                                                    class="p-2 rounded-sys-input border-2 font-display font-bold text-[0.65rem] uppercase {{ $tipo_interes == $val ? 'border-accent bg-accent-light text-accent' : 'border-border bg-white text-muted' }}">
                                                    {{ $etiqueta }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="font-display font-bold text-[0.7rem] text-ink">Tasa Anual
                                            (%)</label>
                                        <input type="number" step="0.01" wire:model="tasa_rendimiento"
                                            class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                                    </div>

                                    @if ($tipo_interes === 'escalonado')
                                        <div class="flex flex-col gap-1">
                                            <label class="font-display font-bold text-[0.7rem] text-ink">Tope de
                                                Rendimiento ($)</label>
                                            <input type="number" step="0.01" wire:model="tope_rendimiento"
                                                class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <label class="font-display font-bold text-[0.7rem] text-ink">Tasa Excedente
                                                (%)</label>
                                            <input type="number" step="0.01" wire:model="tasa_excedente"
                                                class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                                        </div>
                                    @endif

                                    <div class="flex flex-col gap-1">
                                        <label class="font-display font-bold text-[0.7rem] text-ink">Última
                                            Actualización</label>
                                        <input type="date" wire:model="ultima_actualizacion"
                                            class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                                    </div>
                                </div>
                            @endif

                            <button type="submit"
                                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                                {{ $editando_id ? 'ACTUALIZAR CUENTA' : 'GUARDAR CUENTA' }}
                            </button>
                        </div>
                    @else
                        <div wire:key="form-tarjetas" class="space-y-5">
                            <div class="flex gap-4">
                                <div class="flex-1 flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
                                    <input type="text" wire:model="nombre" placeholder="ej. Nu Crédito"
                                        class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent transition-all">
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
                            <div class="flex flex-col gap-1">
                                <label class="font-display font-bold text-[0.7rem] text-ink">Límite de Crédito</label>
                                <input type="number" step="0.01" wire:model="limite_credito"
                                    class="w-full bg-white border border-border rounded-sys-input p-3 font-body text-[0.875rem]">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Día de Corte</label>
                                    <input type="number" wire:model="dia_corte"
                                        class="w-full bg-white border border-border rounded-sys-input p-3">
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-display font-bold text-[0.7rem] text-ink">Día de Pago</label>
                                    <input type="number" wire:model="dia_pago"
                                        class="w-full bg-white border border-border rounded-sys-input p-3">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem]">
                                {{ $editando_id ? 'ACTUALIZAR TARJETA' : 'GUARDAR TARJETA' }}
                            </button>
                        </div>
                    @endif

                    @if ($editando_id)
                        <button type="button" wire:click="limpiar"
                            class="w-full text-muted hover:text-ink font-display font-bold text-[0.65rem] uppercase mt-2">
                            Cancelar Edición
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <div class="lg:col-span-7 space-y-4">
            @if ($tab === 'cuentas')
                <div class="grid gap-3">
                    @forelse ($cuentas as $cuenta)
                        <div wire:click="editarCuenta({{ $cuenta->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface cursor-pointer transition-colors {{ !$cuenta->activo ? 'opacity-50' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-10 rounded-sys-pill flex-shrink-0"
                                    style="background-color: {{ $cuenta->color }}"></div>
                                <div>
                                    <h4
                                        class="font-display font-bold text-[1rem] tracking-[-0.01em] text-ink flex items-center gap-2">
                                        {{ $cuenta->nombre }}
                                        <span
                                            class="font-body text-[0.65rem] font-normal text-hint bg-surface px-2 py-0.5 rounded border border-border">ID:
                                            {{ $cuenta->id }}</span>
                                    </h4>
                                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                        <span
                                            class="font-body text-[0.72rem] text-hint px-2 py-0.5 bg-surface rounded-full border border-border uppercase">{{ $cuenta->entidad_financiera }}</span>
                                        @if ($cuenta->tasa_rendimiento)
                                            <span
                                                class="font-body text-[0.72rem] text-green px-2 py-0.5 bg-green-light rounded-full border border-green/20">{{ $cuenta->tasa_rendimiento }}%
                                                rdto.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[1.25rem] tracking-[-0.04em] text-green">
                                    ${{ number_format($cuenta->saldo_total, 2) }}</p>
                                @if ($cuenta->rendimiento_mensual_estimado > 0)
                                    <span
                                        class="inline-block font-display font-bold text-[0.6rem] tracking-[0.1em] uppercase px-2 py-[0.2rem] rounded-sys-pill bg-green-light text-green">+${{ number_format($cuenta->rendimiento_mensual_estimado, 2) }}/mes
                                        neto</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-hint">Aún no hay cuentas.</div>
                    @endforelse
                </div>
            @elseif ($tab === 'gastos')
                <div class="space-y-3">
                    @forelse ($gastos_fijos as $gasto)
                        <div class="bg-white rounded-sys-card border border-border p-5 space-y-4">
                            <div class="flex justify-between items-start">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-surface flex items-center justify-center text-lg">
                                        {{ $gasto->categoria?->icono ?? '📋' }}</div>
                                    <div>
                                        <h4 class="font-display font-bold text-[1rem] text-ink">{{ $gasto->nombre }}
                                        </h4>
                                        <p class="font-body text-[0.72rem] text-hint">{{ $gasto->frecuencia_label }} ·
                                            Próximo: {{ $gasto->proxima_fecha->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-display font-extrabold text-[1.25rem] text-rose">
                                        -${{ number_format($gasto->monto, 2) }}</p>
                                    <button wire:click="editarGastoFijo({{ $gasto->id }})"
                                        class="text-accent text-[0.65rem] font-bold uppercase">Editar</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-hint">Aún no hay gastos fijos.</div>
                    @endforelse
                </div>
            @elseif ($tab === 'categorias')
                <div class="grid grid-cols-3 gap-4">
                    @foreach ($categorias as $cat)
                        <div wire:click="editarCategoria({{ $cat->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex flex-col items-center gap-3 hover:bg-surface cursor-pointer">
                            <span class="text-3xl">{{ $cat->icono ?? '🏷️' }}</span>
                            <span
                                class="font-display font-bold text-ink text-[0.6rem] uppercase">{{ $cat->nombre }}</span>
                        </div>
                    @endforeach
                </div>
            @elseif ($tab === 'tarjetas')
                <div class="grid gap-3">
                    @foreach ($tarjetas as $tarjeta)
                        <div wire:click="editarTarjeta({{ $tarjeta->id }})"
                            class="bg-white p-5 rounded-sys-card border border-border flex justify-between items-center hover:bg-surface cursor-pointer transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-10 rounded-sys-pill flex-shrink-0"
                                    style="background-color: {{ $tarjeta->color }}"></div>
                                <div>
                                    <h4 class="font-display font-bold text-[1rem] text-ink">{{ $tarjeta->nombre }}
                                        <span
                                            class="font-body text-[0.65rem] font-normal text-hint bg-surface px-2 py-0.5 rounded border border-border">ID:
                                            {{ $tarjeta->id }}</span>
                                    </h4>
                                    <!-- AQUI AGREGAMOS EL LÍMITE DE LA TARJETA -->
                                    <p class="font-body text-[0.82rem] font-light text-muted mt-0.5">Límite:
                                        ${{ number_format($tarjeta->limite_credito, 2) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-display font-extrabold text-[1.25rem] text-rose">
                                    -${{ number_format($tarjeta->deuda_actual, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
