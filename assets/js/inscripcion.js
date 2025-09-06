// ==============================
// Gestión de estado de inscripción
// ==============================

// Actualiza el badge visualmente
function refrescarBadge(nuevoValor, nuevoTexto) {
    const badge = document.querySelector('.status-badge');
    if (!badge) return;

    badge.textContent = nuevoTexto;

    // Colores según estado (ajusta valores según tu BD)
    if (nuevoValor === '10') { // Aprobado
        badge.style.backgroundColor = '#28a745';
    } else if (nuevoValor === '11') { // Rechazado
        badge.style.backgroundColor = '#dc3545';
    } else if (nuevoValor === '7') { // Pendiente
        badge.style.backgroundColor = '#ffc107';
    } else {
        badge.style.backgroundColor = '#17a2b8'; // Otro
    }

    badge.style.color = 'white';
}

// Hace la petición al backend para cambiar el estado
function actualizarEstado(form) {
    const formData = new FormData(form);

    Swal.fire({
        title: 'Actualizando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Hecho!',
                text: data.message,
                timer: 2000,
                showConfirmButton: true
            });

            const select = form.querySelector('#nuevoStatus');
            const selectedOption = select.options[select.selectedIndex];
            refrescarBadge(selectedOption.value, selectedOption.textContent);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo conectar con el servidor'
        });
    });
}

// Confirma antes de cambiar el estado
function confirmarCambioEstado(form) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Vas a cambiar el estado de esta inscripción',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            actualizarEstado(form);
        }
    });
}

// Inicializa listeners para el cambio de estado
function manejarCambioEstado() {
    const formStatus = document.querySelector('form[action*="InscripcionController.php"]');

    if (formStatus) {
        formStatus.addEventListener('submit', function (e) {
            const action = this.querySelector('[name="action"]').value;
            if (action === 'cambiarStatus') {
                e.preventDefault();
                confirmarCambioEstado(this);
            }
        });
    }
}

// ==============================
// Gestión de requisitos
// ==============================
function manejarRequisitos(idInscripcion) {
    const formRequisitos = document.getElementById('form-requisitos');
    const guardarCambios = document.getElementById('guardar-cambios-container');
    const checkboxes = document.querySelectorAll('.requisito-checkbox');
    const contador = document.getElementById('contador-requisitos');
    const botonesIndividuales = document.querySelectorAll('.guardar-individual');

    // Contador dinámico
    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.requisito-checkbox:checked').length;
        const total = checkboxes.length;
        contador.textContent = `${seleccionados}/${total} seleccionados`;
    }

    // Mostrar/ocultar botón de guardar
    function verificarCambios() {
        let hayCambios = false;
        checkboxes.forEach(checkbox => {
            const estadoOriginal = parseInt(checkbox.getAttribute('data-original'));
            if ((checkbox.checked ? 1 : 0) !== estadoOriginal) {
                hayCambios = true;
            }
        });

        guardarCambios.style.display = hayCambios ? 'block' : 'none';
    }

    // Guardar estado original
    checkboxes.forEach(checkbox => {
        checkbox.setAttribute('data-original', checkbox.checked ? 1 : 0);
    });

    // Eventos de checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            actualizarContador();
            verificarCambios();
        });
    });

    // Botón descartar cambios
    document.getElementById('descartar-cambios').addEventListener('click', function() {
        checkboxes.forEach(checkbox => {
            const estadoOriginal = parseInt(checkbox.getAttribute('data-original'));
            checkbox.checked = estadoOriginal === 1;
        });
        actualizarContador();
        verificarCambios();

        Swal.fire({
            icon: 'info',
            title: 'Cambios descartados',
            text: 'Se han restaurado los valores originales',
            timer: 1500,
            showConfirmButton: true
        });
    });

    // Guardado individual
    botonesIndividuales.forEach(boton => {
        boton.addEventListener('click', function() {
            const idRequisito = this.getAttribute('data-requisito');
            const cumplido = this.getAttribute('data-cumplido');

            Swal.fire({
                title: 'Actualizando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggleRequisito&idInscripcion=${idInscripcion}&idRequisito=${idRequisito}&cumplido=${cumplido}`
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    const checkbox = document.getElementById(`req-${idRequisito}`);
                    checkbox.checked = cumplido === '1';
                    checkbox.setAttribute('data-original', cumplido);

                    this.setAttribute('data-cumplido', cumplido === '1' ? '0' : '1');
                    this.innerHTML = cumplido === '1' 
                        ? '<i class="fas fa-times me-1"></i> Desmarcar' 
                        : '<i class="fas fa-check me-1"></i> Marcar';

                    actualizarContador();
                    verificarCambios();

                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: true
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        });
    });

    // Guardado masivo
    formRequisitos.addEventListener('submit', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Guardando...',
            text: 'Actualizando múltiples requisitos',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData(this);

        fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.close();

            if (data.success) {
                checkboxes.forEach(checkbox => {
                    checkbox.setAttribute('data-original', checkbox.checked ? 1 : 0);
                });
                verificarCambios();

                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: true
                });
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        });
    });

    // Inicializar contador al cargar
    actualizarContador();
}

// ==============================
// Inicialización global
// ==============================
document.addEventListener('DOMContentLoaded', function () {
    manejarCambioEstado();
    // ⚠️ Reemplaza con el ID de inscripción real desde PHP
    manejarRequisitos(ID_INSCRIPCION);
});