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

        // Verificar duplicados
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

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // Guardar urbanismo
            $idUrbanismo = $urbanismoModel->guardar();
            if (!$idUrbanismo) {
                throw new Exception("Error al guardar el urbanismo");
            }

            // Confirmar transacción
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['success_message'] = 'Urbanismo creado exitosamente';
            header("Location: ../vistas/registros/urbanismo/urbanismo.php");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al crear urbanismo: " . $e->getMessage());
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Error al crear el urbanismo';
            header("Location: ../vistas/registros/urbanismo/nuevo_urbanismo.php");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general al crear urbanismo: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error interno del servidor';
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
    $required = ['urbanismo'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = "El campo $field es requerido";
            header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
            exit();
        }
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el urbanismo existe
        $urbanismoModel = new Urbanismo($conexion);
        if (!$urbanismoModel->obtenerPorId($id)) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Urbanismo no encontrado';
            header("Location: ../vistas/registros/urbanismo/urbanismo.php");
            exit();
        }

        // Verificar duplicados (excluyendo al urbanismo actual)
        $urbanismo = trim($_POST['urbanismo']);

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
        $urbanismoModel->urbanismo = trim($_POST['urbanismo']);

        // Iniciar transacción PRINCIPAL (solo esta)
        $conexion->beginTransaction();

        try {
            // 1. Actualizar datos básicos
            if (!$urbanismoModel->actualizar()) {
                throw new Exception("Error al actualizar datos básicos");
            }

            // Confirmar transacción PRINCIPAL
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['success_message'] = 'Urbanismo actualizado correctamente';
            header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de actualización: " . $e->getMessage());
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general en edición: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error interno del servidor';
        header("Location: ../vistas/registros/urbanismo/editar_urbanismo.php?id=$id");
        exit();
    }
}

function eliminarUrbanismo() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../vistas/registros/urbanismo/urbanismo.php?error=metodo_no_permitido");
        exit();
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        header("Location: ../vistas/registros/urbanismo/urbanismo.php?error=id_invalido");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el urbanismo existe
        $urbanismoModel = new Urbanismo($conexion);
        if (!$urbanismoModel->obtenerPorId($id)) {
            header("Location: ../vistas/registros/urbanismo/urbanismo.php?error=urbanismo_no_encontrado");
            exit();
        }

        // Iniciar transacción
        $conexion->beginTransaction();

        try {

            // 3. Eliminar el urbanismo
            if (!$urbanismoModel->eliminar()) {
                throw new Exception("Error al eliminar el urbanismo");
            }

            // Confirmar transacción
            $conexion->commit();

            header("Location: ../vistas/registros/urbanismo/urbanismo.php?deleted=1");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al eliminar usuario: " . $e->getMessage());
            header("Location: ../vistas/registros/urbanismo/urbanismo.php?error=operacion_fallida");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general al eliminar usuario: " . $e->getMessage());
        header("Location: ../vistas/registros/urbanismo/urbanismo.php?error=error_interno");
        exit();
    }
}