<?php
/**
 * Tabla reutilizable de órdenes. En móvil se apila como tarjetas (data-label).
 * @var array $ordenes @var string $vacio
 * @var bool $verCliente @var bool $verTecnico
 */
$verCliente = $verCliente ?? true;
$verTecnico = $verTecnico ?? true;
?>
<?php if (!$ordenes): ?>
    <div class="empty-state">
        <i class="bi bi-clipboard-check" aria-hidden="true"></i>
        <p><?= e($vacio) ?></p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-ordenes align-middle mb-0">
            <thead>
            <tr>
                <th scope="col">Orden</th>
                <th scope="col">Vehículo</th>
                <?php if ($verCliente): ?><th scope="col">Cliente</th><?php endif; ?>
                <?php if ($verTecnico): ?><th scope="col">Técnico</th><?php endif; ?>
                <th scope="col">Estado</th>
                <th scope="col">Ingreso</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ordenes as $o): ?>
                <tr>
                    <td data-label="Orden"><a class="fw-semibold" href="<?= e(url('/ordenes/' . (int) $o['id'])) ?>"><?= e($o['codigo']) ?></a></td>
                    <td data-label="Vehículo"><?= placa($o['placa']) ?> <span class="text-secondary"><?= e($o['marca'] . ' ' . $o['modelo']) ?></span></td>
                    <?php if ($verCliente): ?><td data-label="Cliente"><?= e($o['cliente']) ?></td><?php endif; ?>
                    <?php if ($verTecnico): ?><td data-label="Técnico"><?= $o['tecnico'] ? e($o['tecnico']) : '<span class="text-secondary">Sin asignar</span>' ?></td><?php endif; ?>
                    <td data-label="Estado"><?= estado_badge($o['estado']) ?></td>
                    <td data-label="Ingreso"><?= e(fecha($o['fecha_ingreso'], 'd/m/Y')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
