<section class="login-card" aria-labelledby="login-titulo">
    <div class="login-brand">
        <span class="brand-mark brand-mark-lg" aria-hidden="true"><i class="bi bi-wrench-adjustable"></i></span>
        <div>
            <p class="login-kicker">Torque</p>
            <p class="login-tagline">Gestión de órdenes de trabajo del taller</p>
        </div>
        <ol class="flujo" aria-label="Recorrido de un vehículo en el taller">
            <li>Recepción</li>
            <li>Diagnóstico</li>
            <li>Cotización</li>
            <li>Reparación</li>
            <li>Entrega</li>
        </ol>
    </div>

    <div class="login-form">
        <h1 id="login-titulo">Iniciar sesión</h1>
        <p class="text-secondary">Ingresa con tu correo y contraseña.</p>

        <?= App\Core\View::partial('flash') ?>

        <form method="post" action="<?= e(url('/login')) ?>" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-control form-control-lg"
                       value="<?= e(old('email')) ?>" maxlength="150" autocomplete="username" required autofocus>
                <div class="invalid-feedback">Escribe un correo válido.</div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control form-control-lg"
                           maxlength="255" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password"
                            aria-label="Mostrar contraseña" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback">Escribe tu contraseña.</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100" data-loading-text="Verificando…">
                Ingresar
            </button>
        </form>

        <div class="registro-cta">
            <span>¿Eres cliente y aún no tienes cuenta?</span>
            <a href="<?= e(url('/registro')) ?>" class="btn btn-outline-secondary w-100">
                <i class="bi bi-person-plus" aria-hidden="true"></i> Crear cuenta de cliente
            </a>
        </div>
    </div>
</section>
