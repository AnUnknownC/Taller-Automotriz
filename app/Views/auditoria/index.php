<?php
/**
 * @var array $filas @var int $total @var array $acciones @var array $filtros
 * @var int $pagina @var int $paginas
 */
?>
<header class="page-head">
    <div>
        <h1>Auditoría</h1>
        <p class="text-secondary mb-0">Quién hizo qué y cuándo. <?= (int) $total ?> registro(s).</p>
    </div>
</header>

<form method="get" action="<?= e(url('/auditoria')) ?>" class="panel filtros" role="search" aria-label="Filtrar auditoría">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="q" class="form-label">Buscar usuario, correo o IP</label>
            <input type="search" id="q" name="q" class="form-control" maxlength="100" value="<?= e($filtros['q']) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label for="accion" class="form-label">Acción</label>
            <select id="accion" name="accion" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($acciones as $a): ?>
                    <option value="<?= e($a) ?>" <?= $filtros['accion'] === $a ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $a)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="orden" class="form-label">Orden</label>
            <select id="orden" name="orden" class="form-select">
                <option value="desc" <?= $filtros['orden'] === 'desc' ? 'selected' : '' ?>>Más recientes</option>
                <option value="asc" <?= $filtros['orden'] === 'asc' ? 'selected' : '' ?>>Más antiguos</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
            <a href="<?= e(url('/auditoria')) ?>" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </div>
</form>

<section class="panel" aria-label="Resultados">
    <?php if (!$filas): ?>
        <div class="empty-state"><i class="bi bi-search" aria-hidden="true"></i><p>No hay registros con esos filtros.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-ordenes align-middle mb-0">
                <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Acción</th>
                    <th scope="col">Entidad</th>
                    <th scope="col">Detalle</th>
                    <th scope="col">IP</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td data-label="Fecha" class="text-nowrap"><?= e(fecha($f['created_at'])) ?></td>
                        <td data-label="Usuario"><?= e($f['usuario'] ?? 'Sistema') ?>
                            <?php if ($f['email']): ?><span class="d-block small text-secondary"><?= e($f['email']) ?></span><?php endif; ?></td>
                        <td data-label="Acción"><span class="tag"><?= e(str_replace('_', ' ', $f['accion'])) ?></span></td>
                        <td data-label="Entidad"><?= e($f['entidad']) ?><?= $f['entidad_id'] ? ' #' . (int) $f['entidad_id'] : '' ?></td>
                        <td data-label="Detalle" class="small text-break"><?= e($f['datos_despues'] ?? '—') ?></td>
                        <td data-label="IP" class="small"><?= e($f['ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('pagination', [
            'pagina' => $pagina, 'paginas' => $paginas, 'ruta' => '/auditoria', 'query' => $filtros,
        ]) ?>
    <?php endif; ?>
</section>
