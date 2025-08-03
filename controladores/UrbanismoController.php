<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../vistas/login/login.php");
    exit();
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
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
}

function crearUrbanismo() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../vistas/registros/urbanismo/nuevo_urbanismo.php");
        exit();
    }

    // Obtener datos
    $urbanismo = trim($_POST['urbanismo'] ?? '');

    // Validar campos requeridos
    if (empty($urbanismo)) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Campos requeridos faltantes';
        header("Location: ../vistas/registros/urbanismo/nuevo_urbanismo.php");
        exit();
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
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'El nombre de urbanismo ya existe';
            header("Location: ../vistas/registros/urbanismo/nuevo_urbanismo.php");
            exit();
        }

        // Configurar datos del urbanismo
        $urbanismoModel->urbanismo = $urbanismo;

        // Guardar urbanismo
        $idUrbanismo = $urbanismoModel->guardar();
        if (!$idUrbanismo) {
            throw new Exception("Error al guardar el urbanismo");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['success_message'] = 'Urbanismo creado exitosamente';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();

    } catch (Exception $e) {
        error_log("Error al crear urbanismo: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error al crear el urbanismo: ' . $e->getMessage();
        header("Location: ../vistas/registros/urbanismo/nuevo_urbanismo.php");
        exit();
    }
}

function editarUrbanismo() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Método no permitido';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'ID de urbanismo inválido';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
    }

    // Validar campos requeridos
    $urbanismo = trim($_POST['urbanismo'] ?? '');
    if (empty($urbanismo)) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = "El campo urbanismo es requerido";
        header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $urbanismoModel = new Urbanismo($conexion);

        // Verificar que el urbanismo existe
        $urbanismoExistente = $urbanismoModel->obtenerPorId($id);
        if (!$urbanismoExistente) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Urbanismo no encontrado';
            header("Location: ../vistas/registros/urbanismo/urbanismo.php");
            exit();
        }

        // Verificar duplicados (excluyendo al urbanismo actual)
        $stmt = $conexion->prepare("SELECT IdUrbanismo FROM urbanismo WHERE urbanismo = :urbanismo AND IdUrbanismo != :id");
        $stmt->bindParam(':urbanismo', $urbanismo);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'El urbanismo ya está en uso por otro registro';
            header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
            exit();
        }

        // Configurar datos actualizados
        $urbanismoModel->IdUrbanismo = $id;
        $urbanismoModel->urbanismo = $urbanismo;

        // Actualizar datos
        if (!$urbanismoModel->actualizar()) {
            throw new Exception("Error al actualizar datos básicos");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['success_message'] = 'Urbanismo actualizado correctamente';
        header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        exit();

    } catch (Exception $e) {
        error_log("Error en edición: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error al actualizar: ' . $e->getMessage();
        header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        exit();
    }
}

function eliminarUrbanismo() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Método no permitido';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'ID inválido';
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $urbanismoModel = new Urbanismo($conexion);

        // Verificar que el urbanismo existe
        $urbanismoExistente = $urbanismoModel->obtenerPorId($id);
        if (!$urbanismoExistente) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Urbanismo no encontrado';
            header("Location: ../vistas/registros/urbanismo/urbanismo.php");
            exit();
        }

        // Configurar ID
        $urbanismoModel->IdUrbanismo = $id;
        
        // Verificar dependencias primero
        if ($urbanismoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error'; // Nuevo tipo de alerta
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
        error_log("Error al eliminar urbanismo: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error al eliminar: ' . $e->getMessage();
        header("Location: ../vistas/registros/urbanismo/urbanismo.php");
        exit();
    }
}