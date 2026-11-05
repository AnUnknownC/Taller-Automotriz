<?php
/** @var array|null $usuario (null = crear) */
$editar = $usuario !== null;
$esYo = $editar && (int) $usuario['id'] === App\Core\Auth::id();
$esCliente = $editar && $usuario['rol'] === 'cliente';
$rolBloqueado = $esYo || $esCliente;
$rolActual = old('rol', $usuario['rol'] ?? '');
$accion = $editar ? url('/usuarios/' . (int) $usuario['id']) : url('/usuarios');
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/usuarios')) ?>">Usuarios</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editar ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editar ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
    </div>
</header>

<form method="post" action="<?= e($accion) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="nombre" class="form-label">Nombre completo <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control<?= invalid('nombre') ?>" <?= aria_error('nombre') ?>
                   value="<?= e(old('nombre', $usuario['nombre'] ?? '')) ?>" minlength="3" maxlength="100" required>
            <?= field_error('nombre') ?: '<div class="invalid-feedback">Mínimo 3 caracteres.</div>' ?>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Correo electrónico <span class="req" aria-hidden="true">*</span></label>
            <input type="email" id="email" name="email" class="form-control<?= invalid('email') ?>" <?= aria_error('email') ?>
                   value="<?= e(old('email', $usuario['email'] ?? '')) ?>" maxlength="150" autocomplete="off" required>
            <?= field_error('email') ?: '<div class="invalid-feedback">Escribe un correo válido.</div>' ?>
        </div>
        <div class="col-md-6">
            <label for="rol" class="form-label">Rol <span class="req" aria-hidden="true">*</span></label>
            <select id="rol" name="rol" class="form-select<?= invalid('rol') ?>" <?= aria_error('rol') ?>
                    <?= $rolBloqueado ? 'disabled aria-describedby="rol-ayuda"' : 'required' ?>>
                <?php if (!$editar): ?><option value="">Selecciona…</option><?php endif; ?>
                <?php foreach ($esCliente ? ['cliente'] : App\Models\Usuario::ROLES_INTERNOS as $r): ?>
                    <option value="<?= e($r) ?>" <?= $rolActual === $r ? 'selected' : '' ?>><?= e(rol_label($r)) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error('rol') ?: '<div class="invalid-feedback">Selecciona un rol.</div>' ?>
            <?php if ($esYo): ?>
                <div class="form-text" id="rol-ayuda">No puedes cambiar tu propio rol.</div>
            <?php elseif ($esCliente): ?>
                <div class="form-text" id="rol-ayuda">Las cuentas de cliente se gestionan desde el módulo Clientes.</div>
            <?php else: ?>
                <div class="form-text">Para dar acceso a un cliente, regístralo desde <a href="<?= e(url('/clientes/crear')) ?>">Clientes</a>.</div>
            <?php endif; ?>
        </div>
    </div>

    <fieldset class="mt-4">
        <legend class="fs-6 fw-bold"><?= $editar ? 'Cambiar contraseña' : 'Contraseña' ?></legend>
        <?php if ($editar): ?><p class="form-text mt-0">Déjala en blanco para conservar la actual.</p><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="password" class="form-label">Contraseña<?= $editar ? '' : ' <span class="req" aria-hidden="true">*</span>' ?></label>
                <input type="password" id="password" name="password" class="form-control<?= invalid('password') ?>" <?= aria_error('password') ?>
                       minlength="8" maxlength="64" autocomplete="new-password" <?= $editar ? '' : 'required' ?>
                       pattern="(?=.*[A-Za-z])(?=.*\d).{8,64}">
                <?= field_error('password') ?: '<div class="invalid-feedback">Entre 8 y 64 caracteres, con letras y números.</div>' ?>
            </div>
            <div class="col-md-6">
                <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control<?= invalid('password_confirm') ?>"
                       <?= aria_error('password_confirm') ?> maxlength="64" autocomplete="new-password" data-match="password">
                <?= field_error('password_confirm') ?: '<div class="invalid-feedback">Las contraseñas no coinciden.</div>' ?>
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="<?= e(url('/usuarios')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $editar ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </div>
</form>
