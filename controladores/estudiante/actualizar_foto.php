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

// === VALIDAR DATOS RECIBIDOS ===
if (!isset($_POST['idEstudiante']) || empty($_POST['idEstudiante'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de estudiante no proporcionado'
    ]);
    exit();
}

$idEstudiante = intval($_POST['idEstudiante']);
$idRepresentante = $_SESSION['idPersona'];

try {
    $database = new Database();
    $conexion = $database->getConnection();

    // === VERIFICAR QUE EL USUARIO ES REPRESENTANTE DEL ESTUDIANTE ===
    // $queryVerificar = "SELECT COUNT(*) FROM representante
    //                    WHERE IdPersona = :idRepresentante
    //                    AND IdEstudiante = :idEstudiante";
    // $stmtVerificar = $conexion->prepare($queryVerificar);
    // $stmtVerificar->bindParam(':idRepresentante', $idRepresentante, PDO::PARAM_INT);
    // $stmtVerificar->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
    // $stmtVerificar->execute();

    // if ($stmtVerificar->fetchColumn() == 0) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'No tiene permiso para modificar este estudiante'
    //     ]);
    //     exit();
    // }

    // === VALIDAR ARCHIVO ===
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'No se recibió ninguna imagen o hubo un error en la carga'
        ]);
        exit();
    }

    $archivo = $_FILES['foto'];
    $nombreArchivo = $archivo['name'];
    $tipoArchivo = $archivo['type'];
    $tamanoArchivo = $archivo['size'];
    $tmpArchivo = $archivo['tmp_name'];

    // Validar tipo de archivo
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($tipoArchivo, $tiposPermitidos)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de archivo no permitido. Solo se aceptan JPG, JPEG y PNG'
        ]);
        exit();
    }

    // Validar tamaño (2MB máximo)
    $tamanoMaximo = 2 * 1024 * 1024; // 2MB
    if ($tamanoArchivo > $tamanoMaximo) {
        echo json_encode([
            'success' => false,
            'message' => 'El archivo es demasiado grande. Tamaño máximo: 2MB'
        ]);
        exit();
    }

    // === CREAR DIRECTORIO SI NO EXISTE ===
    $directorioBase = __DIR__ . '/../../uploads/fotos_perfil';
    if (!file_exists($directorioBase)) {
        mkdir($directorioBase, 0755, true);
    }

    // === GENERAR NOMBRE ÚNICO PARA EL ARCHIVO ===
    $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    $nuevoNombre = 'estudiante_' . $idEstudiante . '_' . time() . '.' . $extension;
    $rutaDestino = $directorioBase . '/' . $nuevoNombre;
    $rutaRelativa = 'uploads/fotos_perfil/' . $nuevoNombre;

    // === OBTENER FOTO ACTUAL PARA ELIMINARLA ===
    $queryFotoActual = "SELECT foto_perfil FROM persona WHERE IdPersona = :idEstudiante";
    $stmtFotoActual = $conexion->prepare($queryFotoActual);
    $stmtFotoActual->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
    $stmtFotoActual->execute();
    $fotoActual = $stmtFotoActual->fetchColumn();

    // === MOVER ARCHIVO ===
    if (!move_uploaded_file($tmpArchivo, $rutaDestino)) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar el archivo'
        ]);
        exit();
    }

    // === ACTUALIZAR BASE DE DATOS ===
    $queryActualizar = "UPDATE persona SET foto_perfil = :foto WHERE IdPersona = :idEstudiante";
    $stmtActualizar = $conexion->prepare($queryActualizar);
    $stmtActualizar->bindParam(':foto', $rutaRelativa, PDO::PARAM_STR);
    $stmtActualizar->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);

    if ($stmtActualizar->execute()) {
        // === ELIMINAR FOTO ANTERIOR SI EXISTE ===
        if (!empty($fotoActual)) {
            $rutaAnterior = __DIR__ . '/../../' . $fotoActual;
            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'ruta' => $rutaRelativa
        ]);
    } else {
        // Si falla la actualización, eliminar el archivo subido
        if (file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }

        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la base de datos'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
