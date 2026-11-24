/* Ordena las filas en el navegador: la cabecera ya no recarga la pagina. */
(function () {
    /* Quita acentos y mayusculas para ordenar texto. */
    function normalizar(texto) {
        return String(texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    /* Obtiene el valor de una columna para comparar dos filas. */
    function valor_de_celda(fila, indice, tipo) {
        var celda = fila.cells[indice];
        var texto = celda ? (celda.getAttribute('data-orden-valor') || celda.textContent) : '';

        if (tipo === 'numero') {
            /* El guion de codigos como TEC-001 no es signo negativo. */
            var numero = parseFloat(String(texto).replace(/[^0-9.]/g, ''));
            return isNaN(numero) ? 0 : numero;
        }
        return normalizar(texto);
    }

    /* Al hacer clic en una cabecera, ordena las filas de la tabla. */
    document.addEventListener('click', function (evento) {
        if (evento.defaultPrevented || evento.button !== 0 || evento.metaKey ||
            evento.ctrlKey || evento.shiftKey || evento.altKey) {
            return;
        }

        var enlace = evento.target.closest('a[data-ordenar]');
        if (!enlace) {
            return;
        }

        var tabla = enlace.closest('table[data-buscar]');
        if (!tabla) {
            return;
        }
        evento.preventDefault();

        var columna = enlace.getAttribute('data-ordenar');
        var tipo = enlace.getAttribute('data-tipo') || 'texto';
        var url = new URL(window.location.href);
        var ordenActual = url.searchParams.get('orden') || '';
        var direccionActual = url.searchParams.get('direccion') === 'desc' ? 'desc' : 'asc';
        var nuevaDireccion = ordenActual === columna
            ? (direccionActual === 'asc' ? 'desc' : 'asc')
            : (enlace.getAttribute('data-inicial') || 'asc');

        /* Actualiza flechas y enlaces para el siguiente clic. */
        var enlaces = Array.prototype.slice.call(tabla.querySelectorAll('a[data-ordenar]'));
        enlaces.forEach(function (otro) {
            var destino = new URL(otro.href, window.location.href);
            var activo = otro === enlace;
            destino.searchParams.set('orden', otro.getAttribute('data-ordenar'));
            destino.searchParams.set('direccion', activo
                ? nuevaDireccion
                : (otro.getAttribute('data-inicial') || 'asc'));
            otro.href = destino.pathname + destino.search + destino.hash;
            otro.classList.toggle('activo', activo);
            var flecha = otro.querySelector('.flecha-orden');
            if (flecha) {
                flecha.textContent = activo ? (nuevaDireccion === 'asc' ? '↑' : '↓') : '↕';
            }
        });

        url.searchParams.set('orden', columna);
        url.searchParams.set('direccion', nuevaDireccion);
        window.history.replaceState(null, '', url.pathname + url.search + url.hash);

        var tbody = tabla.querySelector('tbody');
        if (!tbody) {
            return;
        }
        var cabeceras = Array.prototype.slice.call(tabla.querySelectorAll('thead th'));
        var indice = cabeceras.indexOf(enlace.closest('th'));
        var filas = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        var ordenadas = filas.map(function (fila, posicion) {
            return {
                fila: fila,
                posicion: posicion,
                valor: valor_de_celda(fila, indice, tipo)
            };
        });

        ordenadas.sort(function (a, b) {
            if (a.valor < b.valor) {
                return nuevaDireccion === 'asc' ? -1 : 1;
            }
            if (a.valor > b.valor) {
                return nuevaDireccion === 'asc' ? 1 : -1;
            }
            return a.posicion - b.posicion;
        });

        /* Desvanece, reordena y vuelve a mostrar sin mover toda la pagina. */
        var contenedor = tabla.closest('.tabla-scroll') || tabla;
        var altoAnterior = contenedor.offsetHeight;
        contenedor.style.minHeight = altoAnterior + 'px';
        contenedor.classList.add('filtrando');

        if (tabla._temporizadorOrden) {
            window.clearTimeout(tabla._temporizadorOrden);
        }
        tabla._temporizadorOrden = window.setTimeout(function () {
            ordenadas.forEach(function (item) {
                tbody.appendChild(item.fila);
            });
            contenedor.style.minHeight = '';
            var altoNuevo = contenedor.offsetHeight;
            contenedor.style.minHeight = altoNuevo + 'px';
            contenedor.classList.remove('filtrando');

            window.setTimeout(function () {
                contenedor.style.minHeight = '';
            }, 220);
        }, 110);
    });
})();
