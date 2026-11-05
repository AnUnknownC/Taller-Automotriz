<?php /** @var array $res @var App\Core\ListParams $p */ ?>
<header class="page-head">
    <div>
        <h1>Clientes</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> cliente(s) con estos filtros.</p>
    </div>
    <a href="<?= e(url('/clientes/crear')) ?>" class="btn btn-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo cliente</a>
</header>

<form method="get" action="<?= e(url('/clientes')) ?>" class="panel filtros" role="search" aria-label="Filtrar clientes">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>">
    <input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-7">
            <label for="q" class="form-label">Buscar por nombre, documento, teléfono o correo</label>
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
            <a href="<?= e(url('/clientes')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de clientes">
    <?php if (!$res['rows']): ?>
        <div class="empty-state">
            <i class="bi bi-people" aria-hidden="true"></i>
            <p>No hay clientes con esos filtros.</p>
            <a href="<?= e(url('/clientes/crear')) ?>" class="btn btn-sm btn-outline-secondary mt-2">Registrar un cliente</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead>
                <tr>
                    <th scope="col" <?= aria_sort('nombre', $p) ?>><?= sort_link('Cliente', 'nombre', $p, '/clientes') ?></th>
                    <th scope="col" <?= aria_sort('documento', $p) ?>><?= sort_link('Documento', 'documento', $p, '/clientes') ?></th>
                    <th scope="col">Contacto</th>
                    <th scope="col" <?= aria_sort('vehiculos', $p) ?>><?= sort_link('Vehículos', 'vehiculos', $p, '/clientes') ?></th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($res['rows'] as $c): ?>
                    <tr>
                        <td data-label="Cliente">
                            <a href="<?= e(url('/clientes/' . (int) $c['id'])) ?>" class="fw-semibold"><?= e($c['nombre']) ?></a>
                            <?php if ($c['usuario_id']): ?><span class="tag" title="Tiene acceso al portal"><i class="bi bi-key" aria-hidden="true"></i> portal</span><?php endif; ?>
                        </td>
                        <td data-label="Documento" class="text-nowrap"><?= e($c['tipo_documento'] . ' ' . $c['documento']) ?></td>
                        <td data-label="Contacto"><?= e($c['telefono']) ?>
                            <?php if ($c['email']): ?><span class="d-block small text-secondary text-break"><?= e($c['email']) ?></span><?php endif; ?></td>
                        <td data-label="Vehículos"><?= (int) $c['vehiculos'] ?></td>
                        <td data-label="Estado"><?= activo_badge($c['activo']) ?></td>
                        <td data-label="Acciones" class="acciones">
                            <a href="<?= e(url('/clientes/' . (int) $c['id'])) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye" aria-hidden="true"></i> Ver</a>
                            <a href="<?= e(url('/clientes/' . (int) $c['id'] . '/editar')) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', [
            'pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/clientes', 'query' => $p->query(['pagina' => null]),
        ]) ?>
    <?php endif; ?>
</section>
