<?php /** @var array $usuario @var array $vehiculos @var array $ordenes @var array $pendientes */ ?>
<header class="page-head">
    <div>
        <h1>Hola, <?= e($usuario['nombre']) ?></h1>
        <p class="text-secondary mb-0">Consulta el estado de tus vehículos en el taller.</p>
    </div>
</header>

<?php foreach ($pendientes as $pend): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-center flex-wrap" role="status">
        <i class="bi bi-clipboard-check" aria-hidden="true"></i>
        <div class="flex-grow-1">La cotización de tu vehículo <strong><?= e($pend['placa']) ?></strong> (<?= e($pend['codigo']) ?>) espera tu aprobación.</div>
        <a href="<?= e(url('/ordenes/' . (int) $pend['id'])) ?>" class="btn btn-sm btn-dark">Revisar y responder</a>
    </div>
<?php endforeach; ?>

<section class="panel mb-3" aria-labelledby="t-vehiculos">
    <h2 id="t-vehiculos" class="panel-title">Mis vehículos</h2>
    <?php if (!$vehiculos): ?>
        <div class="empty-state"><i class="bi bi-car-front" aria-hidden="true"></i><p>No tienes vehículos registrados en el taller.</p></div>
    <?php else: ?>
        <ul class="vehiculos">
            <?php foreach ($vehiculos as $v): ?>
                <li class="vehiculo">
                    <?= placa($v['placa']) ?>
                    <span><strong><?= e($v['marca'] . ' ' . $v['modelo']) ?></strong>
                        <span class="text-secondary"><?= (int) $v['anio'] ?><?= $v['color'] ? ' · ' . e($v['color']) : '' ?></span></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel" aria-labelledby="t-ordenes">
    <h2 id="t-ordenes" class="panel-title">Mis órdenes de trabajo</h2>
    <?= App\Core\View::partial('ordenes_tabla', [
        'ordenes'    => $ordenes,
        'verCliente' => false,
        'vacio'      => 'Aún no tienes órdenes de trabajo.',
    ]) ?>
</section>
