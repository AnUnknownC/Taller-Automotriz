<?php
/** Autorregistro de clientes (layout de acceso). Solo crea cuentas con rol cliente. */
$tipo = old('tipo_documento', 'CC');
$tipos = array_intersect_key(App\Models\Cliente::TIPOS_DOCUMENTO, array_flip(['CC', 'CE', 'PAS', 'NIT']));
?>
<section class="login-card registro-card" aria-labelledby="registro-titulo">
    <div class="login-brand">
        <span class="brand-mark brand-mark-lg" aria-hidden="true"><i class="bi bi-wrench-adjustable"></i></span>
        <div>
            <p class="login-kicker">Torque</p>
            <p class="login-tagline">Portal de clientes del taller</p>
        </div>
        <ul class="beneficios" aria-label="Con tu cuenta puedes">
            <li><i class="bi bi-car-front" aria-hidden="true"></i> Ver el estado de tu vehículo en tiempo real</li>
            <li><i class="bi bi-clipboard-check" aria-hidden="true"></i> Aprobar o rechazar cotizaciones desde el celular</li>
            <li><i class="bi bi-clock-history" aria-hidden="true"></i> Consultar el historial de servicios</li>
        </ul>
    </div>

    <div class="login-form">
        <h1 id="registro-titulo">Crear cuenta de cliente</h1>
        <p class="text-secondary">Tus vehículos aparecerán aquí cuando los traigas al taller.</p>

        <?= App\Core\View::partial('flash') ?>

        <form method="post" action="<?= e(url('/registro')) ?>" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <!-- Campo trampa para bots: invisible para personas y lectores de pantalla -->
            <div class="campo-trampa" aria-hidden="true">
                <label for="sitio_web">No llenar este campo</label>
                <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="tipo_documento" class="form-label">Tipo de documento <span class="req" aria-hidden="true">*</span></label>
                    <select id="tipo_documento" name="tipo_documento" class="form-select<?= invalid('tipo_documento') ?>" <?= aria_error('tipo_documento') ?> required>
                        <?php foreach ($tipos as $k => $l): ?>
                            <option value="<?= e($k) ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error('tipo_documento') ?>
                </div>
                <div class="col-sm-6">
                    <label for="documento" class="form-label">Número de documento <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="documento" name="documento" class="form-control<?= invalid('documento') ?>" <?= aria_error('documento') ?>
                           value="<?= e(old('documento')) ?>" maxlength="20" required inputmode="text" autocomplete="off">
                    <?= field_error('documento') ?: '<div class="invalid-feedback">Escribe tu número de documento.</div>' ?>
                </div>
                <div class="col-12">
                    <label for="nombre" class="form-label">Nombre completo <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="form-control<?= invalid('nombre') ?>" <?= aria_error('nombre') ?>
                           value="<?= e(old('nombre')) ?>" minlength="3" maxlength="120" required autocomplete="name">
                    <?= field_error('nombre') ?: '<div class="invalid-feedback">Mínimo 3 caracteres.</div>' ?>
                </div>
                <div class="col-sm-5">
                    <label for="telefono" class="form-label">Celular <span class="req" aria-hidden="true">*</span></label>
                    <input type="tel" id="telefono" name="telefono" class="form-control<?= invalid('telefono') ?>" <?= aria_error('telefono') ?>
                           value="<?= e(old('telefono')) ?>" maxlength="20" required inputmode="tel" autocomplete="tel">
                    <?= field_error('telefono') ?: '<div class="invalid-feedback">Escribe tu número de celular.</div>' ?>
                </div>
                <div class="col-sm-7">
                    <label for="email" class="form-label">Correo electrónico <span class="req" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="form-control<?= invalid('email') ?>" <?= aria_error('email') ?>
                           value="<?= e(old('email')) ?>" maxlength="150" required autocomplete="email" aria-describedby="email-ayuda">
                    <div class="form-text" id="email-ayuda">Será tu usuario para iniciar sesión.</div>
                    <?= field_error('email') ?: '<div class="invalid-feedback">Escribe un correo válido.</div>' ?>
                </div>
                <div class="col-sm-6">
                    <label for="password" class="form-label">Contraseña <span class="req" aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control<?= invalid('password') ?>" <?= aria_error('password') ?>
                               minlength="8" maxlength="64" required autocomplete="new-password" pattern="(?=.*[A-Za-z])(?=.*\d).{8,64}">
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="password"
                                aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        <?= field_error('password') ?: '<div class="invalid-feedback">Entre 8 y 64 caracteres, con letras y números.</div>' ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label for="password_confirm" class="form-label">Confirmar contraseña <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control<?= invalid('password_confirm') ?>"
                           <?= aria_error('password_confirm') ?> maxlength="64" required autocomplete="new-password" data-match="password">
                    <?= field_error('password_confirm') ?: '<div class="invalid-feedback">Las contraseñas no coinciden.</div>' ?>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input<?= invalid('acepta_datos') ?>" type="checkbox" id="acepta_datos" name="acepta_datos" value="1"
                               <?= aria_error('acepta_datos') ?> required <?= old_checked('acepta_datos') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="acepta_datos">
                            Autorizo a Torque a tratar mis datos personales para gestionar los servicios de mis vehículos,
                            conforme a la Ley 1581 de 2012. <span class="req" aria-hidden="true">*</span>
                        </label>
                        <?= field_error('acepta_datos') ?: '<div class="invalid-feedback">Debes aceptar para crear la cuenta.</div>' ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mt-4" data-loading-text="Creando cuenta…">Crear cuenta</button>
        </form>

        <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="<?= e(url('/login')) ?>">Inicia sesión</a></p>
    </div>
</section>
