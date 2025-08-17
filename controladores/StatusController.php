<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Status.php';
require_once __DIR__ . '/../modelos/TipoStatus.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearStatus();
        break;
    case 'editar':
        editarStatus();
        break;
    case 'eliminar':
        eliminarStatus();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/status/status.php');
}

function crearStatus() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/status/nuevo_status.php');
    }

    // Obtener datos
    $status = trim($_POST['status'] ?? '');
    $tipo_status = trim($_POST['tipo_status'] ?? '');

    // Validar campos requeridos
    if (empty($status)) {
        manejarError('El campo status es requerido');
    }
    if (empty($tipo_status)) {
        manejarError('El campo tipo_status es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $statusModel = new Status($conexion);

        // Verificar status duplicado
        $query = "SELECT IdStatus FROM status WHERE status = :status AND IdTipo_Status = :tipo_status";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':tipo_status', $tipo_status);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de status ya existe para este tipo_status');
        }

        // Configurar datos del status
        $statusModel->status = $status;
        $statusModel->IdTipo_Status = $tipo_status;

        // Guardar status
        if (!$statusModel->guardar()) {
            throw new Exception("Error al guardar el status");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Status creado exitosamente';
        header("Location: ../vistas/registros/status/status.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el status: ' . $e->getMessage());
    }
}

function editarStatus() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/status/status.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de status inválido', '../vistas/registros/status/status.php');
    }

    // Validar campos requeridos
    $status = trim($_POST['status'] ?? '');
    $tipo_status = trim($_POST['tipo_status'] ?? '');
    
    if (empty($status)) {
        manejarError("El campo status es requerido", "../vistas/registros/status/editar_status.php?id=$id");
    }
    if (empty($tipo_status)) {
        manejarError("El campo tipo_status es requerido", "../vistas/registros/status/editar_status.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $statusModel = new Status($conexion);

        // Verificar que el status existe
        if (!$statusModel->obtenerPorId($id)) {
            manejarError('Status no encontrado', '../vistas/registros/status/status.php');
        }

        // Verificar duplicados (excluyendo al status actual)
        $query = "SELECT IdStatus FROM status WHERE status = :status AND IdTipo_Status = :tipo_status AND IdStatus != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':tipo_status', $tipo_status);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El status ya está en uso por otro registro', "../vistas/registros/status/editar_status.php?id=$id");
        }

        // Configurar datos actualizados
        $statusModel->IdStatus = $id;
        $statusModel->status = $status;
        $statusModel->IdTipo_Status = $tipo_status;

        // Actualizar datos
        if (!$statusModel->actualizar()) {
            throw new Exception("Error al actualizar datos del status");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Status actualizado correctamente';
        header("Location: ../vistas/registros/status/editar_status.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/status/editar_status.php?id=$id");
    }
}

function eliminarStatus() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/status/status.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/status/status.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $statusModel = new Status($conexion);

        // Verificar que el status existe
        if (!$statusModel->obtenerPorId($id)) {
            manejarError('Status no encontrado', '../vistas/registros/status/status.php');
        }

        // Configurar ID para verificar dependencias
        $statusModel->IdStatus = $id;

        // Verificar dependencias
        if ($statusModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/status/status.php");
            exit();
        }

        // Intentar eliminar
        if (!$statusModel->eliminar()) {
            throw new Exception("Error al eliminar el status");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/status/status.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/status/status.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/status/nuevo_status.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}