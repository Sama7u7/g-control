<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    @if (session('status'))
        <div
            class="mb-6 p-4 bg-green-light border border-green/20 text-green rounded-sys-input text-center font-body text-[0.85rem]">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-8 text-center">
        <h2 class="font-display font-extrabold text-[1.7rem] tracking-[-0.03em] text-ink">Bienvenido de vuelta</h2>
        <p class="font-body text-muted text-[0.95rem] mt-1">Ingresa a tu cuenta para continuar</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div class="flex flex-col gap-1">
            <label for="email" class="font-display font-bold text-[0.7rem] text-ink">Correo Electrónico</label>
            <input wire:model="form.email" id="email" type="email" required autofocus autocomplete="username"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('form.email') border-rose @enderror">
            @error('form.email')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1">
            <label for="password" class="font-display font-bold text-[0.7rem] text-ink">Contraseña</label>
            <input wire:model="form.password" id="password" type="password" required autocomplete="current-password"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('form.password') border-rose @enderror">
            @error('form.password')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded border-border text-accent focus:ring-accent-light w-4 h-4 cursor-pointer">
                <span class="font-body text-[0.82rem] text-muted">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] text-hint hover:text-accent transition-colors"
                    href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Botón Submit -->
        <div class="pt-2">
            <button type="submit"
                class="w-full bg-accent text-white py-3 rounded-sys-pill font-display font-bold text-[0.82rem] hover:opacity-90 transition-opacity">
                INICIAR SESIÓN
            </button>
        </div>

        <!-- Link a Registro -->
        <div class="text-center mt-6">
            <p class="font-body text-[0.82rem] text-muted">¿No tienes cuenta? <a href="{{ route('register') }}"
                    wire:navigate
                    class="font-display font-bold text-[0.65rem] uppercase tracking-[0.1em] text-accent ml-1">Regístrate</a>
            </p>
        </div>
    </form>
</div>
