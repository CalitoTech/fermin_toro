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
    
    // Obtener el nombre del curso desde los datos ya cargados en la página
    const cursoSeleccionado = $(`button[onclick="abrirFormulario(${idCurso})"]`)
                              .closest('tr')
                              .find('td:first')
                              .text().trim();
    
    // Si encontramos el nombre del curso en la tabla, lo usamos
    if (cursoSeleccionado) {
        $('#formularioModalLabel').html(`Formulario de Inscripción - ${cursoSeleccionado}`);
        $('#formularioModal').modal('show');
    } else {
        // Si no lo encontramos, mostramos un mensaje genérico
        $('#formularioModalLabel').html('Formulario de Inscripción');
        $('#formularioModal').modal('show');
    }
}

function enviarFormulario() {
    const form = document.getElementById('formInscripcion');
    const formData = new FormData(form);
    const tipoRep = $('input[name="tipoRepresentante"]:checked').val();

    // 1. Validación de discapacidades
    let discapacidadesValidas = true;
    $('.discapacidad-row').each(function() {
        const tipo = $(this).find('.tipo-discapacidad').val();
        const descripcion = $(this).find('.descripcion-discapacidad').val();
        
        if (tipo && !descripcion) {
            $(this).find('.descripcion-discapacidad').addClass('is-invalid');
            discapacidadesValidas = false;
        }
    });

    if (!discapacidadesValidas) {
        showErrorAlert('Por favor complete todas las descripciones de discapacidades seleccionadas');
        return false;
    }

    // 2. Ajustar validación para representante
    if (tipoRep !== 'otro') {
        $('#seccionRepresentante').find('input, select').each(function() {
            $(this).prop('required', false);
        });
    }

    // 3. Validación general del formulario
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // 4. Agregar ID del curso
    formData.append('IdCurso', $('#idCursoSeleccionado').val());

    // 5. Configurar botón de envío
    const btn = $('#btnEnviarFormulario');
    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...');
    btn.prop('disabled', true);

    // 6. Enviar formulario
    fetch('../../controladores/InscripcionController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
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
            resetearFormularioCompleto();
        } else {
            throw new Error(data.message || 'Error al procesar la solicitud');
        }
    })
    .catch(error => {
        console.error('Error al enviar el formulario:', error);
        showErrorAlert(error.message || 'Error de conexión. Por favor, intente nuevamente.');
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
// Función para cargar tipos de discapacidad al iniciar
function cargarTiposDiscapacidad() {
    fetch('../../controladores/TipoDiscapacidadController.php?action=obtenerTodos')
        .then(response => {
            if (!response.ok) throw new Error('Error al obtener tipos');
            return response.json();
        })
        .then(tipos => {
            if (tipos.error) throw new Error(tipos.error);
            
            window.tiposDiscapacidad = tipos;
            actualizarSelectsDiscapacidad();
        })
        .catch(error => {
            console.error('Error al cargar tipos de discapacidad:', error);
            // Opcional: Mostrar tipos por defecto si falla
            window.tiposDiscapacidad = [
                {IdTipo_Discapacidad: 1, tipo_discapacidad: 'Visual'},
                {IdTipo_Discapacidad: 2, tipo_discapacidad: 'Auditiva'},
                {IdTipo_Discapacidad: 3, tipo_discapacidad: 'Motora'},
                {IdTipo_Discapacidad: 4, tipo_discapacidad: 'Alergia'},
                {IdTipo_Discapacidad: 5, tipo_discapacidad: 'Enfermedad'}
            ];
            actualizarSelectsDiscapacidad();
        });
}

// Actualizar selects existentes con los tipos cargados
function actualizarSelectsDiscapacidad() {
    const $selects = $('.tipo-discapacidad');
    
    $selects.each(function() {
        const $select = $(this);
        $select.empty().append('<option value="">Seleccione tipo</option>');
        
        if (window.tiposDiscapacidad) {
            window.tiposDiscapacidad.forEach(tipo => {
                $select.append(`<option value="${tipo.IdTipo_Discapacidad}">${tipo.tipo_discapacidad}</option>`);
            });
        }
    });
}

// Función para agregar nueva fila de discapacidad
function agregarFilaDiscapacidad() {
    const nuevaFila = `
        <tr class="discapacidad-row">
            <td>
                <select class="form-control tipo-discapacidad" name="tipo_discapacidad[]">
                    <option value="">Seleccione tipo</option>
                    ${window.tiposDiscapacidad ? 
                      window.tiposDiscapacidad.map(t => 
                        `<option value="${t.IdTipo_Discapacidad}">${t.tipo_discapacidad}</option>`
                      ).join('') : ''}
                </select>
            </td>
            <td>
                <input type="text" class="form-control descripcion-discapacidad" 
                       name="descripcion_discapacidad[]" placeholder="Descripción específica">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-eliminar-discapacidad">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#discapacidadesBody').append(nuevaFila);
    actualizarBotonesEliminar();
}

// Función principal para manejar discapacidades
function inicializarDiscapacidades() {
    // Agregar una fila por defecto si no hay ninguna
    if ($('#discapacidadesBody tr').length === 0) {
        agregarFilaDiscapacidad();
    }
    
    // Configurar botón para agregar
    $('#btn-agregar-discapacidad').off('click').on('click', agregarFilaDiscapacidad);
    
    // Manejar eliminación de filas
    $(document).on('click', '.btn-eliminar-discapacidad', function() {
        const $fila = $(this).closest('tr');
        const $todasFilas = $('#discapacidadesBody tr');
        
        if ($todasFilas.length > 1) {
            $fila.remove();
        } else {
            $fila.find('select').val('');
            $fila.find('input').val('');
        }
        actualizarBotonesEliminar();
    });
    
    // Validación en tiempo real
    $(document).on('change input', '.tipo-discapacidad, .descripcion-discapacidad', function() {
        const $fila = $(this).closest('tr');
        const tipo = $fila.find('.tipo-discapacidad').val();
        const $input = $fila.find('.descripcion-discapacidad');
        
        $input.toggleClass('is-invalid', tipo && !$input.val());
    });
    
    actualizarBotonesEliminar();
}

// Actualizar estado de botones eliminar
function actualizarBotonesEliminar() {
    const $filas = $('#discapacidadesBody tr');
    $filas.find('.btn-eliminar-discapacidad').prop('disabled', $filas.length <= 1);
}

// En tu document.ready:
$(document).ready(function() {
    // Cargar tipos al iniciar
    cargarTiposDiscapacidad();
    
    // Inicializar sistema de discapacidades
    inicializarDiscapacidades();
    
    // Reinicializar cuando se abre el modal
    $('#formularioModal').on('shown.bs.modal', inicializarDiscapacidades);
});