<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/GrupoInteres.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearGrupoInteres();
        break;
    case 'editar':
        editarGrupoInteres();
        break;
    case 'eliminar':
        eliminarGrupoInteres();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/grupo_interes/grupo_interes.php');
}

function crearGrupoInteres() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/grupo_interes/nuevo_grupo_interes.php');
    }

    // Obtener datos
    $idTipo_Grupo = trim($_POST['IdTipo_Grupo'] ?? '');
    $idProfesor = trim($_POST['IdProfesor'] ?? '');
    $idCurso = trim($_POST['IdCurso'] ?? '');

    // Validar campos requeridos
    if (empty($idTipo_Grupo) || empty($idProfesor) || empty($idCurso)) {
        manejarError('Todos los campos son obligatorios');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Obtener año escolar activo
        $fechaEscolarModel = new FechaEscolar($conexion);
        $fechaActiva = $fechaEscolarModel->obtenerActivo();

        if (!$fechaActiva) {
            manejarError('No hay un año escolar activo. Contacte al administrador.');
        }

        $grupoModel = new GrupoInteres($conexion);
        $grupoModel->IdTipo_Grupo = $idTipo_Grupo;
        $grupoModel->IdProfesor = $idProfesor;
        $grupoModel->IdCurso = $idCurso;
        $grupoModel->IdFecha_Escolar = $fechaActiva['IdFecha_Escolar'];

        if ($grupoModel->guardar()) {
            $_SESSION['alert'] = 'success';
            $_SESSION['message'] = 'Grupo de interés creado exitosamente';
            header("Location: ../vistas/registros/grupo_interes/grupo_interes.php");
            exit();
        } else {
            throw new Exception("Error al guardar en la base de datos");
        }

    } catch (Exception $e) {
        manejarError('Error al crear: ' . $e->getMessage());
    }
}

function editarGrupoInteres() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/grupo_interes/grupo_interes.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    $idTipo_Grupo = trim($_POST['IdTipo_Grupo'] ?? '');
    $idProfesor = trim($_POST['IdProfesor'] ?? '');
    $idCurso = trim($_POST['IdCurso'] ?? '');

    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/grupo_interes/grupo_interes.php');
    }

    if (empty($idTipo_Grupo) || empty($idProfesor) || empty($idCurso)) {
        manejarError('Todos los campos son obligatorios', "../vistas/registros/grupo_interes/editar_grupo_interes.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $grupoModel = new GrupoInteres($conexion);

        // Verificar que existe
        $grupoActual = $grupoModel->obtenerPorId($id);
        if (!$grupoActual) {
            manejarError('Grupo no encontrado', '../vistas/registros/grupo_interes/grupo_interes.php');
        }

        // Configurar datos
        $grupoModel->IdGrupo_Interes = $id;
        $grupoModel->IdTipo_Grupo = $idTipo_Grupo;
        $grupoModel->IdProfesor = $idProfesor;
        $grupoModel->IdCurso = $idCurso;
        $grupoModel->IdFecha_Escolar = $grupoActual['IdFecha_Escolar']; // Mantener el mismo año escolar

        if ($grupoModel->actualizar()) {
            $_SESSION['alert'] = 'actualizar';
            $_SESSION['message'] = 'Grupo actualizado correctamente';
            header("Location: ../vistas/registros/grupo_interes/editar_grupo_interes.php?id=$id");
            exit();
        } else {
            throw new Exception("Error al actualizar");
        }

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/grupo_interes/editar_grupo_interes.php?id=$id");
    }
}

function eliminarGrupoInteres() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/grupo_interes/grupo_interes.php');
    }

    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/grupo_interes/grupo_interes.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $grupoModel = new GrupoInteres($conexion);
        
        // Verificar existencia
        if (!$grupoModel->obtenerPorId($id)) {
            manejarError('Grupo no encontrado', '../vistas/registros/grupo_interes/grupo_interes.php');
        }

        $grupoModel->IdGrupo_Interes = $id;

        // Verificar dependencias
        if ($grupoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/grupo_interes/grupo_interes.php");
            exit();
        }

        // Eliminar
        if ($grupoModel->eliminar()) {
            $_SESSION['alert'] = 'deleted';
            header("Location: ../vistas/registros/grupo_interes/grupo_interes.php");
            exit();
        } else {
            throw new Exception("Error al eliminar");
        }

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/grupo_interes/grupo_interes.php');
    }
}

function manejarError($mensaje, $urlRedireccion = '../vistas/registros/grupo_interes/nuevo_grupo_interes.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
