/* Filtrado en vivo: oculta las filas ya cargadas, sin recargar la pagina. */
(function () {
    var input = document.getElementById('q');
    if (!input) {
        return;
    }

    var tabla = document.querySelector('table[data-buscar]');
    if (!tabla) {
        return;
    }

    var filas = Array.prototype.slice.call(tabla.querySelectorAll('tbody tr'));
    var contador = document.querySelector('[data-total]');
    var plantilla = contador ? contador.textContent : '';
    var mensajeVacio = document.querySelector('.buscador-vacio');
    var contenedor = tabla.closest('.tabla-scroll') || tabla;
    var temporizador = null;

    /* Quita acentos y mayusculas para que la busqueda sea mas amable. */
    function normalizar(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function aplicar() {
        var busqueda = normalizar(input.value);
        var visibles = 0;

        filas.forEach(function (fila) {
            var texto = normalizar(fila.getAttribute('data-buscar') || fila.textContent);
            var mostrar = busqueda === '' || texto.indexOf(busqueda) !== -1;
            fila.hidden = !mostrar;
            if (mostrar) {
                visibles++;
            }
        });

        if (contador) {
            contador.textContent = plantilla.replace(/\d+/, String(visibles));
        }
        if (mensajeVacio) {
            mensajeVacio.hidden = visibles !== 0;
        }

        /* La URL cambia sin recargar, para no perder la busqueda al actualizar. */
        var url = new URL(window.location.href);
        if (input.value.trim() === '') {
            url.searchParams.delete('q');
        } else {
            url.searchParams.set('q', input.value.trim());
        }
        window.history.replaceState(null, '', url.pathname + url.search + url.hash);

        /* Las pestañas y las columnas ordenables conservan la busqueda. */
        document.querySelectorAll('nav.tabs a.tab, a[data-ordenar]').forEach(function (enlace) {
            var enlaceUrl = new URL(enlace.href, window.location.href);
            if (input.value.trim() === '') {
                enlaceUrl.searchParams.delete('q');
            } else {
                enlaceUrl.searchParams.set('q', input.value.trim());
            }
            enlace.href = enlaceUrl.pathname + enlaceUrl.search;
        });
    }

    input.addEventListener('input', function () {
        contenedor.classList.add('filtrando');
        window.clearTimeout(temporizador);
        temporizador = window.setTimeout(function () {
            aplicar();
            contenedor.classList.remove('filtrando');
        }, 90);
    });

    /* Enter tambien filtra en el mismo sitio, sin recargar. */
    input.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            window.clearTimeout(temporizador);
            aplicar();
            contenedor.classList.remove('filtrando');
        }
    });
})();
