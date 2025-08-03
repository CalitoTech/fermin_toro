document.addEventListener('DOMContentLoaded', function() {
    // Obtén la fecha y hora actuales
    const ahora = new Date();
    
    // Formatea la fecha actual en formato YYYY-MM-DD
    const fechaActual = ahora.toISOString().split('T')[0];
    
    // Establece el valor del input de fecha con la fecha actual
    const inputFecha = document.querySelector('input[name="fecha_asistencia"]');
    inputFecha.value = fechaActual;

    // Configura la hora actual
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const horaActual = `${horas}:${minutos}`;

    const inputHoraEntrada = document.querySelector('input[name="hora_entrada"]');
    const inputHoraSalida = document.querySelector('input[name="hora_salida"]');
    
    // Establecer valor nulo para la hora de entrada inicialmente
    inputHoraEntrada.value = '';
    inputHoraSalida.value = '';

    // Añade eventos para validar la hora de entrada
    inputHoraEntrada.addEventListener('change', function() {
        if (this.value !== horaActual) {
            alert('Por favor, solo puedes seleccionar la hora actual.');
            this.value = ''; // Reiniciar a nulo en caso de valor inválido
        }
    });

    // Añade eventos para validar la hora de salida
    inputHoraSalida.addEventListener('change', function() {
        if (this.value !== horaActual) {
            alert('Por favor, solo puedes seleccionar la hora actual.');
            this.value = ''; // Reiniciar a nulo en caso de valor inválido
        }
    });

    // Añade eventos para validar la fecha manualmente
    inputFecha.addEventListener('change', function() {
        if (this.value !== fechaActual) {
            alert('Por favor, solo puedes seleccionar la fecha actual.');
            this.value = fechaActual;
        }
    });
});