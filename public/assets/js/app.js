/* Interacciones del cliente. La validación real SIEMPRE ocurre en el servidor (RF-10). */
(function () {
    'use strict';

    // Confirmación de contraseña: <input data-match="id-del-otro-campo">
    document.querySelectorAll('[data-match]').forEach(function (campo) {
        var original = document.getElementById(campo.getAttribute('data-match'));
        var verificar = function () {
            campo.setCustomValidity(campo.value === original.value ? '' : 'Las contraseñas no coinciden.');
        };
        campo.addEventListener('input', verificar);
        original.addEventListener('input', verificar);
    });

    // Mostrar/ocultar una sección y volver obligatorios sus campos: <input type="checkbox" data-toggle-section="id">
    document.querySelectorAll('[data-toggle-section]').forEach(function (check) {
        var seccion = document.getElementById(check.getAttribute('data-toggle-section'));
        var aplicar = function () {
            seccion.hidden = !check.checked;
            seccion.querySelectorAll('input').forEach(function (i) { i.required = check.checked; });
            // Campos fuera de la sección que también se vuelven obligatorios: data-toggle-required="id1,id2"
            (check.getAttribute('data-toggle-required') || '').split(',').forEach(function (id) {
                var campo = id && document.getElementById(id.trim());
                if (!campo) return;
                if (campo.dataset.requiredOriginal === undefined) {      // se guarda una sola vez
                    campo.dataset.requiredOriginal = campo.required ? '1' : '0';
                }
                campo.required = check.checked || campo.dataset.requiredOriginal === '1';
            });
        };
        check.addEventListener('change', aplicar);
        aplicar();
    });

    // Validación amigable con estilos de Bootstrap + evitar doble envío
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                var primero = form.querySelector(':invalid');
                if (primero) primero.focus();
                return;
            }
            var boton = form.querySelector('[type="submit"][data-loading-text]');
            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.getAttribute('data-loading-text');
            }
        });
    });

    // Mostrar / ocultar contraseña (accesible: aria-pressed y etiqueta actualizada)
    document.querySelectorAll('[data-toggle-password]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var campo = document.getElementById(boton.getAttribute('data-toggle-password'));
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            boton.setAttribute('aria-pressed', String(mostrar));
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
            boton.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // RF-12: confirmación de acciones destructivas -> <form data-confirm="¿Seguro...?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });
})();
