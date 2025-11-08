<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearFechaEscolar();
        break;
    case 'editar':
        editarFechaEscolar();
        break;
    case 'eliminar':
        eliminarFechaEscolar();
        break;
    case 'activar':
        activarFechaEscolar();
        break;
    case 'toggle_inscripcion':
        toggleInscripcion();
        break;
    case 'toggle_renovacion':
        toggleRenovacion();
        break;
    default:
        manejarError('Acción no válida', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
}

function toggleInscripcion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit();
    }

    $id = (int)($_POST['id'] ?? 0);
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

    if ($id <= 0 || ($estado !== 0 && $estado !== 1)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        if (!$fecha_escolarModel->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Año escolar no encontrado']);
            exit();
        }

        if ($fecha_escolarModel->actualizarInscripcion($id, $estado)) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Estado de inscripción actualizado',
                'nuevo_estado' => $estado
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No se puede modificar: el año escolar no está activo'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
    exit();
}

function toggleRenovacion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit();
    }

    $id = (int)($_POST['id'] ?? 0);
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

    if ($id <= 0 || ($estado !== 0 && $estado !== 1)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        if (!$fecha_escolarModel->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Año escolar no encontrado']);
            exit();
        }

        if ($fecha_escolarModel->actualizarRenovacion($id, $estado)) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Estado de renovación actualizado',
                'nuevo_estado' => $estado
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No se puede modificar: el año escolar no está activo'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
    exit();
}

function activarFechaEscolar() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        // Verificar que el año escolar existe
        if (!$fecha_escolarModel->obtenerPorId($id)) {
            manejarError('Año escolar no encontrado', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
        }

        // Activar el año escolar
        if (!$fecha_escolarModel->activarFechaEscolar($id)) {
            throw new Exception("Error al activar el año escolar");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Año escolar activado correctamente';
        header("Location: ../vistas/configuracion/fecha_escolar/fecha_escolar.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al activar: ' . $e->getMessage(), '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }
}

function crearFechaEscolar() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/fecha_escolar/nuevo_fecha_escolar.php');
    }

    // Obtener datos
    $fecha_escolar = trim($_POST['fecha_escolar'] ?? '');

    // Validar campos requeridos
    if (empty($fecha_escolar)) {
        manejarError('El campo fecha_escolar es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        // Verificar fecha_escolar duplicado
        $stmt = $conexion->prepare("SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_escolar = :fecha_escolar");
        $stmt->bindParam(':fecha_escolar', $fecha_escolar);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de fecha_escolar ya existe');
        }

        // Configurar datos del fecha_escolar
        $fecha_escolarModel->fecha_escolar = $fecha_escolar;

        // Guardar fecha_escolar
        if (!$fecha_escolarModel->guardar()) {
            throw new Exception("Error al guardar el fecha_escolar");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Año Escolar creado exitosamente';
        header("Location: ../vistas/configuracion/fecha_escolar/fecha_escolar.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el fecha_escolar: ' . $e->getMessage());
    }
}

function editarFechaEscolar() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de fecha_escolar inválido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    // Validar campos requeridos
    $fecha_escolar = trim($_POST['fecha_escolar'] ?? '');
    if (empty($fecha_escolar)) {
        manejarError("El campo fecha_escolar es requerido", "../vistas/configuracion/fecha_escolar/editar_fecha_escolar.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        // Verificar que el fecha_escolar existe
        if (!$fecha_escolarModel->obtenerPorId($id)) {
            manejarError('Año Escolar no encontrado', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
        }

        // Verificar duplicados (excluyendo al fecha_escolar actual)
        $stmt = $conexion->prepare("SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_escolar = :fecha_escolar AND IdFecha_Escolar != :id");
        $stmt->bindParam(':fecha_escolar', $fecha_escolar);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El fecha_escolar ya está en uso por otro registro', "../vistas/configuracion/fecha_escolar/editar_fecha_escolar.php?id=$id");
        }

        // Configurar datos actualizados
        $fecha_escolarModel->IdFecha_Escolar = $id;
        $fecha_escolarModel->fecha_escolar = $fecha_escolar;

        // Actualizar datos
        if (!$fecha_escolarModel->actualizar()) {
            throw new Exception("Error al actualizar datos del fecha_escolar");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Año Escolar actualizado correctamente';
        header("Location: ../vistas/configuracion/fecha_escolar/editar_fecha_escolar.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/configuracion/fecha_escolar/editar_fecha_escolar.php?id=$id");
    }
}

function eliminarFechaEscolar() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $fecha_escolarModel = new FechaEscolar($conexion);

        // Verificar que el fecha_escolar existe
        if (!$fecha_escolarModel->obtenerPorId($id)) {
            manejarError('Año Escolar no encontrado', '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
        }

        // Configurar ID para verificar dependencias
        $fecha_escolarModel->IdFecha_Escolar = $id;

        // Verificar dependencias
        if ($fecha_escolarModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/configuracion/fecha_escolar/fecha_escolar.php");
            exit();
        }

        // Intentar eliminar
        if (!$fecha_escolarModel->eliminar()) {
            throw new Exception("Error al eliminar el fecha_escolar");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/configuracion/fecha_escolar/fecha_escolar.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/configuracion/fecha_escolar/fecha_escolar.php');
    }
// function eliminarFechaEscolar() {
//     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//         http_response_code(405);
//         echo json_encode(['success' => false, 'message' => 'Método no permitido']);
//         exit();
//     }

//     $id = (int)($_POST['id'] ?? 0);
//     if ($id <= 0) {
//         http_response_code(400);
//         echo json_encode(['success' => false, 'message' => 'ID inválido']);
//         exit();
//     }

//     try {
//         $database = new Database();
//         $conexion = $database->getConnection();
//         $fecha_escolarModel = new FechaEscolar($conexion);

//         if (!$fecha_escolarModel->obtenerPorId($id)) {
//             http_response_code(404);
//             echo json_encode(['success' => false, 'message' => 'No encontrado']);
//             exit();
//         }

//         $fecha_escolarModel->IdFecha_Escolar = $id;

//         if ($fecha_escolarModel->tieneDependencias()) {
//             http_response_code(400);
//             echo json_encode([
//                 'success' => false,
//                 'error' => 'dependency',
//                 'message' => 'Tiene dependencias'
//             ]);
//             exit();
//         }

//         if ($fecha_escolarModel->eliminar()) {
//             http_response_code(200);
//             echo json_encode(['success' => true]);
//         } else {
//             http_response_code(500);
//             echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
//         }
//     } catch (Exception $e) {
//         http_response_code(500);
//         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
//     }
//     exit();
// }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/configuracion/fecha_escolar/nuevo_fecha_escolar.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}