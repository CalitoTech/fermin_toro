// Reemplaza el alert de éxito al enviar el formulario
function showSuccessAlert(message) {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        html: message,
        confirmButtonColor: '#c90000',
        confirmButtonText: 'Aceptar',
        customClass: {
            popup: 'animated bounceIn'
        }
    });
}

// Reemplaza los alert de error
function showErrorAlert(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        html: message,
        confirmButtonColor: '#c90000',
        confirmButtonText: 'Entendido',
        customClass: {
            popup: 'animated shake'
        }
    });
}

// Reemplaza los alert de información
function showInfoAlert(message) {
    toastr.info(message, 'Información', {
        timeOut: 3000,
        iconClass: 'toast-info',
        positionClass: "toast-top-center"
    });
}

// Reemplaza los alert de validación
function showWarningAlert(message) {
    toastr.warning(message, 'Advertencia', {
        timeOut: 4000,
        iconClass: 'toast-warning',
        positionClass: "toast-top-center"
    });
}

function mostrarRequisitos(idNivel) {
// Mostrar spinner de carga
$('#requisitosModalBody').html(`
    <div class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
        <p class="mt-2">Cargando requisitos...</p>
    </div>
`);
$('#requisitosModal').modal('show');

fetch(`../../controladores/RequisitosController.php?idNivel=${idNivel}`)
    .then(respuesta => {
        if (!respuesta.ok) {
            throw new Error('Error al obtener requisitos');
        }
        return respuesta.json();
    })
    .then(requisitos => {
        if (requisitos.error) {
            throw new Error(requisitos.error);
        }

        if (requisitos.length === 0) {
            $('#requisitosModalBody').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    No hay requisitos definidos para este nivel.
                </div>
            `);
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="tabla-requisitos">
                    <thead>
                        <tr>
                            <th><i class="fas fa-list-ol mr-1"></i> Requisito</th>
                            <th class="text-center"><i class="fas fa-check-circle mr-1"></i> Obligatorio</th>
                        </tr>
                    </thead>
                    <tbody>`;

        requisitos.forEach(requisito => {
            const esObligatorio = requisito.obligatorio === 'Sí';
            html += `
                <tr>
                    <td>
                        <i class="fas ${esObligatorio ? 'fa-exclamation-circle text-danger' : 'fa-info-circle text-info'} mr-2"></i>
                        ${requisito.requisito}
                    </td>
                    <td class="text-center">
                        <span class="badge-requisito ${esObligatorio ? 'badge-obligatorio' : 'badge-opcional'}">
                            ${requisito.obligatorio}
                        </span>
                    </td>
                </tr>`;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                Todos los documentos deben ser presentados en original y copia.
            </div>`;

        $('#requisitosModalBody').html(html);
    })
    .catch(error => {
        console.error('Error:', error);
        $('#requisitosModalBody').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                ${error.message || 'Error al cargar los requisitos. Por favor intente nuevamente.'}
            </div>
        `);
    });
}

$(document).ready(function () {
    // === 1. Inicializar el formulario (una sola vez) ===
    inicializarFormulario();
});

$(document).on('change', 'input[name="tipoRepresentante"]', function() {
    const valor = $(this).val();
    const repInfo = $('#repAutoInfo');
    
    if (valor === 'otro') {
        repInfo.hide();
        // Resto del código para mostrar sección
    } else {
        repInfo.show();
        $('#repSeleccionado').text(
            valor === 'padre' ? 'el padre' : 'la madre'
        );
        // Resto del código para ocultar sección
    }
});

function abrirFormulario(idCurso) {
$('#idCursoSeleccionado').val(idCurso);

fetch(`../../controladores/CursoController.php?action=getCursoById&id=${idCurso}`)
    .then(response => response.json())
    .then(curso => {
        $('#formularioModalLabel').html(`Formulario de Inscripción - ${curso.curso}`);
        $('#formularioModal').modal('show');
    })
    .catch(error => {
        console.error('Error al obtener datos del curso:', error);
        $('#formularioModalLabel').html('Formulario de Inscripción');
        $('#formularioModal').modal('show');
    });
}

function enviarFormulario() {
    const formData = new FormData(document.getElementById('formInscripcion'));
    const tipoRep = $('input[name="tipoRepresentante"]:checked').val();

    // Si no es "otro", deshabilitar validación para los campos de representante
    if (tipoRep !== 'otro') {
        $('#seccionRepresentante').find('input, select').each(function() {
            $(this).prop('required', false);
        });
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    const idCurso = $('#idCursoSeleccionado').val();
    formData.append('IdCurso', idCurso); // Esta línea es crucial
    
    const btn = $('#btnEnviarFormulario');
    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...');
    btn.prop('disabled', true);

    fetch('../../controladores/InscripcionController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessAlert('Solicitud enviada correctamente.<br>Número de solicitud: ' + data.numeroSolicitud + '<br>Código de inscripción: ' + data.codigo_inscripcion);
            $('#formularioModal').modal('hide');
            $('#formInscripcion')[0].reset();
        } else {
            showErrorAlert(data.message || 'Error al procesar la solicitud.');
        }
    })
    .catch(error => {
        console.error('Error al enviar el formulario:', error);
        showErrorAlert('Error de conexión. Por favor, intente nuevamente.');
    })
    .finally(() => {
        btn.html('Enviar Solicitud');
        btn.prop('disabled', false);
    });
}

function inicializarFormulario() {
// --- A. Manejo del envío del formulario ---
$(document).on('click', '#btnEnviarFormulario', function () {
    const form = document.getElementById('formInscripcion');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const btn = $(this);

    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...');
    btn.prop('disabled', true);

    fetch('../../controladores/InscripcionController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccessAlert(
                'Solicitud enviada correctamente.<br>' +
                'Número de solicitud: ' + data.numeroSolicitud + '<br>' +
                'Código de inscripción: ' + data.codigo_inscripcion
            );
            $('#formularioModal').modal('hide');
        } else {
            throw new Error(data.message || 'Error del servidor');
        }
    })
    .catch(error => {
        console.error('Error al enviar:', error);
        showErrorAlert('Error: ' + error.message);
    })
    .finally(() => {
        btn.html('Enviar Solicitud');
        btn.prop('disabled', false);
    });
});

// --- B. Control de campos condicionales (alergia, enfermedad) ---
$(document).on('change', '#esAlergico', function() {
    $('#alergia').prop('disabled', !this.checked).val(this.checked ? '' : '');
});

$(document).on('change', '#tieneEnfermedad', function() {
    $('#enfermedad').prop('disabled', !this.checked).val(this.checked ? '' : '');
});

// --- C. Limpiar formulario al cerrar el modal ---
$('#formularioModal').on('hidden.bs.modal', function () {
    document.getElementById('formInscripcion').reset();
    // Aseguramos que los campos deshabilitados queden limpios
    $('#alergia, #enfermedad').prop('disabled', true).val('');
});

$(document).on('change', 'input[name="tipoRepresentante"]', function() {
    const valor = $(this).val();
    
    if (valor === 'otro') {
        $('#seccionRepresentante').slideDown();
    } else {
        $('#seccionRepresentante').slideUp();
        // Aquí puedes agregar lógica para autocompletar si es padre/madre
    }
});

// Para inicializar correctamente al abrir el modal
$('#formularioModal').on('show.bs.modal', function() {
    $('input[name="tipoRepresentante"][value="madre"]').prop('checked', true);
    $('#seccionRepresentante').hide();
});

$(document).on('change', 'input[name="tipoRepresentante"]', function() {
    const valor = $(this).val();
    const seccionRep = $('#seccionRepresentante');
    const camposRep = seccionRep.find('input, select');
    
    if (valor === 'otro') {
        seccionRep.slideDown();
        // Hacer los campos requeridos
        camposRep.each(function() {
            if ($(this).data('original-required') === undefined) {
                $(this).data('original-required', $(this).prop('required'));
            }
            $(this).prop('required', true);
        });
    } else {
        seccionRep.slideUp();
        // Quitar requeridos
        camposRep.each(function() {
            $(this).prop('required', false);
        });
    }
});
}