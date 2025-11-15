<?php
/**
 * Controlador genérico de búsqueda
 * Permite buscar en diferentes tablas: estudiantes, urbanismos, parentescos
 */

require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

$database = new Database();
$conexion = $database->getConnection();

$tipo = $_GET['tipo'] ?? '';
$q = $_GET['q'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validar límite
if ($limit < 1 || $limit > 50) {
    $limit = 10;
}

// Validar tipo de búsqueda
$tiposPermitidos = ['estudiante', 'urbanismo', 'parentesco', 'prefijo'];
if (!in_array($tipo, $tiposPermitidos)) {
    echo json_encode(['error' => 'Tipo de búsqueda no válido']);
    exit;
}

// Ejecutar búsqueda según el tipo
switch ($tipo) {
    case 'estudiante':
        buscarEstudiantes($conexion, $q, $limit);
        break;

    case 'urbanismo':
        buscarUrbanismos($conexion, $q, $limit);
        break;

    case 'parentesco':
        buscarParentescos($conexion, $q, $limit);
        break;

    case 'prefijo':
        buscarPrefijos($conexion, $q, $limit);
        break;
}

/**
 * Buscar estudiantes sin egreso
 */
function buscarEstudiantes($conexion, $q, $limit = 10) {
    // Si la búsqueda está vacía, no mostrar nada para estudiantes
    if (empty(trim($q))) {
        echo json_encode([]);
        return;
    }

    $stmt = $conexion->prepare("
        SELECT DISTINCT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
        FROM persona p
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        WHERE pr.nombre_perfil = 'Estudiante'
        AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
        AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ");
    $search = "%$q%";
    $stmt->bindParam(':q', $search);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Buscar urbanismos con búsqueda simple pero efectiva
 */
function buscarUrbanismos($conexion, $q, $limit = 10) {
    // Si la búsqueda está vacía, traer los primeros registros ordenados alfabéticamente
    if (empty(trim($q))) {
        $stmt = $conexion->prepare("
            SELECT IdUrbanismo, urbanismo
            FROM urbanismo
            ORDER BY urbanismo ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        return;
    }

    // Búsqueda simple con LIKE (case-insensitive y con prioridades)
    $stmt = $conexion->prepare("
        SELECT IdUrbanismo, urbanismo,
               CASE
                   WHEN LOWER(urbanismo) = LOWER(:qExacto) THEN 1
                   WHEN LOWER(urbanismo) LIKE LOWER(:qInicio) THEN 2
                   WHEN LOWER(urbanismo) LIKE LOWER(:qContiene) THEN 3
                   ELSE 4
               END as prioridad
        FROM urbanismo
        WHERE LOWER(urbanismo) LIKE LOWER(:qBusqueda)
        ORDER BY prioridad ASC, urbanismo ASC
        LIMIT :limit
    ");

    $qExacto = trim($q);
    $qInicio = trim($q) . '%';
    $qContiene = '%' . trim($q) . '%';
    $qBusqueda = '%' . trim($q) . '%';

    $stmt->bindParam(':qExacto', $qExacto);
    $stmt->bindParam(':qInicio', $qInicio);
    $stmt->bindParam(':qContiene', $qContiene);
    $stmt->bindParam(':qBusqueda', $qBusqueda);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remover campo de prioridad
    foreach ($resultados as &$resultado) {
        unset($resultado['prioridad']);
    }

    // SIEMPRE mostrar la opción de crear nuevo al final
    if (!empty(trim($q))) {
        $resultados[] = [
            'IdUrbanismo' => 'nuevo',
            'urbanismo' => ucwords(strtolower(trim($q))),
            'nuevo' => true
        ];
    }

    echo json_encode($resultados);
}

/**
 * Buscar parentescos con búsqueda simple pero efectiva
 */
function buscarParentescos($conexion, $q, $limit = 10) {
    // Si la búsqueda está vacía, traer los primeros registros ordenados alfabéticamente
    if (empty(trim($q))) {
        $stmt = $conexion->prepare("
            SELECT IdParentesco, parentesco
            FROM parentesco
            WHERE IdParentesco >= 3
            ORDER BY parentesco ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        return;
    }

    // Búsqueda simple con LIKE (case-insensitive y con prioridades)
    $stmt = $conexion->prepare("
        SELECT IdParentesco, parentesco,
               CASE
                   WHEN LOWER(parentesco) = LOWER(:qExacto) THEN 1
                   WHEN LOWER(parentesco) LIKE LOWER(:qInicio) THEN 2
                   WHEN LOWER(parentesco) LIKE LOWER(:qContiene) THEN 3
                   ELSE 4
               END as prioridad
        FROM parentesco
        WHERE LOWER(parentesco) LIKE LOWER(:qBusqueda)
        AND IdParentesco >= 3
        ORDER BY prioridad ASC, parentesco ASC
        LIMIT :limit
    ");

    $qExacto = trim($q);
    $qInicio = trim($q) . '%';
    $qContiene = '%' . trim($q) . '%';
    $qBusqueda = '%' . trim($q) . '%';

    $stmt->bindParam(':qExacto', $qExacto);
    $stmt->bindParam(':qInicio', $qInicio);
    $stmt->bindParam(':qContiene', $qContiene);
    $stmt->bindParam(':qBusqueda', $qBusqueda);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remover campo de prioridad
    foreach ($resultados as &$resultado) {
        unset($resultado['prioridad']);
    }

    // SIEMPRE mostrar la opción de crear nuevo al final
    if (!empty(trim($q))) {
        $resultados[] = [
            'IdParentesco' => 'nuevo',
            'parentesco' => ucwords(strtolower(trim($q))),
            'nuevo' => true
        ];
    }

    echo json_encode($resultados);
}

/**
 * Buscar prefijos con búsqueda simple pero efectiva
 */
function buscarPrefijos($conexion, $q, $limit = 10) {
    // Obtener filtro de tipo (fijo o internacional)
    $filtro = $_GET['filtro'] ?? 'internacional';

    // Construir WHERE clause según el filtro
    $whereFilter = '';
    if ($filtro === 'fijo') {
        // Prefijos sin + (teléfonos fijos)
        $whereFilter = " AND codigo_prefijo NOT LIKE '+%'";
    } else {
        // Prefijos con + (internacionales)
        $whereFilter = " AND codigo_prefijo LIKE '+%'";
    }

    // Si la búsqueda está vacía, traer los primeros registros ordenados por código
    if (empty(trim($q))) {
        $stmt = $conexion->prepare("
            SELECT IdPrefijo, codigo_prefijo, pais, max_digitos
            FROM prefijo
            WHERE 1=1" . $whereFilter . "
            ORDER BY codigo_prefijo ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        return;
    }

    // Búsqueda simple con LIKE (case-insensitive y con prioridades)
    $stmt = $conexion->prepare("
        SELECT IdPrefijo, codigo_prefijo, pais, max_digitos,
               CASE
                   WHEN LOWER(codigo_prefijo) = LOWER(:qExacto) THEN 1
                   WHEN LOWER(pais) = LOWER(:qExactoPais) THEN 2
                   WHEN LOWER(codigo_prefijo) LIKE LOWER(:qInicio) THEN 3
                   WHEN LOWER(pais) LIKE LOWER(:qInicioPais) THEN 4
                   WHEN LOWER(codigo_prefijo) LIKE LOWER(:qContiene) THEN 5
                   WHEN LOWER(pais) LIKE LOWER(:qContienePais) THEN 6
                   ELSE 7
               END as prioridad
        FROM prefijo
        WHERE (LOWER(codigo_prefijo) LIKE LOWER(:qBusqueda)
           OR LOWER(pais) LIKE LOWER(:qBusquedaPais))
        " . $whereFilter . "
        ORDER BY prioridad ASC, codigo_prefijo ASC
        LIMIT :limit
    ");

    $qExacto = trim($q);
    $qExactoPais = trim($q);
    $qInicio = trim($q) . '%';
    $qInicioPais = trim($q) . '%';
    $qContiene = '%' . trim($q) . '%';
    $qContienePais = '%' . trim($q) . '%';
    $qBusqueda = '%' . trim($q) . '%';
    $qBusquedaPais = '%' . trim($q) . '%';

    $stmt->bindParam(':qExacto', $qExacto);
    $stmt->bindParam(':qExactoPais', $qExactoPais);
    $stmt->bindParam(':qInicio', $qInicio);
    $stmt->bindParam(':qInicioPais', $qInicioPais);
    $stmt->bindParam(':qContiene', $qContiene);
    $stmt->bindParam(':qContienePais', $qContienePais);
    $stmt->bindParam(':qBusqueda', $qBusqueda);
    $stmt->bindParam(':qBusquedaPais', $qBusquedaPais);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remover campo de prioridad
    foreach ($resultados as &$resultado) {
        unset($resultado['prioridad']);
    }

    // SIEMPRE mostrar la opción de crear nuevo al final
    if (!empty(trim($q))) {
        $codigoNuevo = trim($q);

        // Si el filtro es fijo y el código no tiene +, usar tal cual
        // Si el filtro es internacional y el código no tiene +, agregarlo
        if ($filtro === 'fijo') {
            // Para fijos, asegurarse que no tenga +
            $codigoNuevo = str_replace('+', '', $codigoNuevo);
        } else {
            // Para internacionales, asegurarse que tenga +
            if (strpos($codigoNuevo, '+') !== 0) {
                $codigoNuevo = '+' . preg_replace('/[^0-9]/', '', $codigoNuevo);
            }
        }

        $resultados[] = [
            'IdPrefijo' => 'nuevo',
            'codigo_prefijo' => $codigoNuevo,
            'pais' => 'Nuevo prefijo',
            'max_digitos' => 10,
            'nuevo' => true
        ];
    }

    echo json_encode($resultados);
}

?>
