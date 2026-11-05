# Bitácora de uso de IA · Torque

> Entregable 7 (sección 07 del documento). Registra la herramienta, la fecha, la tarea o el prompt, el resultado, la decisión humana y los cambios.
> **Regla del curso:** la IA asiste, pero el equipo responde por la arquitectura, la lógica, la seguridad, el código, las pruebas y el soporte.

## 1. Herramienta y condiciones de uso

| Aspecto | Detalle |
|---|---|
| Herramienta | **Claude (Anthropic)**, asistente conversacional con ejecución de código en un entorno aislado (PHP, MariaDB, navegador automatizado) |
| Uso | Análisis del enunciado, propuesta de arquitectura, generación de código, pruebas automatizadas, auditoría de accesibilidad y documentación |
| Aval | El docente autorizó el uso de IA para el desarrollo, con la condición de que los dos integrantes dominen los aspectos clave |
| Datos en los prompts | **Solo datos ficticios.** No se ingresaron datos personales reales, contraseñas reales ni secretos. Las cuentas de prueba (`*@torque.test`) son inventadas. |
| Verificación | Cada entrega se probó ejecutándola: pruebas HTTP, navegador real, comparación de cifras contra SQL directo. Ver `tests/` |

## 2. Registro cronológico

| Fecha | Tarea / prompt del equipo | Resultado de la IA | Decisión humana | Cambios realizados |
|---|---|---|---|---|
| 03/11 | Se entregó el enunciado completo y se pidió actuar como arquitecto: arquitectura, modelo de datos, plan por sprints y código inicial, **sin inventar funcionalidades y preguntando ante cualquier ambigüedad**. | Propuso la arquitectura con el stack obligatorio e identificó 6 ambigüedades (facturación, profundidad del inventario, estados, reglas, equipo, versión de PHP). | El equipo exigió que se preguntara antes de asumir. | — |
| 04/11 | Respuestas a las 6 preguntas. | Generó `schema.sql` (15 tablas). | **Facturación:** solo teórica, sin pasarela → liquidación **simulada**. **Inventario:** nivel (b), con entradas de proveedor y stock mínimo. Se **validó** el flujo de 8 estados y las 4 reglas propuestas. Trabajo en pareja. XAMPP 8.0.30. | Se descartó la facturación legal; tabla `liquidaciones` con método simulado; Argon2id con respaldo a bcrypt. |
| 04/11 | "Hagamos el código del proyecto en sí, para hacer pruebas en mi equipo y ver que corra." | Sprint 1: núcleo MVC, login seguro, tableros por rol, `README`. | El equipo **cambió la prioridad**: código ejecutable antes que más documentación de diseño. | El plan pasó de "diseñar todo primero" a construir y probar sprint por sprint. |
| 05/11 | "Me falta la db." | Generó `instalar_completo.sql` (esquema + datos en un solo archivo). | El equipo detectó un problema de instalación: dos archivos separados no eran claros. | Se adoptó el archivo único como método oficial de instalación (README y manual). |
| 05/11 | Sprint 2A (usuarios, clientes, vehículos). | CRUD con reglas de negocio y 28 pruebas. | Aceptado tras probarlo en XAMPP ("ya entra sin problemas"). | — |
| 05/11 | "Dame un .md con la información del proyecto, etapas, features y mockups." | `docs/PROYECTO.md` con 10 mockups y diagramas. | El equipo pidió un **registro propio** del proyecto para su control. | Se convirtió en el documento maestro, actualizado en cada sprint. |
| 05/11 | Confirmación de las decisiones pendientes del Sprint 3. | Implementó la máquina de estados, el descuento de stock y la liquidación. | **Stock:** se descuenta al iniciar la reparación (no al aprobar). **Cotización rechazada:** se cobra solo el diagnóstico. | `OrdenService::iniciarReparacion` y `calcularLiquidacion`. |
| 05/11 | "Implementa un botón de registro que lleve a un endpoint donde registrar un usuario nuevo cliente." | `RegistroController`, vista `/registro`, botón en el login, 9 pruebas nuevas. | El equipo **pidió la funcionalidad** (no estaba en el plan). | Ver la decisión de seguridad en la sección 4. |
| 05/11 | Sprints 2B, 3B, 4 y 5. | Inventario y kardex, aprobación, reportes, CSV, pruebas automáticas, auditoría de accesibilidad y documentación. | Aceptados sprint a sprint. | Ver la sección 3: defectos corregidos. |

## 3. Correcciones a la salida de la IA detectadas en pruebas

Defectos del código generado que se encontraron al ejecutar las pruebas y se corrigieron. Cada uno muestra por qué **no basta con generar código y hacerlo correr**.

| # | Lo que la IA generó mal | Cómo se detectó | Corrección | Lección |
|---|---|---|---|---|
| 1 | SQL importado sin `SET NAMES utf8mb4` → "MarÃ­a" | Prueba de login | `SET NAMES` en los SQL | El charset del cliente MySQL importa |
| 2 | La contraseña del portal se iba a guardar en la auditoría | Revisión de la tabla `auditoria` | Campos `acceso_password*` añadidos a la lista de secretos | Revisar qué se guarda, no solo que funcione |
| 3 | Editor de cotización: `[data-precio]` coincidía con las `<option>`, así que el total era incorrecto | Prueba con navegador real | Atributo `data-precio-catalogo` separado | Los selectores CSS en JS deben ser únicos |
| 4 | `min="1" step="100"`: el navegador rechazaba $66.000 | Prueba de entrada de mercancía | `step="1"` | `step` se cuenta desde `min` |
| 5 | La **primera prueba de concurrencia no era válida**: el servidor de PHP atiende una petición a la vez | Revisión del método de prueba | Se repitió con `PHP_CLI_SERVER_WORKERS=4` (5/5 rondas correctas) | Verificar que la prueba mida lo que dice medir |
| 6 | Datos históricos con entregas a las 02:44 | Revisión del reporte de ingresos | Generador en horario laboral | Los datos de demostración también se validan |
| 7 | Promedio por técnico ≠ criterio del indicador general | Comparación de cifras | Mismo criterio en ambos | Comparar cifras contra SQL directo |
| 8 | Filtro `<>` sin índice (full scan con 20.000 órdenes) | `EXPLAIN` | `IN (...)` + índices compuestos (4,7 veces más rápido) | Medir antes de optimizar |
| 9 | Contraste insuficiente (4,2:1) heredado de Bootstrap y otras 3 fallas de accesibilidad | axe-core | Paleta propia; 0 violaciones | No asumir que un framework ya es accesible |

## 4. Aportes y decisiones propias del equipo

El documento exige **como mínimo cinco** aportes técnicos propios o decisiones en las que el equipo **corrigió, rechazó o mejoró** la sugerencia de la IA.

**Decisiones del equipo ya registradas en esta bitácora:**

1. Liquidación **simulada** en lugar de facturación (alcance definido por el equipo).
2. Inventario de nivel (b), con proveedores, entradas y stock mínimo, en vez de un catálogo simple.
3. **Reprioridad del plan**: código ejecutable primero, validado en su propio XAMPP.
4. Instalación con **un solo archivo SQL**, a partir de un problema que detectó el equipo.
5. Descuento de stock **al iniciar la reparación** y cobro **solo del diagnóstico** al rechazar (reglas del negocio).
6. Documento de registro con mockups para controlar el avance.
7. **Autorregistro de clientes** desde el login. Decisión de seguridad asociada: un documento que el taller ya tiene registrado **no se vincula** automáticamente (evita suplantación) y los duplicados se informan con un mensaje genérico (evita enumeración).

> ⚠️ **COMPLETAR ANTES DE ENTREGAR (lo debe escribir el equipo).** Las decisiones anteriores son reales, pero el docente evaluará también **cambios técnicos propios**. Al estudiar el código para la sustentación, registren aquí al menos 3 modificaciones hechas por ustedes. Por ejemplo:
> - Una regla nueva o ajustada (¿un vehículo de flota necesita un campo de conductor?).
> - Un cambio de diseño o texto que mejore la experiencia en su contexto.
> - Una prueba manual en Firefox o Edge con su resultado (RNF-09).
> - Algo de la IA que decidieron **rechazar** o hacer distinto, y por qué.

| # | Fecha | Integrante | Qué sugirió la IA o qué había | Qué hicimos nosotros y por qué | Archivo(s) |
|---|---|---|---|---|---|
| 8 | | | | | |
| 9 | | | | | |
| 10 | | | | | |

## 5. Habilidades aplicadas (mínimo 6)

Detalle y evidencia en `docs/MATRIZ_REQUISITOS.md`, sección final: modelado de dominio, PHP MVC, seguridad, UI/UX y accesibilidad (obligatorias); SQL avanzado; JavaScript útil; pruebas y depuración; visualización de datos; optimización.

## 6. Librerías, recursos externos y licencias

| Recurso | Versión | Licencia | Uso | ¿Va en la entrega? |
|---|---|---|---|---|
| Bootstrap | 5.3.3 | MIT | Componentes y grilla (`public/assets/vendor/bootstrap/`) | Sí, copia local |
| Bootstrap Icons | 1.11.3 | MIT | Íconos (`public/assets/vendor/bootstrap-icons/`) | Sí, copia local |
| axe-core | 4.10.2 | MPL-2.0 | Auditoría de accesibilidad durante el desarrollo | No |
| Playwright / Chromium | — | Apache-2.0 / BSD | Navegador automatizado para pruebas y capturas | No |
| Fuentes | — | — | Se usan las fuentes del sistema (`system-ui`), sin descargas | — |

No se usaron plantillas de terceros: el diseño visual (`app.css`) y el código PHP son propios del proyecto.
