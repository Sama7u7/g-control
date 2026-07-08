<?php
use Livewire\Volt\Component;
use Livewire\WithPagination; // 🚨 1. Importamos la clase de paginación
use App\Models\Movimiento;

new class extends Component {
    use WithPagination; // 🚨 2. Activamos la paginación en el componente

    public function with()
    {
        return [
            // 🚨 3. Cambiamos take(10)->get() por simplePaginate(5)
            'movimientos' => Movimiento::with(['movible', 'categoria'])
                ->latest()
                ->simplePaginate(5),
        ];
    }
}; ?>

<div class="flex flex-col">
    @forelse ($movimientos as $mov)
        <div
            class="py-2.5 px-2 border-b border-border flex justify-between items-center last:border-0 hover:bg-surface/50 transition-colors">
            <div class="flex flex-col">
                <span class="font-display font-bold text-ink text-[0.85rem] uppercase tracking-tight">
                    {{ $mov->concepto }}
                </span>
                <span class="font-body text-muted text-[0.65rem] mt-0.5">
                    {{ \Carbon\Carbon::parse($mov->fecha)->format('d M') }}
                    @if ($mov->transferencia_id)
                        • 🔄 Traspaso
                    @elseif($mov->categoria)
                        • {{ $mov->categoria->nombre }}
                    @endif
                </span>
            </div>
            <span
                class="font-display font-extrabold text-[0.95rem] tracking-tight {{ $mov->tipo == 'gasto' ? 'text-rose' : 'text-green' }}">
                {{ $mov->tipo == 'gasto' ? '-' : '+' }}${{ number_format($mov->monto, 2) }}
            </span>
        </div>
    @empty
        <div class="py-6 text-center text-muted font-body text-[0.85rem]">
            No hay movimientos recientes.
        </div>
    @endforelse

    {{-- 🚨 4. Aquí imprimimos los botones de "Anterior" y "Siguiente" --}}
    <div class="pt-3">
        {{ $movimientos->links(data: ['scrollTo' => false]) }}
    </div>
</div>
