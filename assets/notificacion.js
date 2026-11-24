/* La notificacion de exito se puede cerrar con la x. */
(function () {
    /* Al hacer clic en la x, oculta la notificacion. */
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
