/* El aviso flotante se puede quitar, pero vuelve al entrar a otro modulo. */
(function () {
    /* Al hacer clic en la x, oculta el aviso. */
    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.aviso-cerrar');
        if (!boton) {
            return;
        }

        var aviso = boton.closest('.aviso-flotante');
        if (aviso) {
            aviso.hidden = true;
        }
    });
})();
