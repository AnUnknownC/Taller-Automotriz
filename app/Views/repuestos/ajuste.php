<?php /** @var array $repuesto */ $id = (int) $repuesto['id']; $sentido = old('sentido', 'restar'); ?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/repuestos')) ?>">Inventario</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url("/repuestos/$id")) ?>"><?= e($repuesto['codigo']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajuste</li>
        </ol></nav>
        <h1>Ajuste de inventario</h1>
        <p class="text-secondary mb-0"><?= e($repuesto['nombre']) ?> · stock actual <strong><?= (int) $repuesto['stock'] ?></strong></p>
    </div>
</header>
<form method="post" action="<?= e(url("/repuestos/$id/ajuste")) ?>" class="panel form-panel needs-validation" novalidate
      data-confirm="¿Registrar este ajuste? Quedará en el kardex y en la auditoría.">
    <?= csrf_field() ?>
    <p class="form-text mt-0">Úsalo solo para corregir diferencias (conteo físico, daño, pérdida). Las compras se registran como entrada de mercancía.</p>
    <div class="row g-3">
        <fieldset class="col-md-5">
            <legend class="form-label fs-6">Tipo de ajuste <span class="req" aria-hidden="true">*</span></legend>
            <div class="metodos">
                <input type="radio" class="btn-check" name="sentido" id="restar" value="restar" <?= $sentido === 'restar' ? 'checked' : '' ?> required>
                <label class="btn btn-outline-secondary" for="restar"><i class="bi bi-dash-lg" aria-hidden="true"></i> Restar</label>
                <input type="radio" class="btn-check" name="sentido" id="sumar" value="sumar" <?= $sentido === 'sumar' ? 'checked' : '' ?> required>
                <label class="btn btn-outline-secondary" for="sumar"><i class="bi bi-plus-lg" aria-hidden="true"></i> Sumar</label>
            </div>
        </fieldset>
        <div class="col-md-3">
            <label for="cantidad" class="form-label">Cantidad <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="cantidad" name="cantidad" class="form-control<?= invalid('cantidad') ?>" <?= aria_error('cantidad') ?>
                   value="<?= e(old('cantidad')) ?>" min="1" max="9999" required>
            <?= field_error('cantidad') ?: '<div class="invalid-feedback">Entre 1 y 9.999.</div>' ?>
        </div>
        <div class="col-12">
            <label for="motivo" class="form-label">Motivo <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="motivo" name="motivo" class="form-control<?= invalid('motivo') ?>" <?= aria_error('motivo') ?>
                   value="<?= e(old('motivo')) ?>" minlength="5" maxlength="200" required placeholder="Ej.: Conteo físico del 05/11: 1 unidad dañada">
            <?= field_error('motivo') ?: '<div class="invalid-feedback">Explica el motivo (mínimo 5 caracteres).</div>' ?>
        </div>
    </div>
    <div class="form-actions">
        <a href="<?= e(url("/repuestos/$id")) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Registrar ajuste</button>
    </div>
</form>
