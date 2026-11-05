# Pruebas · Sprint 3B (orden: aprobación → reparación → entrega)

> **Nota:** los códigos de orden de este archivo corresponden a los datos de prueba de esa fecha. Desde el Sprint 4 la base incluye 36 órdenes históricas y las órdenes en curso son `OT-2026-0037` a `OT-2026-0040`. Para repetir estas pruebas sobre los datos actuales, usa la suite automática `tests/run_tests.php`.

Ejecutadas el 05/11/2026 (PHP 8 + MariaDB + Chromium, datos de `seed.sql`).

**Pendiente:** repetirlas en XAMPP y adjuntar capturas.

## Respuesta a la cotización (R1)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3B-01 | Responder la cotización de OT-1 (de Óscar) como María, como técnico y como admin | 403 en los tres casos | ✅ |
| P3B-02 | El tablero de Óscar muestra un aviso con "Revisar y responder" | Enlace directo a su orden | ✅ |
| P3B-03 | Óscar aprueba desde el portal (celular) | Cotizada → Aprobada; canal `portal`; queda quién respondió | ✅ |
| P3B-04 | Responder dos veces | "Ya no espera respuesta" | ✅ |
| P3B-05 | El asesor registra un rechazo sin motivo / con canal "whatsapp" | Bloqueado con mensaje | ✅ |
| P3B-06 | El asesor registra un rechazo telefónico con motivo "Otro: …" | Rechazada; canal `telefono`; motivo y asesor guardados | ✅ |

## Reparación y stock (R2)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3B-07 | Iniciar la reparación como técnico no asignado / como asesor | 403 | ✅ |
| P3B-08 | Iniciar con 0 pastillas en stock | Error con el faltante; **no se descuenta nada** (el líquido sigue en 9); 0 movimientos | ✅ |
| P3B-09 | Iniciar con stock suficiente | Pastillas 12→11, líquido 9→8; 2 salidas en el kardex con la orden | ✅ |
| P3B-10 | Iniciar una orden rechazada | Bloqueado | ✅ |
| P3B-11 | **Concurrencia real** (servidor con 4 procesos): 2 técnicos inician a la vez órdenes que necesitan las únicas 2 correas; 5 rondas | En las 5 gana exactamente uno; el stock nunca queda negativo | ✅ |
| P3B-12 | Marcar como lista con una nota | En reparación → Lista; la nota queda en el historial | ✅ |

## Liquidación y entrega (R4)

| ID | Prueba | Esperado | Resultado |
|---|---|---|---|
| P3B-13 | Abrir la liquidación de una orden que no está lista | Redirige con "no está lista para liquidar" | ✅ |
| P3B-14 | Liquidar sin confirmar la entrega (JS y POST) | Bloqueado en el navegador y en el servidor | ✅ |
| P3B-15 | Método de pago "bitcoin" | Rechazado | ✅ |
| P3B-16 | Liquidar OT-1 con tarjeta | 85.000 + 125.000 + IVA 39.900 = **$249.900**; Entregada con fecha de entrega | ✅ |
| P3B-17 | Liquidar dos veces | Bloqueado | ✅ |
| P3B-18 | Liquidar una orden rechazada | Solo el diagnóstico: 60.000 + IVA = **$71.400**; el stock de bujías no cambia | ✅ |
| P3B-19 | Después de entregar, abrir una nueva orden para el mismo vehículo | Permitido (R5 liberada) | ✅ |
| P3B-20 | Bloqueo optimista: transición con un estado desactualizado | "Otro usuario modificó esta orden…" | ✅ |
| P3B-21 | `puedeTransitar(recibida → entregada)` | `false` | ✅ |
| P3B-22 | Ficha y cotización a 360 y 390 px | La tabla cabe sin scroll; sin errores de consola | ✅ |
