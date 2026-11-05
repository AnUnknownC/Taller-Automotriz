<?php
/**
 * @var array $usuario @var int $sinTecnico @var int $pendientes @var int $listas
 * @var array $recientes @var array $repuestosBajos
 */
?>
<header class="page-head">
    <div>
        <h1>Recepción y seguimiento</h1>
        <p class="text-secondary mb-0">Lo que requiere tu atención hoy.</p>
    </div>
    <nav class="shortcuts" aria-label="Accesos directos">
        <a href="<?= e(url('/ordenes/crear')) ?>" class="btn btn-primary"><i class="bi bi-clipboard-plus" aria-hidden="true"></i> Nueva orden</a>
        <a href="<?= e(url('/clientes/crear')) ?>" class="btn btn-outline-secondary"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo cliente</a>
        <a href="<?= e(url('/vehiculos/crear')) ?>" class="btn btn-outline-secondary"><i class="bi bi-car-front" aria-hidden="true"></i> Nuevo vehículo</a>
    </nav>
</header>

<section class="kpis" aria-label="Indicadores">
    <div class="kpi<?= $sinTecnico > 0 ? ' kpi-alerta' : '' ?>">
        <span class="kpi-label">Recibidas sin técnico</span>
        <span class="kpi-value"><?= (int) $sinTecnico ?></span>
    </div>
    <div class="kpi">
        <span class="kpi-label">Cotizaciones esperando al cliente</span>
        <span class="kpi-value"><?= (int) $pendientes ?></span>
    </div>
    <div class="kpi">
        <span class="kpi-label">Listas para entrega</span>
        <span class="kpi-value"><?= (int) $listas ?></span>
    </div>
</section>

<div class="row g-3">
    <section class="col-xl-8" aria-labelledby="t-recientes">
        <div class="panel">
            <h2 id="t-recientes" class="panel-title">Órdenes recientes</h2>
            <?= App\Core\View::partial('ordenes_tabla', [
                'ordenes' => $recientes,
                'vacio'   => 'Todavía no hay órdenes de trabajo registradas.',
            ]) ?>
        </div>
    </section>
    <section class="col-xl-4" aria-labelledby="t-stock">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                <h2 id="t-stock" class="panel-title mb-0">Repuestos en stock mínimo</h2>
                <a href="<?= e(url('/repuestos', ['bajo' => 1])) ?>" class="small">Ver todos</a>
            </div>
            <?php if (!$repuestosBajos): ?>
                <div class="empty-state"><i class="bi bi-box-seam" aria-hidden="true"></i><p>Todo el inventario está por encima del mínimo.</p></div>
            <?php else: ?>
                <ul class="lista-simple">
                    <?php foreach ($repuestosBajos as $r): ?>
                        <li>
                            <span><a href="<?= e(url('/repuestos/' . (int) $r['id'])) ?>"><?= e($r['nombre']) ?></a> <span class="text-secondary small"><?= e($r['codigo']) ?></span></span>
                            <span class="stock-bajo"><?= (int) $r['stock'] ?> / mín. <?= (int) $r['stock_minimo'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
