<?php
/** @var array $res @var App\Core\ListParams $p */
$yo = App\Core\Auth::id();
?>
<header class="page-head">
    <div>
        <h1>Usuarios</h1>
        <p class="text-secondary mb-0"><?= (int) $res['total'] ?> usuario(s) con estos filtros.</p>
    </div>
    <a href="<?= e(url('/usuarios/crear')) ?>" class="btn btn-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario</a>
</header>

<form method="get" action="<?= e(url('/usuarios')) ?>" class="panel filtros" role="search" aria-label="Filtrar usuarios">
    <input type="hidden" name="sort" value="<?= e($p->sort) ?>">
    <input type="hidden" name="dir" value="<?= e($p->dir) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="q" class="form-label">Buscar por nombre o correo</label>
            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($p->q) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label for="rol" class="form-label">Rol</label>
            <select id="rol" name="rol" class="form-select">
                <option value="">Todos</option>
                <?php foreach (App\Models\Usuario::ROLES as $r): ?>
                    <option value="<?= e($r) ?>" <?= $p->filters['rol'] === $r ? 'selected' : '' ?>><?= e(rol_label($r)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <?php foreach (['activos' => 'Activos', 'inactivos' => 'Inactivos', 'todos' => 'Todos'] as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $p->filters['estado'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
            <a href="<?= e(url('/usuarios')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Listado de usuarios">
    <?php if (!$res['rows']): ?>
        <div class="empty-state"><i class="bi bi-people" aria-hidden="true"></i><p>No hay usuarios con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead>
                <tr>
                    <th scope="col" <?= aria_sort('nombre', $p) ?>><?= sort_link('Nombre', 'nombre', $p, '/usuarios') ?></th>
                    <th scope="col" <?= aria_sort('email', $p) ?>><?= sort_link('Correo', 'email', $p, '/usuarios') ?></th>
                    <th scope="col" <?= aria_sort('rol', $p) ?>><?= sort_link('Rol', 'rol', $p, '/usuarios') ?></th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($res['rows'] as $u): $esYo = (int) $u['id'] === $yo; ?>
                    <tr>
                        <td data-label="Nombre" class="fw-semibold"><?= e($u['nombre']) ?><?= $esYo ? ' <span class="tag">tú</span>' : '' ?></td>
                        <td data-label="Correo" class="text-break"><?= e($u['email']) ?></td>
                        <td data-label="Rol"><span class="role-chip role-<?= e($u['rol']) ?>"><?= e(rol_label($u['rol'])) ?></span></td>
                        <td data-label="Estado"><?= activo_badge($u['activo']) ?></td>
                        <td data-label="Acciones" class="acciones">
                            <a href="<?= e(url('/usuarios/' . (int) $u['id'] . '/editar')) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                            <?php if (!$esYo): $activo = (int) $u['activo'] === 1; ?>
                                <form method="post" action="<?= e(url('/usuarios/' . (int) $u['id'] . '/estado')) ?>" class="d-inline"
                                      data-confirm="<?= e(($activo ? '¿Desactivar a ' : '¿Activar a ') . $u['nombre'] . '?' . ($activo ? ' No podrá iniciar sesión.' : '')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <i class="bi bi-<?= $activo ? 'person-slash' : 'person-check' ?>" aria-hidden="true"></i>
                                        <?= $activo ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', [
            'pagina' => $res['page'], 'paginas' => $res['pages'], 'ruta' => '/usuarios', 'query' => $p->query(['pagina' => null]),
        ]) ?>
    <?php endif; ?>
</section>
