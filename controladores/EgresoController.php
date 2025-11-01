<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Egreso.php';

// Determinar acción (crear, editar o eliminar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearEgreso();
        break;
    case 'editar':
        editarEgreso();
        break;
    case 'eliminar':
        eliminarEgreso();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/egreso/egreso.php');
}

function crearEgreso() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/egreso/nuevo_egreso.php');
    }

    // Obtener datos
    $fecha_egreso = trim($_POST['fecha_egreso'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $idPersona = (int)($_POST['IdPersona'] ?? 0);
    $idStatus = (int)($_POST['IdStatus'] ?? 0);

    // Validar campos requeridos
    if (empty($fecha_egreso)) {
        manejarError('La fecha de egreso es requerida', '../vistas/registros/egreso/nuevo_egreso.php');
    }
    if ($idPersona <= 0) {
        manejarError('Debe seleccionar un estudiante', '../vistas/registros/egreso/nuevo_egreso.php');
    }
    if ($idStatus <= 0) {
        manejarError('Debe seleccionar un status', '../vistas/registros/egreso/nuevo_egreso.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $egresoModel = new Egreso($conexion);

        // Verificar que no exista un egreso para esta persona
        $queryCheck = "SELECT IdEgreso FROM egreso WHERE IdPersona = :idPersona";
        $stmtCheck = $conexion->prepare($queryCheck);
        $stmtCheck->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            manejarError('Esta persona ya tiene un egreso registrado', '../vistas/registros/egreso/nuevo_egreso.php');
        }

        // Configurar datos del egreso
        $egresoModel->fecha_egreso = $fecha_egreso;
        $egresoModel->motivo = $motivo;
        $egresoModel->IdPersona = $idPersona;
        $egresoModel->IdStatus = $idStatus;

        // Guardar egreso
        if (!$egresoModel->guardar()) {
            throw new Exception("Error al guardar el egreso");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Egreso creado exitosamente';
        header("Location: ../vistas/registros/egreso/egreso.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el egreso: ' . $e->getMessage(), '../vistas/registros/egreso/nuevo_egreso.php');
    }
}

function editarEgreso() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/egreso/egreso.php');
    }

    // Obtener ID
    $id = (int)($_POST['IdEgreso'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de egreso inválido', '../vistas/registros/egreso/egreso.php');
    }

    // Obtener datos
    $fecha_egreso = trim($_POST['fecha_egreso'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $idPersona = (int)($_POST['IdPersona'] ?? 0);
    $idStatus = (int)($_POST['IdStatus'] ?? 0);

    // Validar campos requeridos
    if (empty($fecha_egreso)) {
        manejarError('La fecha de egreso es requerida', "../vistas/registros/egreso/editar_egreso.php?id=$id");
    }
    if ($idPersona <= 0) {
        manejarError('Debe seleccionar un estudiante', "../vistas/registros/egreso/editar_egreso.php?id=$id");
    }
    if ($idStatus <= 0) {
        manejarError('Debe seleccionar un status', "../vistas/registros/egreso/editar_egreso.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $egresoModel = new Egreso($conexion);

        // Verificar que el egreso existe
        if (!$egresoModel->obtenerPorId($id)) {
            manejarError('Egreso no encontrado', '../vistas/registros/egreso/egreso.php');
        }

        // Verificar que no exista otro egreso para esta persona (excluyendo el actual)
        $queryCheck = "SELECT IdEgreso FROM egreso WHERE IdPersona = :idPersona AND IdEgreso != :id";
        $stmtCheck = $conexion->prepare($queryCheck);
        $stmtCheck->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            manejarError('Esta persona ya tiene otro egreso registrado', "../vistas/registros/egreso/editar_egreso.php?id=$id");
        }

        // Configurar datos actualizados
        $egresoModel->IdEgreso = $id;
        $egresoModel->fecha_egreso = $fecha_egreso;
        $egresoModel->motivo = $motivo;
        $egresoModel->IdPersona = $idPersona;
        $egresoModel->IdStatus = $idStatus;

        // Actualizar datos
        if (!$egresoModel->actualizar()) {
            throw new Exception("Error al actualizar datos del egreso");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Egreso actualizado correctamente';
        header("Location: ../vistas/registros/egreso/editar_egreso.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/egreso/editar_egreso.php?id=$id");
    }
}

function eliminarEgreso() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/egreso/egreso.php');
    }

    // Obtener ID
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/egreso/egreso.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $egresoModel = new Egreso($conexion);

        // Verificar que el egreso existe
        if (!$egresoModel->obtenerPorId($id)) {
            manejarError('Egreso no encontrado', '../vistas/registros/egreso/egreso.php');
        }

        // Configurar ID para eliminar
        $egresoModel->IdEgreso = $id;

        // Intentar eliminar
        if (!$egresoModel->eliminar()) {
            throw new Exception("Error al eliminar el egreso");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/egreso/egreso.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/egreso/egreso.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/egreso/nuevo_egreso.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
