<?php

use Livewire\Volt\Component;
use App\Models\{Movimiento, Categoria, Cuenta, TarjetaCredito};
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Layout};

new #[Title('Historial - Mi Varo'), Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    // Filtros
    public $mes;
    public $categoria_id;
    public $tipo_movible; // 'cuenta' o 'tarjeta'
    public $busqueda = '';

    // Estado de Edición
    public $editando_id = null;
    public $edit_concepto, $edit_monto, $edit_fecha, $edit_categoria_id;

    public function mount()
    {
        $this->mes = now()->format('Y-m');
    }

    public function updating()
    {
        $this->resetPage();
    }

    // --- Lógica de Edición ---
    public function abrirEdicion($id)
    {
        $mov = Movimiento::find($id);
        $this->editando_id = $id;
        $this->edit_concepto = $mov->concepto;
        $this->edit_monto = $mov->monto;
        $this->edit_fecha = $mov->fecha->format('Y-m-d');
        $this->edit_categoria_id = $mov->categoria_id;
    }

    public function cancelarEdicion()
    {
        $this->reset(['editando_id', 'edit_concepto', 'edit_monto', 'edit_fecha', 'edit_categoria_id']);
    }

    public function actualizar()
    {
        $this->validate([
            'edit_concepto' => 'required|min:3',
            'edit_monto' => 'required|numeric',
            'edit_fecha' => 'required|date',
        ]);

        $mov = Movimiento::find($this->editando_id);
        $mov->update([
            'concepto' => mb_strtoupper($this->edit_concepto),
            'monto' => $this->edit_monto,
            'fecha' => $this->edit_fecha,
            'categoria_id' => $this->edit_categoria_id,
        ]);

        $this->cancelarEdicion();
        session()->flash('ok', 'Movimiento actualizado correctamente.');
    }

    // --- Lógica de Eliminación ---
    public function eliminar($id)
    {
        Movimiento::destroy($id);
        session()->flash('ok', 'Movimiento eliminado. Los balances se han recalculado.');
    }

    public function with()
    {
        $query = Movimiento::with(['movible', 'categoria'])
            ->when($this->busqueda, fn($q) => $q->where('concepto', 'like', "%{$this->busqueda}%"))
            ->when($this->mes, fn($q) => $q->whereMonth('fecha', explode('-', $this->mes)[1])->whereYear('fecha', explode('-', $this->mes)[0]))
            ->when($this->categoria_id, fn($q) => $q->where('categoria_id', $this->categoria_id))
            ->when($this->tipo_movible, function ($q) {
                $class = $this->tipo_movible === 'cuenta' ? Cuenta::class : TarjetaCredito::class;
                return $q->where('movible_type', $class);
            })
            ->latest('fecha')
            ->latest('id');

        return [
            'movimientos' => $query->paginate(20),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ];
    }
}; ?>

<div class="space-y-8 pb-20">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-[2rem] tracking-[-0.03em] text-ink leading-tight">Historial.</h1>
            <p class="font-body font-normal text-muted text-[0.95rem]">Gestiona cada centavo registrado.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <input type="month" wire:model.live="mes"
                class="bg-white border border-border rounded-sys-pill px-4 py-2 font-display font-bold text-[0.65rem] uppercase outline-none focus:border-accent">

            {{-- Filtro por tipo de pago --}}
            <select wire:model.live="tipo_movible"
                class="bg-white border border-border rounded-sys-pill px-4 py-2 font-display font-bold text-[0.65rem] uppercase outline-none focus:border-accent">
                <option value="">Todos los Métodos</option>
                <option value="cuenta">Cuentas / Efectivo</option>
                <option value="tarjeta">Tarjetas de Crédito</option>
            </select>

            <select wire:model.live="categoria_id"
                class="bg-white border border-border rounded-sys-pill px-4 py-2 font-display font-bold text-[0.65rem] uppercase outline-none focus:border-accent">
                <option value="">Categorías</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
    </header>

    {{-- Buscador --}}
    <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted">🔍</span>
        <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar concepto..."
            class="w-full bg-white border border-border rounded-sys-card p-4 pl-12 font-body text-[0.9rem] outline-none focus:border-accent transition-all">
    </div>

    @if (session('ok'))
        <div
            class="p-4 bg-green-light border border-green/20 text-green rounded-sys-card text-center font-body text-[0.85rem]">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-sys-card border border-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface border-b border-border">
                        <th class="p-5 font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-hint">
                            Fecha</th>
                        <th class="p-5 font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-hint">
                            Concepto</th>
                        <th class="p-5 font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-hint">
                            Origen</th>
                        <th
                            class="p-5 font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-hint text-right">
                            Monto</th>
                        <th
                            class="p-5 font-display font-bold text-[0.65rem] tracking-[0.14em] uppercase text-hint text-center">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($movimientos as $mov)
                        <tr class="hover:bg-surface transition-colors">
                            <td class="p-5 font-body text-[0.85rem] text-muted">{{ $mov->fecha->format('d/m/Y') }}</td>
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg">{{ $mov->categoria->icono ?? '🏷️' }}</span>
                                    <span
                                        class="font-display font-bold text-[0.9rem] text-ink">{{ $mov->concepto }}</span>
                                </div>
                            </td>
                            <td class="p-5">
                                <span
                                    class="font-body text-[0.75rem] px-2 py-1 bg-surface rounded border border-border text-muted">
                                    {{ $mov->movible->nombre ?? 'N/A' }}
                                </span>
                            </td>
                            <td
                                class="p-5 text-right font-display font-extrabold text-[1rem] {{ $mov->tipo == 'gasto' ? 'text-rose' : 'text-green' }}">
                                {{ $mov->tipo == 'gasto' ? '-' : '+' }}${{ number_format($mov->monto, 2) }}
                            </td>
                            <td class="p-5 text-center">
                                <div class="flex items-center justify-center gap-4">
                                    {{-- BOTÓN EDITAR (YA FUNCIONA) --}}
                                    <button wire:click="abrirEdicion({{ $mov->id }})"
                                        class="text-[1.1rem] hover:scale-110 transition-transform"
                                        title="Editar">✏️</button>

                                    <button
                                        onclick="confirm('¿Eliminar este registro?') || event.stopImmediatePropagation()"
                                        wire:click="eliminar({{ $mov->id }})"
                                        class="text-[1.1rem] hover:scale-110 transition-transform"
                                        title="Eliminar">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-hint italic">No hay resultados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-border bg-surface">
            {{ $movimientos->links() }}
        </div>
    </div>

    {{-- MODAL DE EDICIÓN (Aparece al hacer clic en el lápiz) --}}
    @if ($editando_id)
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div
                class="bg-white w-full max-w-md rounded-sys-card border border-border p-8 shadow-2xl animate-in zoom-in-95 duration-200">
                <h3 class="font-display font-bold text-[1.25rem] text-ink mb-6">Editar Movimiento</h3>

                <form wire:submit.prevent="actualizar" class="space-y-4">
                    <div>
                        <label class="font-display font-bold text-[0.7rem] text-ink uppercase">Concepto</label>
                        <input type="text" wire:model="edit_concepto"
                            class="w-full border border-border rounded-sys-input p-3 font-body text-[0.9rem] mt-1">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="font-display font-bold text-[0.7rem] text-ink uppercase">Monto</label>
                            <input type="number" step="0.01" wire:model="edit_monto"
                                class="w-full border border-border rounded-sys-input p-3 font-body text-[0.9rem] mt-1">
                        </div>
                        <div>
                            <label class="font-display font-bold text-[0.7rem] text-ink uppercase">Fecha</label>
                            <input type="date" wire:model="edit_fecha"
                                class="w-full border border-border rounded-sys-input p-3 font-body text-[0.9rem] mt-1">
                        </div>
                    </div>

                    <div>
                        <label class="font-display font-bold text-[0.7rem] text-ink uppercase">Categoría</label>
                        <select wire:model="edit_categoria_id"
                            class="w-full border border-border rounded-sys-input p-3 font-body text-[0.9rem] mt-1">
                            <option value="">Sin Categoría</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="cancelarEdicion"
                            class="flex-1 py-3 border border-border rounded-sys-pill font-display font-bold text-[0.75rem] uppercase text-muted hover:bg-surface transition-colors">Cancelar</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-accent text-white rounded-sys-pill font-display font-bold text-[0.75rem] uppercase hover:opacity-90 transition-opacity">Guardar
                            Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
