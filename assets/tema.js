/* Cambia entre modo oscuro y claro, y recuerda la eleccion. */
(function () {
    var raiz = document.documentElement;
    var boton = document.getElementById('tema-toggle');
    var clave = 'tecnostock-tema';

    function aplicar(tema) {
        var oscuro = tema !== 'claro';
        raiz.dataset.tema = oscuro ? 'oscuro' : 'claro';
        raiz.style.colorScheme = oscuro ? 'dark' : 'light';

        try {
            localStorage.setItem(clave, raiz.dataset.tema);
        } catch (error) {
            // Si el navegador bloquea localStorage, el tema igual funciona.
        }

        if (!boton) {
            return;
        }
        boton.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
        boton.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        var texto = boton.querySelector('.tema-texto');
        if (texto) {
            texto.textContent = oscuro ? 'Modo claro' : 'Modo oscuro';
        }
    }

    aplicar(raiz.dataset.tema);

    if (boton) {
        boton.addEventListener('click', function () {
            aplicar(raiz.dataset.tema === 'oscuro' ? 'claro' : 'oscuro');
        });
    }
})();
