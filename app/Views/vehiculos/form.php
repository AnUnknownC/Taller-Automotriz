<?php
/** @var array|null $vehiculo @var array $clientes @var int $clienteId */
$editar = $vehiculo !== null;
$clienteSel = (int) old('cliente_id', (string) ($vehiculo['cliente_id'] ?? ($clienteId ?: '')));
$accion = $editar ? url('/vehiculos/' . (int) $vehiculo['id']) : url('/vehiculos');
$volver = $editar ? url('/vehiculos/' . (int) $vehiculo['id']) : ($clienteId ? url('/clientes/' . $clienteId) : url('/vehiculos'));
$anioMax = (int) date('Y') + 1;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/vehiculos')) ?>">Vehículos</a></li>
            <?php if ($editar): ?><li class="breadcrumb-item"><a href="<?= e($volver) ?>"><?= e($vehiculo['placa']) ?></a></li><?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $editar ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
        <h1><?= $editar ? 'Editar vehículo' : 'Nuevo vehículo' ?></h1>
    </div>
</header>

<?php if (!$clientes): ?>
    <div class="panel empty-state">
        <i class="bi bi-people" aria-hidden="true"></i>
        <p>Primero registra un cliente activo para asociarle el vehículo.</p>
        <a href="<?= e(url('/clientes/crear')) ?>" class="btn btn-sm btn-primary mt-2">Registrar cliente</a>
    </div>
<?php else: ?>
<form method="post" action="<?= e($accion) ?>" class="panel form-panel needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label for="cliente_id" class="form-label">Propietario <span class="req" aria-hidden="true">*</span></label>
            <select id="cliente_id" name="cliente_id" class="form-select<?= invalid('cliente_id') ?>" <?= aria_error('cliente_id') ?> required>
                <option value="">Selecciona un cliente…</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $clienteSel === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?><?= $c['documento'] ? ' — ' . e($c['tipo_documento'] . ' ' . $c['documento']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= field_error('cliente_id') ?: '<div class="invalid-feedback">Selecciona el propietario.</div>' ?>
        </div>
        <div class="col-md-4">
            <label for="placa" class="form-label">Placa <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="placa" name="placa" class="form-control text-uppercase placa-input<?= invalid('placa') ?>" <?= aria_error('placa') ?>
                   value="<?= e(old('placa', $vehiculo['placa'] ?? '')) ?>" maxlength="7" required
                   pattern="[A-Za-z]{3}[\s\-]?[0-9]{2}[0-9A-Za-z]" aria-describedby="placa-ayuda" autocomplete="off">
            <div class="form-text" id="placa-ayuda">ABC123 (carro) o ABC12D (moto).</div>
            <?= field_error('placa') ?: '<div class="invalid-feedback">Formato: ABC123 o ABC12D.</div>' ?>
        </div>
        <div class="col-md-4">
            <label for="marca" class="form-label">Marca <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="marca" name="marca" class="form-control<?= invalid('marca') ?>" <?= aria_error('marca') ?>
                   value="<?= e(old('marca', $vehiculo['marca'] ?? '')) ?>" maxlength="50" required list="marcas">
            <datalist id="marcas">
                <?php foreach (['Chevrolet', 'Renault', 'Mazda', 'Kia', 'Toyota', 'Nissan', 'Hyundai', 'Volkswagen', 'Ford', 'Suzuki'] as $m): ?>
                    <option value="<?= $m ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <?= field_error('marca') ?: '<div class="invalid-feedback">La marca es obligatoria.</div>' ?>
        </div>
        <div class="col-md-4">
            <label for="modelo" class="form-label">Modelo / línea <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="modelo" name="modelo" class="form-control<?= invalid('modelo') ?>" <?= aria_error('modelo') ?>
                   value="<?= e(old('modelo', $vehiculo['modelo'] ?? '')) ?>" maxlength="50" required>
            <?= field_error('modelo') ?: '<div class="invalid-feedback">El modelo es obligatorio.</div>' ?>
        </div>
        <div class="col-6 col-md-2">
            <label for="anio" class="form-label">Año <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="anio" name="anio" class="form-control<?= invalid('anio') ?>" <?= aria_error('anio') ?>
                   value="<?= e(old('anio', (string) ($vehiculo['anio'] ?? ''))) ?>" min="1950" max="<?= $anioMax ?>" required>
            <?= field_error('anio') ?: '<div class="invalid-feedback">Entre 1950 y ' . $anioMax . '.</div>' ?>
        </div>
        <div class="col-6 col-md-2">
            <label for="color" class="form-label">Color</label>
            <input type="text" id="color" name="color" class="form-control<?= invalid('color') ?>" <?= aria_error('color') ?>
                   value="<?= e(old('color', $vehiculo['color'] ?? '')) ?>" maxlength="30">
            <?= field_error('color') ?>
        </div>
        <div class="col-md-6">
            <label for="vin" class="form-label">VIN (número de chasis)</label>
            <input type="text" id="vin" name="vin" class="form-control text-uppercase<?= invalid('vin') ?>" <?= aria_error('vin') ?>
                   value="<?= e(old('vin', $vehiculo['vin'] ?? '')) ?>" maxlength="17" minlength="17" autocomplete="off">
            <?= field_error('vin') ?: '<div class="invalid-feedback">Debe tener 17 caracteres.</div>' ?>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= e($volver) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $editar ? 'Guardar cambios' : 'Registrar vehículo' ?></button>
    </div>
</form>
<?php endif; ?>
