<?php /** @var array $usuario @var array $asignadas */ ?>
<header class="page-head">
    <div>
        <h1>Mis órdenes asignadas</h1>
        <p class="text-secondary mb-0">Solo ves los vehículos que el asesor te asignó.</p>
    </div>
</header>

<section class="kpis" aria-label="Indicadores">
    <div class="kpi">
        <span class="kpi-label">Órdenes activas a tu cargo</span>
        <span class="kpi-value"><?= count($asignadas) ?></span>
    </div>
</section>

<section class="panel" aria-labelledby="t-asignadas">
    <h2 id="t-asignadas" class="panel-title">En curso (más antigua primero)</h2>
    <?= App\Core\View::partial('ordenes_tabla', [
        'ordenes'    => $asignadas,
        'verTecnico' => false,
        'vacio'      => 'No tienes órdenes asignadas en este momento.',
    ]) ?>
</section>
