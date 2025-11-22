<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Aula.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearAula();
        break;
    case 'editar':
        editarAula();
        break;
    case 'eliminar':
        eliminarAula();
        break;
    case 'getByNivel': // Nueva acción para el filtrado AJAX
        obtenerAulasPorNivel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/aula/aula.php');
}

function obtenerAulasPorNivel() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit();
    }

    $nivelId = $_GET['nivelId'] ?? 0;
    
    if ($nivelId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de nivel inválido']);
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $aulaModel = new Aula($conexion);
        
        $aulas = $aulaModel->obtenerPorNivel($nivelId);
        
        header('Content-Type: application/json');
        echo json_encode($aulas, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener aulas: ' . $e->getMessage()]);
    }
    exit();
}

function crearAula() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/aula/nuevo_aula.php');
    }

    // Obtener datos
    $aula = trim($_POST['aula'] ?? '');
    $capacidad = trim($_POST['capacidad'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');

    // Validar campos requeridos
    if (empty($aula)) {
        manejarError('El campo aula es requerido');
    }
    if (empty($nivel)) {
        manejarError('El campo nivel es requerido');
    }
    if (empty($capacidad)) {
        manejarError('El campo capacidad es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $aulaModel = new Aula($conexion);

        // Verificar aula duplicado
        $query = "SELECT IdAula FROM aula WHERE aula = :aula AND IdNivel = :nivel";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':aula', $aula);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de aula ya existe para este nivel');
        }

        // Configurar datos del aula
        $aulaModel->aula = $aula;
        $aulaModel->IdNivel = $nivel;
        $aulaModel->capacidad = $capacidad;

        // Guardar aula
        if (!$aulaModel->guardar()) {
            throw new Exception("Error al guardar el aula");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Aula creado exitosamente';
        header("Location: ../vistas/registros/aula/aula.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el aula: ' . $e->getMessage());
    }
}

function editarAula() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/aula/aula.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de aula inválido', '../vistas/registros/aula/aula.php');
    }

    // Validar campos requeridos
    $aula = trim($_POST['aula'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $capacidad = trim($_POST['capacidad'] ?? '');
    
    if (empty($aula)) {
        manejarError("El campo aula es requerido", "../vistas/registros/aula/editar_aula.php?id=$id");
    }
    if (empty($nivel)) {
        manejarError("El campo nivel es requerido", "../vistas/registros/aula/editar_aula.php?id=$id");
    }if (empty($capacidad)) {
        manejarError("El campo capacidad es requerido", "../vistas/registros/aula/editar_aula.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $aulaModel = new Aula($conexion);

        // Verificar que el aula existe
        if (!$aulaModel->obtenerPorId($id)) {
            manejarError('Aula no encontrado', '../vistas/registros/aula/aula.php');
        }

        // Verificar duplicados (excluyendo al aula actual)
        $query = "SELECT IdAula FROM aula WHERE aula = :aula AND IdNivel = :nivel AND IdAula != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':aula', $aula);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El aula ya está en uso por otro registro', "../vistas/registros/aula/editar_aula.php?id=$id");
        }

        // Configurar datos actualizados
        $aulaModel->IdAula = $id;
        $aulaModel->aula = $aula;
        $aulaModel->IdNivel = $nivel;
        $aulaModel->capacidad = $capacidad;

        // Actualizar datos
        if (!$aulaModel->actualizar()) {
            throw new Exception("Error al actualizar datos del aula");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Aula actualizado correctamente';
        header("Location: ../vistas/registros/aula/editar_aula.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/aula/editar_aula.php?id=$id");
    }
}

function eliminarAula() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/aula/aula.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/aula/aula.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $aulaModel = new Aula($conexion);

        // Verificar que el aula existe
        if (!$aulaModel->obtenerPorId($id)) {
            manejarError('Aula no encontrado', '../vistas/registros/aula/aula.php');
        }

        // Configurar ID para verificar dependencias
        $aulaModel->IdAula = $id;

        // Verificar dependencias
        if ($aulaModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/aula/aula.php");
            exit();
        }

        // Intentar eliminar
        if (!$aulaModel->eliminar()) {
            throw new Exception("Error al eliminar el aula");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/aula/aula.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/aula/aula.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/aula/nuevo_aula.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}