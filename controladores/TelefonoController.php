<?php
/**
 * Controlador para operaciones con teléfonos
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Telefono.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'verificarTelefono':
        verificarTelefono();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no encontrada']);
        exit();
}

/**
 * Verifica si un número de teléfono ya está registrado en el sistema
 * @return void Retorna JSON con el resultado de la verificación
 */
function verificarTelefono() {
    header('Content-Type: application/json');

    try {
        $telefono = $_GET['telefono'] ?? '';
        $idPrefijo = $_GET['prefijo'] ?? '';
        $idPersonaExcluir = $_GET['idPersona'] ?? null; // Para excluir en ediciones

        if (empty($telefono)) {
            echo json_encode(['existe' => false]);
            exit();
        }

        // Validar formato de teléfono
        if (!preg_match('/^[0-9]{7,10}$/', $telefono)) {
            echo json_encode([
                'existe' => false,
                'error' => 'Formato de teléfono inválido'
            ]);
            exit();
        }

        // Validar que no empiece con 0
        if (substr($telefono, 0, 1) === '0') {
            echo json_encode([
                'existe' => false,
                'error' => 'El número no puede empezar con 0'
            ]);
            exit();
        }

        $database = new Database();
        $conexion = $database->getConnection();

        // Buscar si el teléfono ya existe
        $sql = "SELECT t.IdTelefono, t.numero_telefono, t.IdPrefijo,
                       p.IdPersona, p.nombre, p.apellido, p.cedula,
                       n.nacionalidad,
                       pf.codigo_prefijo
                FROM telefono t
                INNER JOIN persona p ON t.IdPersona = p.IdPersona
                LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                LEFT JOIN prefijo pf ON t.IdPrefijo = pf.IdPrefijo
                WHERE t.numero_telefono = :telefono";

        // Filtrar por prefijo si se proporciona
        if (!empty($idPrefijo) && is_numeric($idPrefijo)) {
            $sql .= " AND t.IdPrefijo = :idPrefijo";
        }

        // Excluir persona si se proporciona (para ediciones)
        if ($idPersonaExcluir && is_numeric($idPersonaExcluir)) {
            $sql .= " AND p.IdPersona != :idPersona";
        }

        $sql .= " LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':telefono', $telefono);

        if (!empty($idPrefijo) && is_numeric($idPrefijo)) {
            $stmt->bindParam(':idPrefijo', $idPrefijo, PDO::PARAM_INT);
        }

        if ($idPersonaExcluir && is_numeric($idPersonaExcluir)) {
            $stmt->bindParam(':idPersona', $idPersonaExcluir, PDO::PARAM_INT);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'existe' => true,
                'persona' => [
                    'nombre' => $resultado['nombre'],
                    'apellido' => $resultado['apellido'],
                    'nombreCompleto' => $resultado['nombre'] . ' ' . $resultado['apellido'],
                    'cedula' => $resultado['cedula'],
                    'nacionalidad' => $resultado['nacionalidad']
                ],
                'telefono' => [
                    'numero' => $resultado['numero_telefono'],
                    'prefijo' => $resultado['codigo_prefijo']
                ]
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }

    } catch (Exception $e) {
        error_log("Error en verificarTelefono: " . $e->getMessage());
        echo json_encode([
            'existe' => false,
            'error' => 'Error al verificar el teléfono'
        ]);
    }
    exit();
}
?>
