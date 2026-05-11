<?php
use Livewire\Volt\Component;
use App\Models\Movimiento;

new class extends Component {
    public function with()
    {
        return [
            'movimientos' => Movimiento::with(['movible', 'categoria'])
                ->latest()
                ->take(10)
                ->get(),
        ];
    }
}; ?>

<div>
    @foreach ($movimientos as $mov)
        <div class="p-3 border-b border-gray-100 flex justify-between">
            <span>{{ $mov->concepto }}</span>
            <span class="{{ $mov->tipo == 'gasto' ? 'text-red-500' : 'text-green-500' }}">
                ${{ number_format($mov->monto, 2) }}
            </span>
        </div>
    @endforeach
</div>
