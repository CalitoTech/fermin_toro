<?php
/**
 * Clase utilitaria para validaciones del lado del servidor
 * Proporciona métodos estáticos para validar datos de formularios
 */
class Validador {

    /**
     * Valida que un valor solo contenga letras y espacios (con acentos y ñ)
     */
    public static function soloLetras($valor) {
        return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u', $valor);
    }

    /**
     * Valida que un valor solo contenga números
     */
    public static function soloNumeros($valor) {
        return preg_match('/^[0-9]+$/', $valor);
    }

    /**
     * Valida formato de email
     */
    public static function emailValido($valor) {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida longitud de cadena
     */
    public static function longitudValida($valor, $min, $max) {
        $len = mb_strlen($valor, 'UTF-8');
        return $len >= $min && $len <= $max;
    }

    /**
     * Valida cédula (7-8 dígitos para normal, 10-11 para escolar)
     */
    public static function cedulaValida($valor, $esEscolar = false) {
        if (!self::soloNumeros($valor)) {
            return false;
        }

        if ($esEscolar) {
            return self::longitudValida($valor, 10, 11);
        } else {
            return self::longitudValida($valor, 7, 8);
        }
    }

    /**
     * Valida teléfono (10 dígitos)
     */
    public static function telefonoValido($valor) {
        return self::soloNumeros($valor) && strlen($valor) === 10;
    }

    /**
     * Valida nombre (solo letras, entre 3 y 40 caracteres)
     */
    public static function nombreValido($valor) {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new Exception("El nombre es obligatorio");
        }

        if (!self::soloLetras($valor)) {
            throw new Exception("El nombre solo puede contener letras");
        }

        if (!self::longitudValida($valor, 3, 40)) {
            throw new Exception("El nombre debe tener entre 3 y 40 caracteres");
        }

        return true;
    }

    /**
     * Valida apellido (solo letras, entre 3 y 40 caracteres)
     */
    public static function apellidoValido($valor) {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new Exception("El apellido es obligatorio");
        }

        if (!self::soloLetras($valor)) {
            throw new Exception("El apellido solo puede contener letras");
        }

        if (!self::longitudValida($valor, 3, 40)) {
            throw new Exception("El apellido debe tener entre 3 y 40 caracteres");
        }

        return true;
    }

    /**
     * Valida cédula con validación completa
     * @param string $valor Valor de la cédula
     * @param bool $esObligatorio Si la cédula es obligatoria
     * @param bool $esEscolar Si es cédula escolar (10-11 dígitos) o normal (7-8 dígitos)
     */
    public static function validarCedula($valor, $esObligatorio = false, $esEscolar = false) {
        $valor = trim($valor);

        if (empty($valor)) {
            if ($esObligatorio) {
                throw new Exception("La cédula es obligatoria");
            }
            return true; // Es opcional y está vacía
        }

        if (!self::soloNumeros($valor)) {
            throw new Exception("La cédula solo puede contener números");
        }

        if ($esEscolar) {
            if (!self::longitudValida($valor, 10, 11)) {
                throw new Exception("La cédula escolar debe tener entre 10 y 11 dígitos");
            }
        } else {
            if (!self::longitudValida($valor, 7, 8)) {
                throw new Exception("La cédula debe tener entre 7 y 8 dígitos");
            }
        }

        return true;
    }

    /**
     * Valida correo electrónico
     */
    public static function validarCorreo($valor, $esObligatorio = false) {
        $valor = trim($valor);

        if (empty($valor)) {
            if ($esObligatorio) {
                throw new Exception("El correo electrónico es obligatorio");
            }
            return true; // Es opcional y está vacío
        }

        if (!self::emailValido($valor)) {
            throw new Exception("El formato del correo electrónico no es válido");
        }

        if (!self::longitudValida($valor, 5, 100)) {
            throw new Exception("El correo debe tener entre 5 y 100 caracteres");
        }

        return true;
    }

    /**
     * Valida teléfono con validación completa
     */
    public static function validarTelefono($valor, $esObligatorio = false) {
        $valor = trim($valor);

        if (empty($valor)) {
            if ($esObligatorio) {
                throw new Exception("El teléfono es obligatorio");
            }
            return true; // Es opcional y está vacío
        }

        if (!self::soloNumeros($valor)) {
            throw new Exception("El teléfono solo puede contener números");
        }

        if (strlen($valor) !== 10) {
            throw new Exception("El teléfono debe tener exactamente 10 dígitos");
        }

        return true;
    }

    /**
     * Valida dirección
     */
    public static function validarDireccion($valor, $esObligatorio = false) {
        $valor = trim($valor);

        if (empty($valor)) {
            if ($esObligatorio) {
                throw new Exception("La dirección es obligatoria");
            }
            return true; // Es opcional y está vacía
        }

        if (!self::longitudValida($valor, 10, 250)) {
            throw new Exception("La dirección debe tener entre 10 y 250 caracteres");
        }

        return true;
    }

    /**
     * Valida fecha de nacimiento (rango de edad)
     */
    public static function validarFechaNacimiento($valor, $edadMinima = 3, $edadMaxima = 25) {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new Exception("La fecha de nacimiento es obligatoria");
        }

        $fecha = new DateTime($valor);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha)->y;

        if ($edad < $edadMinima || $edad > $edadMaxima) {
            throw new Exception("La edad debe estar entre {$edadMinima} y {$edadMaxima} años");
        }

        return true;
    }

    /**
     * Valida que un valor sea un entero positivo
     */
    public static function enteroPositivo($valor, $esObligatorio = false) {
        if (empty($valor) && $valor !== 0 && $valor !== '0') {
            if ($esObligatorio) {
                throw new Exception("Este campo es obligatorio");
            }
            return true;
        }

        if (!is_numeric($valor) || (int)$valor <= 0) {
            throw new Exception("Debe ser un número entero positivo");
        }

        return true;
    }

    /**
     * Sanitiza una cadena para prevenir XSS
     */
    public static function sanitizar($valor) {
        return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida campo de texto general
     */
    public static function validarTexto($valor, $nombre, $min, $max, $esObligatorio = false) {
        $valor = trim($valor);

        if (empty($valor)) {
            if ($esObligatorio) {
                throw new Exception("{$nombre} es obligatorio");
            }
            return true;
        }

        if (!self::longitudValida($valor, $min, $max)) {
            throw new Exception("{$nombre} debe tener entre {$min} y {$max} caracteres");
        }

        return true;
    }
}
?>
