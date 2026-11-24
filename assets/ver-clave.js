/* Muestra u oculta la contrasena al hacer clic en el ojo. */
(function () {
    var botones = document.querySelectorAll('.ver-clave');
    if (botones.length === 0) {
        return;
    }

    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            var campo = document.getElementById(boton.dataset.clave);
            if (!campo) {
                return;
            }

            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            boton.classList.toggle('activo', mostrar);
            boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contrasena' : 'Mostrar contrasena');
            boton.setAttribute('title', mostrar ? 'Ocultar contrasena' : 'Mostrar contrasena');

            campo.focus();
        });
    });
})();
