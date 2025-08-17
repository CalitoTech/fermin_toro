<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Bloque.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearBloque();
        break;
    case 'editar':
        editarBloque();
        break;
    case 'eliminar':
        eliminarBloque();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/bloque/bloque.php');
}

function crearBloque() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/bloque/nuevo_bloque.php');
    }

    // Obtener datos
    $hora_inicio = trim($_POST['hora_inicio'] ?? '');
    $hora_fin = trim($_POST['hora_fin'] ?? '');

    // Validar campos requeridos
    if (empty($hora_inicio)) {
        manejarError('El campo hora_inicio es requerido');
    }
    if (empty($hora_fin)) {
        manejarError('El campo hora_fin es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $bloqueModel = new Bloque($conexion);

        // Verificar bloque duplicado
        $stmt = $conexion->prepare("SELECT IdBloque FROM bloque WHERE hora_inicio = :hora_inicio");
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El bloque ya existe');
        }

        // Configurar datos del bloque
        $bloqueModel->hora_inicio = $hora_inicio;
        $bloqueModel->hora_fin = $hora_fin;

        // Guardar bloque
        if (!$bloqueModel->guardar()) {
            throw new Exception("Error al guardar el bloque");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Bloque creado exitosamente';
        header("Location: ../vistas/registros/bloque/bloque.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el bloque: ' . $e->getMessage());
    }
}

function editarBloque() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/bloque/bloque.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de bloque inválido', '../vistas/registros/bloque/bloque.php');
    }

    // Validar campos requeridos
    $hora_inicio = trim($_POST['hora_inicio'] ?? '');
    if (empty($hora_inicio)) {
        manejarError("El campo hora_inicio es requerido", "../vistas/registros/bloque/editar_bloque.php?id=$id");
    }

    $hora_fin = trim($_POST['hora_fin'] ?? '');
    if (empty($hora_fin)) {
        manejarError("El campo hora_fin es requerido", "../vistas/registros/bloque/editar_bloque.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $bloqueModel = new Bloque($conexion);

        // Verificar que el bloque existe
        if (!$bloqueModel->obtenerPorId($id)) {
            manejarError('Bloque no encontrado', '../vistas/registros/bloque/bloque.php');
        }

        // Verificar duplicados (excluyendo al bloque actual)
        $stmt = $conexion->prepare("SELECT IdBloque FROM bloque WHERE hora_inicio = :hora_inicio AND IdBloque != :id");
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El bloque ya está en uso por otro registro', "../vistas/registros/bloque/editar_bloque.php?id=$id");
        }

        // Configurar datos actualizados
        $bloqueModel->IdBloque = $id;
        $bloqueModel->hora_inicio = $hora_inicio;
        $bloqueModel->hora_fin = $hora_fin;

        // Actualizar datos
        if (!$bloqueModel->actualizar()) {
            throw new Exception("Error al actualizar datos del bloque");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Bloque actualizado correctamente';
        header("Location: ../vistas/registros/bloque/editar_bloque.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/bloque/editar_bloque.php?id=$id");
    }
}

function eliminarBloque() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/bloque/bloque.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/bloque/bloque.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $bloqueModel = new Bloque($conexion);

        // Verificar que el bloque existe
        if (!$bloqueModel->obtenerPorId($id)) {
            manejarError('Bloque no encontrado', '../vistas/registros/bloque/bloque.php');
        }

        // Configurar ID para verificar dependencias
        $bloqueModel->IdBloque = $id;

        // Verificar dependencias
        if ($bloqueModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/bloque/bloque.php");
            exit();
        }

        // Intentar eliminar
        if (!$bloqueModel->eliminar()) {
            throw new Exception("Error al eliminar el bloque");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/bloque/bloque.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/bloque/bloque.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/bloque/nuevo_bloque.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}