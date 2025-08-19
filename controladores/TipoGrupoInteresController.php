<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/TipoGrupoInteres.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearTipoGrupoInteres();
        break;
    case 'editar':
        editarTipoGrupoInteres();
        break;
    case 'eliminar':
        eliminarTipoGrupoInteres();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
}

function crearTipoGrupoInteres() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/tipo_grupo_interes/nuevo_tipo_grupo_interes.php');
    }

    // Obtener datos
    $nombre_grupo = trim($_POST['nombre_grupo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad_maxima = trim($_POST['capacidad_maxima'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $inscripcion_activa = isset($_POST['inscripcion_activa']) ? 1 : 0;

    // Validar campos requeridos
    if (empty($nombre_grupo)) {
        manejarError('El campo nombre_grupo es requerido');
    }
    if (empty($nivel)) {
        manejarError('El campo nivel es requerido');
    }
    if (empty($descripcion)) {
        manejarError('El campo descripcion es requerido');
    }
    if (empty($capacidad_maxima)) {
        manejarError('El campo capacidad_maxima es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $tipo_grupo_interesModel = new TipoGrupoInteres($conexion);

        // Verificar tipo_grupo_interes duplicado
        $query = "SELECT IdTipo_Grupo FROM tipo_grupo_interes WHERE nombre_grupo = :nombre_grupo AND IdNivel = :nivel";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nombre_grupo', $nombre_grupo);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre de nombre_grupo ya existe para este nivel');
        }

        // Configurar datos del tipo_grupo_interes
        $tipo_grupo_interesModel->nombre_grupo = $nombre_grupo;
        $tipo_grupo_interesModel->descripcion = $descripcion;
        $tipo_grupo_interesModel->capacidad_maxima = $capacidad_maxima;
        $tipo_grupo_interesModel->IdNivel = $nivel;
        $tipo_grupo_interesModel->inscripcion_activa = $inscripcion_activa;

        // Guardar tipo_grupo_interes
        if (!$tipo_grupo_interesModel->guardar()) {
            throw new Exception("Error al guardar el tipo de grupo de interés");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Tipo de Grupo de Interés creado exitosamente';
        header("Location: ../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el tipo de grupo de interés: ' . $e->getMessage());
    }
}

function editarTipoGrupoInteres() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de tipo de grupo de interés inválido', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
    }

    // Validar campos requeridos
    $nombre_grupo = trim($_POST['nombre_grupo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad_maxima = trim($_POST['capacidad_maxima'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $inscripcion_activa = isset($_POST['inscripcion_activa']) ? 1 : 0;
    
    if (empty($nombre_grupo)) {
        manejarError("El campo nombre_grupo es requerido", "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
    }
    if (empty($nivel)) {
        manejarError("El campo nivel es requerido", "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
    }
    if (empty($descripcion)) {
        manejarError("El campo descripcion es requerido", "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
    }
    if (empty($capacidad_maxima)) {
        manejarError("El campo capacidad_maxima es requerido", "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $tipo_grupo_interesModel = new TipoGrupoInteres($conexion);

        // Verificar que el tipo_grupo_interes existe
        if (!$tipo_grupo_interesModel->obtenerPorId($id)) {
            manejarError('Tipo de Grupo de Interés no encontrado', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
        }

        // Verificar duplicados (excluyendo al tipo_grupo_interes actual)
        $query = "SELECT IdTipo_Grupo FROM tipo_grupo_interes WHERE nombre_grupo = :nombre_grupo AND IdNivel = :nivel AND IdTipo_Grupo != :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nombre_grupo', $nombre_grupo);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            manejarError('El nombre_grupo ya está en uso por otro registro', "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
        }

        // Configurar datos actualizados
        $tipo_grupo_interesModel->IdTipoGrupo = $id;
        $tipo_grupo_interesModel->nombre_grupo = $nombre_grupo;
        $tipo_grupo_interesModel->descripcion = $descripcion;
        $tipo_grupo_interesModel->capacidad_maxima = $capacidad_maxima;
        $tipo_grupo_interesModel->IdNivel = $nivel;
        $tipo_grupo_interesModel->inscripcion_activa = $inscripcion_activa;

        // Actualizar datos
        if (!$tipo_grupo_interesModel->actualizar()) {
            throw new Exception("Error al actualizar datos del tipo de grupo de interés");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Tipo de Grupo de Interés actualizado correctamente';
        header("Location: ../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/tipo_grupo_interes/editar_tipo_grupo_interes.php?id=$id");
    }
}

function eliminarTipoGrupoInteres() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $tipo_grupo_interesModel = new TipoGrupoInteres($conexion);

        // Verificar que el tipo_grupo_interes existe
        if (!$tipo_grupo_interesModel->obtenerPorId($id)) {
            manejarError('Tipo de Grupo de Interés no encontrado', '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
        }

        // Configurar ID para verificar dependencias
        $tipo_grupo_interesModel->IdTipoGrupo = $id;

        // Verificar dependencias
        if ($tipo_grupo_interesModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php");
            exit();
        }

        // Intentar eliminar
        if (!$tipo_grupo_interesModel->eliminar()) {
            throw new Exception("Error al eliminar el tipo de grupo de interés");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/tipo_grupo_interes/tipo_grupo_interes.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/tipo_grupo_interes/nuevo_tipo_grupo_interes.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}