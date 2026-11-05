<?php /** @var array $repuestos @var array $proveedores @var int $repuestoId */
$sel = (int) old('repuesto_id', (string) ($repuestoId ?: ''));
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/repuestos')) ?>">Inventario</a></li>
            <li class="breadcrumb-item active" aria-current="page">Entrada de mercancía</li>
        </ol></nav>
        <h1>Entrada de mercancía</h1>
        <p class="text-secondary mb-0">Repuestos que llegan de un proveedor. El stock aumenta y queda registrado en el kardex.</p>
    </div>
</header>
<?php if (!$proveedores): ?>
    <div class="panel empty-state"><i class="bi bi-truck" aria-hidden="true"></i><p>No hay proveedores activos. El administrador debe registrar uno.</p></div>
<?php else: ?>
<form method="post" action="<?= e(url('/inventario/entrada')) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-7">
            <label for="repuesto_id" class="form-label">Repuesto <span class="req" aria-hidden="true">*</span></label>
            <select id="repuesto_id" name="repuesto_id" class="form-select<?= invalid('repuesto_id') ?>" <?= aria_error('repuesto_id') ?> required>
                <option value="">Selecciona…</option>
                <?php foreach ($repuestos as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= $sel === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?> (<?= e($r['codigo']) ?>) · stock <?= (int) $r['stock'] ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error('repuesto_id') ?: '<div class="invalid-feedback">Selecciona el repuesto.</div>' ?>
        </div>
        <div class="col-md-5">
            <label for="proveedor_id" class="form-label">Proveedor <span class="req" aria-hidden="true">*</span></label>
            <select id="proveedor_id" name="proveedor_id" class="form-select<?= invalid('proveedor_id') ?>" <?= aria_error('proveedor_id') ?> required>
                <option value="">Selecciona…</option>
                <?php foreach ($proveedores as $pr): ?>
                    <option value="<?= (int) $pr['id'] ?>" <?= old('proveedor_id') === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error('proveedor_id') ?: '<div class="invalid-feedback">Selecciona el proveedor.</div>' ?>
        </div>
        <div class="col-6 col-md-3">
            <label for="cantidad" class="form-label">Cantidad <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="cantidad" name="cantidad" class="form-control<?= invalid('cantidad') ?>" <?= aria_error('cantidad') ?>
                   value="<?= e(old('cantidad')) ?>" min="1" max="9999" required>
            <?= field_error('cantidad') ?: '<div class="invalid-feedback">Entre 1 y 9.999.</div>' ?>
        </div>
        <div class="col-6 col-md-4">
            <label for="costo_unitario" class="form-label">Costo unitario</label>
            <div class="input-group"><span class="input-group-text">$</span>
                <input type="number" id="costo_unitario" name="costo_unitario" class="form-control<?= invalid('costo_unitario') ?>" <?= aria_error('costo_unitario') ?>
                       value="<?= e(old('costo_unitario')) ?>" min="1" step="1" aria-describedby="costo-ayuda"></div>
            <div class="form-text" id="costo-ayuda">Opcional. Actualiza el último costo del repuesto.</div>
            <?= field_error('costo_unitario') ?>
        </div>
        <div class="col-md-5">
            <label for="nota" class="form-label">Factura / nota</label>
            <input type="text" id="nota" name="nota" class="form-control<?= invalid('nota') ?>" <?= aria_error('nota') ?>
                   value="<?= e(old('nota')) ?>" maxlength="200" placeholder="Ej.: Factura FB-2231">
            <?= field_error('nota') ?>
        </div>
    </div>
    <div class="form-actions">
        <a href="<?= e(url('/repuestos')) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Registrando…">Registrar entrada</button>
    </div>
</form>
<?php endif; ?>
