<?php
/**
 * @var array $usuario @var int $usuariosActivos @var array $usuariosPorRol @var int $ordenesAbiertas
 * @var array $porEstado @var int $bajoStock @var array $auditoria
 */
$maxEstado = max(1, max($porEstado));
?>
<header class="page-head">
    <div>
        <h1>Panel de administración</h1>
        <p class="text-secondary mb-0">Estado general del taller.</p>
    </div>
    <nav class="shortcuts" aria-label="Accesos directos">
        <a href="<?= e(url('/usuarios/crear')) ?>" class="btn btn-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario</a>
        <a href="<?= e(url('/reportes')) ?>" class="btn btn-outline-secondary"><i class="bi bi-graph-up" aria-hidden="true"></i> Reportes</a>
    </nav>
</header>

<section class="kpis" aria-label="Indicadores">
    <div class="kpi">
        <span class="kpi-label">Usuarios activos</span>
        <span class="kpi-value"><?= (int) $usuariosActivos ?></span>
    </div>
    <div class="kpi">
        <span class="kpi-label">Órdenes abiertas</span>
        <span class="kpi-value"><?= (int) $ordenesAbiertas ?></span>
    </div>
    <a class="kpi kpi-link" href="<?= e(url('/reportes/ingresos')) ?>">
        <span class="kpi-label">Ingresos últimos 30 días (simulados)</span>
        <span class="kpi-value"><?= pesos($ingresos30['total']) ?></span>
        <span class="small text-secondary"><?= (int) $ingresos30['liquidaciones'] ?> entrega(s) · ver reporte</span>
    </a>
    <a class="kpi kpi-link<?= $bajoStock > 0 ? ' kpi-alerta' : '' ?>" href="<?= e(url('/repuestos', ['bajo' => 1])) ?>">
        <span class="kpi-label">Repuestos en stock mínimo</span>
        <span class="kpi-value"><?= (int) $bajoStock ?></span>
    </a>
</section>

<div class="row g-3">
    <section class="col-lg-7" aria-labelledby="t-estados">
        <div class="panel h-100">
            <h2 id="t-estados" class="panel-title">Órdenes por estado</h2>
            <ul class="barras">
                <?php foreach (App\Models\OrdenTrabajo::ESTADOS as $clave => $label): ?>
                    <li>
                        <span class="barras-label"><?= e($label) ?></span>
                        <span class="barras-track" aria-hidden="true">
                            <span class="barras-fill estado-fill-<?= e($clave) ?>"
                                  style="width: <?= round($porEstado[$clave] / $maxEstado * 100) ?>%"></span>
                        </span>
                        <span class="barras-num"><?= (int) $porEstado[$clave] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="col-lg-5" aria-labelledby="t-roles">
        <div class="panel h-100">
            <h2 id="t-roles" class="panel-title">Usuarios activos por rol</h2>
            <ul class="lista-simple">
                <?php foreach ($usuariosPorRol as $rol => $total): ?>
                    <li><span class="role-chip role-<?= e($rol) ?>"><?= e(rol_label($rol)) ?></span>
                        <strong><?= (int) $total ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="col-12" aria-labelledby="t-actividad">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 id="t-actividad" class="panel-title mb-0">Actividad reciente</h2>
                <a href="<?= e(url('/auditoria')) ?>" class="small">Ver auditoría completa</a>
            </div>
            <?php if (!$auditoria): ?>
                <div class="empty-state"><i class="bi bi-journal" aria-hidden="true"></i><p>Aún no hay actividad registrada.</p></div>
            <?php else: ?>
                <ul class="timeline">
                    <?php foreach ($auditoria as $a): ?>
                        <li>
                            <span class="timeline-when"><?= e(fecha($a['created_at'])) ?></span>
                            <span><strong><?= e($a['usuario'] ?? 'Sistema') ?></strong>
                                · <?= e(str_replace('_', ' ', $a['accion'])) ?>
                                <span class="text-secondary">(<?= e($a['entidad']) ?>)</span></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
