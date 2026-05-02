<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        event(new Registered(($user = User::create($validated))));
        Auth::login($user);
        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h2 class="font-display font-extrabold text-[1.7rem] tracking-[-0.03em] text-ink">Crea tu cuenta</h2>
        <p class="font-body text-muted text-[0.95rem] mt-1">Empieza a controlar tus finanzas hoy mismo</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <!-- Name -->
        <div class="flex flex-col gap-1">
            <label for="name" class="font-display font-bold text-[0.7rem] text-ink">Nombre</label>
            <input wire:model="name" id="name" type="text" required autofocus autocomplete="name"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('name') border-rose @enderror">
            @error('name')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="flex flex-col gap-1">
            <label for="email" class="font-display font-bold text-[0.7rem] text-ink">Correo Electrónico</label>
            <input wire:model="email" id="email" type="email" required autocomplete="username"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('email') border-rose @enderror">
            @error('email')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1">
            <label for="password" class="font-display font-bold text-[0.7rem] text-ink">Contraseña</label>
            <input wire:model="password" id="password" type="password" required autocomplete="new-password"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('password') border-rose @enderror">
            @error('password')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1">
            <label for="password_confirmation" class="font-display font-bold text-[0.7rem] text-ink">Confirmar
                Contraseña</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" required
                autocomplete="new-password"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('password_confirmation') border-rose @enderror">
            @error('password_confirmation')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
            <button type="submit"
                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                REGISTRARSE
            </button>
        </div>

        <!-- Link a Login -->
        <div class="text-center mt-4">
            <p class="font-body text-[0.82rem] text-muted">¿Ya tienes cuenta? <a href="{{ route('login') }}"
                    wire:navigate
                    class="font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] text-accent ml-1">Inicia
                    sesión</a></p>
        </div>
    </form>
</div>
