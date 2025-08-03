function establecerFechas() {
    const fechaActual = new Date();
    const fechaMaxima = new Date(fechaActual);
    fechaMaxima.setFullYear(fechaActual.getFullYear() - 18); // Fecha máxima: 18 años atrás
    
    const fechaMinima = new Date(fechaActual);
    fechaMinima.setFullYear(fechaActual.getFullYear() - 100); // Fecha mínima: 100 años atrás

    // Formatear fechas a 'YYYY-MM-DD'
    const fechaMaximaFormateada = fechaMaxima.toISOString().split("T")[0];
    const fechaMinimaFormateada = fechaMinima.toISOString().split("T")[0];

    document.getElementById("fecha_nacimiento").setAttribute("max", fechaMaximaFormateada);
    document.getElementById("fecha_nacimiento").setAttribute("min", fechaMinimaFormateada);
}

function validarFechaManual() {
    const fecha_nacimientoInput = document.getElementById("fecha_nacimiento").value;
    const fecha_nacimiento = new Date(fecha_nacimientoInput);
    const fechaActual = new Date();

    // Fecha máxima y mínima
    const fechaMaxima = new Date(fechaActual);
    fechaMaxima.setFullYear(fechaActual.getFullYear() - 18);
    const fechaMinima = new Date(fechaActual);
    fechaMinima.setFullYear(fechaActual.getFullYear() - 100);

    // Validar si la fecha está dentro del rango permitido
    if (fecha_nacimiento > fechaMaxima || fecha_nacimiento < fechaMinima) {
            Swal.fire({
                icon: "error",
                title: "Error...",
                text: "La fecha de nacimiento debe estar entre los 18 y 100 años de edad",
            });
        document.getElementById("fecha_nacimiento").value = ""; // Limpiar el campo si es inválido
    }
}

function validarEdad() {
    const fecha_nacimientoInput = document.getElementById("fecha_nacimiento").value;
    const fecha_nacimiento = new Date(fecha_nacimientoInput);
    const fechaActual = new Date();

    // Calcular la edad
    let edad = fechaActual.getFullYear() - fecha_nacimiento.getFullYear();
    const mes = fechaActual.getMonth() - fecha_nacimiento.getMonth();

    // Ajustar la edad si no ha cumplido años este año
    if (mes < 0 || (mes === 0 && fechaActual.getDate() < fecha_nacimiento.getDate())) {
        edad--;
    }
}

window.onload = establecerFechas; // Establecer fechas al cargar la página