<?php /** @var array|null $proveedor */
$editar = $proveedor !== null;
$accion = $editar ? url('/proveedores/' . (int) $proveedor['id']) : url('/proveedores');
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/proveedores')) ?>">Proveedores</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $editar ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editar ? 'Editar proveedor' : 'Nuevo proveedor' ?></h1>
    </div>
</header>
<form method="post" action="<?= e($accion) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="nit" class="form-label">NIT <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="nit" name="nit" class="form-control<?= invalid('nit') ?>" <?= aria_error('nit') ?>
                   value="<?= e(old('nit', $proveedor['nit'] ?? '')) ?>" maxlength="20" required placeholder="900123456-7">
            <?= field_error('nit') ?: '<div class="invalid-feedback">El NIT es obligatorio.</div>' ?>
        </div>
        <div class="col-md-8">
            <label for="nombre" class="form-label">Razón social <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control<?= invalid('nombre') ?>" <?= aria_error('nombre') ?>
                   value="<?= e(old('nombre', $proveedor['nombre'] ?? '')) ?>" minlength="3" maxlength="120" required>
            <?= field_error('nombre') ?: '<div class="invalid-feedback">Mínimo 3 caracteres.</div>' ?>
        </div>
        <div class="col-md-4">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" class="form-control<?= invalid('telefono') ?>" <?= aria_error('telefono') ?>
                   value="<?= e(old('telefono', $proveedor['telefono'] ?? '')) ?>" maxlength="20" inputmode="tel">
            <?= field_error('telefono') ?>
        </div>
        <div class="col-md-8">
            <label for="email" class="form-label">Correo</label>
            <input type="email" id="email" name="email" class="form-control<?= invalid('email') ?>" <?= aria_error('email') ?>
                   value="<?= e(old('email', $proveedor['email'] ?? '')) ?>" maxlength="150">
            <?= field_error('email') ?: '<div class="invalid-feedback">Escribe un correo válido.</div>' ?>
        </div>
    </div>
    <div class="form-actions">
        <a href="<?= e(url('/proveedores')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $editar ? 'Guardar cambios' : 'Registrar proveedor' ?></button>
    </div>
</form>
