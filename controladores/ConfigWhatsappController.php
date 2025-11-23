<?php
session_start();

// Verificación de sesión y permisos (solo administradores)
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

// Verificar que sea administrador (IdPerfil = 1)
if (!isset($_SESSION['idPerfil']) || $_SESSION['idPerfil'] != 1) {
    manejarError('No tiene permisos para acceder a esta sección', '../vistas/inicio/inicio/inicio.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ConfigWhatsapp.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'guardar':
        guardarConfiguracion();
        break;
    case 'actualizar':
        actualizarConfiguracion();
        break;
    case 'actualizar_apikey':
        actualizarApiKey();
        break;
    case 'activar':
        activarConfiguracion();
        break;
    case 'eliminar':
        eliminarConfiguracion();
        break;
    default:
        manejarError('Acción no válida', '../vistas/configuracion/whatsapp/whatsapp.php');
}

function guardarConfiguracion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    // Obtener datos
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');
    $nombreInstancia = trim($_POST['nombre_instancia'] ?? '');
    $loginUrl = trim($_POST['login_url'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (empty($apiUrl)) {
        manejarError('La URL de la API es requerida');
    }

    if (empty($nombreInstancia)) {
        manejarError('El nombre de la instancia es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $configModel = new ConfigWhatsapp($conexion);

        // Si se activa esta configuración, desactivar las demás
        if ($activo) {
            $configModel->desactivarOtras();
        }

        // Configurar datos
        $configModel->api_url = $apiUrl;
        $configModel->nombre_instancia = $nombreInstancia;
        $configModel->login_url = !empty($loginUrl) ? $loginUrl : null;
        $configModel->activo = $activo;

        // Guardar con API key (será encriptada)
        if (!$configModel->guardar($apiKey)) {
            throw new Exception("Error al guardar la configuración");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Configuración guardada exitosamente';
        header("Location: ../vistas/configuracion/whatsapp/whatsapp.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al guardar: ' . $e->getMessage());
    }
}

function actualizarConfiguracion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de configuración inválido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    // Obtener datos
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? ''); // Vacío = no cambiar
    $nombreInstancia = trim($_POST['nombre_instancia'] ?? '');
    $loginUrl = trim($_POST['login_url'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (empty($apiUrl)) {
        manejarError('La URL de la API es requerida', "../vistas/configuracion/whatsapp/whatsapp.php");
    }

    if (empty($nombreInstancia)) {
        manejarError('El nombre de la instancia es requerido', "../vistas/configuracion/whatsapp/whatsapp.php");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $configModel = new ConfigWhatsapp($conexion);

        // Verificar que existe
        if (!$configModel->obtenerPorId($id)) {
            manejarError('Configuración no encontrada', '../vistas/configuracion/whatsapp/whatsapp.php');
        }

        // Si se activa esta configuración, desactivar las demás
        if ($activo) {
            $configModel->desactivarOtras($id);
        }

        // Actualizar datos
        $configModel->IdConfigWhatsapp = $id;
        $configModel->api_url = $apiUrl;
        $configModel->nombre_instancia = $nombreInstancia;
        $configModel->login_url = !empty($loginUrl) ? $loginUrl : null;
        $configModel->activo = $activo;

        // Actualizar (con o sin nueva API key)
        $nuevaApiKey = !empty($apiKey) ? $apiKey : null;
        if (!$configModel->actualizar($nuevaApiKey)) {
            throw new Exception("Error al actualizar la configuración");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Configuración actualizada correctamente';
        header("Location: ../vistas/configuracion/whatsapp/whatsapp.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/configuracion/whatsapp/whatsapp.php");
    }
}

function actualizarApiKey() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $apiKey = trim($_POST['api_key'] ?? '');

    if ($id <= 0) {
        manejarError('ID de configuración inválido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    if (empty($apiKey)) {
        manejarError('La API key es requerida', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $configModel = new ConfigWhatsapp($conexion);

        if (!$configModel->obtenerPorId($id)) {
            manejarError('Configuración no encontrada', '../vistas/configuracion/whatsapp/whatsapp.php');
        }

        if (!$configModel->actualizarApiKey($apiKey)) {
            throw new Exception("Error al actualizar la API key");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'API Key actualizada correctamente';
        header("Location: ../vistas/configuracion/whatsapp/whatsapp.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar API key: ' . $e->getMessage());
    }
}

function activarConfiguracion() {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        manejarError('ID de configuración inválido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $configModel = new ConfigWhatsapp($conexion);

        if (!$configModel->activar($id)) {
            throw new Exception("Error al activar la configuración");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Configuración activada correctamente';
        header("Location: ../vistas/configuracion/whatsapp/whatsapp.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al activar: ' . $e->getMessage());
    }
}

function eliminarConfiguracion() {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        manejarError('ID de configuración inválido', '../vistas/configuracion/whatsapp/whatsapp.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $configModel = new ConfigWhatsapp($conexion);

        if (!$configModel->obtenerPorId($id)) {
            manejarError('Configuración no encontrada', '../vistas/configuracion/whatsapp/whatsapp.php');
        }

        // No permitir eliminar si es la única configuración activa
        if ($configModel->activo && !$configModel->hayConfiguracionActiva()) {
            manejarError('No puede eliminar la única configuración activa', '../vistas/configuracion/whatsapp/whatsapp.php');
        }

        if (!$configModel->eliminar()) {
            throw new Exception("Error al eliminar la configuración");
        }

        $_SESSION['alert'] = 'deleted';
        $_SESSION['message'] = 'Configuración eliminada correctamente';
        header("Location: ../vistas/configuracion/whatsapp/whatsapp.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al eliminar: ' . $e->getMessage());
    }
}

function manejarError(string $mensaje, string $urlRedireccion = '../vistas/configuracion/whatsapp/whatsapp.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
