<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearNivel();
        break;
    case 'editar':
        editarNivel();
        break;
    case 'eliminar':
        eliminarNivel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/nivel/nivel.php');
}

function crearNivel() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/nivel/nuevo_nivel.php');
    }

    // Obtener datos
    $nivel = trim($_POST['nivel'] ?? '');

    // Validar campos requeridos
    if (empty($nivel)) {
        manejarError('El campo nivel es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $nivelModel = new Nivel($conexion);

        // Verificar nivel duplicado
        $query = "SELECT IdNivel FROM nivel WHERE nivel = :nivel";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de nivel ya existe');
        }

        // Configurar datos del nivel
        $nivelModel->nivel = $nivel;

        // Guardar nivel
        if (!$nivelModel->guardar()) {
            throw new Exception("Error al guardar el nivel");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Nivel creado exitosamente';
        header("Location: ../vistas/registros/nivel/nivel.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el nivel: ' . $e->getMessage());
    }
}

function editarNivel() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/nivel/nivel.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de nivel inválido', '../vistas/registros/nivel/nivel.php');
    }

    // Validar campos requeridos
    $nivel = trim($_POST['nivel'] ?? '');
    
    if (empty($nivel)) {
        manejarError("El campo nivel es requerido", "../vistas/registros/nivel/editar_nivel.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $nivelModel = new Nivel($conexion);

        // Verificar que el nivel existe
        if (!$nivelModel->obtenerPorId($id)) {
            manejarError('Nivel no encontrado', '../vistas/registros/nivel/nivel.php');
        }

        // Verificar duplicados (excluyendo al nivel actual)
        $query = "SELECT IdNivel FROM nivel WHERE nivel = :nivel AND IdNivel != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nivel ya está en uso por otro registro', "../vistas/registros/nivel/editar_nivel.php?id=$id");
        }

        // Configurar datos actualizados
        $nivelModel->IdNivel = $id;
        $nivelModel->nivel = $nivel;

        // Actualizar datos
        if (!$nivelModel->actualizar()) {
            throw new Exception("Error al actualizar datos del nivel");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Nivel actualizado correctamente';
        header("Location: ../vistas/registros/nivel/editar_nivel.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/nivel/editar_nivel.php?id=$id");
    }
}

function eliminarNivel() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/nivel/nivel.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/nivel/nivel.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $nivelModel = new Nivel($conexion);

        // Verificar que el nivel existe
        if (!$nivelModel->obtenerPorId($id)) {
            manejarError('Nivel no encontrado', '../vistas/registros/nivel/nivel.php');
        }

        // Configurar ID para verificar dependencias
        $nivelModel->IdNivel = $id;

        // Verificar dependencias
        if ($nivelModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/nivel/nivel.php");
            exit();
        }

        // Intentar eliminar
        if (!$nivelModel->eliminar()) {
            throw new Exception("Error al eliminar el nivel");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/nivel/nivel.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/nivel/nivel.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/nivel/nuevo_nivel.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}