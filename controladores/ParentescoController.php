<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Parentesco.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearParentesco();
        break;
    case 'editar':
        editarParentesco();
        break;
    case 'eliminar':
        eliminarParentesco();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/parentesco/parentesco.php');
}

function crearParentesco() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/parentesco/nuevo_parentesco.php');
    }

    // Obtener datos
    $parentesco = trim($_POST['parentesco'] ?? '');

    // Validar campos requeridos
    if (empty($parentesco)) {
        manejarError('El campo parentesco es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $parentescoModel = new Parentesco($conexion);

        // Verificar parentesco duplicado
        $stmt = $conexion->prepare("SELECT IdParentesco FROM parentesco WHERE parentesco = :parentesco");
        $stmt->bindParam(':parentesco', $parentesco);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de parentesco ya existe');
        }

        // Configurar datos del parentesco
        $parentescoModel->parentesco = $parentesco;

        // Guardar parentesco
        if (!$parentescoModel->guardar()) {
            throw new Exception("Error al guardar el parentesco");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Parentesco creado exitosamente';
        header("Location: ../vistas/registros/parentesco/parentesco.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el parentesco: ' . $e->getMessage());
    }
}

function editarParentesco() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/parentesco/parentesco.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de parentesco inválido', '../vistas/registros/parentesco/parentesco.php');
    }

    // Validar campos requeridos
    $parentesco = trim($_POST['parentesco'] ?? '');
    if (empty($parentesco)) {
        manejarError("El campo parentesco es requerido", "../vistas/registros/parentesco/editar_parentesco.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $parentescoModel = new Parentesco($conexion);

        // Verificar que el parentesco existe
        if (!$parentescoModel->obtenerPorId($id)) {
            manejarError('Parentesco no encontrado', '../vistas/registros/parentesco/parentesco.php');
        }

        // Verificar duplicados (excluyendo al parentesco actual)
        $stmt = $conexion->prepare("SELECT IdParentesco FROM parentesco WHERE parentesco = :parentesco AND IdParentesco != :id");
        $stmt->bindParam(':parentesco', $parentesco);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El parentesco ya está en uso por otro registro', "../vistas/registros/parentesco/editar_parentesco.php?id=$id");
        }

        // Configurar datos actualizados
        $parentescoModel->IdParentesco = $id;
        $parentescoModel->parentesco = $parentesco;

        // Actualizar datos
        if (!$parentescoModel->actualizar()) {
            throw new Exception("Error al actualizar datos del parentesco");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Parentesco actualizado correctamente';
        header("Location: ../vistas/registros/parentesco/editar_parentesco.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/parentesco/editar_parentesco.php?id=$id");
    }
}

function eliminarParentesco() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/parentesco/parentesco.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/parentesco/parentesco.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $parentescoModel = new Parentesco($conexion);

        // Verificar que el parentesco existe
        if (!$parentescoModel->obtenerPorId($id)) {
            manejarError('Parentesco no encontrado', '../vistas/registros/parentesco/parentesco.php');
        }

        // Configurar ID para verificar dependencias
        $parentescoModel->IdParentesco = $id;

        // Verificar dependencias
        if ($parentescoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/parentesco/parentesco.php");
            exit();
        }

        // Intentar eliminar
        if (!$parentescoModel->eliminar()) {
            throw new Exception("Error al eliminar el parentesco");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/parentesco/parentesco.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/parentesco/parentesco.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/parentesco/nuevo_parentesco.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}