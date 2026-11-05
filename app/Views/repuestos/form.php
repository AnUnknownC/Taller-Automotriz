<?php /** @var array|null $repuesto */
$editar = $repuesto !== null;
$accion = $editar ? url('/repuestos/' . (int) $repuesto['id']) : url('/repuestos');
$volver = $editar ? url('/repuestos/' . (int) $repuesto['id']) : url('/repuestos');
$num = fn ($v) => $v === null || $v === '' ? '' : (string) (float) $v;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/repuestos')) ?>">Inventario</a></li>
            <?php if ($editar): ?><li class="breadcrumb-item"><a href="<?= e($volver) ?>"><?= e($repuesto['codigo']) ?></a></li><?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $editar ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editar ? 'Editar repuesto' : 'Nuevo repuesto' ?></h1>
    </div>
</header>
<form method="post" action="<?= e($accion) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="codigo" class="form-label">Código interno <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="codigo" name="codigo" class="form-control text-uppercase placa-input<?= invalid('codigo') ?>" <?= aria_error('codigo') ?>
                   value="<?= e(old('codigo', $repuesto['codigo'] ?? '')) ?>" maxlength="30" required pattern="[A-Za-z0-9][A-Za-z0-9\- ]{2,29}" placeholder="FRN-PAST-DEL">
            <?= field_error('codigo') ?: '<div class="invalid-feedback">3 a 30 letras, números o guiones.</div>' ?>
        </div>
        <div class="col-md-5">
            <label for="nombre" class="form-label">Nombre <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control<?= invalid('nombre') ?>" <?= aria_error('nombre') ?>
                   value="<?= e(old('nombre', $repuesto['nombre'] ?? '')) ?>" minlength="3" maxlength="120" required>
            <?= field_error('nombre') ?: '<div class="invalid-feedback">Mínimo 3 caracteres.</div>' ?>
        </div>
        <div class="col-md-3">
            <label for="marca" class="form-label">Marca</label>
            <input type="text" id="marca" name="marca" class="form-control<?= invalid('marca') ?>" <?= aria_error('marca') ?>
                   value="<?= e(old('marca', $repuesto['marca'] ?? '')) ?>" maxlength="50">
            <?= field_error('marca') ?>
        </div>
        <div class="col-6 col-md-4">
            <label for="costo" class="form-label">Costo <span class="req" aria-hidden="true">*</span></label>
            <div class="input-group"><span class="input-group-text">$</span>
                <input type="number" id="costo" name="costo" class="form-control<?= invalid('costo') ?>" <?= aria_error('costo') ?>
                       value="<?= e(old('costo', $num($repuesto['costo'] ?? ''))) ?>" min="1" step="1" required></div>
            <?= field_error('costo') ?>
        </div>
        <div class="col-6 col-md-4">
            <label for="precio_venta" class="form-label">Precio de venta <span class="req" aria-hidden="true">*</span></label>
            <div class="input-group"><span class="input-group-text">$</span>
                <input type="number" id="precio_venta" name="precio_venta" class="form-control<?= invalid('precio_venta') ?>" <?= aria_error('precio_venta') ?>
                       value="<?= e(old('precio_venta', $num($repuesto['precio_venta'] ?? ''))) ?>" min="1" step="1" required></div>
            <?= field_error('precio_venta') ?>
        </div>
        <div class="col-md-4">
            <label for="stock_minimo" class="form-label">Stock mínimo (alerta) <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="stock_minimo" name="stock_minimo" class="form-control<?= invalid('stock_minimo') ?>" <?= aria_error('stock_minimo') ?>
                   value="<?= e(old('stock_minimo', (string) ($repuesto['stock_minimo'] ?? '0'))) ?>" min="0" max="9999" required>
            <?= field_error('stock_minimo') ?>
        </div>
    </div>
    <p class="form-text mt-3 mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i>
        <?= $editar ? 'El stock (' . (int) $repuesto['stock'] . ') no se edita aquí: usa una entrada de mercancía o un ajuste, para que quede en el kardex.'
                    : 'El repuesto se crea con stock 0. Luego registra una entrada de mercancía.' ?></p>
    <div class="form-actions">
        <a href="<?= e($volver) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $editar ? 'Guardar cambios' : 'Crear repuesto' ?></button>
    </div>
</form>
