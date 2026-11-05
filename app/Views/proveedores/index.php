<?php /** @var array $res @var App\Core\ListParams $p @var bool $puedeEditar */ ?>
<header class="page-head">
    <div>
        <h1>Proveedores</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> proveedor(es) con estos filtros.</p>
    </div>
    <div class="shortcuts">
        <a href="<?= e(url('/inventario/entrada')) ?>" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Entrada de mercancía</a>
        <?php if ($puedeEditar): ?>
            <a href="<?= e(url('/proveedores/crear')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo proveedor</a>
        <?php endif; ?>
    </div>
</header>

<form method="get" action="<?= e(url('/proveedores')) ?>" class="panel filtros" role="search" aria-label="Filtrar proveedores">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>"><input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-7">
            <label for="q" class="form-label">Buscar por nombre, NIT o correo</label>
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
            <a href="<?= e(url('/proveedores')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de proveedores">
    <?php if (!$res['rows']): ?>
        <div class="empty-state"><i class="bi bi-truck" aria-hidden="true"></i><p>No hay proveedores con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead><tr>
                    <th scope="col" <?= aria_sort('nombre', $p) ?>><?= sort_link('Proveedor', 'nombre', $p, '/proveedores') ?></th>
                    <th scope="col" <?= aria_sort('nit', $p) ?>><?= sort_link('NIT', 'nit', $p, '/proveedores') ?></th>
                    <th scope="col">Contacto</th>
                    <th scope="col" <?= aria_sort('entradas', $p) ?>><?= sort_link('Entradas', 'entradas', $p, '/proveedores') ?></th>
                    <th scope="col" <?= aria_sort('ultima', $p) ?>><?= sort_link('Última', 'ultima', $p, '/proveedores') ?></th>
                    <th scope="col">Estado</th>
                    <?php if ($puedeEditar): ?><th scope="col" class="text-end">Acciones</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($res['rows'] as $pr): $activo = (int) $pr['activo'] === 1; ?>
                    <tr>
                        <td data-label="Proveedor" class="fw-semibold"><?= e($pr['nombre']) ?></td>
                        <td data-label="NIT" class="text-nowrap"><?= e($pr['nit']) ?></td>
                        <td data-label="Contacto"><?= e($pr['telefono'] ?: '—') ?>
                            <?php if ($pr['email']): ?><span class="d-block small text-secondary text-break"><?= e($pr['email']) ?></span><?php endif; ?></td>
                        <td data-label="Entradas"><?= (int) $pr['entradas'] ?></td>
                        <td data-label="Última"><?= e(fecha($pr['ultima'], 'd/m/Y')) ?></td>
                        <td data-label="Estado"><?= activo_badge($pr['activo']) ?></td>
                        <?php if ($puedeEditar): ?>
                            <td data-label="Acciones" class="acciones">
                                <a href="<?= e(url('/proveedores/' . (int) $pr['id'] . '/editar')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                                <form method="post" action="<?= e(url('/proveedores/' . (int) $pr['id'] . '/estado')) ?>" class="d-inline"
                                      data-confirm="<?= e(($activo ? '¿Desactivar a ' : '¿Activar a ') . $pr['nombre'] . '?') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $activo ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', ['pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/proveedores', 'query' => $p->query(['pagina' => null])]) ?>
    <?php endif; ?>
</section>
