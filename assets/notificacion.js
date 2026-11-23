/* La notificacion de exito se puede cerrar con la x. */
(function () {
    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.notificacion-cerrar');
        if (!boton) {
            return;
        }

        var notificacion = boton.closest('.notificacion-flotante');
        if (notificacion) {
            notificacion.hidden = true;
        }
    });
})();
