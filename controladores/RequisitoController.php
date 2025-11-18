<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Requisito.php';
require_once __DIR__ . '/../modelos/Nivel.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearRequisito();
        break;
    case 'editar':
        editarRequisito();
        break;
    case 'eliminar':
        eliminarRequisito();
        break;
    case 'getByNivel':
        obtenerRequisitosPorNivel();
        break;
    default:
        manejarError('Acción no válida', '../vistas/registros/requisito/requisito.php');
}

function obtenerRequisitosPorNivel() {
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
        $requisitoModel = new Requisito($conexion);

        // Obtener requisitos sin filtros adicionales por ahora
        $requisitos = $requisitoModel->obtenerPorNivel($nivelId);

        header('Content-Type: application/json');
        echo json_encode($requisitos, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener requisitos: ' . $e->getMessage()]);
    }
    exit();
}

function crearRequisito() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/requisito/nuevo_requisito.php');
    }

    // Obtener datos
    $requisito = trim($_POST['requisito'] ?? '');
    $nivel = !empty($_POST['nivel']) ? (int)$_POST['nivel'] : null;
    $tipoTrabajador = !empty($_POST['tipoTrabajador']) ? (int)$_POST['tipoTrabajador'] : null;
    $tipoRequisito = !empty($_POST['tipoRequisito']) ? (int)$_POST['tipoRequisito'] : null;
    $soloPlantelPrivado = isset($_POST['soloPlantelPrivado']) ? 1 : 0;
    $descripcionAdicional = trim($_POST['descripcionAdicional'] ?? '');
    $obligatorio = isset($_POST['obligatorio']) ? 1 : 0;

    // Validar campos requeridos
    if (empty($requisito)) {
        manejarError('El campo requisito es requerido');
    }
    if (empty($tipoRequisito)) {
        manejarError('El campo tipo de requisito es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $requisitoModel = new Requisito($conexion);

        // Configurar datos del requisito
        $requisitoModel->requisito = $requisito;
        $requisitoModel->IdNivel = $nivel;
        $requisitoModel->IdTipoTrabajador = $tipoTrabajador;
        $requisitoModel->IdTipo_Requisito = $tipoRequisito;
        $requisitoModel->solo_plantel_privado = $soloPlantelPrivado;
        $requisitoModel->descripcion_adicional = !empty($descripcionAdicional) ? $descripcionAdicional : null;
        $requisitoModel->obligatorio = $obligatorio;

        // Guardar requisito
        if (!$requisitoModel->guardar()) {
            throw new Exception("Error al guardar el requisito");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Requisito creado exitosamente';
        header("Location: ../vistas/registros/requisito/requisito.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear el requisito: ' . $e->getMessage());
    }
}

function editarRequisito() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/registros/requisito/requisito.php');
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de requisito inválido', '../vistas/registros/requisito/requisito.php');
    }

    // Validar campos requeridos
    $requisito = trim($_POST['requisito'] ?? '');
    $nivel = !empty($_POST['nivel']) ? (int)$_POST['nivel'] : null;
    $tipoTrabajador = !empty($_POST['tipoTrabajador']) ? (int)$_POST['tipoTrabajador'] : null;
    $tipoRequisito = !empty($_POST['tipoRequisito']) ? (int)$_POST['tipoRequisito'] : null;
    $soloPlantelPrivado = isset($_POST['soloPlantelPrivado']) ? 1 : 0;
    $descripcionAdicional = trim($_POST['descripcionAdicional'] ?? '');
    $obligatorio = isset($_POST['obligatorio']) ? 1 : 0;

    if (empty($requisito)) {
        manejarError("El campo requisito es requerido", "../vistas/registros/requisito/editar_requisito.php?id=$id");
    }
    if (empty($tipoRequisito)) {
        manejarError("El campo tipo de requisito es requerido", "../vistas/registros/requisito/editar_requisito.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $requisitoModel = new Requisito($conexion);

        // Verificar que el requisito existe
        if (!$requisitoModel->obtenerPorId($id)) {
            manejarError('Requisito no encontrado', '../vistas/registros/requisito/requisito.php');
        }

        // Configurar datos actualizados
        $requisitoModel->IdRequisito = $id;
        $requisitoModel->requisito = $requisito;
        $requisitoModel->IdNivel = $nivel;
        $requisitoModel->IdTipoTrabajador = $tipoTrabajador;
        $requisitoModel->IdTipo_Requisito = $tipoRequisito;
        $requisitoModel->solo_plantel_privado = $soloPlantelPrivado;
        $requisitoModel->descripcion_adicional = !empty($descripcionAdicional) ? $descripcionAdicional : null;
        $requisitoModel->obligatorio = $obligatorio;

        // Actualizar datos
        if (!$requisitoModel->actualizar()) {
            throw new Exception("Error al actualizar datos del requisito");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Requisito actualizado correctamente';
        header("Location: ../vistas/registros/requisito/editar_requisito.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/registros/requisito/editar_requisito.php?id=$id");
    }
}

function eliminarRequisito() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', '../vistas/registros/requisito/requisito.php');
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        manejarError('ID inválido', '../vistas/registros/requisito/requisito.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $requisitoModel = new Requisito($conexion);

        // Verificar que el requisito existe
        if (!$requisitoModel->obtenerPorId($id)) {
            manejarError('Requisito no encontrado', '../vistas/registros/requisito/requisito.php');
        }

        // Configurar ID para verificar dependencias
        $requisitoModel->IdRequisito = $id;

        // Verificar dependencias
        if ($requisitoModel->tieneDependencias()) {
            $_SESSION['alert'] = 'dependency_error';
            header("Location: ../vistas/registros/requisito/requisito.php");
            exit();
        }

        // Intentar eliminar
        if (!$requisitoModel->eliminar()) {
            throw new Exception("Error al eliminar el requisito");
        }

        $_SESSION['alert'] = 'deleted';
        header("Location: ../vistas/registros/requisito/requisito.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage(), '../vistas/registros/requisito/requisito.php');
    }
}

/**
 * Maneja los errores de forma consistente
 * @param string $mensaje Mensaje de error a mostrar
 * @param string $urlRedireccion URL a la que redirigir (opcional)
 */
function manejarError(string $mensaje, string $urlRedireccion = '../vistas/registros/requisito/nuevo_requisito.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
