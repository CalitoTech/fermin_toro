<?php
// Eliminar cualquier espacio antes de la etiqueta de apertura PHP
session_start();

// 1. Verificación estricta de sesión sin HTML
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode([
        'status' => 'error',
        'message' => 'Debe iniciar sesión para acceder',
        'redirect' => '../../login/login.php'
    ]);
    exit();
}

// 2. Conexión a base de datos
require_once __DIR__ . '/../config/conexion.php'; // Ajusta la ruta según tu estructura

// 3. Función para obtener bloques
function obtenerBloquesJSON() {
    // Limpiar buffer de salida completamente
    while (ob_get_level() > 0) ob_end_clean();
    
    // Configurar headers para respuesta JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Consulta optimizada
        $query = "SELECT 
                    IdBloque, 
                    TIME_FORMAT(hora_inicio, '%H:%i') as hora_inicio, 
                    TIME_FORMAT(hora_fin, '%H:%i') as hora_fin 
                  FROM bloque 
                  ORDER BY hora_inicio";
        
        $stmt = $conexion->prepare($query);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al ejecutar la consulta');
        }

        $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Verificar que no haya salida accidental
        if (ob_get_length() > 0) {
            throw new Exception('Contenido no esperado en el buffer de salida');
        }

        // Respuesta exitosa
        echo json_encode([
            'status' => 'success',
            'data' => $bloques,
            'timestamp' => time()
        ]);
        exit();

    } catch (Exception $e) {
        // Limpiar buffer nuevamente
        while (ob_get_level() > 0) ob_end_clean();
        
        // Respuesta de error
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al obtener bloques: ' . $e->getMessage(),
            'timestamp' => time()
        ]);
        exit();
    }
}

// 4. Llamar a la función
obtenerBloquesJSON();