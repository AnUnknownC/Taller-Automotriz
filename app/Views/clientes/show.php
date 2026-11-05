<?php /** @var array $cliente @var array $vehiculos @var array $ordenes */
$id = (int) $cliente['id'];
$activo = (int) $cliente['activo'] === 1;
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= e(url('/clientes')) ?>">Clientes</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($cliente['nombre']) ?></li>
        </ol></nav>
        <h1><?= e($cliente['nombre']) ?> <?= activo_badge($cliente['activo']) ?></h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(url("/clientes/$id/editar")) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
        <form method="post" action="<?= e(url("/clientes/$id/estado")) ?>"
              data-confirm="<?= e($activo ? '¿Desactivar este cliente? Sus vehículos quedarán inactivos y, si tiene acceso al portal, se bloqueará.' : '¿Activar este cliente?') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                <i class="bi bi-<?= $activo ? 'person-slash' : 'person-check' ?>" aria-hidden="true"></i> <?= $activo ? 'Desactivar' : 'Activar' ?>
            </button>
        </form>
    </div>
</header>

<div class="row g-3">
    <section class="col-lg-4" aria-labelledby="t-datos">
        <div class="panel h-100">
            <h2 id="t-datos" class="panel-title">Datos</h2>
            <dl class="ficha">
                <dt>Documento</dt><dd><?= e($cliente['tipo_documento'] . ' ' . $cliente['documento']) ?></dd>
                <dt>Teléfono</dt><dd><?= e($cliente['telefono']) ?></dd>
                <dt>Correo</dt><dd class="text-break"><?= e($cliente['email'] ?: '—') ?></dd>
                <dt>Dirección</dt><dd><?= e($cliente['direccion'] ?: '—') ?></dd>
                <dt>Portal</dt>
                <dd><?php if ($cliente['usuario_id']): ?>
                        <i class="bi bi-key" aria-hidden="true"></i> <?= (int) $cliente['acceso_activo'] === 1 ? 'Con acceso' : 'Acceso bloqueado' ?>
                    <?php else: ?>Sin acceso<?php endif; ?></dd>
                <dt>Registrado</dt><dd><?= e(fecha($cliente['created_at'], 'd/m/Y')) ?></dd>
            </dl>
        </div>
    </section>

    <section class="col-lg-8" aria-labelledby="t-vehiculos">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                <h2 id="t-vehiculos" class="panel-title mb-0">Vehículos (<?= count($vehiculos) ?>)</h2>
                <?php if ($activo): ?>
                    <a href="<?= e(url('/vehiculos/crear', ['cliente' => $id])) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar vehículo</a>
                <?php endif; ?>
            </div>
            <?php if (!$vehiculos): ?>
                <div class="empty-state"><i class="bi bi-car-front" aria-hidden="true"></i><p>Este cliente aún no tiene vehículos.</p></div>
            <?php else: ?>
                <ul class="vehiculos">
                    <?php foreach ($vehiculos as $v): ?>
                        <li>
                            <a class="vehiculo vehiculo-link<?= (int) $v['activo'] === 1 ? '' : ' inactivo' ?>" href="<?= e(url('/vehiculos/' . (int) $v['id'])) ?>">
                                <?= placa($v['placa']) ?>
                                <span><strong><?= e($v['marca'] . ' ' . $v['modelo']) ?></strong>
                                    <span class="text-secondary"><?= (int) $v['anio'] ?><?= (int) $v['activo'] === 1 ? '' : ' · inactivo' ?></span></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="col-12" aria-labelledby="t-ordenes">
        <div class="panel">
            <h2 id="t-ordenes" class="panel-title">Historial de órdenes</h2>
            <?= App\Core\View::partial('ordenes_tabla', [
                'ordenes' => $ordenes, 'verCliente' => false, 'vacio' => 'Este cliente aún no tiene órdenes de trabajo.',
            ]) ?>
        </div>
    </section>
</div>
