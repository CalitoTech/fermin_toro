// Configuración global
const CONFIG = {
    bloquesEndpoint: '../../../controladores/ObtenerBloquesController.php',
    loginPage: '../../login/login.php',
    jornadaLaboral: { inicio: '07:00', fin: '16:00' }
};

// Función principal para obtener bloques
async function obtenerBloques(maxIntentos = 3) {
    let intentos = 0;
    
    while (intentos < maxIntentos) {
        intentos++;
        try {
            const response = await fetch(`${CONFIG.bloquesEndpoint}?_=${Date.now()}`, {
                headers: { 'Accept': 'application/json' },
                credentials: 'include'
            });

            if (response.redirected && response.url.includes('login')) {
                throw new Error('Redirección a login detectada');
            }

            const data = await response.json();
            
            if (data.redirect) {
                window.location.href = data.redirect;
                return [];
            }
            
            if (data.status === 'success') return data.data || [];
            
            throw new Error(data.message || 'Error en la respuesta del servidor');
            
        } catch (error) {
            if (intentos >= maxIntentos) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * intentos));
        }
    }
    
    return [];
}

// Función para validar horarios
async function validarBloqueHorario(horaInicio, horaFin) {
    try {
        const [inicio, fin] = [moment(horaInicio, 'HH:mm'), moment(horaFin, 'HH:mm')];
        
        if (!inicio.isValid() || !fin.isValid()) return errorResponse('Formato de hora inválido');
        if (fin.isSameOrBefore(inicio)) return errorResponse('La hora final debe ser posterior a la inicial');
        
        const bloques = await obtenerBloques();
        const solapamiento = bloques.find(bloque => {
            const [bInicio, bFin] = [moment(bloque.hora_inicio, 'HH:mm'), moment(bloque.hora_fin, 'HH:mm')];
            return inicio.isBefore(bFin) && fin.isAfter(bInicio);
        });

        if (solapamiento) {
            return errorResponse(
                `El horario se solapa con un bloque existente (${moment(solapamiento.hora_inicio, 'HH:mm').format('h:mm A')} - ${moment(solapamiento.hora_fin, 'HH:mm').format('h:mm A')}`,
                await generarSugerencias()
            );
        }

        return { valido: true };

    } catch (error) {
        return errorResponse(
            `No se pudo verificar disponibilidad. ${error.message}`,
            await generarSugerencias()
        );
    }
}

// Helpers
async function generarSugerencias() {
    const sugerencias = await sugerirHorariosDisponibles();
    if (!sugerencias.length) return '';
    
    return `
        <p class="mt-3">Horarios disponibles:</p>
        <ul class="list-group">
            ${sugerencias.map(s => `<li class="list-group-item">${s.inicio} - ${s.fin}</li>`).join('')}
        </ul>
    `;
}

function errorResponse(mensaje, html = '') {
    return { valido: false, mensaje, html: `<p>${mensaje}</p>${html}` };
}

// Manejador del formulario optimizado
document.querySelector('form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Obtener referencias directas a los inputs
    const horaInicioInput = form.querySelector('[name="hora_inicio"]');
    const horaFinInput = form.querySelector('[name="hora_fin"]');
    
    // Guardar estados originales
    const originalStates = {
        horaInicio: horaInicioInput.disabled,
        horaFin: horaFinInput.disabled,
        submitBtn: submitBtn.disabled
    };
    
    try {
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Validando...';
        submitBtn.disabled = true;
        
        const horaInicio = form.querySelector('[name="hora_inicio"]').value;
        const horaFin = form.querySelector('[name="hora_fin"]').value;
        
        if (!horaInicio || !horaFin) throw new Error('Ambas horas son requeridas');
        
        const inicio = moment(horaInicio, 'h:mm A');
        const fin = moment(horaFin, 'h:mm A');
        
        if (!inicio.isValid() || !fin.isValid()) throw new Error('Formato de hora inválido');
        if (inicio.hours() < 7 || fin.hours() >= 16) throw new Error('El horario debe estar entre 7:00 AM y 4:00 PM');
        if (fin.isSameOrBefore(inicio)) throw new Error('La hora de fin debe ser posterior a la de inicio');
        
        const validacion = await validarBloqueHorario(inicio.format('HH:mm'), fin.format('HH:mm'));
        if (!validacion.valido) {
            await Swal.fire({
                title: 'Conflicto de horario',
                html: validacion.html,
                icon: 'error',
                confirmButtonColor: '#c90000'
            });
            return;
        }
        
        form.submit();
        
    } catch (error) {
        await Swal.fire({
            title: 'Error',
            text: error.message,
            icon: 'error',
            confirmButtonColor: '#c90000'
        });
    } finally {
        // Restaurar estados originales de TODOS los elementos
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = originalStates.submitBtn;
        horaInicioInput.disabled = originalStates.horaInicio;
        horaFinInput.disabled = originalStates.horaFin;
        
        // Fuerza el habilitado como medida adicional
        horaInicioInput.disabled = false;
        horaFinInput.disabled = false;
    }
});

// Función para sugerir horarios (opcional)
async function sugerirHorariosDisponibles() {
    try {
        const bloques = await obtenerBloques();
        const [inicioJornada, finJornada] = [moment(CONFIG.jornadaLaboral.inicio, 'HH:mm'), moment(CONFIG.jornadaLaboral.fin, 'HH:mm')];
        
        if (!bloques.length) return [{ inicio: inicioJornada.format('h:mm A'), fin: finJornada.format('h:mm A') }];
        
        bloques.sort((a, b) => moment(a.hora_inicio, 'HH:mm').diff(moment(b.hora_inicio, 'HH:mm')));
        
        const horarios = [];
        let horaAnterior = inicioJornada;
        
        for (const bloque of bloques) {
            const bloqueInicio = moment(bloque.hora_inicio, 'HH:mm');
            if (bloqueInicio.isAfter(horaAnterior)) {
                horarios.push({ inicio: horaAnterior.format('h:mm A'), fin: bloqueInicio.format('h:mm A') });
            }
            horaAnterior = moment(bloque.hora_fin, 'HH:mm');
        }
        
        if (finJornada.isAfter(horaAnterior)) {
            horarios.push({ inicio: horaAnterior.format('h:mm A'), fin: finJornada.format('h:mm A') });
        }
        
        return horarios;
    } catch {
        return [];
    }
}