function onlyText(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key).toLowerCase();
    let letras = " abcdefghijklmnñopqrstuvwxyzáéíóúÁÉÍÓÚ";
    let especiales = [8, 37, 38, 46]; 
    let teclado_especial = false;
    for (let i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
        if (letras.indexOf(teclado) == -1 && !teclado_especial) {
            return false; 
        }
    }
}

function onlyText2(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key).toLowerCase();
    let letras = " abcdefghijklmnñopqrstuvwxyzáéíóúÁÉÍÓÚ-,./'";
    let especiales = [8, 37, 38, 46]; 
    let teclado_especial = false;
    for (let i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
        if (letras.indexOf(teclado) == -1 && !teclado_especial) {
            return false; 
        }
    }
}

function onlyText3(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key).toLowerCase();
    let letras = " abcdefghijklmnñopqrstuvwxyzáéíóúÁÉÍÓÚ-_0123456789";
    let especiales = [8, 37, 38, 46]; 
    let teclado_especial = false;
    for (let i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
        if (letras.indexOf(teclado) == -1 && !teclado_especial) {
            return false; 
        }
    }
}

function onlyNumber(e) {
    key1 = e.keyCode || e.which;
    teclado1 = String.fromCharCode(key1);
    numeros = '0123456789';
    especiales1 = "8-37-38-46"; 
    teclado_especial1 = false;

    for (i in especiales1) {
        if (key1 == especiales1[i]) {
            teclado_especial1 = true;
            break;
        }
    }
    if (numeros.indexOf(teclado1) == -1 && !teclado_especial1) {
        return false;
    }
}

function onlyNumber2(e) {
    key1 = e.keyCode || e.which;
    teclado1 = String.fromCharCode(key1);
    numeros = '0123456789+-';
    especiales1 = "8-37-38-46"; 
    teclado_especial1 = false;

    for (i in especiales1) {
        if (key1 == especiales1[i]) {
            teclado_especial1 = true;
            break;
        }
    }
    if (numeros.indexOf(teclado1) == -1 && !teclado_especial1) {
        return false;
    }
}

function fechita(e) {
    key2 = e.keyCode || e.which;
    teclado2 = String.fromCharCode(key2);
    numeros1 = ' 0123456789-/';
    especiales2="8-37-38-46"; 
    teclado_especial2 = false;
    
    for ( i in especiales2 ) {
        if ( key2 == especiales2[i] ) {
            teclado_especial2 = true;
            break;
        }
    }
        if (numeros1.indexOf(teclado2) == -1 && !teclado_especial2) {
            return false;
        }
    
    };

    function formatearTexto() {
        const input = document.getElementById('texto');
        let palabras = input.value.split(' ');
        palabras = palabras.map(palabra => {
            if (palabra.length > 0) {
                return palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase();
            }
            return '';
        });
        input.value = palabras.join(' ');
    }

    function formatearTexto1() {
        const input = document.getElementById('nombre');
        let palabras = input.value.split(' ');
        palabras = palabras.map(palabra => {
            if (palabra.length > 0) {
                return palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase();
            }
            return '';
        });
        input.value = palabras.join(' ');
    }

    function formatearTexto2() {
        const input = document.getElementById('apellido');
        let palabras = input.value.split(' ');
        palabras = palabras.map(palabra => {
            if (palabra.length > 0) {
                return palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase();
            }
            return '';
        });
        input.value = palabras.join(' ');
    }

    function mayusculas(e) {
        e.value = e.value.toUpperCase();
    }