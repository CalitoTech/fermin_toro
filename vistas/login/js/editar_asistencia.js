document.addEventListener('DOMContentLoaded', function() {
    // Obtén la fecha y hora actuales
    const ahora = new Date();
    
    // Formatea la fecha actual en formato YYYY-MM-DD
    const fechaActual = ahora.toISOString().split('T')[0];
    
    // Establece el valor del input de fecha con la fecha actual
    const inputFecha = document.querySelector('input[name="fecha_asistencia"]');
    const inputIdPersona = document.querySelector('select[name="IdPersona"]');
    
    // Bloquear el campo de fecha y el ID de persona
    inputFecha.disabled = true;
    inputIdPersona.disabled = true;

    // Configura la hora actual
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const horaActual = `${horas}:${minutos}`;

    const inputHoraEntrada = document.querySelector('input[name="hora_entrada"]');
    const inputHoraSalida = document.querySelector('input[name="hora_salida"]');

    // Guardamos el valor original de la hora de entrada y salida
    const horaEntradaOriginal = inputHoraEntrada.value;
    const horaSalidaOriginal = inputHoraSalida.value;
    
    // Añade eventos para validar la hora de entrada
    inputHoraEntrada.addEventListener('blur', function() {
        if (this.value !== horaActual) {
            alert('Por favor, solo puedes seleccionar la hora actual.');
            this.value = horaEntradaOriginal; // Volver al valor original
        } else {
            // Actualizar el valor original si se selecciona correctamente
            horaEntradaOriginal = this.value;
        }
    });

    // Añade eventos para validar la hora de salida
    inputHoraSalida.addEventListener('blur', function() {
        if (this.value !== horaActual) {
            alert('Por favor, solo puedes seleccionar la hora actual.');
            this.value = horaSalidaOriginal; // Volver al valor original
        } else {
            // Actualizar el valor original si se selecciona correctamente
            horaSalidaOriginal = this.value;
        }
    });

    // Validar si la fecha actual coincide con la fecha guardada
    if (inputFecha.value !== fechaActual) {
        // Si no coincide, habilitar solo el campo de observaciones
        const campoObservaciones = document.querySelector('input[name="observaciones_asistencia"]');
        campoObservaciones.disabled = false; // Habilitar campo de observaciones
        inputHoraEntrada.disabled = true; // Deshabilitar hora de entrada
        inputHoraSalida.disabled = true; // Deshabilitar hora de salida
    } else {
        // Si coincide, habilitar todos los campos que no estén bloqueados
        inputHoraEntrada.disabled = false;
        inputHoraSalida.disabled = false;
    }
});