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

let nivelSeleccionadoGlobal = null;

$('#formularioModal').on('hidden.bs.modal', function() {
    nivelSeleccionadoGlobal = null;
});

function mostrarRequisitos(idNivel) {
    // Mostrar modal y spinner de carga
    $('#requisitosModalBody').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="mt-2">Cargando requisitos...</p>
        </div>
    `);
    $('#requisitosModal').modal('show');

    // Cargar requisitos desde el controlador
    fetch(`../../controladores/RequisitosController.php?idNivel=${idNivel}`)
        .then(respuesta => {
            if (!respuesta.ok) throw new Error('Error al obtener requisitos');
            return respuesta.json();
        })
        .then(requisitos => {
            if (requisitos.error) throw new Error(requisitos.error);

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
                    <table class="tabla-requisitos table table-bordered">
                        <thead>
                            <tr>
                                <th><i class="fas fa-list-ol mr-1"></i> Requisito</th>
                                <th class="text-center"><i class="fas fa-check-circle mr-1"></i> Obligatorio</th>
                            </tr>
                        </thead>
                        <tbody>`;

            requisitos.forEach(r => {
                const esObligatorio = r.obligatorio === 'Sí';
                html += `
                    <tr>
                        <td>
                            <i class="fas ${esObligatorio ? 'fa-exclamation-circle text-danger' : 'fa-info-circle text-info'} mr-2"></i>
                            ${r.requisito}
                        </td>
                        <td class="text-center">
                            <span class="badge-requisito ${esObligatorio ? 'badge-obligatorio' : 'badge-opcional'}">
                                ${r.obligatorio}
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
            console.error('Error al cargar requisitos:', error);
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

function abrirFormulario(idCurso, idNivel) {
    $('#idCursoSeleccionado').val(idCurso);

    // Mostrar/ocultar campos de cédula y teléfono para nivel Inicial (IdNivel == 1)
    if (parseInt(idNivel) === 1) {
        $('#estudianteCedulaContainer').hide();
        $('#estudianteTelefonoContainer').hide();

        // Desactivar validación HTML y limpiar valores
        $('#estudianteCedula').prop('required', false).val('');
        $('#estudianteTelefono').prop('required', false).val('');

        // Guardar el nivel para validaciones posteriores
        $('#idNivelSeleccionado').val(idNivel);
    } else {
        $('#estudianteCedulaContainer').show();
        $('#estudianteTelefonoContainer').show();
        $('#estudianteCedula').prop('required', true);
        $('#estudianteTelefono').prop('required', true);
        $('#idNivelSeleccionado').val(idNivel);
    }


    // Obtener el nombre del curso desde los datos ya cargados en la página
    const cursoSeleccionado = $(`button[onclick="abrirModalImprimir(${idCurso})"]`)
                              .closest('tr')
                              .find('td:first')
                              .text().trim();

    if (cursoSeleccionado) {
        $('#formularioModalLabel').html(`Formulario de Inscripción - ${cursoSeleccionado}`);
        $('#formularioModal').modal('show');
    } else {
        $('#formularioModalLabel').html('Formulario de Inscripción');
        $('#formularioModal').modal('show');
    }
}

function enviarFormulario() {
    const form = document.getElementById('formInscripcion');
    const formData = new FormData(form);
    const tipoRep = $('input[name="tipoRepresentante"]:checked').val();

    // Limpiar validaciones anteriores
    $('.is-invalid').removeClass('is-invalid');

    // 1. Validación de prefijos de teléfono
    let camposFaltantes = [];

    // Validar prefijos de teléfonos
    const prefijosTelefono = [
        {inputId: 'estudianteTelefonoPrefijo', hiddenId: 'estudianteTelefonoPrefijo', nombre: 'Prefijo del teléfono del estudiante', container: '#estudianteTelefonoContainer'},
        {inputId: 'padreTelefonoHabitacionPrefijo', hiddenId: 'padreTelefonoHabitacionPrefijo', nombre: 'Prefijo del teléfono de habitación del padre'},
        {inputId: 'padreCelularPrefijo', hiddenId: 'padreCelularPrefijo', nombre: 'Prefijo del celular del padre'},
        {inputId: 'madreTelefonoHabitacionPrefijo', hiddenId: 'madreTelefonoHabitacionPrefijo', nombre: 'Prefijo del teléfono de habitación de la madre'},
        {inputId: 'madreCelularPrefijo', hiddenId: 'madreCelularPrefijo', nombre: 'Prefijo del celular de la madre'},
        {inputId: 'emergenciaCelularPrefijo', hiddenId: 'emergenciaCelularPrefijo', nombre: 'Prefijo del teléfono de emergencia'}
    ];

    prefijosTelefono.forEach(prefijo => {
        // Si tiene container, verificar si está visible
        if (prefijo.container && $(prefijo.container).is(':hidden')) {
            return; // Saltar validación si el contenedor está oculto
        }

        const hiddenValue = $(`#${prefijo.hiddenId}`).val();
        const inputValue = $(`#${prefijo.inputId}_input`).val();

        // El campo de teléfono asociado
        const telefonoId = prefijo.inputId.replace('Prefijo', '');
        const telefonoValue = $(`#${telefonoId}`).val();

        // Si hay número de teléfono pero no hay prefijo seleccionado
        if (telefonoValue && !hiddenValue) {
            camposFaltantes.push(prefijo.nombre);
            $(`#${prefijo.inputId}_input`).addClass('is-invalid');
        }
    });

    // 2. Validación de campos requeridos básicos

    // Validar sección del estudiante (siempre requerido) pero omitir cédula/telefono si sus contenedores están ocultos
    const camposEstudiante = [
        {id: 'estudianteNombres', nombre: 'Nombres del estudiante', container: null},
        {id: 'estudianteApellidos', nombre: 'Apellidos del estudiante', container: null},
        {id: 'estudianteCedula', nombre: 'Cédula del estudiante', container: '#estudianteCedulaContainer'},
        {id: 'estudianteFechaNacimiento', nombre: 'Fecha de nacimiento del estudiante', container: null},
        {id: 'estudianteLugarNacimiento', nombre: 'Lugar de nacimiento del estudiante', container: null},
        {id: 'estudianteTelefono', nombre: 'Teléfono del estudiante', container: '#estudianteTelefonoContainer'},
        {id: 'estudianteCorreo', nombre: 'Correo electrónico del estudiante', container: null}
    ];

    const idNivelSeleccionado = parseInt($('#idNivelSeleccionado').val() || 0);


    // 🔧 Filtramos solo los que deben validarse
    const camposAValidar = camposEstudiante.filter(campo => {
        // Omitir cédula y teléfono si es nivel inicial
        if (idNivelSeleccionado === 1 && 
            (campo.id === 'estudianteCedula' || campo.id === 'estudianteTelefono')) {
            return false;
        }

        // Omitir si el contenedor está oculto
        if (campo.container && $(campo.container).is(':hidden')) {
            return false;
        }

        return true;
    });

    // ✅ Validamos solo los campos que quedan en la lista filtrada
    camposAValidar.forEach(campo => {
        const valor = $(`#${campo.id}`).val();

        if (!valor) {
            camposFaltantes.push(campo.nombre);
            $(`#${campo.id}`).addClass('is-invalid');
        }
    });
    
    // 2. Validación de datos del padre (siempre requeridos)
    const camposPadre = [
        {id: 'padreNombres', nombre: 'Nombres del padre'},
        {id: 'padreApellidos', nombre: 'Apellidos del padre'},
        {id: 'padreCedula', nombre: 'Cédula del padre'},
        {id: 'padreNacionalidad', nombre: 'Nacionalidad del padre'},
        {id: 'padreOcupacion', nombre: 'Ocupación del padre'},
        {id: 'padreUrbanismo', nombre: 'Urbanismo/Sector del padre'},
        {id: 'padreDireccion', nombre: 'Dirección del padre'},
        {id: 'padreTelefonoHabitacion', nombre: 'Teléfono de habitación del padre'},
        {id: 'padreCelular', nombre: 'Celular del padre'},
        {id: 'padreCorreo', nombre: 'Correo electrónico del padre'},
        {id: 'padreLugarTrabajo', nombre: 'Lugar de trabajo del padre'}
    ];
    
    camposPadre.forEach(campo => {
        if (!$(`#${campo.id}`).val()) {
            camposFaltantes.push(campo.nombre);
            $(`#${campo.id}`).addClass('is-invalid');
            $('#seccionPadre').collapse('show');
        }
    });
    
    // 3. Validación de datos de la madre (siempre requeridos)
    const camposMadre = [
        {id: 'madreNombres', nombre: 'Nombres de la madre'},
        {id: 'madreApellidos', nombre: 'Apellidos de la madre'},
        {id: 'madreCedula', nombre: 'Cédula de la madre'},
        {id: 'madreNacionalidad', nombre: 'Nacionalidad de la madre'},
        {id: 'madreOcupacion', nombre: 'Ocupación de la madre'},
        {id: 'madreUrbanismo', nombre: 'Urbanismo/Sector de la madre'},
        {id: 'madreDireccion', nombre: 'Dirección de la madre'},
        {id: 'madreTelefonoHabitacion', nombre: 'Teléfono de habitación de la madre'},
        {id: 'madreCelular', nombre: 'Celular de la madre'},
        {id: 'madreCorreo', nombre: 'Correo electrónico de la madre'},
        {id: 'madreLugarTrabajo', nombre: 'Lugar de trabajo de la madre'},
        {id: 'emergenciaNombre', nombre: 'Nombre de contacto de emergencia'},
        {id: 'emergenciaParentesco', nombre: 'Parentesco de contacto de emergencia'},
        {id: 'emergenciaCelular', nombre: 'Teléfono de contacto de emergencia'}
    ];
    
    camposMadre.forEach(campo => {
        if (!$(`#${campo.id}`).val()) {
            camposFaltantes.push(campo.nombre);
            $(`#${campo.id}`).addClass('is-invalid');
            $('#seccionMadre').collapse('show');
        }
    });
    
    // 4. Validación del representante legal (si es otro)
    if (tipoRep === 'otro') {
        const camposRepresentante = [
            {id: 'representanteNombres', nombre: 'Nombres del representante legal'},
            {id: 'representanteApellidos', nombre: 'Apellidos del representante legal'},
            {id: 'representanteCedula', nombre: 'Cédula del representante legal'},
            {id: 'representanteNacionalidad', nombre: 'Nacionalidad del representante legal'},
            {id: 'representanteParentesco', nombre: 'Parentesco del representante legal'},
            {id: 'representanteOcupacion', nombre: 'Ocupación del representante legal'},
            {id: 'representanteUrbanismo', nombre: 'Urbanismo/Sector del representante legal'},
            {id: 'representanteDireccion', nombre: 'Dirección del representante legal'},
            {id: 'representanteTelefonoHabitacion', nombre: 'Teléfono de habitación del representante legal'},
            {id: 'representanteCelular', nombre: 'Celular del representante legal'},
            {id: 'representanteCorreo', nombre: 'Correo electrónico del representante legal'},
            {id: 'representanteLugarTrabajo', nombre: 'Lugar de trabajo del representante legal'}
        ];

        camposRepresentante.forEach(campo => {
            if (!$(`#${campo.id}`).val()) {
                camposFaltantes.push(campo.nombre);
                $(`#${campo.id}`).addClass('is-invalid');
                $('#seccionRepresentante').slideDown();
            }
        });

        // Validar prefijos del representante legal
        const prefijosRepresentante = [
            {inputId: 'representanteTelefonoHabitacionPrefijo', hiddenId: 'representanteTelefonoHabitacionPrefijo', nombre: 'Prefijo del teléfono de habitación del representante legal'},
            {inputId: 'representanteCelularPrefijo', hiddenId: 'representanteCelularPrefijo', nombre: 'Prefijo del celular del representante legal'}
        ];

        prefijosRepresentante.forEach(prefijo => {
            const hiddenValue = $(`#${prefijo.hiddenId}`).val();
            const telefonoId = prefijo.inputId.replace('Prefijo', '');
            const telefonoValue = $(`#${telefonoId}`).val();

            if (telefonoValue && !hiddenValue) {
                camposFaltantes.push(prefijo.nombre);
                $(`#${prefijo.inputId}_input`).addClass('is-invalid');
                $('#seccionRepresentante').slideDown();
            }
        });
    }
    
    // 5. Validación de discapacidades
    let discapacidadesValidas = true;
    $('.discapacidad-row').each(function() {
        const tipo = $(this).find('.tipo-discapacidad').val();
        const descripcion = $(this).find('.descripcion-discapacidad').val();
        
        if ((tipo && !descripcion) || (!tipo && descripcion)) {
            $(this).find('.descripcion-discapacidad').addClass('is-invalid');
            discapacidadesValidas = false;
        }

    });

    if (!discapacidadesValidas) {
        camposFaltantes.push('descripciones de discapacidades seleccionadas');
    }

    // 6. Validación de fecha de nacimiento del estudiante (6-18 años)
    const fechaNacimiento = $('#estudianteFechaNacimiento').val();
    if (fechaNacimiento) {
        const hoy = new Date();
        const fechaNac = new Date(fechaNacimiento);
        let edad = hoy.getFullYear() - fechaNac.getFullYear();
        const mes = hoy.getMonth() - fechaNac.getMonth();

        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
            edad--;
        }

        if (edad < 6 || edad > 18) {
            camposFaltantes.push('La edad del estudiante debe estar entre 6 y 18 años');
            $('#estudianteFechaNacimiento').addClass('is-invalid');
        }
    }

    // 7. Validación adicional del contacto de emergencia
    if ($('#emergenciaNombre').val()) {
        const nombreCompleto = $('#emergenciaNombre').val().trim();
        if (nombreCompleto.split(' ').length < 2) {
            camposFaltantes.push('Debe ingresar nombre y apellido para el contacto de emergencia');
            $('#emergenciaNombre').addClass('is-invalid');
        }
    }

    // Mostrar errores si hay campos faltantes
    if (camposFaltantes.length > 0) {
        let mensaje = '<strong>Datos incompletos</strong><br>Por favor complete los siguientes campos requeridos:<br><ul class="text-left">';
        
        // Eliminar duplicados y ordenar
        const camposUnicos = [...new Set(camposFaltantes)];
        camposUnicos.forEach(campo => {
            mensaje += `<li>${campo}</li>`;
        });
        
        mensaje += '</ul>';
        
        showErrorAlert(mensaje);
        
        // Enfocar el primer campo con error
        $('.is-invalid').first().focus();
        return false;
    }

    // Configurar botón de envío
    const btn = $('#btnEnviarFormulario');
    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...');
    btn.prop('disabled', true);

    // Agregar ID del curso
    formData.append('IdCurso', $('#idCursoSeleccionado').val());

    // Enviar formulario
    fetch('http://localhost/mis_apps/fermin_toro/controladores/InscripcionController.php', {
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
                'Código de Seguimiento: ' + data.codigo_inscripcion
            );

            const origen = form.getAttribute('data-origen');

            if (origen === 'modal') {
                // ✅ Si se envió desde un modal, solo lo cerramos
                $('#formularioModal').modal('hide');
            } else if (origen === 'pagina') {
                // ✅ Si se envió desde inscripcion.php, redirigimos
                setTimeout(() => {
                    window.location.href = 'inscripcion.php';
                }, 1500);
            }
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
    // Inicializar acordeón
    $('.form-title').click(function() {
        $(this).toggleClass('collapsed');
    });
    
    // Por defecto, solo el estudiante está abierto
    $('.form-title').not(':first').addClass('collapsed');

    // Validación de cédula al perder foco
    $('#estudianteCedula').on('blur', function() {
        const idNivelSeleccionado = parseInt(nivelSeleccionadoGlobal || 0);
        // Si es nivel inicial, no hacemos verificación
        if (idNivelSeleccionado === 1) return;

        const nacionalidad = $('#estudianteNacionalidad').val();
        const cedula = $(this).val();
        
        if (nacionalidad && cedula) {
            verificarCedulaExistente(cedula, nacionalidad, function(inscrito, estado) {
                if (inscrito) {
                    mostrarAlertaCedulaExistente(estado);
                }
            });
        }
    });

    // Manejo del envío del formulario
    $(document).on('click', '#btnEnviarFormulario', function(e) {
        e.preventDefault(); // Prevenir envío por defecto
        
        const form = document.getElementById('formInscripcion');
    const nacionalidad = $('#estudianteNacionalidad').val();
    let cedula = $('#estudianteCedula').val();
    const idNivelSeleccionado = parseInt($('#idNivelSeleccionado').val() || 0);

        // Si el nivel es inicial (IdNivel == 1), forzamos cedula vacía y no validamos
        if (idNivelSeleccionado === 1) {
            cedula = '';
        }

        // Primero validar cédula si está completa y visible
        if (nacionalidad && cedula) {
            verificarCedulaExistente(cedula, nacionalidad, function(inscrito, estado) {
                if (inscrito) {
                    mostrarAlertaCedulaExistente(estado);
                    return false; // Detener el proceso dentro del callback
                } else {
                    enviarFormulario(); // Proceder con el envío
                }
            });
        } else {
            enviarFormulario(); // Si no hay cédula (o no es visible), proceder con validación normal
        }
    });
}


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

// Función para verificar si la cédula ya existe
function verificarCedulaExistente(cedula, nacionalidad, callback) {
    if (!cedula || !nacionalidad) {
        callback(false);
        return;
    }

    const url = `../../controladores/PersonaController.php?action=verificarCedula&cedula=${encodeURIComponent(cedula)}&idNacionalidad=${encodeURIComponent(nacionalidad)}`;

    fetch(url)
        .then(res => {
            // intentar parsear JSON aunque el status sea 4xx/5xx para ver el error del backend
            return res.json().then(json => {
                if (!res.ok) {
                    // forzar error con el mensaje del backend si existe
                    const msg = json.error || json.message || `HTTP ${res.status}`;
                    throw new Error(msg);
                }
                return json;
            });
        })
        .then(data => {

            // admitir ambos formatos: { existe: bool } o { inscrito: bool }
            const existe = (typeof data.existe !== 'undefined') ? data.existe : (typeof data.inscrito !== 'undefined' ? data.inscrito : false);

            // estado puede venir en distintos campos; normalizamos a string
            const estado = (data.estado ?? data.status ?? data.estado_inscripcion ?? '').toString();

            callback(Boolean(existe), estado);
        })
        .catch(err => {
            console.error('Error al verificar cédula (fetch):', err);
            // Si quieres mostrar un toast de error aquí, descomenta:
            // showErrorAlert('No se pudo verificar la cédula. Por favor intente nuevamente.');
            callback(false);
        });
}

// ---------- Mostrar alerta (Swal primero, toastr fallback) ----------
function mostrarAlertaCedulaExistente(estadoRaw) {
    const estado = (estadoRaw || '').toString().toLowerCase();
    let mensaje = '⚠️ Este estudiante ya existe en el sistema.';

    // heurística amplia para cubrir variaciones ("Pendiente de aprobación", "pendiente", "inscrito", etc.)
    if (estado.includes('inscr') || estado.includes('inscrito')) {
        mensaje = '⚠️ Este estudiante ya está inscrito en el año escolar actual.';
    } else if (estado.includes('pend') || estado.includes('aprob')) {
        mensaje = '⚠️ Este estudiante ya tiene una solicitud pendiente de aprobación.';
    }

    // Mostramos con Swal (más fiable / ya utilizado en tu app). Si Swal no existe, usamos toastr si está disponible.
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            html: mensaje,
            confirmButtonColor: '#c90000',
            confirmButtonText: 'Entendido'
        });
    } else if (typeof toastr !== 'undefined') {
        toastr.warning(mensaje, 'Advertencia', {
            timeOut: 4000,
            iconClass: 'toast-warning',
            positionClass: "toast-top-center"
        });
    } else {
        // último recurso
        alert(mensaje);
    }
}

// ---------- Bindings: blur del input y cambio de nacionalidad ----------
function instalarHandlersCedula() {
    // blur en la cédula
    $('#estudianteCedula').off('blur.cedulaCheck').on('blur.cedulaCheck', function () {
        const cedula = $(this).val().trim();
        const nacionalidad = $('#estudianteNacionalidad').val();
        if (cedula && nacionalidad) {
            verificarCedulaExistente(cedula, nacionalidad, function (existe, estado) {
                if (existe) {
                    mostrarAlertaCedulaExistente(estado);
                }
            });
        }
    });

    // si el usuario cambia la nacionalidad después de escribir la cédula, revalidamos
    $('#estudianteNacionalidad').off('change.cedulaCheck').on('change.cedulaCheck', function () {
        const nacionalidad = $(this).val();
        const cedula = $('#estudianteCedula').val().trim();
        if (cedula && nacionalidad) {
            verificarCedulaExistente(cedula, nacionalidad, function (existe, estado) {
                if (existe) {
                    mostrarAlertaCedulaExistente(estado);
                }
            });
        }
    });
}

// Llamar a la instalación cuando el formulario esté listo (ya tienes inicializarFormulario)
$(document).ready(function () {
    instalarHandlersCedula();
});

// Variable para guardar temporalmente el ID del curso
let cursoSeleccionadoTemporal = null;

/**
 * Muestra el modal informativo y guarda el ID del curso
 */
// Ahora aceptamos (idCurso, idNivel). Si idNivel == 1 -> nivel Inicial
function mostrarInformacionModal(idCurso, idNivel) {
    cursoSeleccionadoTemporal = idCurso;
    nivelSeleccionadoGlobal = idNivel;
    // Guardamos el idNivel temporalmente en el modal para usarlo al abrir el formulario
    $('#informacionModal').data('idNivel', idNivel);
    $('#informacionModal').modal('show');
}

$('#btnContinuarFormulario').on('click', function() {
    if (!cursoSeleccionadoTemporal) {
        showWarningAlert('No se ha seleccionado un curso válido.');
        return;
    }

    // Cerrar el modal informativo y esperar a que esté completamente oculto
    $('#informacionModal').one('hidden.bs.modal', function() {
        // Abrir el formulario después de que el modal informativo se haya cerrado
        // Detectar si el curso corresponde a nivel Inicial (IdNivel == 1)
        const idNivel = $('#informacionModal').data('idNivel') || 0;
        abrirFormulario(cursoSeleccionadoTemporal, nivelSeleccionadoGlobal);
    }).modal('hide');
});

function abrirModalImprimir() {
    $('#imprimirPlanillaModal').modal('show');
}

function imprimirInscripcion(anioEscolar, nacionalidad, nacionalidadTexto, cedula) {
    if (!anioEscolar) {
        showWarningAlert('Debe seleccionar el Año Escolar.');
        return;
    }
    if (!nacionalidad || !cedula) {
        showWarningAlert('Debe ingresar la cédula del estudiante.');
        return;
    }

    fetch(`../../controladores/InscripcionController.php?action=verificar&anio=${anioEscolar}&cedula=${cedula}&nacionalidad=${nacionalidad}`)
        .then(r => r.json())
        .then(data => {
            if (data.existe) {
                const url = `../inscripciones/inscripcion/reporte_inscripcion.php?anio_escolar=${anioEscolar}&nacionalidad=${nacionalidad}&cedula=${cedula}`;
                window.open(url, '_blank');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No inscrito',
                    html: `El estudiante con cédula <b>${nacionalidadTexto}-${cedula}</b> aún no está inscrito.`,
                    confirmButtonColor: '#c90000',
                    confirmButtonText: 'Entendido'
                });
            }
        })
        .catch(err => {
            console.error(err);
            showErrorAlert('Error al verificar inscripción. Intente de nuevo.');
        });
}

$(document).ready(function() {
    $('#btnImprimirPlanilla').on('click', function() {
        const anioEscolar = $('#anioEscolar').val();
        const nacionalidad = $('#nacionalidad').val();
        const nacionalidadTexto = $('#nacionalidad option:selected').text();
        const cedula = $('#documentoEstudiante').val().trim();

        imprimirInscripcion(anioEscolar, nacionalidad, nacionalidadTexto, cedula);
    });
});