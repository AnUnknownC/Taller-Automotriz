<?php
/** @var array $res @var App\Core\ListParams $p @var array $tecnicos @var array $conteo */
$f = $p->filters;
$abiertas = array_sum($conteo) - $conteo['entregada'];
$chips = ['abiertas' => ['Abiertas', $abiertas]];
foreach (App\Models\OrdenTrabajo::ESTADOS as $k => $l) {
    $chips[$k] = [$l, $conteo[$k]];
}
?>
<header class="page-head">
    <div>
        <h1>Órdenes de trabajo</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> orden(es) con estos filtros.</p>
    </div>
    <?php if (App\Core\Auth::hasRole('asesor')): ?>
        <a href="<?= e(url('/ordenes/crear')) ?>" class="btn btn-primary"><i class="bi bi-clipboard-plus" aria-hidden="true"></i> Nueva orden</a>
    <?php endif; ?>
</header>

<nav class="estado-chips" aria-label="Filtrar por estado">
    <?php foreach ($chips as $k => [$label, $n]): $activo = $f['estado'] === $k; ?>
        <a href="<?= e(url('/ordenes', $p->query(['estado' => $k, 'pagina' => null]))) ?>"
           class="estado-chip<?= $activo ? ' active' : '' ?>" <?= $activo ? 'aria-current="true"' : '' ?>>
            <?= e($label) ?> <span class="estado-chip-n"><?= (int) $n ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('/ordenes')) ?>" class="panel filtros" role="search" aria-label="Filtrar órdenes">
    <input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>">
    <input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-lg-4">
            <label for="q" class="form-label">Buscar código, placa o cliente</label>
            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($p->q) ?>">
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="tecnico" class="form-label">Técnico</label>
            <select id="tecnico" name="tecnico" class="form-select">
                <option value="">Todos</option>
                <option value="-1" <?= $f['tecnico'] === '-1' ? 'selected' : '' ?>>Sin asignar</option>
                <?php foreach ($tecnicos as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= $f['tecnico'] === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label for="desde" class="form-label">Ingreso desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?= e($f['desde']) ?>">
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($f['hasta']) ?>">
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
            <a href="<?= e(url('/ordenes')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de órdenes">
    <?php if (!$res['rows']): ?>
        <div class="empty-state"><i class="bi bi-clipboard" aria-hidden="true"></i><p>No hay órdenes con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead>
                <tr>
                    <th scope="col" <?= aria_sort('codigo', $p) ?>><?= sort_link('Orden', 'codigo', $p, '/ordenes') ?></th>
                    <th scope="col" <?= aria_sort('placa', $p) ?>><?= sort_link('Vehículo', 'placa', $p, '/ordenes') ?></th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Técnico</th>
                    <th scope="col" <?= aria_sort('estado', $p) ?>><?= sort_link('Estado', 'estado', $p, '/ordenes') ?></th>
                    <th scope="col" <?= aria_sort('ingreso', $p) ?>><?= sort_link('Ingreso', 'ingreso', $p, '/ordenes') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($res['rows'] as $o):
                    $vencida = $o['fecha_estimada'] && $o['fecha_estimada'] < date('Y-m-d') && !in_array($o['estado'], ['entregada', 'rechazada'], true); ?>
                    <tr>
                        <td data-label="Orden"><a class="fw-semibold" href="<?= e(url('/ordenes/' . (int) $o['id'])) ?>"><?= e($o['codigo']) ?></a></td>
                        <td data-label="Vehículo"><?= placa($o['placa']) ?> <span class="text-secondary"><?= e($o['marca'] . ' ' . $o['modelo']) ?></span></td>
                        <td data-label="Cliente"><?= e($o['cliente']) ?></td>
                        <td data-label="Técnico"><?= $o['tecnico'] ? e($o['tecnico']) : '<span class="text-secondary">Sin asignar</span>' ?></td>
                        <td data-label="Estado"><?= estado_badge($o['estado']) ?></td>
                        <td data-label="Ingreso" class="text-nowrap"><?= e(fecha($o['fecha_ingreso'], 'd/m/Y')) ?>
                            <?php if ($vencida): ?><span class="d-block small text-danger"><i class="bi bi-clock-history" aria-hidden="true"></i> Vencida <?= e(fecha($o['fecha_estimada'], 'd/m')) ?></span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', [
            'pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/ordenes', 'query' => $p->query(['pagina' => null]),
        ]) ?>
    <?php endif; ?>
</section>
