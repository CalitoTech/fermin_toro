const formulario = document.getElementById('añadir');
const inputs = document.querySelectorAll('#añadir input');

const expresiones = {
    usuario: /^[a-zA-Z0-9\_\-]{4,20}$/, // Letras, numeros, guion y guion_bajo
    nombre: /^[a-zA-ZÀ-ÿ\s]{3,40}$/, // Letras y espacios, pueden llevar acentos.
    password: /^.{4,20}$/, // 4 a 20 dígitos.
    password3: /^.{4,20}$/, // Validación para la contraseña actual
    correo: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
    telefono: /^\d{7}$/, // 7 números.
    ficha: /^\d{4}$/, // 4 números.
    cedula: /^\d{7,8}$/, // 7 a 8 números.
    carga_familiar: /^[0-9]{1}$/, // 1 espacio, números del 1 al 9.
    fecha_escolar: /^(?=.*[0-9])(?=.*[-\/]).{5,11}$/, // Al menos un número y puede incluir "-" o "/", longitud entre 5 y 11
}

const campos = {
    usuario: false,
    nombre: false,
    password: false,
    password3: false, // Agregar campo para password3
    correo: false,
    telefono: false,
    ficha: false,
    fecha_escolar: false,
    cedula: false,
    carga_familiar: false
}

const validarFormulario = (e) => {
    switch (e.target.name) {
        case "cedula":
            validarCampo(expresiones.cedula, e.target, 'cedula');
        break;
        case "carga_familiar":
            validarCampo(expresiones.carga_familiar, e.target, 'carga_familiar');
        break;
        //case "usuario":
            //validarCampo(expresiones.usuario, e.target, 'usuario');
        //break;
        case "nombre":
            validarCampo(expresiones.nombre, e.target, 'nombre');
        break;
        case "apellido":
            validarCampo(expresiones.nombre, e.target, 'apellido');
        break;
        case "cargo":
            validarCampo(expresiones.nombre, e.target, 'cargo');
        break;
        case "profesion":
            validarCampo(expresiones.nombre, e.target, 'profesion');
        break;
        case "password":
            validarCampo(expresiones.password, e.target, 'password');
            validarPassword2();
        break;
        case "password2":
            validarPassword2();
        break;
        //case "password3": // Validar la contraseña actual
           // validarPassword3(e.target.value);
        //break;
        case "correo":
            validarCampo(expresiones.correo, e.target, 'correo');
        break;
        case "telefono":
            validarCampo(expresiones.telefono, e.target, 'telefono');
        break;
        case "ficha":
            validarCampo(expresiones.ficha, e.target, 'ficha');
        break;
        case "fecha_escolar":
            validarCampo(expresiones.fecha_escolar, e.target, 'fecha_escolar');
        break;
    }
}

const validarCampo = (expresion, input, campo) => {
    if (expresion.test(input.value)) {
        document.getElementById(`grupo__${campo}`).classList.remove('añadir__grupo-incorrecto');
        document.querySelector(`#grupo__${campo} i`).classList.remove('fa-times-circle');
        document.querySelector(`#grupo__${campo} .añadir__input-error`).classList.remove('añadir__input-error-activo');
        campos[campo] = true;
    } else {
        document.getElementById(`grupo__${campo}`).classList.add('añadir__grupo-incorrecto');
        document.querySelector(`#grupo__${campo} i`).classList.add('fa-times-circle');
        document.querySelector(`#grupo__${campo} .añadir__input-error`).classList.add('añadir__input-error-activo');
        campos[campo] = false;
    }
}

const validarPassword2 = () => {
    const inputPassword1 = document.getElementById('password');
    const inputPassword2 = document.getElementById('password2');

    if(inputPassword1.value !== inputPassword2.value){
        document.getElementById(`grupo__password2`).classList.add('añadir__grupo-incorrecto');
        document.getElementById(`grupo__password2`).classList.remove('añadir__grupo-correcto');
        document.querySelector(`#grupo__password2 i`).classList.add('fa-times-circle');
        document.querySelector(`#grupo__password2 i`).classList.remove('fa-check-circle');
        document.querySelector(`#grupo__password2 .añadir__input-error`).classList.add('añadir__input-error-activo');
        campos['password'] = false;
    } else {
        document.getElementById(`grupo__password2`).classList.remove('añadir__grupo-incorrecto');
        document.getElementById(`grupo__password2`).classList.add('añadir__grupo-correcto');
        document.querySelector(`#grupo__password2 i`).classList.remove('fa-times-circle');
        document.querySelector(`#grupo__password2 i`).classList.add('fa-check-circle');
        document.querySelector(`#grupo__password2 .añadir__input-error`).classList.remove('añadir__input-error-activo');
        campos['password'] = true;
    }
}

const validarPassword3 = (password3) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'php/verificar_password.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.valid) {
                document.getElementById(`grupo__password3`).classList.remove('añadir__grupo-incorrecto');
                document.getElementById(`grupo__password3`).classList.add('añadir__grupo-correcto');
                document.querySelector(`#grupo__password3 i`).classList.remove('fa-times-circle');
                document.querySelector(`#grupo__password3 i`).classList.add('fa-check-circle');
                document.querySelector(`#grupo__password3 .añadir__input-error`).classList.remove('añadir__input-error-activo');
                campos['password3'] = true;
            } else {
                document.getElementById(`grupo__password3`).classList.add('añadir__grupo-incorrecto');
                document.getElementById(`grupo__password3`).classList.remove('añadir__grupo-correcto');
                document.querySelector(`#grupo__password3 i`).classList.add('fa-times-circle');
                document.querySelector(`#grupo__password3 i`).classList.remove('fa-check-circle');
                document.querySelector(`#grupo__password3 .añadir__input-error`).classList.add('añadir__input-error-activo');
                campos['password3'] = false;
            }
        }
    }

    xhr.send(`password=${encodeURIComponent(password3)}`);
}

inputs.forEach((input) => {
    input.addEventListener('keyup', validarFormulario);
    input.addEventListener('blur', validarFormulario);
});