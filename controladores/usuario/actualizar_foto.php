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
    
    if (isset($_POST['idEstudiante']) && !empty($_POST['idEstudiante'])) {
        $idPersona = intval($_POST['idEstudiante']);
    } elseif (isset($_POST['idRepresentante']) && !empty($_POST['idRepresentante'])) {
        $idPersona = intval($_POST['idRepresentante']);
    } elseif (isset($_POST['idPersona']) && !empty($_POST['idPersona'])) {
        $idPersona = intval($_POST['idPersona']);
    } else {
        $idPersona = $_SESSION['idPersona']; // Fallback a usuario actual
    }

    // === VALIDAR ARCHIVO ===
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ninguna imagen o hubo un error en la carga (Error Code: ' . $_FILES['foto']['error'] . ')');
    }

    $archivo = $_FILES['foto'];
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

    // === LEER CONTENIDO DEL ARCHIVO (BINARIO) ===
    $contenidoImagen = file_get_contents($tmpArchivo);
    if ($contenidoImagen === false) {
        throw new Exception('Error al leer el contenido de la imagen temporal');
    }

    // === ACTUALIZAR BASE DE DATOS ===
    // Nota: Usamos bindParam con PDO::PARAM_LOB para datos binarios grandes
    $queryActualizar = "UPDATE persona SET foto_perfil = :foto WHERE IdPersona = :idPersona";
    $stmtActualizar = $conexion->prepare($queryActualizar);
    $stmtActualizar->bindParam(':foto', $contenidoImagen, PDO::PARAM_LOB);
    $stmtActualizar->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);

    if ($stmtActualizar->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Foto actualizada correctamente en base de datos',
            'idPersona' => $idPersona
        ]);
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
