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
        <!-- Email Address or Username -->
        <div class="flex flex-col gap-1">
            <label for="email" class="font-display font-bold text-[0.7rem] text-ink">Correo Electrónico o Usuario</label>
            <!-- Cambiamos type="email" por type="text" -->
            <input wire:model="form.email" id="email" type="text" required autofocus autocomplete="username"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('form.email') border-rose @enderror">
            @error('form.email')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1" x-data="{ show: false }">
            <label for="password" class="font-display font-bold text-[0.7rem] text-ink">Contraseña</label>
            <div class="relative">
                <input wire:model="form.password" id="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                    class="w-full border border-border bg-white rounded-sys-input p-3 pr-12 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('form.password') border-rose @enderror">

                <!-- Botón Toggle -->
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-ink transition-colors focus:outline-none">
                    <!-- Icono Ojo -->
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <!-- Icono Ojo Tachado -->
                    <svg x-show="show" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
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
