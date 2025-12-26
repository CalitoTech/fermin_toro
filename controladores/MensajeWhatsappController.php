<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

// Verificar perfiles internos (1=Admin, 6=Director, 7=Control de Estudios, 8,9,10=Coordinadores)
$perfilesPermitidos = [1, 6, 7, 8, 9, 10, 11, 12];
if (!isset($_SESSION['idPerfil']) || !in_array($_SESSION['idPerfil'], $perfilesPermitidos)) {
    manejarError('No tiene permisos para acceder a esta sección', '../vistas/inicio/inicio/inicio.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/MensajeWhatsapp.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearMensaje();
        break;
    case 'editar':
        editarMensaje();
        break;
    case 'cambiar_estado':
        cambiarEstadoMensaje();
        break;
    default:
        manejarError('Acción no válida', '../vistas/configuracion/whatsapp/mensajes.php');
}

function crearMensaje() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/whatsapp/nuevo_mensaje.php');
    }

    // Obtener datos
    $idStatus = (int)($_POST['id_status'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $incluirRequisitos = isset($_POST['incluir_requisitos']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if ($idStatus <= 0) {
        manejarError('Debe seleccionar un status de inscripción');
    }

    if (empty($titulo)) {
        manejarError('El título es requerido');
    }

    if (empty($contenido)) {
        manejarError('El contenido del mensaje es requerido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $mensajeModel = new MensajeWhatsapp($conexion);

        // Validar que no exista ya un mensaje para este estado
        if ($mensajeModel->existeMensajeParaStatus($idStatus)) {
            manejarError('Ya existe un mensaje configurado para este estado. Solo puede haber un mensaje por estado.');
        }

        // Configurar datos
        $mensajeModel->IdStatus = $idStatus;
        $mensajeModel->titulo = $titulo;
        $mensajeModel->contenido = $contenido;
        $mensajeModel->incluir_requisitos = $incluirRequisitos;
        $mensajeModel->activo = $activo;

        if (!$mensajeModel->guardar()) {
            throw new Exception("Error al guardar el mensaje");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Mensaje creado exitosamente';
        header("Location: ../vistas/configuracion/whatsapp/mensajes.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al crear: ' . $e->getMessage());
    }
}

function editarMensaje() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/configuracion/whatsapp/mensajes.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID de mensaje inválido', '../vistas/configuracion/whatsapp/mensajes.php');
    }

    // Obtener datos
    $idStatus = (int)($_POST['id_status'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $incluirRequisitos = isset($_POST['incluir_requisitos']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if ($idStatus <= 0) {
        manejarError('Debe seleccionar un status de inscripción', "../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
    }

    if (empty($titulo)) {
        manejarError('El título es requerido', "../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
    }

    if (empty($contenido)) {
        manejarError('El contenido del mensaje es requerido', "../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $mensajeModel = new MensajeWhatsapp($conexion);

        // Verificar que existe
        $mensajeActual = $mensajeModel->obtenerPorId($id);
        if (!$mensajeActual) {
            manejarError('Mensaje no encontrado', '../vistas/configuracion/whatsapp/mensajes.php');
        }

        // Si cambió el estado, validar que no exista otro mensaje con ese estado
        if ($mensajeActual['IdStatus'] != $idStatus && $mensajeModel->existeMensajeParaStatus($idStatus)) {
            manejarError('Ya existe un mensaje para ese estado. Solo puede haber un mensaje por estado.', "../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
        }

        // Actualizar datos
        $mensajeModel->IdMensajeWhatsapp = $id;
        $mensajeModel->IdStatus = $idStatus;
        $mensajeModel->titulo = $titulo;
        $mensajeModel->contenido = $contenido;
        $mensajeModel->incluir_requisitos = $incluirRequisitos;
        $mensajeModel->activo = $activo;

        if (!$mensajeModel->actualizar()) {
            throw new Exception("Error al actualizar el mensaje");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = 'Mensaje actualizado correctamente';
        header("Location: ../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
        exit();

    } catch (Exception $e) {
        manejarError('Error al actualizar: ' . $e->getMessage(), "../vistas/configuracion/whatsapp/editar_mensaje.php?id=$id");
    }
}

function cambiarEstadoMensaje() {
    // Solo el administrador (perfil 1) puede activar/desactivar mensajes
    if ($_SESSION['idPerfil'] != 1) {
        manejarError('Solo el administrador puede activar o desactivar mensajes', '../vistas/configuracion/whatsapp/mensajes.php');
    }

    $id = (int)($_GET['id'] ?? 0);
    $nuevoEstado = (int)($_GET['activo'] ?? 0);

    if ($id <= 0) {
        manejarError('ID de mensaje inválido', '../vistas/configuracion/whatsapp/mensajes.php');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $mensajeModel = new MensajeWhatsapp($conexion);

        if (!$mensajeModel->obtenerPorId($id)) {
            manejarError('Mensaje no encontrado', '../vistas/configuracion/whatsapp/mensajes.php');
        }

        if (!$mensajeModel->cambiarEstado($nuevoEstado)) {
            throw new Exception("Error al cambiar el estado del mensaje");
        }

        $_SESSION['alert'] = 'success';
        $_SESSION['message'] = $nuevoEstado ? 'Mensaje activado' : 'Mensaje desactivado';
        header("Location: ../vistas/configuracion/whatsapp/mensajes.php");
        exit();

    } catch (Exception $e) {
        manejarError('Error al cambiar estado: ' . $e->getMessage());
    }
}

function manejarError(string $mensaje, string $urlRedireccion = '../vistas/configuracion/whatsapp/nuevo_mensaje.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
