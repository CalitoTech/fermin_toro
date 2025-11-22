<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Materia.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearMateria();
        break;
    case 'editar':
        editarMateria();
        break;
    case 'eliminar':
        eliminarMateria();
        break;
    case 'getByNivel':
        obtenerMateriasPorNivel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/materia/materia.php');
}

function obtenerMateriasPorNivel() {
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
        $materiaModel = new Materia($conexion);
        
        $materias = $materiaModel->obtenerPorNivel($nivelId);
        
        header('Content-Type: application/json');
        echo json_encode($materias, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener materias: ' . $e->getMessage()]);
    }
    exit();
}

function crearMateria() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/materia/nuevo_materia.php');
    }

    // Obtener datos
    $materia = trim($_POST['materia'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');

    // Validar campos requeridos
    if (empty($materia)) {
        manejarError('El campo materia es requerido');
    }
    if (empty($nivel)) {
        manejarError('El campo nivel es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $materiaModel = new Materia($conexion);

        // Verificar materia duplicado
        $query = "SELECT IdMateria FROM materia WHERE materia = :materia AND IdNivel = :nivel";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':materia', $materia);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de materia ya existe para este nivel');
        }

        // Configurar datos del materia
        $materiaModel->materia = $materia;
        $materiaModel->IdNivel = $nivel;

        // Guardar materia
        if (!$materiaModel->guardar()) {
            throw new Exception("Error al guardar el materia");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Materia creado exitosamente';
        header("Location: ../vistas/registros/materia/materia.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el materia: ' . $e->getMessage());
    }
}

function editarMateria() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/materia/materia.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de materia inválido', '../vistas/registros/materia/materia.php');
    }

    // Validar campos requeridos
    $materia = trim($_POST['materia'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    if (empty($materia)) {
        manejarError("El campo materia es requerido", "../vistas/registros/materia/editar_materia.php?id=$id");
    }
    if (empty($nivel)) {
        manejarError("El campo nivel es requerido", "../vistas/registros/materia/editar_materia.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $materiaModel = new Materia($conexion);

        // Verificar que el materia existe
        if (!$materiaModel->obtenerPorId($id)) {
            manejarError('Materia no encontrado', '../vistas/registros/materia/materia.php');
        }

        // Verificar duplicados (excluyendo al materia actual)
        $query = "SELECT IdMateria FROM materia WHERE materia = :materia AND IdNivel = :nivel AND IdMateria != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':materia', $materia);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El materia ya está en uso por otro registro', "../vistas/registros/materia/editar_materia.php?id=$id");
        }

        // Configurar datos actualizados
        $materiaModel->IdMateria = $id;
        $materiaModel->materia = $materia;
        $materiaModel->IdNivel = $nivel;

        // Actualizar datos
        if (!$materiaModel->actualizar()) {
            throw new Exception("Error al actualizar datos del materia");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Materia actualizado correctamente';
        header("Location: ../vistas/registros/materia/editar_materia.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/materia/editar_materia.php?id=$id");
    }
}

function eliminarMateria() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/materia/materia.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/materia/materia.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $materiaModel = new Materia($conexion);

        // Verificar que el materia existe
        if (!$materiaModel->obtenerPorId($id)) {
            manejarError('Materia no encontrado', '../vistas/registros/materia/materia.php');
        }

        // Configurar ID para verificar dependencias
        $materiaModel->IdMateria = $id;

        // Verificar dependencias
        if ($materiaModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/materia/materia.php");
            exit();
        }

        // Intentar eliminar
        if (!$materiaModel->eliminar()) {
            throw new Exception("Error al eliminar el materia");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/materia/materia.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/materia/materia.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/materia/nuevo_materia.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}