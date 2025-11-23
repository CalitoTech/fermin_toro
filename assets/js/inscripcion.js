// ==============================
// Gestión de estado de inscripción (status bar estilo Odoo)
// ==============================

function manejarStatusBar(idInscripcion, idInscrito) {
    const steps = document.querySelectorAll('.status-step:not(.disabled)');
    const estaEnModoRechazado = document.querySelector('.status-step.rejected-mode');

    steps.forEach(step => {
        step.addEventListener('click', async function () {
            if (!this.dataset.nombre || this.dataset.nombre === 'undefined') {
                console.error('Nombre de estado no definido');
                return;
            }

            const nuevoId = parseInt(this.dataset.id);
            const nuevoNombre = this.dataset.nombre;
            const stepActivo = document.querySelector('.status-step.active');

            if (!stepActivo) {
                console.error('No se encontró step activo');
                return;
            }

            const actualId = parseInt(stepActivo.dataset.id);

            // Prevenir cambio de estado si no es el año escolar activo
            if (typeof ES_ANO_ESCOLAR_ACTIVO !== 'undefined' && !ES_ANO_ESCOLAR_ACTIVO) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Año escolar inactivo',
                    html: `
                        <div class="text-center">
                            <p>Esta inscripción pertenece a un año escolar diferente al activo.</p>
                            <p>No se pueden realizar cambios de estado en inscripciones de años escolares anteriores.</p>
                        </div>
                    `,
                    confirmButtonColor: '#c90000',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Prevenir clic en el estado actual
            if (nuevoId === actualId) {
                Swal.fire({
                    icon: 'info',
                    title: 'Estado actual',
                    html: `
                        <div class="text-center">
                            <i class="bi bi-info-circle-fill fa-2x mb-3 text-primary"></i>
                            <p>La inscripción ya se encuentra en estado:<br>
                            <strong>"${nuevoNombre}"</strong></p>
                            <small class="text-muted">Selecciona un estado diferente para cambiar</small>
                        </div>
                    `,
                    confirmButtonColor: '#c90000',
                    confirmButtonText: 'Entendido',
                    showCloseButton: true
                });
                return;
            }

            // Prevenir clic en modo rechazado
            if (estaEnModoRechazado) {
                Swal.fire({
                    icon: 'info',
                    title: 'Estado actual',
                    text: 'La inscripción ya está rechazada. No se pueden realizar más cambios.',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            // Validaciones
            if (actualId === idInscrito && nuevoNombre.toLowerCase().includes('rechazado')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción no permitida',
                    text: 'No puedes rechazar una inscripción ya inscrita.',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            if (nuevoId < actualId && !nuevoNombre.toLowerCase().includes('rechazado')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción no permitida',
                    text: 'No puedes retroceder a un estado anterior.',
                    confirmButtonColor: '#c90000'
                });
                return;
            }

            // ========================
            // Si es cambio a "Inscrito", mostrar modal de pago
            // ========================
            if (nuevoId === idInscrito) {
                // Mostrar modal de validación de pago
                const modalPago = new bootstrap.Modal(document.getElementById('modalValidarPago'));
                modalPago.show();
                return; // No continuar con el flujo normal
            }

            // ========================
            // Para otros estados, flujo normal
            // ========================
            let titulo = '¿Cambiar estado?';
            let texto = `¿Deseas cambiar el estado a "${nuevoNombre}"?`;

            Swal.fire({
                title: titulo,
                html: `<div style="text-align:center">${texto}</div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#c90000',
                cancelButtonColor: '#6c757d'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'cambiarStatus');
                    formData.append('idInscripcion', idInscripcion);
                    formData.append('nuevoStatus', nuevoId);

                    fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            let mensaje = data.message || 'Estado actualizado correctamente';

                            if (data.cambioAutomatico) {
                                mensaje += '\nSe asignó automáticamente a la sección recomendada';
                            }

                            if (data.alertaCapacidad && data.alertaCapacidad.trim() !== '') {
                                mensaje += '\n\n⚠️ ' + data.alertaCapacidad;
                            }

                            Swal.fire({
                                icon: 'success',
                                title: '¡Hecho!',
                                text: mensaje,
                                timer: 2000,
                                showConfirmButton: true
                            });

                            actualizarBarraStatus(nuevoId);

                            if (nuevoNombre.toLowerCase().includes('rechazado')) {
                                setTimeout(() => {
                                    transformarAModoRechazado();
                                }, 100);
                            }

                            const cambiosMayores = nuevoNombre.toLowerCase().includes('rechazado');

                            if (cambiosMayores) {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            }
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
            });
        });
    });

    // Prevenir clics en modo rechazado
    if (estaEnModoRechazado) {
        const rejectedStep = document.querySelector('.status-step.rejected-mode');
        rejectedStep.style.cursor = 'default';
        rejectedStep.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            Swal.fire({
                icon: 'info',
                title: 'Inscripción rechazada',
                text: 'Esta inscripción ha sido rechazada. No se pueden realizar más cambios de estado.',
                confirmButtonColor: '#c90000'
            });
        });
    }
}


// Función para transformar la barra a modo rechazado
function transformarAModoRechazado() {
    const statusBar = document.querySelector('.status-bar');
    const todosStatus = document.querySelectorAll('.status-step');
    const lineas = document.querySelectorAll('.status-line');
    
    // Ocultar todos los elementos
    todosStatus.forEach(step => {
        if (!step.classList.contains('rejected-mode')) {
            step.style.display = 'none';
        }
    });
    
    lineas.forEach(linea => {
        linea.style.display = 'none';
    });
    
    // Crear o mostrar el modo rechazado
    let rejectedStep = document.querySelector('.status-step.rejected-mode');
    
    if (!rejectedStep) {
        rejectedStep = document.createElement('div');
        rejectedStep.className = 'status-step rejected-mode active';
        rejectedStep.setAttribute('data-id', '11');
        rejectedStep.setAttribute('data-nombre', 'Rechazada');
        rejectedStep.innerHTML = `
            <span class="status-icon">
                <i class="fas fa-times-circle"></i>
            </span>
            <span class="status-label">Rechazada</span>
            <div class="rejected-message">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Inscripción rechazada
            </div>
        `;
        statusBar.appendChild(rejectedStep);
    } else {
        rejectedStep.style.display = 'flex';
    }
    
    // Centrar y ajustar estilos
    statusBar.style.justifyContent = 'center';
    statusBar.classList.add('rejected-mode-active');
    
    // Deshabilitar futuros clics
    statusBar.style.pointerEvents = 'none';
}

// Función auxiliar para actualizar la barra
function actualizarBarraStatus(nuevoId) {
    document.querySelectorAll('.status-step').forEach(step => {
        // Saltar si es modo rechazado
        if (step.classList.contains('rejected-mode')) return;
        
        const stepId = parseInt(step.dataset.id);
        const icon = step.querySelector('.status-icon i');
        
        // Resetear
        step.classList.remove('active', 'completed');
        if (icon) icon.style.color = '';
        
        // Aplicar nuevo estado
        if (stepId === nuevoId) {
            step.classList.add('active');
            if (icon) icon.style.color = 'white';
        } else if (stepId < nuevoId) {
            step.classList.add('completed');
            if (icon) icon.style.color = '#28a745';
        }
        
        // Actualizar iconos
        if (icon) {
            icon.className = stepId <= nuevoId ? 'fas fa-check-circle' : 'fas fa-circle';
        }
        
        // Ocultar rechazado si ahora está inscrito
        if (nuevoId === 11) { // ID_INSCRITO
            const rechazadoStep = document.querySelector('.status-step[data-id="12"]');
            if (rechazadoStep) {
                rechazadoStep.style.display = 'none';
                const lineAfter = rechazadoStep.nextElementSibling;
                if (lineAfter && lineAfter.classList.contains('status-line')) {
                    lineAfter.style.display = 'none';
                }
            }
        }
    });
    
    // Forzar repaint para asegurar las transiciones
    document.body.offsetHeight;
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

    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.requisito-checkbox:checked').length;
        const total = checkboxes.length;
        contador.textContent = `${seleccionados}/${total} seleccionados`;
    }

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

    // Asegurar estado inicial correcto: ocultar aviso y fijar estado original
    if (guardarCambios) {
        guardarCambios.style.display = 'none';
    }

    checkboxes.forEach(checkbox => {
        checkbox.setAttribute('data-original', checkbox.checked ? 1 : 0);
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            actualizarContador();
            verificarCambios();
        });
    });

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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    actualizarContador();
    // Evaluar inmediatamente si hay cambios (no debería haberlos al cargar)
    verificarCambios();
}

// ==============================
// Gestión de cambio de sección
// ==============================
function manejarCambioSeccion(idInscripcion) {
    const btnCambiarSeccion = document.getElementById('btn-cambiar-seccion');
    const selectorSeccion = document.getElementById('selector-seccion');
    const selectNuevaSeccion = document.getElementById('select-nueva-seccion');
    const infoSeccion = document.getElementById('info-seccion');
    const btnConfirmarCambio = document.getElementById('btn-confirmar-cambio');
    const btnCancelarCambio = document.getElementById('btn-cancelar-cambio');
    const textoSeccionActual = document.getElementById('texto-seccion-actual');

    if (!btnCambiarSeccion) return;

    // Mostrar/ocultar selector
    btnCambiarSeccion.addEventListener('click', function() {
        selectorSeccion.style.display = selectorSeccion.style.display === 'none' ? 'block' : 'none';
    });

    // Cancelar cambio
    btnCancelarCambio.addEventListener('click', function() {
        selectorSeccion.style.display = 'none';
        selectNuevaSeccion.value = '';
        infoSeccion.style.display = 'none';
    });

    // Mostrar información de la sección seleccionada
        selectNuevaSeccion.addEventListener('change', function() {
        if (this.value) {
            const option = this.options[this.selectedIndex];
            const capacidad = option.getAttribute('data-capacidad') || '0';
            const estudiantes = option.getAttribute('data-estudiantes') || '0';
            const urbanismo = option.getAttribute('data-urbanismo') || '0';
            
            document.getElementById('info-capacidad').textContent = capacidad;
            document.getElementById('info-ocupacion').textContent = `${estudiantes}/${capacidad}`;
            document.getElementById('info-urbanismo').textContent = urbanismo;
            
            infoSeccion.style.display = 'block';
        } else {
            infoSeccion.style.display = 'none';
        }
    });

    // Confirmar cambio de sección
    btnConfirmarCambio.addEventListener('click', function() {
        const nuevaSeccionId = selectNuevaSeccion.value;
        const nuevaSeccionTexto = selectNuevaSeccion.options[selectNuevaSeccion.selectedIndex].text;
        
        if (!nuevaSeccionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Selección requerida',
                text: 'Por favor selecciona una sección',
                confirmButtonColor: '#c90000'
            });
            return;
        }

        Swal.fire({
            title: '¿Cambiar sección?',
            html: `¿Estás seguro de cambiar la sección a:<br><strong>${nuevaSeccionTexto}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#c90000',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                cambiarSeccionInscripcion(idInscripcion, nuevaSeccionId, nuevaSeccionTexto);
            }
        });
    });
}

// Función para cambiar la sección via AJAX
function cambiarSeccionInscripcion(idInscripcion, nuevaSeccionId, nuevaSeccionTexto) {
    Swal.fire({
        title: 'Actualizando...',
        text: 'Cambiando sección',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const formData = new FormData();
    formData.append('action', 'cambiarSeccion');
    formData.append('idInscripcion', idInscripcion);
    formData.append('nuevaSeccion', nuevaSeccionId);

    fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            // Actualizar la UI
            document.getElementById('texto-seccion-actual').textContent = nuevaSeccionTexto.split(' - ')[0];
            document.getElementById('selector-seccion').style.display = 'none';
            document.getElementById('select-nueva-seccion').value = '';
            document.getElementById('info-seccion').style.display = 'none';

            Swal.fire({
                icon: 'success',
                title: '¡Sección cambiada!',
                text: 'La sección se ha actualizado correctamente',
                timer: 2000,
                showConfirmButton: true
            });
        } else {
            throw new Error(data.message || 'Error al cambiar sección');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    });
}

// ==============================
// Gestión de validación de pago
// ==============================
function manejarValidacionPago(idInscripcion, idInscrito) {
    const btnConfirmarPago = document.getElementById('btn-confirmar-pago');
    const inputCodigoPago = document.getElementById('codigo-pago');
    const modalElement = document.getElementById('modalValidarPago');

    if (!btnConfirmarPago || !inputCodigoPago || !modalElement) return;

    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);

    // Limpiar input al cerrar modal
    modalElement.addEventListener('hidden.bs.modal', function () {
        inputCodigoPago.value = '';
        inputCodigoPago.classList.remove('is-invalid');
    });

    // Validar al presionar Enter en el input
    inputCodigoPago.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnConfirmarPago.click();
        }
    });

    // Confirmar pago
    btnConfirmarPago.addEventListener('click', async function() {
        const codigoPago = inputCodigoPago.value.trim();

        // Validar que no esté vacío
        if (!codigoPago) {
            inputCodigoPago.classList.add('is-invalid');
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Debe ingresar el código de pago',
                confirmButtonColor: '#c90000'
            });
            return;
        }

        // Validar longitud mínima
        if (codigoPago.length < 5) {
            inputCodigoPago.classList.add('is-invalid');
            Swal.fire({
                icon: 'warning',
                title: 'Código inválido',
                text: 'El código de pago debe tener al menos 5 caracteres',
                confirmButtonColor: '#c90000'
            });
            return;
        }

        inputCodigoPago.classList.remove('is-invalid');

        // Cerrar modal y mostrar loading
        modal.hide();

        Swal.fire({
            title: 'Verificando...',
            html: `
                <div class="text-center">
                    <p>Verificando información del estudiante</p>
                    <p class="text-muted small">Por favor espere...</p>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            // Primero verificar si es repitiente
            const responseRepitiente = await fetch(`/mis_apps/fermin_toro/controladores/InscripcionController.php?action=verificarRepitiente&idInscripcion=${idInscripcion}`);
            const dataRepitiente = await responseRepitiente.json();

            if (dataRepitiente.success && dataRepitiente.esRepitiente) {
                // Es posible repitiente, preguntar al usuario
                Swal.fire({
                    icon: 'question',
                    title: '¿Estudiante Repitiente?',
                    html: `
                        <div class="text-center">
                            <p><strong>${dataRepitiente.estudiante}</strong> estuvo inscrito en <strong>${dataRepitiente.curso}</strong> durante el año escolar <strong>${dataRepitiente.anioAnterior}</strong>.</p>
                            <p class="text-muted">¿El estudiante está repitiendo este curso?</p>
                            <hr>
                            <p class="small text-info"><i class="fas fa-info-circle me-1"></i>Si marca "Sí, repite", el estudiante será asignado a una sección diferente con menos estudiantes.</p>
                        </div>
                    `,
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fas fa-redo me-1"></i>Sí, repite',
                    denyButtonText: '<i class="fas fa-arrow-up me-1"></i>No, avanza',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    denyButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then(async (result) => {
                    if (result.isDismissed) {
                        // Cancelado, reabrir modal de pago
                        inputCodigoPago.value = codigoPago;
                        modal.show();
                        return;
                    }

                    const repite = result.isConfirmed;
                    await procesarInscripcionConRepitiente(idInscripcion, codigoPago, repite, modal, inputCodigoPago);
                });
            } else {
                // No es repitiente, proceder normalmente
                await procesarInscripcionNormal(idInscripcion, codigoPago, modal, inputCodigoPago);
            }

        } catch (error) {
            console.error('Error al verificar repitiente:', error);
            // Si falla la verificación de repitiente, continuar con inscripción normal
            await procesarInscripcionNormal(idInscripcion, codigoPago, modal, inputCodigoPago);
        }
    });
}

// Procesar inscripción con verificación de repitiente
async function procesarInscripcionConRepitiente(idInscripcion, codigoPago, repite, modal, inputCodigoPago) {
    Swal.fire({
        title: 'Procesando inscripción...',
        html: `
            <div class="text-center">
                <p>Validando pago y completando inscripción</p>
                <p class="text-muted small">Por favor espere...</p>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        // Primero validar el pago
        const formDataPago = new FormData();
        formDataPago.append('action', 'validarPagoEInscribir');
        formDataPago.append('idInscripcion', idInscripcion);
        formDataPago.append('codigoPago', codigoPago);

        const responsePago = await fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
            method: 'POST',
            body: formDataPago
        });

        const dataPago = await responsePago.json();

        if (!dataPago.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: dataPago.message || 'No se pudo validar el código de pago',
                confirmButtonColor: '#c90000'
            }).then(() => {
                inputCodigoPago.value = codigoPago;
                modal.show();
            });
            return;
        }

        // Si el pago es válido y el estudiante repite, confirmar repitiente
        if (repite) {
            const formDataRepite = new FormData();
            formDataRepite.append('action', 'confirmarRepitiente');
            formDataRepite.append('idInscripcion', idInscripcion);
            formDataRepite.append('repite', 'true');

            const responseRepite = await fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
                method: 'POST',
                body: formDataRepite
            });

            const dataRepite = await responseRepite.json();

            if (dataRepite.success) {
                let mensajeExtra = '';
                if (dataRepite.cambioSeccion && dataRepite.nuevaSeccion) {
                    mensajeExtra = `<br><span class="text-info"><i class="fas fa-exchange-alt me-1"></i>Nueva sección asignada: <strong>${dataRepite.nuevaSeccion}</strong></span>`;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Inscripción Completada',
                    html: `
                        <div class="text-center">
                            <p>${dataRepite.message}</p>
                            <p class="text-muted small mt-2">Código de pago: <strong>${codigoPago}</strong></p>
                            ${mensajeExtra}
                        </div>
                    `,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(dataRepite.message);
            }
        } else {
            // No repite, mostrar éxito normal
            mostrarExitoInscripcion(dataPago, codigoPago);
        }

    } catch (error) {
        console.error('Error al procesar inscripción:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo completar la inscripción',
            confirmButtonColor: '#c90000'
        }).then(() => {
            inputCodigoPago.value = codigoPago;
            modal.show();
        });
    }
}

// Procesar inscripción normal (sin verificación de repitiente)
async function procesarInscripcionNormal(idInscripcion, codigoPago, modal, inputCodigoPago) {
    Swal.fire({
        title: 'Validando pago...',
        html: `
            <div class="text-center">
                <p>Verificando código de pago en el sistema</p>
                <p class="text-muted small">Por favor espere...</p>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('action', 'validarPagoEInscribir');
        formData.append('idInscripcion', idInscripcion);
        formData.append('codigoPago', codigoPago);

        const response = await fetch('/mis_apps/fermin_toro/controladores/InscripcionController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarExitoInscripcion(data, codigoPago);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: data.message || 'No se pudo validar el código de pago',
                confirmButtonColor: '#c90000'
            }).then(() => {
                inputCodigoPago.value = codigoPago;
                modal.show();
            });
        }

    } catch (error) {
        console.error('Error al validar pago:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Por favor intente nuevamente.',
            confirmButtonColor: '#c90000'
        }).then(() => {
            inputCodigoPago.value = codigoPago;
            modal.show();
        });
    }
}

// Mostrar mensaje de éxito de inscripción
function mostrarExitoInscripcion(data, codigoPago) {
    let mensaje = data.message || 'Inscripción completada exitosamente';

    if (data.cambioAutomatico) {
        mensaje += '\nSe asignó automáticamente a la sección recomendada';
    }

    if (data.alertaCapacidad && data.alertaCapacidad.trim() !== '') {
        mensaje += '\n\n' + data.alertaCapacidad;
    }

    Swal.fire({
        icon: 'success',
        title: 'Inscripción Completada',
        html: `
            <div class="text-center">
                <p>${mensaje}</p>
                <p class="text-muted small mt-2">Código de pago: <strong>${data.codigoPago || codigoPago}</strong></p>
            </div>
        `,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        window.location.reload();
    });
}

// ==============================
// Gestión de historial de inscripción (fusionado con última modificación)
// ==============================
function manejarHistorial(idInscripcion) {
    const btnToggleHistorial = document.getElementById('btn-toggle-historial');
    const historialContainer = document.getElementById('historial-container');
    const historialContent = document.getElementById('historial-content');

    if (!btnToggleHistorial || !historialContainer) return;

    let historialCargado = false;

    btnToggleHistorial.addEventListener('click', async function() {
        const isExpanded = historialContainer.classList.contains('show');

        if (!isExpanded && !historialCargado) {
            // Cargar historial si no se ha cargado
            historialContent.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</div>';

            try {
                const response = await fetch(`/mis_apps/fermin_toro/controladores/InscripcionController.php?action=obtenerHistorial&idInscripcion=${idInscripcion}`);
                const data = await response.json();

                if (data.success && data.historial.length > 0) {
                    let html = '<div class="timeline-historial">';
                    data.historial.forEach((item, index) => {
                        const fecha = new Date(item.fecha_cambio);
                        const fechaFormateada = fecha.toLocaleDateString('es-VE', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        html += `
                            <div class="timeline-item ${index === 0 ? 'timeline-item-latest' : ''}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-date">${fechaFormateada}</span>
                                        <span class="timeline-user">${item.usuario_nombre} ${item.usuario_apellido}</span>
                                    </div>
                                    <div class="timeline-body">
                                        <p class="mb-0">${item.descripcion || 'Cambio realizado'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    historialContent.innerHTML = html;
                } else {
                    historialContent.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-history me-2"></i>No hay cambios registrados en el historial</div>';
                }

                historialCargado = true;
            } catch (error) {
                console.error('Error al cargar historial:', error);
                historialContent.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar el historial</div>';
            }
        }

        // Toggle collapse y clase expanded para estilos
        if (isExpanded) {
            historialContainer.classList.remove('show');
            btnToggleHistorial.classList.remove('expanded');
        } else {
            historialContainer.classList.add('show');
            btnToggleHistorial.classList.add('expanded');
        }
    });
}

// ==============================
// Inicialización global
// ==============================
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ID_INSCRIPCION !== 'undefined' && typeof ID_INSCRITO !== 'undefined') {
        manejarStatusBar(ID_INSCRIPCION, ID_INSCRITO);
        manejarRequisitos(ID_INSCRIPCION);
        manejarCambioSeccion(ID_INSCRIPCION);
        manejarValidacionPago(ID_INSCRIPCION, ID_INSCRITO);
        manejarHistorial(ID_INSCRIPCION);
    } else {
        console.error('Variables no definidas:', {
            ID_INSCRIPCION: typeof ID_INSCRIPCION,
            ID_INSCRITO: typeof ID_INSCRITO
        });
    }
});