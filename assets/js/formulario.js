const formulario = document.getElementById('añadir');
const inputs = document.querySelectorAll('#añadir input');

const expresiones = {
    usuario: /^[a-zA-Z0-9\_\-]{4,20}$/, // Letras, números, guion y guion_bajo
    nombre: /^[a-zA-ZÀ-ÿ\s]{3,40}$/, // Letras y espacios, pueden llevar acentos
    password: /^.{4,20}$/, // 4 a 20 dígitos
    password3: /^.{4,20}$/, // Contraseña actual
    correo: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
    telefono: /^\d{7}$/, // 7 números
    ficha: /^\d{4}$/, // 4 números
    cedula: /^\d{7,8}$/, // 7 a 8 números
    carga_familiar: /^[0-9]{1}$/, // 1 dígito
    fecha_escolar: /^(?=.*[0-9])(?=.*[-\/]).{5,11}$/, // Números, - o /, 5-11 caracteres
};

const campos = {
    usuario: false,
    nombre: false,
    password: false,
    password3: false,
    correo: false,
    telefono: false,
    ficha: false,
    fecha_escolar: false,
    cedula: false,
    carga_familiar: false
};

const validarFormulario = (e) => {
    switch (e.target.name) {
        case "cedula":
            validarCampo(expresiones.cedula, e.target, 'cedula');
            break;
        case "carga_familiar":
            validarCampo(expresiones.carga_familiar, e.target, 'carga_familiar');
            break;
        // case "usuario":
        //     validarCampo(expresiones.usuario, e.target, 'usuario');
        //     break;
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
        // case "password3":
        //     validarCampo(expresiones.password3, e.target, 'password3');
        //     break;
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
};

const validarCampo = (expresion, input, campo) => {
    const grupo = document.getElementById(`grupo__${campo}`);
    const icon = grupo.querySelector('.añadir__validacion-estado');
    const mensajeError = grupo.querySelector('.añadir__input-error');
    const inputGroupText = grupo.querySelector('.input-group-text'); // ← Candado

    if (!icon) {
        console.warn(`No se encontró el icono en grupo__${campo}`);
        return;
    }

    const valor = input.value.trim();

    if (valor === '') {
        // 🟡 Estado neutro
        grupo.classList.remove('añadir__grupo-correcto', 'añadir__grupo-incorrecto');
        icon.className = 'añadir__validacion-estado fas';
        icon.style.color = '';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#f8f9fa';
            inputGroupText.querySelector('i').style.color = '#c90000';
        }
        mensajeError.classList.remove('añadir__input-error-activo');
        campos[campo] = false;
    } else if (expresion.test(valor)) {
        // ✅ Válido
        grupo.classList.remove('añadir__grupo-incorrecto');
        grupo.classList.add('añadir__grupo-correcto');
        icon.className = 'añadir__validacion-estado fas fa-check-circle';
        icon.style.color = '#1ed12d';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#d4edda';
            inputGroupText.querySelector('i').style.color = '#155724';
        }
        mensajeError.classList.remove('añadir__input-error-activo');
        campos[campo] = true;
    } else {
        // ❌ Inválido
        grupo.classList.remove('añadir__grupo-correcto');
        grupo.classList.add('añadir__grupo-incorrecto');
        icon.className = 'añadir__validacion-estado fas fa-times-circle';
        icon.style.color = '#bb2929';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#f8d7da';
            inputGroupText.querySelector('i').style.color = '#721c24';
        }
        mensajeError.classList.add('añadir__input-error-activo');
        campos[campo] = false;
    }
};

const validarPassword2 = () => {
    const inputPassword1 = document.getElementById('password');
    const inputPassword2 = document.getElementById('password2');
    const grupo = document.getElementById('grupo__password2');
    const icon = grupo.querySelector('.añadir__validacion-estado');
    const mensajeError = grupo.querySelector('.añadir__input-error');
    const inputGroupText = grupo.querySelector('.input-group-text');

    const valor1 = inputPassword1.value.trim();
    const valor2 = inputPassword2.value.trim();

    if (valor1 === '' && valor2 === '') {
        // 🟡 Neutro
        grupo.classList.remove('añadir__grupo-correcto', 'añadir__grupo-incorrecto');
        icon.className = 'añadir__validacion-estado fas';
        icon.style.color = '';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#f8f9fa';
            inputGroupText.querySelector('i').style.color = '#c90000';
        }
        mensajeError.classList.remove('añadir__input-error-activo');
        campos['password'] = false;
    } else if (valor2 === '') {
        // ❌ Vacío
        grupo.classList.remove('añadir__grupo-correcto');
        grupo.classList.add('añadir__grupo-incorrecto');
        icon.className = 'añadir__validacion-estado fas fa-times-circle';
        icon.style.color = '#bb2929';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#f8d7da';
            inputGroupText.querySelector('i').style.color = '#721c24';
        }
        mensajeError.classList.add('añadir__input-error-activo');
        campos['password'] = false;
    } else if (valor1 === valor2) {
        // ✅ Coinciden
        grupo.classList.remove('añadir__grupo-incorrecto');
        grupo.classList.add('añadir__grupo-correcto');
        icon.className = 'añadir__validacion-estado fas fa-check-circle';
        icon.style.color = '#1ed12d';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#d4edda';
            inputGroupText.querySelector('i').style.color = '#155724';
        }
        mensajeError.classList.remove('añadir__input-error-activo');
        campos['password'] = true;
    } else {
        // ❌ No coinciden
        grupo.classList.remove('añadir__grupo-correcto');
        grupo.classList.add('añadir__grupo-incorrecto');
        icon.className = 'añadir__validacion-estado fas fa-times-circle';
        icon.style.color = '#bb2929';
        if (inputGroupText) {
            inputGroupText.style.backgroundColor = '#f8d7da';
            inputGroupText.querySelector('i').style.color = '#721c24';
        }
        mensajeError.classList.add('añadir__input-error-activo');
        campos['password'] = false;
    }
};

// Asignar eventos
inputs.forEach((input) => {
    input.addEventListener('keyup', validarFormulario);
    input.addEventListener('blur', validarFormulario);
});