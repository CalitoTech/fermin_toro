<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/conexion.php';

// === VERIFICACIÓN DE SESIÓN ===
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debe iniciar sesión para realizar esta acción'
    ]);
    exit();
}

// === VERIFICACIÓN DE MÉTODO ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $database = new Database();
    $conexion = $database->getConnection();

    // === DETERMINAR ID DE PERSONA A ACTUALIZAR ===
    $idPersona = 0;
    $prefijo = 'usuario_';

    if (isset($_POST['idEstudiante']) && !empty($_POST['idEstudiante'])) {
        $idPersona = intval($_POST['idEstudiante']);
        $prefijo = 'estudiante_';
    } elseif (isset($_POST['idRepresentante']) && !empty($_POST['idRepresentante'])) {
        $idPersona = intval($_POST['idRepresentante']);
        $prefijo = 'representante_';
    } elseif (isset($_POST['idPersona']) && !empty($_POST['idPersona'])) {
        $idPersona = intval($_POST['idPersona']);
    } else {
        $idPersona = $_SESSION['idPersona'];
    }

    // === VALIDAR ARCHIVO ===
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ninguna imagen o hubo un error en la carga');
    }

    $archivo = $_FILES['foto'];
    $nombreArchivo = $archivo['name'];
    $tipoArchivo = $archivo['type'];
    $tamanoArchivo = $archivo['size'];
    $tmpArchivo = $archivo['tmp_name'];

    // Validar tipo de archivo
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($tipoArchivo, $tiposPermitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPG, JPEG y PNG');
    }

    // Validar tamaño (2MB máximo)
    $tamanoMaximo = 2 * 1024 * 1024; // 2MB
    if ($tamanoArchivo > $tamanoMaximo) {
        throw new Exception('El archivo es demasiado grande. Tamaño máximo: 2MB');
    }

    // === CREAR DIRECTORIO SI NO EXISTE ===
    $directorioBase = __DIR__ . '/../../uploads/fotos_perfil';
    if (!file_exists($directorioBase)) {
        if (!mkdir($directorioBase, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }

    // === GENERAR NOMBRE ÚNICO PARA EL ARCHIVO ===
    $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    $nuevoNombre = $prefijo . $idPersona . '_' . time() . '.' . $extension;
    $rutaDestino = $directorioBase . '/' . $nuevoNombre;
    $rutaRelativa = 'uploads/fotos_perfil/' . $nuevoNombre;

    // === OBTENER FOTO ACTUAL PARA ELIMINARLA ===
    $queryFotoActual = "SELECT foto_perfil FROM persona WHERE IdPersona = :idPersona";
    $stmtFotoActual = $conexion->prepare($queryFotoActual);
    $stmtFotoActual->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
    $stmtFotoActual->execute();
    $fotoActual = $stmtFotoActual->fetchColumn();

    // === MOVER ARCHIVO ===
    if (!move_uploaded_file($tmpArchivo, $rutaDestino)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }

    // === ACTUALIZAR BASE DE DATOS ===
    $queryActualizar = "UPDATE persona SET foto_perfil = :foto WHERE IdPersona = :idPersona";
    $stmtActualizar = $conexion->prepare($queryActualizar);
    $stmtActualizar->bindParam(':foto', $rutaRelativa, PDO::PARAM_STR);
    $stmtActualizar->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);

    if ($stmtActualizar->execute()) {
        // === ELIMINAR FOTO ANTERIOR SI EXISTE ===
        if (!empty($fotoActual)) {
            $rutaAnterior = __DIR__ . '/../../' . $fotoActual;
            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior); // Ignorar errores de eliminación
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'ruta' => $rutaRelativa,
            'idPersona' => $idPersona
        ]);
    } else {
        // Si falla la actualización, eliminar el archivo subido
        if (file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }
        throw new Exception('Error al actualizar la base de datos');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
