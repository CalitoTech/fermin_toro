<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Urbanismo.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearUrbanismo();
        break;
    case 'editar':
        editarUrbanismo();
        break;
    case 'eliminar':
        eliminarUrbanismo();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/urbanismo/urbanismo.php');
}

function crearUrbanismo() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/urbanismo/nuevo_urbanismo.php');
    }

    // Obtener datos
    $urbanismo = trim($_POST['urbanismo'] ?? '');

    // Validar campos requeridos
    if (empty($urbanismo)) {
        manejarError('El campo urbanismo es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $urbanismoModel = new Urbanismo($conexion);

        // Verificar urbanismo duplicado
        $stmt = $conexion->prepare("SELECT IdUrbanismo FROM urbanismo WHERE urbanismo = :urbanismo");
        $stmt->bindParam(':urbanismo', $urbanismo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de urbanismo ya existe');
        }

        // Configurar datos del urbanismo
        $urbanismoModel->urbanismo = $urbanismo;

        // Guardar urbanismo
        if (!$urbanismoModel->guardar()) {
            throw new Exception("Error al guardar el urbanismo");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Urbanismo creado exitosamente';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el urbanismo: ' . $e->getMessage());
    }
}

function editarUrbanismo() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/urbanismo/urbanismo.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de urbanismo inválido', '../vistas/registros/urbanismo/urbanismo.php');
    }

    // Validar campos requeridos
    $urbanismo = trim($_POST['urbanismo'] ?? '');
    if (empty($urbanismo)) {
        manejarError("El campo urbanismo es requerido", "../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $urbanismoModel = new Urbanismo($conexion);

        // Verificar que el urbanismo existe
        if (!$urbanismoModel->obtenerPorId($id)) {
            manejarError('Urbanismo no encontrado', '../vistas/registros/urbanismo/urbanismo.php');
        }

        // Verificar duplicados (excluyendo al urbanismo actual)
        $stmt = $conexion->prepare("SELECT IdUrbanismo FROM urbanismo WHERE urbanismo = :urbanismo AND IdUrbanismo != :id");
        $stmt->bindParam(':urbanismo', $urbanismo);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El urbanismo ya está en uso por otro registro', "../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        }

        // Configurar datos actualizados
        $urbanismoModel->IdUrbanismo = $id;
        $urbanismoModel->urbanismo = $urbanismo;

        // Actualizar datos
        if (!$urbanismoModel->actualizar()) {
            throw new Exception("Error al actualizar datos del urbanismo");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Urbanismo actualizado correctamente';
        header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
    }
}

function eliminarUrbanismo() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/urbanismo/urbanismo.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/urbanismo/urbanismo.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $urbanismoModel = new Urbanismo($conexion);

        // Verificar que el urbanismo existe
        if (!$urbanismoModel->obtenerPorId($id)) {
            manejarError('Urbanismo no encontrado', '../vistas/registros/urbanismo/urbanismo.php');
        }

        // Configurar ID para verificar dependencias
        $urbanismoModel->IdUrbanismo = $id;

        // Verificar dependencias
        if ($urbanismoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/urbanismo/urbanismo.php");
            exit();
        }

        // Intentar eliminar
        if (!$urbanismoModel->eliminar()) {
            throw new Exception("Error al eliminar el urbanismo");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/urbanismo/urbanismo.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/urbanismo/nuevo_urbanismo.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}