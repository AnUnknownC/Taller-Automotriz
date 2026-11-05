# Guía de diseño visual · Torque

> Entregable 5: sistema visual, componentes principales y evidencias responsive (RNF-02, RNF-03, RNF-04).

## 1. Concepto

Un taller es un lugar de **grafito, metal y señalización ámbar**. Su objeto más reconocible para el cliente colombiano es la **placa amarilla** del carro. La interfaz toma esos tres elementos:

- **Grafito** para la estructura: barra superior, menú, botones principales. Transmite sobriedad y da contraste alto.
- **Ámbar de señalización** solo para lo que pide atención: la marca, el paso actual de la orden, las alertas de stock y el foco del menú.
- **La placa** como identificador visual de cada vehículo en todas las pantallas. Se reconoce de un vistazo, igual que en el taller real.

Nada es decorativo: no hay degradados genéricos, ni imágenes de stock, ni estadísticas inventadas.

## 2. Paleta y contraste (WCAG 2.2 AA)

Relaciones calculadas con la fórmula de luminancia relativa de WCAG.

| Uso | Colores (texto / fondo) | Contraste | Cumple |
|---|---|---|---|
| Texto principal sobre fondo | `#1c232c` / `#f2f3f0` | 14,2:1 | AA ✅ |
| Texto secundario sobre fondo | `#56616e` / `#f2f3f0` | 5,7:1 | AA ✅ |
| Texto secundario sobre blanco | `#56616e` / `#ffffff` | 6,3:1 | AA ✅ |
| Blanco sobre grafito (barra, botones) | `#ffffff` / `#1c232c` | 15,8:1 | AA ✅ |
| Grafito sobre ámbar (marca) | `#1c232c` / `#f0a71a` | 7,7:1 | AA ✅ |
| Ámbar sobre grafito (pasos del login) | `#f0a71a` / `#1c232c` | 7,7:1 | AA ✅ |
| Placa: negro sobre amarillo | `#111111` / `#f7c600` | 11,7:1 | AA ✅ |
| Enlaces | `#0b5cad` / `#ffffff` | 6,7:1 | AA ✅ |
| Menú lateral | `#d5dbe2` / `#2b3440` | 9,0:1 | AA ✅ |
| Botón de contorno | `#47525e` / `#ffffff` | 8,0:1 | AA ✅ |
| Stock bajo (alerta) | `#a12a1a` / `#ffffff` | 7,3:1 | AA ✅ |
| Contorno de foco | `#1a73e8` / `#f2f3f0` | 4,0:1 | AA para UI (≥ 3:1) ✅ |

### Estados de la orden

Siempre llevan **texto + color**: el color nunca va solo.

| Estado | Texto / fondo | Contraste |
|---|---|---|
| Recibida | `#3b4652` / `#eceff2` | 8,3:1 |
| En diagnóstico | `#134a8e` / `#e1ecfb` | 7,3:1 |
| Cotizada | `#7a4d00` / `#fdf0d5` | 6,4:1 |
| Aprobada | `#1b6537` / `#ddf3e5` | 6,1:1 |
| Rechazada | `#8f2318` / `#fbe1df` | 7,0:1 |
| En reparación | `#8a3f00` / `#fde6cf` | 6,2:1 |
| Lista para entrega | `#0c5d58` / `#d8f1f0` | 6,5:1 |
| Entregada | `#ffffff` / `#1c232c` | 15,8:1 |

Los colores de Bootstrap que no cumplían (`.text-secondary` #6c757d = 4,2:1 y `.btn-outline-secondary`) se **reemplazaron** tras la auditoría con axe-core.

## 3. Tipografía, espaciado y forma

| Elemento | Decisión | Razón |
|---|---|---|
| Familia | `system-ui` (Segoe UI en Windows, SF en Mac, Roboto en Android) | Legible, nativa y sin descargas |
| Títulos | 700, `1.6rem` (h1), `1rem` (títulos de panel) | Jerarquía clara sin tamaños exagerados |
| Cifras | `font-variant-numeric: tabular-nums` | Los números se alinean en tablas e indicadores |
| Placa y códigos | Monoespaciada, 800, `letter-spacing: .08em` | Imita la placa y facilita leer códigos |
| Espaciado | Escala de Bootstrap (4, 8, 12, 16, 24 px); paneles de 18–20 px | Consistencia |
| Radio | 10 px en paneles, 8 px en botones, 20 px en insignias | Suave, pero sólido |
| Íconos | Bootstrap Icons, siempre con `aria-hidden="true"` y texto visible | Apoyan, no reemplazan al texto |

## 4. Componentes principales

| Componente | Dónde | Descripción |
|---|---|---|
| **Placa** (`placa()`) | Listados, fichas, recepción | Amarillo y negro, monoespaciada, con borde. Identifica el vehículo. |
| **Insignia de estado** (`estado_badge()`) | Órdenes | Un color por estado + texto. |
| **Barra de progreso de la orden** | Ficha de la orden | 7 pasos; el actual en ámbar con halo; los completados en grafito con ✓; el rechazo cambia la ruta y se pinta en rojo. En celular solo se muestra la etiqueta del paso actual. |
| **Panel "Siguiente paso"** | Ficha de la orden | Borde ámbar; muestra **solo** las acciones que el rol puede ejecutar en ese estado. |
| **Indicador (KPI)** | Tableros y reportes | Borde izquierdo grafito (ámbar si es una alerta); cifra grande con números tabulares. |
| **Tabla adaptable** (`.table-ordenes`) | Todos los listados | En menos de 768 px cada fila se convierte en tarjeta con `data-label`. |
| **Barras horizontales** (`barras_h`) | Reportes | Una sola serie en un solo color (la identidad la da la etiqueta); valor en texto; tabla de datos al lado. |
| **Chips de estado** | Listado de órdenes | Filtro rápido con conteo por estado. |
| **Formularios** | Todos | `<label>` visible, asterisco en los obligatorios, ayuda debajo, error por campo asociado con `aria-describedby`. |
| **Mensajes flash** | Tras cada acción | Ícono + texto; `role="alert"` para errores y `role="status"` para el resto. |
| **Pantallas de error** | 403/404/405/419/500 | Tarjeta con código, explicación humana y botón de regreso. |

## 5. Microinteracciones (todas con propósito)

| Interacción | Propósito |
|---|---|
| Botón con "Guardando…" y deshabilitado al enviar | Evita el doble envío y da retroalimentación |
| Totales de la cotización en vivo | El asesor ve el impacto de cada ítem antes de enviar |
| Aviso de stock insuficiente en el editor | Anticipa que habrá que reabastecer antes de reparar |
| Confirmación antes de desactivar, aprobar, iniciar o entregar | Evita acciones irreversibles por error (RF-12) |
| Formulario de rechazo plegable | La aprobación queda como acción principal; el rechazo pide motivo |
| Mostrar/ocultar contraseña con `aria-pressed` | Usabilidad en el celular, accesible |
| `prefers-reduced-motion` | Se desactivan transiciones si el usuario lo pide |

## 6. Responsive: evidencias

Puntos de quiebre: **< 576 px** (celular), **< 768 px** (tablas en tarjetas), **< 992 px** (menú lateral plegable).

| Pantalla | Celular | Tablet | Escritorio |
|---|---|---|---|
| Login | `img/02-login-movil.png` | — | `img/01-login-escritorio.png` |
| Tablero | `img/17-cliente-movil-tablero.png` | — | `img/03-admin-tablero.png`, `img/08-asesor-tablero.png` |
| Órdenes | `img/15-tecnico-movil-bandeja.png` | `img/19-asesor-tablet-ordenes.png` | `img/09-asesor-ordenes.png` |
| Ficha de la orden | `img/16-tecnico-movil-diagnostico.png`, `img/18-cliente-movil-cotizacion.png` | — | `img/10-asesor-ficha-orden.png` |
| Reportes | — | — | `img/04`, `img/05` |

Verificación automática: sin scroll horizontal a 360, 390, 768 y 1366 px en todas las pantallas (`tests/PLAN_DE_PRUEBAS.md` §6).
