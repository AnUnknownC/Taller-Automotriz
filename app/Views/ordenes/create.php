<?php
/** @var string $placa @var array|null $vehiculo @var array|null $abierta @var int $ultimoKm */
$puedeRecibir = $vehiculo && (int) $vehiculo['activo'] === 1 && (int) $vehiculo['cliente_activo'] === 1 && !$abierta;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/ordenes')) ?>">Órdenes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nueva</li>
        </ol></nav>
        <h1>Recepción de vehículo</h1>
    </div>
</header>

<div class="panel form-panel">
    <form method="get" action="<?= e(url('/ordenes/crear')) ?>" role="search" aria-label="Buscar vehículo por placa">
        <label for="placa-buscar" class="form-label fw-bold">1. Placa del vehículo</label>
        <div class="input-group input-group-lg buscar-placa">
            <input type="text" id="placa-buscar" name="placa" class="form-control text-uppercase placa-input"
                   value="<?= e($placa) ?>" maxlength="7" placeholder="ABC123" autocomplete="off" required <?= $vehiculo ? '' : 'autofocus' ?>>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
        </div>
    </form>

    <?php if ($placa !== '' && !$vehiculo): ?>
        <div class="alert alert-warning mt-3 mb-0 d-flex gap-2 align-items-start" role="status">
            <i class="bi bi-question-circle" aria-hidden="true"></i>
            <div>No hay ningún vehículo con la placa <strong><?= e($placa) ?></strong>.
                <a href="<?= e(url('/vehiculos/crear')) ?>">Regístralo primero</a> y vuelve a esta pantalla.</div>
        </div>
    <?php elseif ($vehiculo): ?>
        <div class="vehiculo-hallado mt-3">
            <?= placa($vehiculo['placa']) ?>
            <div>
                <strong><?= e($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></strong>
                <span class="text-secondary"><?= (int) $vehiculo['anio'] ?><?= $vehiculo['color'] ? ' · ' . e($vehiculo['color']) : '' ?></span>
                <span class="d-block">Propietario:
                    <a href="<?= e(url('/clientes/' . (int) $vehiculo['cliente_id'])) ?>"><?= e($vehiculo['cliente']) ?></a>
                    · <?= e($vehiculo['cliente_telefono']) ?></span>
            </div>
        </div>
        <?php if ($abierta): ?>
            <div class="alert alert-danger mt-3 mb-0 d-flex gap-2" role="alert">
                <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
                <div>Este vehículo ya tiene la orden
                    <a href="<?= e(url('/ordenes/' . (int) $abierta['id'])) ?>" class="alert-link"><?= e($abierta['codigo']) ?></a>
                    abierta (<?= e(App\Models\OrdenTrabajo::ESTADOS[$abierta['estado']]) ?>). No se puede abrir otra hasta entregarlo.</div>
            </div>
        <?php elseif ((int) $vehiculo['activo'] !== 1 || (int) $vehiculo['cliente_activo'] !== 1): ?>
            <div class="alert alert-danger mt-3 mb-0" role="alert">El vehículo o su propietario están inactivos.
                <a href="<?= e(url('/vehiculos/' . (int) $vehiculo['id'])) ?>" class="alert-link">Ver vehículo</a></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($puedeRecibir): ?>
        <form method="post" action="<?= e(url('/ordenes')) ?>" class="needs-validation mt-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="vehiculo_id" value="<?= (int) $vehiculo['id'] ?>">
            <input type="hidden" name="placa" value="<?= e($vehiculo['placa']) ?>">
            <p class="form-label fw-bold mb-2">2. Datos del ingreso</p>
            <div class="row g-3">
                <div class="col-12">
                    <label for="motivo_ingreso" class="form-label">Motivo de ingreso (lo que reporta el cliente) <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="motivo_ingreso" name="motivo_ingreso" rows="3" class="form-control<?= invalid('motivo_ingreso') ?>"
                              <?= aria_error('motivo_ingreso') ?> minlength="5" maxlength="1000" required autofocus><?= e(old('motivo_ingreso')) ?></textarea>
                    <?= field_error('motivo_ingreso') ?: '<div class="invalid-feedback">Describe el motivo (mínimo 5 caracteres).</div>' ?>
                </div>
                <div class="col-sm-6">
                    <label for="kilometraje_ingreso" class="form-label">Kilometraje <span class="req" aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input type="number" id="kilometraje_ingreso" name="kilometraje_ingreso" class="form-control<?= invalid('kilometraje_ingreso') ?>"
                               <?= aria_error('kilometraje_ingreso') ?> value="<?= e(old('kilometraje_ingreso')) ?>"
                               min="<?= (int) $ultimoKm ?>" max="2000000" required aria-describedby="km-ayuda">
                        <span class="input-group-text">km</span>
                    </div>
                    <div class="form-text" id="km-ayuda"><?= $ultimoKm > 0 ? 'Último ingreso: ' . number_format($ultimoKm, 0, ',', '.') . ' km.' : 'Primer ingreso de este vehículo.' ?></div>
                    <?= field_error('kilometraje_ingreso') ?: '<div class="invalid-feedback">No puede ser menor al último registrado.</div>' ?>
                </div>
                <div class="col-sm-6">
                    <label for="fecha_estimada" class="form-label">Fecha estimada de entrega</label>
                    <input type="date" id="fecha_estimada" name="fecha_estimada" class="form-control<?= invalid('fecha_estimada') ?>"
                           <?= aria_error('fecha_estimada') ?> value="<?= e(old('fecha_estimada')) ?>" min="<?= date('Y-m-d') ?>">
                    <?= field_error('fecha_estimada') ?: '<div class="invalid-feedback">No puede ser anterior a hoy.</div>' ?>
                </div>
            </div>
            <div class="form-actions">
                <a href="<?= e(url('/ordenes')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" data-loading-text="Registrando…">Registrar ingreso</button>
            </div>
        </form>
    <?php endif; ?>
</div>
