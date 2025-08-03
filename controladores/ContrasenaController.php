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
        } else {
            // Contraseña correcta: reiniciar intentos
            $_SESSION['intento_cambio'] = 0;
            $_SESSION['tiempo_cambio'] = 0;

            // Validar nuevas contraseñas
            if ($nuevaPassword !== $confirmarPassword) {
                $this->alerta = Notificaciones::error("Las contraseñas no coinciden.");
            } else {
                $usuarioFinal = !empty($usuario) ? $usuario : $this->credenciales['usuario'];

                if ($this->persona->actualizarCredenciales($usuarioFinal, $nuevaPassword)) {
                    $_SESSION['usuario'] = $usuarioFinal;
                    $this->alerta = Notificaciones::exito("Datos actualizados correctamente.");
                } else {
                    $this->alerta = Notificaciones::error();
                }
            }
        }
    }

    public function obtenerCredenciales() {
        return $this->credenciales;
    }
}