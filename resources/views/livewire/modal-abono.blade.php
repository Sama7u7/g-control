<?php
use Livewire\Volt\Component;
use App\Models\TarjetaCredito;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

new class extends Component {
    public $mostrar = false;
    public $tarjeta = null;
    public $monto = '';
    public $cuenta_id = '';

    #[On('abrir-modal-abono')]
    public function abrir($tarjetaId)
    {
        $this->tarjeta = TarjetaCredito::find($tarjetaId);
        $this->mostrar = true;
    }

    public function registrarAbono()
    {
        // 1. Validación estricta
        $this->validate([
            'monto' => 'required|numeric|min:0.01',
            'cuenta_id' => 'required|exists:cuentas,id',
        ]);

        DB::transaction(function () {
            // 2. Refrescamos para asegurar que tenemos los datos más recientes de la BD
            $cuenta = Cuenta::find($this->cuenta_id);
            $tarjeta = TarjetaCredito::find($this->tarjeta->id);

            // 3. Registro en tabla abonos (Esto dispara el recálculo en el modelo)
            \App\Models\Abono::create([
                'monto' => $this->monto,
                'fecha' => now(),
                'cuenta_id' => $cuenta->id,
                'tarjeta_id' => $tarjeta->id,
                'user_id' => auth()->id(),
            ]);

            // 4. Registro en historial de movimientos (tipo 'abono')
            \App\Models\Movimiento::create([
                'user_id' => auth()->id(),
                'movible_id' => $cuenta->id,
                'movible_type' => \App\Models\Cuenta::class,
                'concepto' => 'Abono a ' . $tarjeta->nombre,
                'monto' => $this->monto,
                'tipo' => 'gasto', // Contablemente es una salida de la cuenta
                'fecha' => now(),
            ]);
        });

        // 5. Feedback inmediato
        $this->reset(['monto', 'cuenta_id', 'mostrar']);
        $this->dispatch('movimiento-registrado');
        // Usamos una notificación flash para confirmar al usuario
        session()->flash('message', 'Abono registrado correctamente.');
    }
}; ?>

<div x-data="{ mostrar: @entangle('mostrar') }" x-show="mostrar" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    style="display: none;">
    <div @click.away="mostrar = false" class="bg-white w-full max-w-sm p-6 rounded-2xl shadow-xl space-y-4">
        <h2 class="font-display font-bold text-lg text-ink">Abonar a {{ $tarjeta->nombre ?? '' }}</h2>

        <input type="number" wire:model="monto" placeholder="Monto a abonar"
            class="w-full p-3 bg-surface border border-border rounded-lg outline-none font-body">

        <select wire:model="cuenta_id"
            class="w-full p-3 bg-surface border border-border rounded-lg outline-none font-body">
            <option value="">Selecciona cuenta de origen</option>
            @foreach (auth()->user()->cuentas as $c)
                {{-- Usamos 'saldo_inicial' --}}
                <option value="{{ $c->id }}">{{ $c->nombre }} (Disp:
                    ${{ number_format($c->saldo_inicial, 2) }})</option>
            @endforeach
        </select>

        <div class="flex gap-2 pt-2">
            <button @click="mostrar = false"
                class="flex-1 py-2 font-bold text-muted font-display text-sm">Cancelar</button>
            <button wire:click="registrarAbono"
                class="flex-1 py-2 bg-accent text-white rounded-lg font-bold font-display text-sm hover:bg-accent/90 transition-colors">Confirmar
                Abono</button>
        </div>
    </div>
</div>
