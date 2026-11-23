/* Modal de producto en Inicio: abre Editar o Entrada sin salir del modulo. */
(function () {
    var dialog = document.getElementById('modal-producto');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }

    var cerrar = dialog.querySelector('.modal-cerrar');

    function abrir(fila) {
        document.getElementById('modal-codigo').textContent = fila.dataset.codigo || '';
        document.getElementById('modal-nombre').textContent = fila.dataset.nombre || '';
        document.getElementById('modal-stock').textContent = fila.dataset.stock || '0';
        document.getElementById('modal-minimo').textContent = fila.dataset.minimo || '0';

        var imagen = document.getElementById('modal-imagen');
        if (fila.dataset.imagen) {
            imagen.src = fila.dataset.imagen;
            imagen.hidden = false;
        } else {
            imagen.removeAttribute('src');
            imagen.hidden = true;
        }

        var id = encodeURIComponent(fila.dataset.id || '');
        var editar = document.getElementById('modal-editar');
        var entrada = document.getElementById('modal-entrada');

        if (editar) {
            editar.href = 'productos/editar.php?id=' + id;
        }
        if (entrada) {
            entrada.href = 'movimientos/crear.php?tipo=entrada&producto=' + id;
        }

        dialog.showModal();
    }

    document.querySelectorAll('.fila-producto').forEach(function (fila) {
        fila.addEventListener('click', function (evento) {
            if (evento.target.closest('a, button')) {
                return;
            }
            abrir(fila);
        });

        fila.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                abrir(fila);
            }
        });
    });

    cerrar.addEventListener('click', function () {
        dialog.close();
    });

    dialog.addEventListener('click', function (evento) {
        if (evento.target === dialog) {
            dialog.close();
        }
    });
})();
