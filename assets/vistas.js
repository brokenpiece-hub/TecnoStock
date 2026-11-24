/* Cambia entre lista y tarjetas sin recargar y recuerda la ultima eleccion. */
(function () {
    var clave = 'tecnostock-vista-productos';

    /* Cambia el aspecto entre lista y tarjetas. */
    function aplicar(contenedor, vista) {
        contenedor.dataset.vista = vista;

        var botones = contenedor.closest('.card').querySelectorAll('.vista-btn');
        botones.forEach(function (boton) {
            var activo = boton.dataset.vista === vista;
            boton.classList.toggle('activo', activo);
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
    }

    /* Al hacer clic en Lista o Tarjetas solo cambia el aspecto. */
    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.vista-btn[data-vista]');
        if (!boton) {
            return;
        }

        var contenedor = boton.closest('.card').querySelector('[data-vista-productos]');
        if (!contenedor) {
            return;
        }

        aplicar(contenedor, boton.dataset.vista);
        try {
            localStorage.setItem(clave, boton.dataset.vista);
        } catch (error) {
            // Si el navegador no permite guardar, igual funciona en esta pagina.
        }
    });

    var guardado = null;
    try {
        guardado = localStorage.getItem(clave);
    } catch (error) {
        guardado = null;
    }
    if (guardado !== 'lista' && guardado !== 'tarjetas') {
        guardado = 'lista';
    }

    document.querySelectorAll('[data-vista-productos]').forEach(function (contenedor) {
        aplicar(contenedor, guardado);
    });
})();
