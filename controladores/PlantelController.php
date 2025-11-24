<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Plantel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearPlantel();
        break;
    case 'editar':
        editarPlantel();
        break;
    case 'eliminar':
        eliminarPlantel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/plantel/plantel.php');
}

function crearPlantel() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/plantel/nuevo_plantel.php');
    }

    // Obtener datos
    $plantel = trim($_POST['plantel'] ?? '');
    $es_privado = isset($_POST['es_privado']) ? true : false;

    // Validar campos requeridos
    if (empty($plantel)) {
        manejarError('El campo plantel es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $plantelModel = new Plantel($conexion);

        // Verificar plantel duplicado
        $stmt = $conexion->prepare("SELECT IdPlantel FROM plantel WHERE plantel = :plantel");
        $stmt->bindParam(':plantel', $plantel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de plantel ya existe');
        }

        // Configurar datos del plantel
        $plantelModel->plantel = $plantel;
        $plantelModel->es_privado = $es_privado;

        // Guardar plantel
        if (!$plantelModel->guardar()) {
            throw new Exception("Error al guardar el plantel");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Plantel creado exitosamente';
        header("Location: ../vistas/registros/plantel/plantel.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el plantel: ' . $e->getMessage());
    }
}

function editarPlantel() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/plantel/plantel.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de plantel inválido', '../vistas/registros/plantel/plantel.php');
    }

    // Validar campos requeridos
    $plantel = trim($_POST['plantel'] ?? '');
    $es_privado = isset($_POST['es_privado']) ? true : false;

    if (empty($plantel)) {
        manejarError("El campo plantel es requerido", "../vistas/registros/plantel/editar_plantel.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $plantelModel = new Plantel($conexion);

        // Verificar que el plantel existe
        if (!$plantelModel->obtenerPorId($id)) {
            manejarError('Plantel no encontrado', '../vistas/registros/plantel/plantel.php');
        }

        // Verificar duplicados (excluyendo al plantel actual)
        $stmt = $conexion->prepare("SELECT IdPlantel FROM plantel WHERE plantel = :plantel AND IdPlantel != :id");
        $stmt->bindParam(':plantel', $plantel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El plantel ya está en uso por otro registro', "../vistas/registros/plantel/editar_plantel.php?id=$id");
        }

        // Configurar datos actualizados
        $plantelModel->IdPlantel = $id;
        $plantelModel->plantel = $plantel;
        $plantelModel->es_privado = $es_privado;

        // Actualizar datos
        if (!$plantelModel->actualizar()) {
            throw new Exception("Error al actualizar datos del plantel");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Plantel actualizado correctamente';
        header("Location: ../vistas/registros/plantel/editar_plantel.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/plantel/editar_plantel.php?id=$id");
    }
}

function eliminarPlantel() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/plantel/plantel.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/plantel/plantel.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $plantelModel = new Plantel($conexion);

        // Verificar que el plantel existe
        if (!$plantelModel->obtenerPorId($id)) {
            manejarError('Plantel no encontrado', '../vistas/registros/plantel/plantel.php');
        }

        // Configurar ID para verificar dependencias
        $plantelModel->IdPlantel = $id;

        // Verificar dependencias
        if ($plantelModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/plantel/plantel.php");
            exit();
        }

        // Intentar eliminar
        if (!$plantelModel->eliminar()) {
            throw new Exception("Error al eliminar el plantel");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/plantel/plantel.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/plantel/plantel.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/plantel/nuevo_plantel.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
