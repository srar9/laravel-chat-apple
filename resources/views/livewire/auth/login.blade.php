<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo"></div>
        <h2 class="auth-title">Iniciar sesión</h2>
        <p class="auth-subtitle">Usa tu cuenta para acceder a la aplicación</p>

        <form wire:submit.prevent="login">
            <div class="form-group">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="nombre@ejemplo.com" required autocomplete="email" autofocus>
                @error('email')
                    <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" wire:model="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                @error('password')
                    <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn">
                <span wire:loading.remove wire:target="login">Continuar</span>
                <span wire:loading wire:target="login">Cargando...</span>
            </button>
        </form>

        <div class="auth-footer">
            ¿No tienes una cuenta? <a href="/register" wire:navigate>Crear una ahora</a>
        </div>
    </div>
</div>
