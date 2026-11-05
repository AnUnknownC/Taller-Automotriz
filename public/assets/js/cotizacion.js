/*
 * Editor de cotización: agregar/quitar filas y calcular totales en vivo.
 * Es solo ayuda visual: el servidor valida todo y recalcula con los precios del catálogo.
 */
(function () {
    'use strict';
    var form = document.getElementById('cotizacion');
    if (!form) return;

    var cuerpo = form.querySelector('[data-filas]');
    var plantilla = document.getElementById('plantilla-fila').innerHTML;
    var iva = parseFloat(form.getAttribute('data-iva')) || 0;
    var siguiente = cuerpo.querySelectorAll('[data-fila]').length;
    var pesos = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

    function prepararFila(fila) {
        var tipo = fila.querySelector('[data-tipo]');
        var repuesto = fila.querySelector('[data-repuesto]');
        var descripcion = fila.querySelector('[data-descripcion]');
        var cantidad = fila.querySelector('[data-cantidad]');
        var precio = fila.querySelector('[data-precio]');
        var aviso = fila.querySelector('[data-aviso-stock]');

        function actualizar() {
            var esRepuesto = tipo.value === 'repuesto';
            repuesto.hidden = !esRepuesto;
            repuesto.required = esRepuesto;
            descripcion.hidden = esRepuesto;
            descripcion.required = !esRepuesto;
            precio.readOnly = esRepuesto;          // el precio del repuesto viene del catálogo
            precio.required = !esRepuesto;
            cantidad.step = esRepuesto ? '1' : '0.5';
            cantidad.min = esRepuesto ? '1' : '0.5';

            aviso.hidden = true;
            if (esRepuesto) {
                var opcion = repuesto.options[repuesto.selectedIndex];
                precio.value = opcion && opcion.value ? opcion.getAttribute('data-precio-catalogo') : '';
                var stock = opcion && opcion.value ? parseInt(opcion.getAttribute('data-stock'), 10) : null;
                if (stock !== null && (parseFloat(cantidad.value) || 0) > stock) {
                    aviso.textContent = 'Stock actual: ' + stock + '. Habrá que reabastecer antes de reparar.';
                    aviso.hidden = false;
                }
            }
            recalcular();
        }

        [tipo, repuesto, cantidad, precio, descripcion].forEach(function (campo) {
            campo.addEventListener('input', actualizar);
            campo.addEventListener('change', actualizar);
        });
        fila.querySelector('[data-quitar]').addEventListener('click', function () {
            if (cuerpo.querySelectorAll('[data-fila]').length > 1) {
                fila.remove();
                recalcular();
            }
        });
        actualizar();
    }

    function recalcular() {
        var subtotal = 0;
        cuerpo.querySelectorAll('[data-fila]').forEach(function (fila) {
            var valor = (parseFloat(fila.querySelector('[data-cantidad]').value) || 0) *
                        (parseFloat(fila.querySelector('[data-precio]').value) || 0);
            fila.querySelector('[data-subtotal]').textContent = pesos.format(valor);
            subtotal += valor;
        });
        var impuesto = Math.round(subtotal * iva * 100) / 100;
        form.querySelector('[data-total-subtotal]').textContent = pesos.format(subtotal);
        form.querySelector('[data-total-iva]').textContent = pesos.format(impuesto);
        form.querySelector('[data-total]').textContent = pesos.format(subtotal + impuesto);
    }

    form.querySelectorAll('[data-agregar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var temporal = document.createElement('tbody');
            temporal.innerHTML = plantilla.replace(/__N__/g, String(siguiente++));
            var fila = temporal.firstElementChild;
            fila.querySelector('[data-tipo]').value = boton.getAttribute('data-agregar');
            cuerpo.appendChild(fila);
            prepararFila(fila);
            fila.querySelector(boton.getAttribute('data-agregar') === 'repuesto' ? '[data-repuesto]' : '[data-descripcion]').focus();
        });
    });

    // Validación nativa antes de enviar (el servidor vuelve a validar)
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.classList.add('was-validated');
            var primero = form.querySelector(':invalid');
            if (primero) primero.focus();
        }
    });

    cuerpo.querySelectorAll('[data-fila]').forEach(prepararFila);
})();
