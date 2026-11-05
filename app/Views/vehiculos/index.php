<?php /** @var array $res @var App\Core\ListParams $p */ ?>
<header class="page-head">
    <div>
        <h1>Vehículos</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> vehículo(s) con estos filtros.</p>
    </div>
    <a href="<?= e(url('/vehiculos/crear')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo vehículo</a>
</header>

<form method="get" action="<?= e(url('/vehiculos')) ?>" class="panel filtros" role="search" aria-label="Filtrar vehículos">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>">
    <input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-7">
            <label for="q" class="form-label">Buscar por placa, marca, modelo o cliente</label>
            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($p->q) ?>">
        </div>
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <?php foreach (['activos' => 'Activos', 'inactivos' => 'Inactivos', 'todos' => 'Todos'] as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $p->filters['estado'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
            <a href="<?= e(url('/vehiculos')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de vehículos">
    <?php if (!$res['rows']): ?>
        <div class="empty-state"><i class="bi bi-car-front" aria-hidden="true"></i><p>No hay vehículos con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead>
                <tr>
                    <th scope="col" <?= aria_sort('placa', $p) ?>><?= sort_link('Placa', 'placa', $p, '/vehiculos') ?></th>
                    <th scope="col" <?= aria_sort('marca', $p) ?>><?= sort_link('Vehículo', 'marca', $p, '/vehiculos') ?></th>
                    <th scope="col" <?= aria_sort('anio', $p) ?>><?= sort_link('Año', 'anio', $p, '/vehiculos') ?></th>
                    <th scope="col" <?= aria_sort('cliente', $p) ?>><?= sort_link('Propietario', 'cliente', $p, '/vehiculos') ?></th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($res['rows'] as $v): ?>
                    <tr>
                        <td data-label="Placa"><a href="<?= e(url('/vehiculos/' . (int) $v['id'])) ?>" aria-label="Ver vehículo <?= e($v['placa']) ?>"><?= placa($v['placa']) ?></a></td>
                        <td data-label="Vehículo"><?= e($v['marca'] . ' ' . $v['modelo']) ?><?= $v['color'] ? ' <span class="text-secondary">· ' . e($v['color']) . '</span>' : '' ?></td>
                        <td data-label="Año"><?= (int) $v['anio'] ?></td>
                        <td data-label="Propietario"><a href="<?= e(url('/clientes/' . (int) $v['cliente_id'])) ?>"><?= e($v['cliente']) ?></a></td>
                        <td data-label="Estado"><?= activo_badge($v['activo']) ?></td>
                        <td data-label="Acciones" class="acciones">
                            <a href="<?= e(url('/vehiculos/' . (int) $v['id'] . '/editar')) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', [
            'pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/vehiculos', 'query' => $p->query(['pagina' => null]),
        ]) ?>
    <?php endif; ?>
</section>
