<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo"></div>
        <h2 class="auth-title">Crear ID de Chat</h2>
        <p class="auth-subtitle">Regístrate para empezar a chatear</p>

        <form wire:submit.prevent="register">
            <div class="form-group">
                <label for="name" class="form-label">Nombre completo</label>
                <input type="text" id="name" wire:model="name" class="form-control" placeholder="Juan Pérez" required autofocus>
                @error('name')
                    <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" wire:model="email" class="form-control" placeholder="nombre@ejemplo.com" required autocomplete="email">
                @error('email')
                    <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" wire:model="password" class="form-control" placeholder="••••••••" required>
                @error('password')
                    <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" wire:model="password_confirmation" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn">
                <span wire:loading.remove wire:target="register">Registrarse</span>
                <span wire:loading wire:target="register">Creando cuenta...</span>
            </button>
        </form>

        <div class="auth-footer">
            ¿Ya tienes una cuenta? <a href="/login" wire:navigate>Iniciar sesión</a>
        </div>
    </div>
</div>
