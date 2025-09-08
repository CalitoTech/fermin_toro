<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UECFT Araure</title>
    <link rel="icon" href="../../assets/images/fermin.png">
    <!-- Incluyendo SweetAlert2 CSS y JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>

<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conexion = $database->getConnection();

    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = $_POST['password'] ?? '';

    // Normalizar la clave de sesión para el usuario (minúsculas, sin espacios al inicio/fin)
    $usernameKey = mb_strtolower($usuario);
    $usernameKey = $usernameKey === '' ? '_empty_' : $usernameKey;

    // Parámetros de control
    $max_intentos = 3;
    $bloqueo_minutos = 1; // 1 minuto

    // Verificar si ese usuario está bloqueado
    if (isset($_SESSION['bloqueos'][$usernameKey]) && $_SESSION['bloqueos'][$usernameKey] > time()) {
        $tiempo_restante = $_SESSION['bloqueos'][$usernameKey] - time();
        mostrarAlertaTiempo($tiempo_restante, $max_intentos);
        exit;
    } else {
        // Si el bloqueo expiró, limpiar estado
        if (isset($_SESSION['bloqueos'][$usernameKey]) && $_SESSION['bloqueos'][$usernameKey] <= time()) {
            unset($_SESSION['bloqueos'][$usernameKey]);
            if (isset($_SESSION['intentos'][$usernameKey])) {
                $_SESSION['intentos'][$usernameKey] = 0;
            }
        }
    }

    try {
        // Buscar el usuario en la base de datos (si existe)
        $query = "SELECT p.IdPersona, dp.IdPerfil, p.password, p.IdStatus
                 FROM detalle_perfil dp
                 INNER JOIN persona p ON dp.IdPersona = p.IdPersona
                 WHERE p.usuario = :usuario";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $login_ok = false;
        if ($user && password_verify($password, $user['password'])) {
            $login_ok = true;
        }

        if ($login_ok) {
            // Inicio de sesión exitoso: resetear contador para este usuario
            $_SESSION['usuario'] = $usuario;
            $_SESSION['idPersona'] = $user['IdPersona'];
            $_SESSION['idPerfil'] = $user['IdPerfil'];
            $_SESSION['login_exitoso'] = true;

            if (isset($_SESSION['intentos'][$usernameKey])) {
                unset($_SESSION['intentos'][$usernameKey]);
            }
            if (isset($_SESSION['bloqueos'][$usernameKey])) {
                unset($_SESSION['bloqueos'][$usernameKey]);
            }

            header("Location: ../../vistas/inicio/inicio/inicio.php");
            exit;
        } else {
            // Incrementar intentos específicos para el usuario proporcionado
            if (!isset($_SESSION['intentos'])) {
                $_SESSION['intentos'] = [];
            }
            if (!isset($_SESSION['intentos'][$usernameKey])) {
                $_SESSION['intentos'][$usernameKey] = 0;
            }
            $_SESSION['intentos'][$usernameKey] += 1;
            $intento = $_SESSION['intentos'][$usernameKey];

            if ($intento >= $max_intentos) {
                // Bloquear solo ese usuario durante $bloqueo_minutos
                $_SESSION['bloqueos'][$usernameKey] = time() + ($bloqueo_minutos * 60);
                $tiempo_restante = $_SESSION['bloqueos'][$usernameKey] - time();
                mostrarAlertaTiempo($tiempo_restante, $max_intentos);
                exit;
            } else {
                mostrarAlertaError($intento, $max_intentos);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Error en el login: " . $e->getMessage());
        mostrarAlertaError(null, null);
        exit;
    }
}

/**
 * Muestra alerta de tiempo restante para bloqueo (por usuario)
 */
function mostrarAlertaTiempo($tiempo_restante, $max_intentos = 3) {
    $html = "Has agotado tus $max_intentos intentos. Inténtalo de nuevo en <b></b>.";
    $html_js = json_encode($html);
    $timer_ms = intval($tiempo_restante * 1000);

    echo "<script>
    let timerInterval;
    Swal.fire({
        title: 'Acceso Denegado',
        html: $html_js,
        icon: 'error',
        timer: $timer_ms,
        timerProgressBar: true,
        didOpen: () => {
            const b = Swal.getHtmlContainer().querySelector('b');
            timerInterval = setInterval(() => {
                let left = Swal.getTimerLeft();
                let minutos = Math.floor(left / 60000);
                let segundos = Math.floor((left % 60000) / 1000);
                b.textContent = minutos + ' minuto(s) y ' + (segundos < 10 ? '0' : '') + segundos + ' segundo(s)';
            }, 1000);
        },
        willClose: () => {
            clearInterval(timerInterval);
        }
    }).then((result) => {
        window.location = '../../vistas/login/login.php';
    });
    </script>";
}

/**
 * Muestra alerta de error de credenciales (por usuario)
 */
function mostrarAlertaError($intento = null, $max_intentos = null) {
    if ($intento !== null && $max_intentos !== null) {
        $mensaje = "Usuario o contraseña incorrectos, intento $intento/$max_intentos";
    } else {
        $mensaje = "Error en el sistema. Por favor, intente más tarde.";
    }
    $mensaje_js = json_encode($mensaje);

    echo "<script>
    Swal.fire({
        title: 'Error',
        text: $mensaje_js,
        icon: 'warning',
        confirmButtonText: 'Aceptar'
    }).then((result) => {
        window.location = '../../vistas/login/login.php';
    });
    </script>";
}
?>

</body>
</html>