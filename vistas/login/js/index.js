const fechaNacimiento = document.getElementById("fecha_nacimiento");
const edad = document.getElementById("edad");

const calcularEdad = (fechaNacimiento) => {
    const fechaActual = new Date();
    const anoActual = parseInt(fechaActual.getFullYear());
    const mesActual = parseInt(fechaActual.getMonth()) + 1;
    const diaActual = parseInt(fechaActual.getDate());

    const anoNacimiento = parseInt(String(fechaNacimiento).substring(0, 4));
    const mesNacimiento = parseInt(String(fechaNacimiento).substring(5, 7));
    const diaNacimiento = parseInt(String(fechaNacimiento).substring(8, 10));

    let edad = anoActual - anoNacimiento;
    if (mesActual < mesNacimiento || (mesActual === mesNacimiento && diaActual < diaNacimiento)) {
        edad--;
    }
    return edad;
};

const calcularTiempo = (fechaIngreso) => {
    const fechaActual = new Date();
    const anoIngreso = parseInt(String(fechaIngreso).substring(0, 4));
    const mesIngreso = parseInt(String(fechaIngreso).substring(5, 7));
    const diaIngreso = parseInt(String(fechaIngreso).substring(8, 10));

    let anos = fechaActual.getFullYear() - anoIngreso;
    let meses = fechaActual.getMonth() - mesIngreso + 1;
    let dias = fechaActual.getDate() - diaIngreso;

    if (dias < 0) {
        meses--;
        const ultimoDiaMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 0).getDate();
        dias += ultimoDiaMes;
    }

    if (meses < 0) {
        anos--;
        meses += 12;
    }

    // Asegurarse de que no haya valores negativos
    anos = Math.max(0, anos);
    meses = Math.max(0, meses);
    dias = Math.max(0, dias);

    return `${anos} años, ${meses} meses y ${dias} días`;
};

window.addEventListener('load', function () {
    fechaNacimiento.addEventListener('change', function () {
        if (this.value) {
            edad.innerText = `La edad es: ${calcularEdad(this.value)} años`;
        }
    });
});
