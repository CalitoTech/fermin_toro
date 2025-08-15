<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Seccion.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearSeccion();
        break;
    case 'editar':
        editarSeccion();
        break;
    case 'eliminar':
        eliminarSeccion();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/seccion/seccion.php');
}

function crearSeccion() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/seccion/nuevo_seccion.php');
    }

    // Obtener datos
    $seccion = trim($_POST['seccion'] ?? '');

    // Validar campos requeridos
    if (empty($seccion)) {
        manejarError('El campo seccion es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $seccionModel = new Seccion($conexion);

        // Verificar seccion duplicado
        $query = "SELECT IdSeccion FROM seccion WHERE seccion = :seccion";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':seccion', $seccion);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de seccion ya existe');
        }

        // Configurar datos del seccion
        $seccionModel->seccion = $seccion;

        // Guardar seccion
        if (!$seccionModel->guardar()) {
            throw new Exception("Error al guardar el seccion");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Seccion creado exitosamente';
        header("Location: ../vistas/registros/seccion/seccion.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el seccion: ' . $e->getMessage());
    }
}

function editarSeccion() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/seccion/seccion.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de seccion inválido', '../vistas/registros/seccion/seccion.php');
    }

    // Validar campos requeridos
    $seccion = trim($_POST['seccion'] ?? '');
    
    if (empty($seccion)) {
        manejarError("El campo seccion es requerido", "../vistas/registros/seccion/editar_seccion.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $seccionModel = new Seccion($conexion);

        // Verificar que el seccion existe
        if (!$seccionModel->obtenerPorId($id)) {
            manejarError('Seccion no encontrado', '../vistas/registros/seccion/seccion.php');
        }

        // Verificar duplicados (excluyendo al seccion actual)
        $query = "SELECT IdSeccion FROM seccion WHERE seccion = :seccion AND IdSeccion != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':seccion', $seccion);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El seccion ya está en uso por otro registro', "../vistas/registros/seccion/editar_seccion.php?id=$id");
        }

        // Configurar datos actualizados
        $seccionModel->IdSeccion = $id;
        $seccionModel->seccion = $seccion;

        // Actualizar datos
        if (!$seccionModel->actualizar()) {
            throw new Exception("Error al actualizar datos del seccion");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Seccion actualizado correctamente';
        header("Location: ../vistas/registros/seccion/editar_seccion.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/seccion/editar_seccion.php?id=$id");
    }
}

function eliminarSeccion() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/seccion/seccion.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/seccion/seccion.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $seccionModel = new Seccion($conexion);

        // Verificar que el seccion existe
        if (!$seccionModel->obtenerPorId($id)) {
            manejarError('Seccion no encontrado', '../vistas/registros/seccion/seccion.php');
        }

        // Configurar ID para verificar dependencias
        $seccionModel->IdSeccion = $id;

        // Verificar dependencias
        if ($seccionModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/seccion/seccion.php");
            exit();
        }

        // Intentar eliminar
        if (!$seccionModel->eliminar()) {
            throw new Exception("Error al eliminar el seccion");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/seccion/seccion.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/seccion/seccion.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/seccion/nuevo_seccion.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}