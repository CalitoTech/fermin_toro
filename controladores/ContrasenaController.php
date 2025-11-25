<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/Notificaciones.php';

class ContrasenaController {
    private $conexion;
    private $persona;
    private $credenciales;

    public $alerta = null;

    public function __construct() {
        $database = new Database();
        $this->conexion = $database->getConnection();
        $this->persona = new Persona($this->conexion);
        $this->persona->IdPersona = $_SESSION['idPersona'];
        $this->credenciales = $this->persona->obtenerCredenciales();
    }

    public function manejarSolicitud() {
        // === MANEJO DE INTENTOS FALLIDOS ===
        $this->inicializarIntentos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarFormulario();
        }
    }

    private function inicializarIntentos() {
        if (!isset($_SESSION['intento_cambio'])) $_SESSION['intento_cambio'] = 0;
        if (!isset($_SESSION['tiempo_cambio'])) $_SESSION['tiempo_cambio'] = 0;

        $actual = time();
        $tiempo_bloqueo = $_SESSION['tiempo_cambio'];

        // Si el tiempo de bloqueo ya pasó, reiniciar
        if ($actual >= $tiempo_bloqueo && $tiempo_bloqueo > 0) {
            $_SESSION['intento_cambio'] = 0;
            $_SESSION['tiempo_cambio'] = 0;
        }
    }

    private function procesarFormulario() {
        // Verificar si es un envío del formulario de credenciales (no de foto)
        if (!isset($_POST['password3'])) {
            return; // No es el formulario de contraseña, ignorar
        }

        $usuario = trim($_POST['usuario'] ?? '');
        $passwordActual = $_POST['password3'] ?? '';
        $nuevaPassword = $_POST['password'] ?? '';
        $confirmarPassword = $_POST['password2'] ?? '';

        $actual = time();
        $tiempo_bloqueo = $_SESSION['tiempo_cambio'];

        // Verificar si está bloqueado
        if ($actual < $tiempo_bloqueo) {
            $this->alerta = Notificaciones::bloqueo($tiempo_bloqueo - $actual);
            return;
        }

        // Si NO se ingresó contraseña actual, no hacer nada
        if (empty($passwordActual)) {
            return;
        }

        // Validar contraseña actual
        if (!password_verify($passwordActual, $this->credenciales['password'])) {
            $_SESSION['intento_cambio']++;
            $intento = $_SESSION['intento_cambio'];
            $max_intentos = 3;

            if ($intento >= $max_intentos) {
                $_SESSION['tiempo_cambio'] = time() + 60;
                $this->alerta = Notificaciones::bloqueo(60);
            } else {
                $intentos_restantes = $max_intentos - $intento;
                $this->alerta = Notificaciones::advertencia("Contraseña incorrecta. Te quedan $intentos_restantes intentos.");
            }
            return;
        }

        // Contraseña correcta: reiniciar intentos
        $_SESSION['intento_cambio'] = 0;
        $_SESSION['tiempo_cambio'] = 0;

        // Determinar qué se va a actualizar
        $cambiarUsuario = !empty($usuario) && $usuario !== $this->credenciales['usuario'];
        $cambiarPassword = !empty($nuevaPassword);

        // Si no hay cambios, mostrar mensaje
        if (!$cambiarUsuario && !$cambiarPassword) {
            $this->alerta = Notificaciones::advertencia("No hay cambios para guardar.");
            return;
        }

        // Validar nuevas contraseñas si se van a cambiar
        if ($cambiarPassword && $nuevaPassword !== $confirmarPassword) {
            $this->alerta = Notificaciones::error("Las contraseñas no coinciden.");
            return;
        }

        // Actualizar según lo que se haya modificado
        if ($cambiarUsuario && $cambiarPassword) {
            // Cambiar usuario y contraseña
            if ($this->persona->actualizarCredenciales($usuario, $nuevaPassword)) {
                $_SESSION['usuario'] = $usuario;
                $this->alerta = Notificaciones::exito("Usuario y contraseña actualizados correctamente.");
            } else {
                $this->alerta = Notificaciones::error();
            }
        } elseif ($cambiarUsuario) {
            // Solo cambiar usuario
            if ($this->persona->actualizarCredenciales($usuario)) {
                $_SESSION['usuario'] = $usuario;
                $this->alerta = Notificaciones::exito("Usuario actualizado correctamente.");
            } else {
                $this->alerta = Notificaciones::error();
            }
        } elseif ($cambiarPassword) {
            // Solo cambiar contraseña
            if ($this->persona->actualizarCredenciales($this->credenciales['usuario'], $nuevaPassword)) {
                $this->alerta = Notificaciones::exito("Contraseña actualizada correctamente.");
            } else {
                $this->alerta = Notificaciones::error();
            }
        }
    }

    public function obtenerCredenciales() {
        return $this->credenciales;
    }
}