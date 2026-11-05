<?php /** @var array $vehiculo @var array $ordenes */
$id = (int) $vehiculo['id'];
$activo = (int) $vehiculo['activo'] === 1;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/vehiculos')) ?>">Vehículos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($vehiculo['placa']) ?></li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap"><?= placa($vehiculo['placa']) ?>
            <?= e($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?> <?= activo_badge($vehiculo['activo']) ?></h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($activo && App\Core\Auth::hasRole('asesor')): ?>
            <a href="<?= e(url('/ordenes/crear', ['placa' => $vehiculo['placa']])) ?>" class="btn btn-primary">
                <i class="bi bi-clipboard-plus" aria-hidden="true"></i> Nueva orden</a>
        <?php endif; ?>
        <a href="<?= e(url("/vehiculos/$id/editar")) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
        <form method="post" action="<?= e(url("/vehiculos/$id/estado")) ?>"
              data-confirm="<?= e($activo ? '¿Desactivar este vehículo?' : '¿Activar este vehículo?') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                <i class="bi bi-<?= $activo ? 'slash-circle' : 'check-circle' ?>" aria-hidden="true"></i> <?= $activo ? 'Desactivar' : 'Activar' ?>
            </button>
        </form>
    </div>
</header>

<div class="row g-3">
    <section class="col-lg-4" aria-labelledby="t-datos">
        <div class="panel h-100">
            <h2 id="t-datos" class="panel-title">Datos del vehículo</h2>
            <dl class="ficha">
                <dt>Propietario</dt>
                <dd><a href="<?= e(url('/clientes/' . (int) $vehiculo['cliente_id'])) ?>"><?= e($vehiculo['cliente']) ?></a>
                    <span class="d-block small text-secondary"><?= e($vehiculo['cliente_telefono']) ?></span></dd>
                <dt>Año</dt><dd><?= (int) $vehiculo['anio'] ?></dd>
                <dt>Color</dt><dd><?= e($vehiculo['color'] ?: '—') ?></dd>
                <dt>VIN</dt><dd class="text-break"><?= e($vehiculo['vin'] ?: '—') ?></dd>
                <dt>Registrado</dt><dd><?= e(fecha($vehiculo['created_at'], 'd/m/Y')) ?></dd>
            </dl>
        </div>
    </section>
    <section class="col-lg-8" aria-labelledby="t-ordenes">
        <div class="panel h-100">
            <h2 id="t-ordenes" class="panel-title">Historial de servicio</h2>
            <?= App\Core\View::partial('ordenes_tabla', [
                'ordenes' => $ordenes, 'verCliente' => false, 'vacio' => 'Este vehículo aún no ha ingresado al taller.',
            ]) ?>
        </div>
    </section>
</div>
