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

    // Normalizar usuario para clave de sesión
    $usernameKey = mb_strtolower($usuario);
    $usernameKey = $usernameKey === '' ? '_empty_' : $usernameKey;

    $max_intentos = 3;
    $bloqueo_minutos = 1; // minutos de bloqueo

    try {
        // Verificar que el usuario exista y esté activo
        $queryCheck = "SELECT IdPersona, IdStatus FROM persona WHERE usuario = :usuario";
        $stmtCheck = $conexion->prepare($queryCheck);
        $stmtCheck->bindParam(':usuario', $usuario);
        $stmtCheck->execute();
        $persona = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$persona) {
            // Usuario no existe
            mostrarAlertaUsuario("El usuario no existe en el sistema.");
            exit;
        }

        if ($persona['IdStatus'] != 1) {
            // Usuario inactivo
            mostrarAlertaUsuario("El usuario está inactivo, contacte al administrador.");
            exit;
        }

        // Validar si este usuario está bloqueado
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

        // Buscar credenciales completas
        $query = "SELECT p.IdPersona, dp.IdPerfil, p.password, p.IdStatus
                 FROM detalle_perfil dp
                 INNER JOIN persona p ON dp.IdPersona = p.IdPersona
                 WHERE p.usuario = :usuario AND p.IdStatus = 1";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $login_ok = false;
        if ($user && password_verify($password, $user['password'])) {
            $login_ok = true;
        }

        if ($login_ok) {
            // Inicio de sesión exitoso: resetear contador
            $_SESSION['usuario'] = $usuario;
            $_SESSION['idPersona'] = $user['IdPersona'];
            $_SESSION['idPerfil'] = $user['IdPerfil'];
            $_SESSION['login_exitoso'] = true;

            unset($_SESSION['intentos'][$usernameKey]);
            unset($_SESSION['bloqueos'][$usernameKey]);

            header("Location: ../../vistas/inicio/inicio/inicio.php");
            exit;
        } else {
            // Manejo de intentos fallidos solo para este usuario
            if (!isset($_SESSION['intentos'][$usernameKey])) {
                $_SESSION['intentos'][$usernameKey] = 0;
            }
            $_SESSION['intentos'][$usernameKey] += 1;
            $intento = $_SESSION['intentos'][$usernameKey];

            if ($intento >= $max_intentos) {
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
 * Alerta cuando usuario no existe o está inactivo
 */
function mostrarAlertaUsuario($mensaje) {
    $mensaje_js = json_encode($mensaje);
    echo "<script>
    Swal.fire({
        title: 'Acceso Denegado',
        text: $mensaje_js,
        icon: 'error',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        window.location = '../../vistas/login/login.php';
    });
    </script>";
}

/**
 * Alerta de bloqueo
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
    }).then(() => {
        window.location = '../../vistas/login/login.php';
    });
    </script>";
}

/**
 * Alerta de error de credenciales
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
    }).then(() => {
        window.location = '../../vistas/login/login.php';
    });
    </script>";
}
?>

</body>
</html>