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
require_once __DIR__ . '/../../modelos/Persona.php'; // asegúrate de la ruta correcta

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conexion = $database->getConnection();

    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = $_POST['password'] ?? '';

    // --- LOGIN CON CÓDIGO TEMPORAL ---
    if (isset($_POST['cedula']) && isset($_POST['codigo'])) {
        $cedula = trim($_POST['cedula']);
        $codigo = trim($_POST['codigo']);

        if ($cedula === '' || $codigo === '') {
            mostrarAlertaUsuario("Debes ingresar la cédula y el código de verificación.");
            exit;
        }

        $personaModel = new Persona($conexion);
        $resultado = $personaModel->validarCodigoTemporal($cedula, $codigo);

        if ($resultado['valido']) {
            // ✅ Verificar si el usuario estaba bloqueado (IdEstadoAcceso = 3)
            $idPersona = $resultado['IdPersona'];
            $queryStatus = "SELECT IdEstadoAcceso FROM persona WHERE IdPersona = :id";
            $stmtStatus = $conexion->prepare($queryStatus);
            $stmtStatus->bindParam(':id', $idPersona, PDO::PARAM_INT);
            $stmtStatus->execute();
            $estado = (int)$stmtStatus->fetchColumn();

            if ($estado === 3) {
                // ✅ Reactivar usuario automáticamente
                $queryReactivar = "UPDATE persona SET IdEstadoAcceso = 1 WHERE IdPersona = :id";
                $stmtReactivar = $conexion->prepare($queryReactivar);
                $stmtReactivar->bindParam(':id', $idPersona, PDO::PARAM_INT);
                $stmtReactivar->execute();
            }

            // Login exitoso sin contraseña
            $_SESSION['idPersona'] = $resultado['IdPersona'];
            $_SESSION['usuario'] = $resultado['usuario'];
            $_SESSION['idPerfil'] = null;

            // Limpiar el código para que no se pueda reutilizar
            $personaModel->limpiarCodigoTemporal($resultado['IdPersona']);

            $_SESSION['login_exitoso'] = true;
            header("Location: ../../vistas/inicio/inicio/inicio.php");
            exit;
        } else {
            mostrarAlertaUsuario($resultado['mensaje']);
            exit;
        }
    }


    // si usuario vacío -> error inmediato
    if ($usuario === '') {
        mostrarAlertaUsuario("Debes ingresar tu usuario.");
        exit;
    }

    // Normalizar clave de sesión (sólo para intentos)
    $usernameKey = mb_strtolower($usuario);
    $usernameKey = $usernameKey === '' ? '_empty_' : $usernameKey;

    $max_intentos = 3;

    try {
        // 1) Buscar persona por usuario (solo IdPersona + IdEstadoAcceso)
        $queryCheck = "SELECT IdPersona, IdEstadoAcceso, usuario FROM persona WHERE usuario = :usuario LIMIT 1";
        $stmtCheck = $conexion->prepare($queryCheck);
        $stmtCheck->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmtCheck->execute();
        $persona = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$persona) {
            mostrarAlertaUsuario("El usuario no existe en el sistema.");
            exit;
        }

        $status = (int)($persona['IdEstadoAcceso'] ?? 0);

        if ($status === 3) {
            // Bloqueado permanentemente
            mostrarAlertaUsuario("El usuario ha sido bloqueado por medidas de seguridad. Contacte al administrador.");
            exit;
        }

        $permitidos_login = [1]; // Activo

        if (!in_array((int)$persona['IdEstadoAcceso'], $permitidos_login)) {
            mostrarAlertaUsuario("El usuario no tiene permitido acceder al sistema. Contacte al administrador.");
            exit;
        }

        // 2) Obtener credenciales (usar LEFT JOIN para no depender de detalle_perfil)
        $query = "SELECT p.IdPersona, dp.IdPerfil, p.password
                  FROM persona p
                  LEFT JOIN detalle_perfil dp ON dp.IdPersona = p.IdPersona
                  WHERE p.usuario = :usuario AND p.IdEstadoAcceso = 1
                  LIMIT 1";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $login_ok = false;
        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            $login_ok = true;
        }

        if ($login_ok) {
            // Inicio de sesión exitoso: resetear contador
            $_SESSION['usuario'] = $usuario;
            $_SESSION['idPersona'] = $user['IdPersona'];
            $_SESSION['idPerfil'] = $user['IdPerfil'] ?? null;
            $_SESSION['login_exitoso'] = true;

            // limpiar contador de intentos para este usuario
            unset($_SESSION['intentos'][$usernameKey]);

            header("Location: ../../vistas/inicio/inicio/inicio.php");
            exit;
        } else {
            // Manejo de intentos fallidos
            if (!isset($_SESSION['intentos'][$usernameKey])) {
                $_SESSION['intentos'][$usernameKey] = 0;
            }
            $_SESSION['intentos'][$usernameKey] += 1;
            $intento = $_SESSION['intentos'][$usernameKey];

            if ($intento >= $max_intentos) {
                // Bloquear definitivamente en BD usando el modelo Persona
                $personaModel = new Persona($conexion);
                $personaModel->IdPersona = $persona['IdPersona'];
                $okBloqueo = $personaModel->bloquearCuenta();

                // Enviar aviso via whatsapp (si quieres)
                require_once __DIR__ . '/../../controladores/WhatsAppController.php';
                $whatsapp = new WhatsAppController($conexion);
                // enviarAvisoBloqueo espera idPersona
                $whatsapp->enviarAvisoBloqueo($persona['IdPersona'], 'bloqueo');

                error_log("LOGIN: usuario '{$usuario}' bloqueado en BD (IdPersona={$persona['IdPersona']}). Bloqueo ok? " . ($okBloqueo ? 'si' : 'no'));

                mostrarAlertaUsuario("El usuario ha sido bloqueado por medidas de seguridad. Contacte al administrador.");
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

function mostrarAlertaExito($mensaje) {
    $mensaje_js = json_encode($mensaje);
    echo "<script>
    Swal.fire({
        title: 'Ëxito',
        text: $mensaje_js,
        icon: 'success',
        confirmButtonText: 'Aceptar'
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