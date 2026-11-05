<?php
/**
 * Ficha de la orden: una sola vista para los 4 roles; cada bloque se adapta al rol y al estado.
 * @var array $orden @var array $usuario @var array|null $diagnostico @var array|null $cotizacion
 * @var array $items @var array $totales @var array $historial @var string[] $acciones @var array $tecnicos
 * @var array|null $liquidacion
 */
use App\Models\OrdenTrabajo as OT;

$id = (int) $orden['id'];
$rol = $usuario['rol'];
$esPersonal = in_array($rol, ['administrador', 'asesor'], true);
$estado = $orden['estado'];

// Barra de progreso: si se rechazó, el camino es recibida → diagnóstico → cotizada → rechazada → entregada
$pasos = $estado === OT::RECHAZADA || ($estado === OT::ENTREGADA && $cotizacion && $cotizacion['estado'] === 'rechazada')
    ? [OT::RECIBIDA, OT::DIAGNOSTICO, OT::COTIZADA, OT::RECHAZADA, OT::ENTREGADA]
    : OT::PASOS;
$actual = array_search($estado, $pasos, true);
$cortos = [OT::RECIBIDA => 'Recibida', OT::DIAGNOSTICO => 'Diagnóstico', OT::COTIZADA => 'Cotizada', OT::APROBADA => 'Aprobada',
           OT::RECHAZADA => 'Rechazada', OT::EN_REPARACION => 'Reparación', OT::LISTA => 'Lista', OT::ENTREGADA => 'Entregada'];
$estadoCot = ['pendiente' => 'Esperando respuesta del cliente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'];
?>
<header class="page-head">
    <div>
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb mb-1">
            <?php if ($esPersonal): ?>
                <li class="breadcrumb-item"><a href="<?= e(url('/ordenes')) ?>">Órdenes</a></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= e(url('/dashboard')) ?>">Inicio</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e($orden['codigo']) ?></li>
        </ol></nav>
        <h1 class="d-flex align-items-center gap-2 flex-wrap">
            <?= e($orden['codigo']) ?> <?= placa($orden['placa']) ?>
            <span class="fs-5 fw-normal"><?= e($orden['marca'] . ' ' . $orden['modelo']) ?></span>
            <?= estado_badge($estado) ?>
        </h1>
    </div>
</header>

<ol class="stepper" aria-label="Progreso de la orden">
    <?php foreach ($pasos as $i => $paso):
        $clase = $i < $actual ? 'hecho' : ($i === $actual ? 'actual' : '');
        if ($paso === OT::RECHAZADA) { $clase .= ' rechazo'; } ?>
        <li class="<?= trim($clase) ?>" <?= $i === $actual ? 'aria-current="step"' : '' ?>>
            <span class="stepper-dot" aria-hidden="true"><?= $i < $actual ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?></span>
            <span class="stepper-label"><?= e($cortos[$paso]) ?></span>
        </li>
    <?php endforeach; ?>
</ol>

<div class="row g-3">
    <!-- Columna izquierda: datos del ingreso y acciones -->
    <div class="col-lg-5 d-flex flex-column gap-3">
        <?php if (array_intersect($acciones, ['asignar', 'reasignar', 'cotizar', 'iniciar', 'terminar', 'liquidar'])): ?>
            <section class="panel panel-accion" aria-labelledby="t-acciones">
                <h2 id="t-acciones" class="panel-title"><i class="bi bi-lightning-charge" aria-hidden="true"></i> Siguiente paso</h2>

                <?php if (array_intersect($acciones, ['asignar', 'reasignar'])): $primera = in_array('asignar', $acciones, true); ?>
                    <form method="post" action="<?= e(url("/ordenes/$id/asignar")) ?>" class="needs-validation mb-2" novalidate>
                        <?= csrf_field() ?>
                        <label for="tecnico_id" class="form-label"><?= $primera ? 'Asignar técnico para el diagnóstico' : 'Cambiar técnico asignado' ?></label>
                        <div class="input-group">
                            <select id="tecnico_id" name="tecnico_id" class="form-select" required>
                                <option value="">Selecciona…</option>
                                <?php foreach ($tecnicos as $t): if ((int) $t['id'] === (int) $orden['tecnico_id']) { continue; } ?>
                                    <option value="<?= (int) $t['id'] ?>"><?= e($t['nombre']) ?> · <?= (int) $t['carga'] ?> en curso</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn <?= $primera ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $primera ? 'Asignar' : 'Cambiar' ?></button>
                        </div>
                        <?php if (!$tecnicos): ?><div class="form-text text-danger">No hay técnicos activos. Créalos en Usuarios.</div><?php endif; ?>
                    </form>
                <?php endif; ?>

                <?php if (in_array('cotizar', $acciones, true)): ?>
                    <a href="<?= e(url("/ordenes/$id/cotizacion")) ?>" class="btn btn-primary w-100">
                        <i class="bi bi-receipt" aria-hidden="true"></i> <?= $cotizacion ? 'Corregir cotización' : 'Armar cotización' ?>
                    </a>
                <?php elseif ($rol === 'asesor' && $estado === OT::DIAGNOSTICO && !$diagnostico): ?>
                    <p class="form-text mb-0"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Esperando el diagnóstico de <?= e($orden['tecnico']) ?> para poder cotizar.</p>
                <?php endif; ?>

                <?php if (in_array('iniciar', $acciones, true)): ?>
                    <p class="mb-2">El cliente aprobó la cotización. Al iniciar se retiran de bodega los repuestos cotizados.</p>
                    <form method="post" action="<?= e(url("/ordenes/$id/iniciar")) ?>" data-confirm="¿Iniciar la reparación? Se descontarán los repuestos del inventario.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i> Iniciar reparación</button>
                    </form>
                <?php endif; ?>

                <?php if (in_array('terminar', $acciones, true)): ?>
                    <form method="post" action="<?= e(url("/ordenes/$id/terminar")) ?>" data-confirm="¿Marcar el vehículo como listo para entrega?">
                        <?= csrf_field() ?>
                        <label for="nota" class="form-label">Nota para el asesor (opcional)</label>
                        <input type="text" id="nota" name="nota" class="form-control mb-2" maxlength="255" placeholder="Ej.: se recomienda alineación en 5.000 km">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i> Marcar como lista</button>
                    </form>
                <?php endif; ?>

                <?php if (in_array('liquidar', $acciones, true)): ?>
                    <p class="mb-2"><?= $estado === OT::RECHAZADA
                        ? 'El cliente rechazó la cotización. Cobra solo el diagnóstico y entrega el vehículo.'
                        : 'El vehículo está listo. Registra la liquidación y entrégalo.' ?></p>
                    <a href="<?= e(url("/ordenes/$id/liquidar")) ?>" class="btn btn-primary w-100">
                        <i class="bi bi-cash-coin" aria-hidden="true"></i> Liquidar y entregar</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="panel" aria-labelledby="t-ingreso">
            <h2 id="t-ingreso" class="panel-title">Ingreso</h2>
            <dl class="ficha">
                <dt>Cliente</dt>
                <dd><?php if ($esPersonal): ?><a href="<?= e(url('/clientes/' . (int) $orden['cliente_id'])) ?>"><?= e($orden['cliente']) ?></a><?php else: ?><?= e($orden['cliente']) ?><?php endif; ?>
                    <span class="d-block small text-secondary"><?= e($orden['cliente_telefono']) ?></span></dd>
                <dt>Vehículo</dt><dd><?= e($orden['marca'] . ' ' . $orden['modelo'] . ' ' . $orden['anio']) ?><?= $orden['color'] ? ' · ' . e($orden['color']) : '' ?></dd>
                <dt>Kilometraje</dt><dd><?= number_format((int) $orden['kilometraje_ingreso'], 0, ',', '.') ?> km</dd>
                <dt>Motivo</dt><dd class="texto-largo"><?= e($orden['motivo_ingreso']) ?></dd>
                <dt>Ingresó</dt><dd><?= e(fecha($orden['fecha_ingreso'])) ?></dd>
                <dt>Entrega est.</dt><dd><?= e(fecha($orden['fecha_estimada'], 'd/m/Y')) ?></dd>
                <dt>Asesor</dt><dd><?= e($orden['asesor']) ?></dd>
                <dt>Técnico</dt><dd><?= $orden['tecnico'] ? e($orden['tecnico']) : '<span class="text-secondary">Sin asignar</span>' ?></dd>
            </dl>
        </section>
    </div>

    <!-- Columna derecha: diagnóstico y cotización (en celular va primero si el técnico debe diagnosticar) -->
    <div class="col-lg-7 d-flex flex-column gap-3<?= in_array('diagnosticar', $acciones, true) ? ' order-first order-lg-0' : '' ?>">
        <section class="panel" aria-labelledby="t-diag">
            <h2 id="t-diag" class="panel-title">Diagnóstico</h2>
            <?php if (in_array('diagnosticar', $acciones, true)): ?>
                <form method="post" action="<?= e(url("/ordenes/$id/diagnostico")) ?>" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <label for="descripcion" class="form-label">Hallazgos y trabajo recomendado <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="5" class="form-control<?= invalid('descripcion') ?>" <?= aria_error('descripcion') ?>
                              minlength="10" maxlength="2000" required><?= e(old('descripcion', $diagnostico['descripcion'] ?? '')) ?></textarea>
                    <?= field_error('descripcion') ?: '<div class="invalid-feedback">Describe el diagnóstico (mínimo 10 caracteres).</div>' ?>
                    <div class="d-flex justify-content-between align-items-center mt-2 gap-2 flex-wrap">
                        <span class="form-text m-0"><?= $diagnostico ? 'Puedes corregirlo hasta que el asesor envíe la cotización.' : 'El asesor cotizará con base en esto.' ?></span>
                        <button type="submit" class="btn btn-primary" data-loading-text="Guardando…"><?= $diagnostico ? 'Actualizar diagnóstico' : 'Guardar diagnóstico' ?></button>
                    </div>
                </form>
            <?php elseif ($diagnostico): ?>
                <p class="texto-largo mb-1"><?= e($diagnostico['descripcion']) ?></p>
                <p class="small text-secondary mb-0"><?= e($diagnostico['tecnico']) ?> · <?= e(fecha($diagnostico['created_at'])) ?></p>
            <?php else: ?>
                <div class="empty-state py-3"><i class="bi bi-search" aria-hidden="true"></i>
                    <p><?= $orden['tecnico'] ? 'Pendiente: ' . e($orden['tecnico']) . ' aún no registra el diagnóstico.' : 'Se registrará cuando se asigne un técnico.' ?></p></div>
            <?php endif; ?>
        </section>

        <section class="panel" aria-labelledby="t-cot">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                <h2 id="t-cot" class="panel-title mb-0">Cotización</h2>
                <?php if ($cotizacion): ?>
                    <span class="estado estado-<?= $cotizacion['estado'] === 'pendiente' ? 'cotizada' : ($cotizacion['estado'] === 'aprobada' ? 'aprobada' : 'rechazada') ?>">
                        <?= e($estadoCot[$cotizacion['estado']]) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!$items): ?>
                <div class="empty-state py-3"><i class="bi bi-receipt" aria-hidden="true"></i><p>Aún no hay cotización.</p></div>
            <?php else: ?>
                <?= App\Core\View::partial('cotizacion_items', ['items' => $items, 'totales' => $totales, 'verStock' => $esPersonal || $rol === 'tecnico']) ?>
                <p class="small text-secondary mt-2 mb-0">Elaborada por <?= e($cotizacion['elaborada_por_nombre']) ?> · <?= e(fecha($cotizacion['created_at'])) ?>.
                    Valores con IVA simulado; no es una factura.</p>
                <?php if ($cotizacion['estado'] !== 'pendiente' && $cotizacion['respondida_en']): ?>
                    <p class="small mt-2 mb-0 <?= $cotizacion['estado'] === 'rechazada' ? 'text-danger' : 'text-success' ?>">
                        <i class="bi bi-<?= $cotizacion['estado'] === 'rechazada' ? 'x-circle' : 'check-circle' ?>" aria-hidden="true"></i>
                        <?= $cotizacion['estado'] === 'rechazada' ? 'Rechazada' : 'Aprobada' ?> el <?= e(fecha($cotizacion['respondida_en'])) ?>
                        <?= $cotizacion['canal_respuesta'] === 'portal' ? 'por el cliente desde el portal'
                            : '· registrada por ' . e($cotizacion['respondida_por_nombre']) . ($cotizacion['canal_respuesta'] === 'telefono' ? ' (teléfono)' : ' (en persona)') ?>
                        <?php if ($cotizacion['motivo_rechazo']): ?>· Motivo: <?= e($cotizacion['motivo_rechazo']) ?><?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if (in_array('responder', $acciones, true)): ?>
                    <?= App\Core\View::partial('respuesta_cotizacion', ['id' => $id, 'esAsesor' => $rol === 'asesor', 'total' => $totales['total']]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($liquidacion): ?>
        <section class="col-12" aria-labelledby="t-liq">
            <div class="panel panel-liquidacion">
                <h2 id="t-liq" class="panel-title"><i class="bi bi-cash-coin" aria-hidden="true"></i> Liquidación y entrega <span class="tag">simulada</span></h2>
                <dl class="ficha ficha-liq">
                    <dt>Mano de obra</dt><dd><?= pesos($liquidacion['subtotal_mano_obra']) ?></dd>
                    <dt>Repuestos</dt><dd><?= pesos($liquidacion['subtotal_repuestos']) ?></dd>
                    <dt>IVA</dt><dd><?= pesos($liquidacion['impuestos']) ?></dd>
                    <dt>Total</dt><dd class="fw-bold fs-5"><?= pesos($liquidacion['total']) ?></dd>
                    <dt>Método</dt><dd><?= e(App\Models\Liquidacion::METODOS[$liquidacion['metodo_simulado']]) ?> (simulado)</dd>
                    <dt>Entregado</dt><dd><?= e(fecha($orden['fecha_entrega'])) ?> · <?= e($liquidacion['registrada_por_nombre']) ?></dd>
                </dl>
            </div>
        </section>
    <?php endif; ?>

    <section class="col-12" aria-labelledby="t-hist">
        <div class="panel">
            <h2 id="t-hist" class="panel-title">Historial</h2>
            <ul class="timeline">
                <?php foreach ($historial as $h): ?>
                    <li>
                        <span class="timeline-when"><?= e(fecha($h['created_at'])) ?></span>
                        <span><strong><?= e($h['usuario']) ?></strong> ·
                            <?php if ($h['estado_anterior'] && $h['estado_anterior'] !== $h['estado_nuevo']): ?>
                                <?= e(OT::ESTADOS[$h['estado_anterior']]) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i><span class="visually-hidden"> a </span> <?= e(OT::ESTADOS[$h['estado_nuevo']]) ?>
                            <?php elseif (!$h['estado_anterior']): ?>
                                <?= e(OT::ESTADOS[$h['estado_nuevo']]) ?>
                            <?php endif; ?>
                            <?php if ($h['comentario']): ?><span class="text-secondary">— <?= e($h['comentario']) ?></span><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
</div>
