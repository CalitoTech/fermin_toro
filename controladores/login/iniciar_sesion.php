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

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Buscar el usuario en la base de datos con IdRol
        $query = "SELECT p.IdPersona, dp.IdPerfil, p.password 
                 FROM detalle_perfil dp
                 INNER JOIN persona p ON dp.IdPersona = p.IdPersona
                 WHERE p.usuario = :usuario";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Comprobación de intentos fallidos
        if (isset($_SESSION['intento']) && $_SESSION['intento'] >= 3) {
            if (!isset($_SESSION['tiempo']) || $_SESSION['tiempo'] == 0) {
                $_SESSION['tiempo'] = time() + (1 * 60); // 1 minuto de bloqueo
            }

            $actual = time();
            $tiempo = $_SESSION['tiempo'];

            if ($actual < $tiempo) {
                $tiempo_restante = $tiempo - $actual;
                mostrarAlertaTiempo($tiempo_restante);
                exit;
            } else {
                $_SESSION['intento'] = 0;
                $_SESSION['tiempo'] = 0;
            }
        }

        if (!isset($_SESSION['intento']) || $_SESSION['intento'] < 3) {
            if ($user && password_verify($password, $user['password'])) {
                // Inicio de sesión exitoso
                $_SESSION['usuario'] = $usuario; 
                $_SESSION['idPersona'] = $user['IdPersona'];
                $_SESSION['idPerfil'] = $user['IdPerfil'];
                $_SESSION['login_exitoso'] = true;
                
                header("Location: ../../vistas/inicio/inicio/inicio.php");
                exit;
            } else {
                // Manejo de intentos fallidos
                $_SESSION['intento'] = ($_SESSION['intento'] ?? 0) + 1;
                $intento = $_SESSION['intento'];
                $max_intentos = 3;

                if ($intento >= $max_intentos) {
                    $_SESSION['tiempo'] = time() + (1 * 60);
                    $tiempo_restante = $_SESSION['tiempo'] - time();
                    mostrarAlertaTiempo($tiempo_restante, $max_intentos);
                    exit;
                } else {
                    mostrarAlertaError($intento, $max_intentos);
                    exit;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error en el login: " . $e->getMessage());
        mostrarAlertaError("Error en el sistema. Por favor, intente más tarde.");
        exit;
    }
}

/**
 * Muestra alerta de tiempo restante para bloqueo
 */
function mostrarAlertaTiempo($tiempo_restante, $max_intentos = 3) {
    echo "<script>
    let timerInterval;
    Swal.fire({
        title: 'Acceso Denegado',
        html: 'Has agotado tus $max_intentos intentos. Inténtalo de nuevo en <b></b>.',
        icon: 'error',
        timer: $tiempo_restante * 1000,
        timerProgressBar: true,
        didOpen: () => {
            const b = Swal.getHtmlContainer().querySelector('b');
            timerInterval = setInterval(() => {
                let minutos = Math.floor(Swal.getTimerLeft() / 60000);
                let segundos = ((Swal.getTimerLeft() % 60000) / 1000).toFixed(0);
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
 * Muestra alerta de error de credenciales
 */
function mostrarAlertaError($intento = null, $max_intentos = null) {
    if ($intento !== null && $max_intentos !== null) {
        $mensaje = 'Usuario o contraseña incorrectos, intento '.$intento.'/'.$max_intentos;
    } else {
        $mensaje = 'Error en el sistema. Por favor, intente más tarde.';
    }
    
    echo "<script>
    Swal.fire({
        title: 'Error',
        text: '$mensaje',
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