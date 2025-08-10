<?php

class Validaciones {
    // Expresiones regulares equivalentes a las del frontend
    const PATRONES = [
        'usuario' => '/^[a-zA-Z0-9\_\-]{4,20}$/',
        'nombre' => '/^[a-zA-ZÀ-ÿ\s]{3,40}$/u',
        'password' => '/^.{4,20}$/',
        'correo' => '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/',
        'telefono' => '/^[0-9]{11,}$/',
        'ficha' => '/^\d{4}$/',
        'cedula' => '/^\d{7,8}$/',
        'carga_familiar' => '/^[0-9]{1}$/',
        'fecha_escolar' => '/^(?=.*[0-9])(?=.*[-\/]).{5,11}$/',
    ];

    /**
     * Valida un campo según su tipo
     * @param string $valor Valor a validar
     * @param string $tipo Tipo de validación (debe existir en self::PATRONES)
     * @return bool True si es válido, false si no
     * @throws InvalidArgumentException Si el tipo no está definido
     */
    public static function validarCampo(string $valor, string $tipo): bool {
        if (!array_key_exists($tipo, self::PATRONES)) {
            throw new InvalidArgumentException("Tipo de validación no soportado: $tipo");
        }

        return preg_match(self::PATRONES[$tipo], $valor) === 1;
    }
}