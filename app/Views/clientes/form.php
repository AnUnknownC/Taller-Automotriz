<?php
/** @var array|null $cliente (null = crear) */
$editar = $cliente !== null;
$tieneAcceso = $editar && $cliente['usuario_id'];
$tipo = old('tipo_documento', $cliente['tipo_documento'] ?? 'CC');
$crearAcceso = old_checked('crear_acceso');
$accion = $editar ? url('/clientes/' . (int) $cliente['id']) : url('/clientes');
$volver = $editar ? url('/clientes/' . (int) $cliente['id']) : url('/clientes');
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/clientes')) ?>">Clientes</a></li>
            <?php if ($editar): ?><li class="breadcrumb-item"><a href="<?= e($volver) ?>"><?= e($cliente['nombre']) ?></a></li><?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $editar ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editar ? 'Editar cliente' : 'Nuevo cliente' ?></h1>
    </div>
</header>

<form method="post" action="<?= e($accion) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <fieldset>
        <legend class="fs-6 fw-bold">Identificación</legend>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="tipo_documento" class="form-label">Tipo de documento <span class="req" aria-hidden="true">*</span></label>
                <select id="tipo_documento" name="tipo_documento" class="form-select<?= invalid('tipo_documento') ?>" <?= aria_error('tipo_documento') ?> required>
                    <?php foreach (App\Models\Cliente::TIPOS_DOCUMENTO as $k => $l): ?>
                        <option value="<?= e($k) ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error('tipo_documento') ?>
            </div>
            <div class="col-md-4">
                <label for="documento" class="form-label">Número <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="documento" name="documento" class="form-control<?= invalid('documento') ?>" <?= aria_error('documento') ?>
                       value="<?= e(old('documento', $cliente['documento'] ?? '')) ?>" maxlength="20" required>
                <?= field_error('documento') ?: '<div class="invalid-feedback">El documento es obligatorio.</div>' ?>
            </div>
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre o razón social <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control<?= invalid('nombre') ?>" <?= aria_error('nombre') ?>
                       value="<?= e(old('nombre', $cliente['nombre'] ?? '')) ?>" minlength="3" maxlength="120" required>
                <?= field_error('nombre') ?: '<div class="invalid-feedback">Mínimo 3 caracteres.</div>' ?>
            </div>
        </div>
    </fieldset>

    <fieldset class="mt-4">
        <legend class="fs-6 fw-bold">Contacto</legend>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono <span class="req" aria-hidden="true">*</span></label>
                <input type="tel" id="telefono" name="telefono" class="form-control<?= invalid('telefono') ?>" <?= aria_error('telefono') ?>
                       value="<?= e(old('telefono', $cliente['telefono'] ?? '')) ?>" maxlength="20" inputmode="tel" required>
                <?= field_error('telefono') ?: '<div class="invalid-feedback">El teléfono es obligatorio.</div>' ?>
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">Correo<?= $tieneAcceso ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
                <input type="email" id="email" name="email" class="form-control<?= invalid('email') ?>" <?= aria_error('email') ?>
                       value="<?= e(old('email', $cliente['email'] ?? '')) ?>" maxlength="150" <?= $tieneAcceso ? 'required' : '' ?>>
                <?= field_error('email') ?: '<div class="invalid-feedback">Escribe un correo válido.</div>' ?>
                <?php if ($tieneAcceso): ?><div class="form-text">Es también su usuario para ingresar al portal.</div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" id="direccion" name="direccion" class="form-control<?= invalid('direccion') ?>" <?= aria_error('direccion') ?>
                       value="<?= e(old('direccion', $cliente['direccion'] ?? '')) ?>" maxlength="200">
                <?= field_error('direccion') ?>
            </div>
        </div>
    </fieldset>

    <fieldset class="mt-4">
        <legend class="fs-6 fw-bold">Acceso al portal de clientes</legend>
        <?php if ($tieneAcceso): ?>
            <p class="mb-0"><i class="bi bi-key" aria-hidden="true"></i> Ya tiene acceso con <strong><?= e($cliente['acceso_email']) ?></strong>
                (<?= (int) $cliente['acceso_activo'] === 1 ? 'activo' : 'bloqueado' ?>).</p>
        <?php else: ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="crear_acceso" name="crear_acceso" value="1"
                       data-toggle-section="seccion-acceso" data-toggle-required="email" <?= $crearAcceso ? 'checked' : '' ?>>
                <label class="form-check-label" for="crear_acceso">Crear cuenta para que consulte sus vehículos y apruebe cotizaciones</label>
            </div>
            <div id="seccion-acceso" <?= $crearAcceso ? '' : 'hidden' ?>>
                <p class="form-text mt-0 mb-2">Ingresará con el correo de contacto. Comunícale la contraseña en persona.</p>
                <div class="row g-3">
                <div class="col-md-6">
                    <label for="acceso_password" class="form-label">Contraseña inicial <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="acceso_password" name="acceso_password" class="form-control<?= invalid('acceso_password') ?>"
                           <?= aria_error('acceso_password') ?> minlength="8" maxlength="64" autocomplete="new-password"
                           pattern="(?=.*[A-Za-z])(?=.*\d).{8,64}">
                    <?= field_error('acceso_password') ?: '<div class="invalid-feedback">Entre 8 y 64 caracteres, con letras y números.</div>' ?>
                </div>
                <div class="col-md-6">
                    <label for="acceso_password_confirm" class="form-label">Confirmar contraseña <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="acceso_password_confirm" name="acceso_password_confirm"
                           class="form-control<?= invalid('acceso_password_confirm') ?>" <?= aria_error('acceso_password_confirm') ?>
                           maxlength="64" autocomplete="new-password" data-match="acceso_password">
                    <?= field_error('acceso_password_confirm') ?: '<div class="invalid-feedback">Las contraseñas no coinciden.</div>' ?>
                </div>
                </div>
            </div>
        <?php endif; ?>
    </fieldset>

    <div class="form-actions">
        <a href="<?= e($volver) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $editar ? 'Guardar cambios' : 'Registrar cliente' ?></button>
    </div>
</form>
