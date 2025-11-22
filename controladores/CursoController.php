<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Curso.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearCurso();
        break;
    case 'editar':
        editarCurso();
        break;
    case 'eliminar':
        eliminarCurso();
        break;
    case 'getByNivel':
        obtenerCursosPorNivel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/curso/curso.php');
}

function obtenerCursosPorNivel() {
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
        $cursoModel = new Curso($conexion);
        
        $cursos = $cursoModel->obtenerPorNivel($nivelId);
        
        header('Content-Type: application/json');
        echo json_encode($cursos, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener cursos: ' . $e->getMessage()]);
    }
    exit();
}

function crearCurso() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/curso/nuevo_curso.php');
    }

    // Obtener datos
    $curso = trim($_POST['curso'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');

    // Validar campos requeridos
    if (empty($curso)) {
        manejarError('El campo curso es requerido');
    }
    if (empty($nivel)) {
        manejarError('El campo nivel es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoModel = new Curso($conexion);

        // Verificar curso duplicado
        $query = "SELECT IdCurso FROM curso WHERE curso = :curso AND IdNivel = :nivel";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':curso', $curso);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de curso ya existe para este nivel');
        }

        // Configurar datos del curso
        $cursoModel->curso = $curso;
        $cursoModel->IdNivel = $nivel;

        // Guardar curso
        if (!$cursoModel->guardar()) {
            throw new Exception("Error al guardar el curso");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Curso creado exitosamente';
        header("Location: ../vistas/registros/curso/curso.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el curso: ' . $e->getMessage());
    }
}

function editarCurso() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/curso/curso.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de curso inválido', '../vistas/registros/curso/curso.php');
    }

    // Validar campos requeridos
    $curso = trim($_POST['curso'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    
    if (empty($curso)) {
        manejarError("El campo curso es requerido", "../vistas/registros/curso/editar_curso.php?id=$id");
    }
    if (empty($nivel)) {
        manejarError("El campo nivel es requerido", "../vistas/registros/curso/editar_curso.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoModel = new Curso($conexion);

        // Verificar que el curso existe
        if (!$cursoModel->obtenerPorId($id)) {
            manejarError('Curso no encontrado', '../vistas/registros/curso/curso.php');
        }

        // Verificar duplicados (excluyendo al curso actual)
        $query = "SELECT IdCurso FROM curso WHERE curso = :curso AND IdNivel = :nivel AND IdCurso != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':curso', $curso);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El curso ya está en uso por otro registro', "../vistas/registros/curso/editar_curso.php?id=$id");
        }

        // Configurar datos actualizados
        $cursoModel->IdCurso = $id;
        $cursoModel->curso = $curso;
        $cursoModel->IdNivel = $nivel;

        // Actualizar datos
        if (!$cursoModel->actualizar()) {
            throw new Exception("Error al actualizar datos del curso");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Curso actualizado correctamente';
        header("Location: ../vistas/registros/curso/editar_curso.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/curso/editar_curso.php?id=$id");
    }
}

function eliminarCurso() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/curso/curso.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/curso/curso.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $cursoModel = new Curso($conexion);

        // Verificar que el curso existe
        if (!$cursoModel->obtenerPorId($id)) {
            manejarError('Curso no encontrado', '../vistas/registros/curso/curso.php');
        }

        // Configurar ID para verificar dependencias
        $cursoModel->IdCurso = $id;

        // Verificar dependencias
        if ($cursoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/curso/curso.php");
            exit();
        }

        // Intentar eliminar
        if (!$cursoModel->eliminar()) {
            throw new Exception("Error al eliminar el curso");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/curso/curso.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/curso/curso.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/curso/nuevo_curso.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}