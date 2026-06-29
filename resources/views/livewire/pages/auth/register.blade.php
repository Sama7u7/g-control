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
    public string $last_name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
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

        <!-- Last Name -->
        <div class="flex flex-col gap-1">
            <label for="last_name" class="font-display font-bold text-[0.7rem] text-ink">Apellidos</label>
            <input wire:model="last_name" id="last_name" type="text" required autocomplete="last_name"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('last_name') border-rose @enderror">
            @error('last_name')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Username -->
        <div class="flex flex-col gap-1">
            <label for="username" class="font-display font-bold text-[0.7rem] text-ink">Nombre de Usuario</label>
            <input wire:model="username" id="username" type="text" required autocomplete="username"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('username') border-rose @enderror">
            @error('username')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="flex flex-col gap-1">
            <label for="email" class="font-display font-bold text-[0.7rem] text-ink">Correo Electrónico</label>
            <input wire:model="email" id="email" type="email" required autocomplete="email"
                class="w-full border border-border bg-white rounded-sys-input p-3 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('email') border-rose @enderror">
            @error('email')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1" x-data="{ show: false }">
            <label for="password" class="font-display font-bold text-[0.7rem] text-ink">Contraseña</label>
            <div class="relative">
                <!-- Se agregó pr-12 para que el texto no se superponga con el icono -->
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" required autocomplete="new-password"
                    class="w-full border border-border bg-white rounded-sys-input p-3 pr-12 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('password') border-rose @enderror">

                <!-- Botón Toggle -->
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-ink transition-colors focus:outline-none">
                    <!-- Icono de Ojo (Mostrar) -->
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <!-- Icono de Ojo Tachado (Ocultar) -->
                    <svg x-show="show" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <span class="font-body text-[0.68rem] text-rose mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1" x-data="{ show: false }">
            <label for="password_confirmation" class="font-display font-bold text-[0.7rem] text-ink">Confirmar Contraseña</label>
            <div class="relative">
                <input wire:model="password_confirmation" id="password_confirmation" :type="show ? 'text' : 'password'" required autocomplete="new-password"
                    class="w-full border border-border bg-white rounded-sys-input p-3 pr-12 font-body text-[0.875rem] outline-none focus:border-accent focus:ring-4 focus:ring-accent-light transition-all @error('password_confirmation') border-rose @enderror">

                <!-- Botón Toggle -->
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-ink transition-colors focus:outline-none">
                    <!-- Icono de Ojo (Mostrar) -->
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <!-- Icono de Ojo Tachado (Ocultar) -->
                    <svg x-show="show" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
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
