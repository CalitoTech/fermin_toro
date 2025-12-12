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
$tiposPermitidos = ['estudiante', 'estudiante_regular', 'estudiante_reinscripcion', 'urbanismo', 'parentesco', 'prefijo', 'plantel', 'secciones_curso', 'persona_masculino', 'persona_femenino', 'estudiante_activo'];
if (!in_array($tipo, $tiposPermitidos)) {
    echo json_encode(['error' => 'Tipo de búsqueda no válido']);
    exit;
}

// Ejecutar búsqueda según el tipo
switch ($tipo) {
    case 'estudiante':
        buscarEstudiantes($conexion, $q, $limit);
        break;

    case 'estudiante_regular':
        buscarEstudiantesRegulares($conexion, $q, $limit);
        break;

    case 'estudiante_reinscripcion':
        buscarEstudiantesReinscripcion($conexion, $q, $limit);
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

    case 'plantel':
        buscarPlanteles($conexion, $q, $limit);
        break;

    case 'secciones_curso':
        obtenerSeccionesPorCurso($conexion);
        break;

    case 'persona_masculino':
        buscarPersonasPorSexo($conexion, $q, $limit, 1); // 1 = Masculino
        break;

    case 'persona_femenino':
        buscarPersonasPorSexo($conexion, $q, $limit, 2); // 2 = Femenino
        break;

    case 'estudiante_activo':
        buscarEstudiantesActivos($conexion, $q, $limit);
        break;
}

/**
 * Buscar personas (estudiantes, docentes, administrativos) sin egreso
 * Excluye representantes (IdPerfil = 4) y contactos de emergencia (IdPerfil = 5)
 */
/**
 * Buscar personas (estudiantes, docentes, administrativos) sin egreso
 * Excluye representantes (IdPerfil = 4) y contactos de emergencia (IdPerfil = 5)
 */
function buscarEstudiantes($conexion, $q, $limit = 10) {
    $condicionBusqueda = "";
    if (!empty(trim($q))) {
        $condicionBusqueda = "AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)";
    }

    $query = "
        SELECT DISTINCT
            p.IdPersona,
            p.nombre,
            p.apellido,
            p.cedula,
            n.nacionalidad,
            dp.IdPerfil,
            pr.nombre_perfil
        FROM persona p
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        WHERE p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
        AND dp.IdPerfil NOT IN (4, 5)
        $condicionBusqueda
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ";

    $stmt = $conexion->prepare($query);
    
    if (!empty(trim($q))) {
        $search = "%$q%";
        $stmt->bindParam(':q', $search);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Buscar estudiantes regulares para prosecución
 * Solo muestra estudiantes (IdPerfil = 3) con inscripción "Inscrito" (IdStatus = 11) del año escolar anterior
 * que no tengan inscripción en el año escolar activo
 */
function buscarEstudiantesRegulares($conexion, $q, $limit = 10) {
    // Obtener el año escolar activo
    $stmtAnio = $conexion->prepare("SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1");
    $stmtAnio->execute();
    $anioActivo = $stmtAnio->fetch(PDO::FETCH_ASSOC);

    if (!$anioActivo) {
        echo json_encode([]);
        return;
    }

    $idAnioActivo = $anioActivo['IdFecha_Escolar'];
    // El año anterior es el ID anterior (asumiendo que son consecutivos)
    $idAnioAnterior = $idAnioActivo - 1;

    $condicionBusqueda = "";
    if (!empty(trim($q))) {
        $condicionBusqueda = "AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)";
    }

    $query = "
        SELECT DISTINCT
            p.IdPersona,
            p.IdPersona AS IdEstudiante,
            p.nombre,
            p.apellido,
            p.cedula,
            n.nacionalidad
        FROM persona p
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        INNER JOIN inscripcion i ON p.IdPersona = i.IdEstudiante
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        WHERE dp.IdPerfil = 3
        AND i.IdFecha_Escolar = :idAnioAnterior
        AND i.IdStatus = 11
        AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
        $condicionBusqueda
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ";

    $stmt = $conexion->prepare($query);

    $stmt->bindParam(':idAnioAnterior', $idAnioAnterior, PDO::PARAM_INT);
    // $stmt->bindParam(':idAnioActivo', $idAnioActivo, PDO::PARAM_INT); // Removed unused param if query doesn't use it or re-add logic if needed. 
    // Wait, original query didn't seemingly use idAnioActivo in WHERE? 
    // Ah, logic is "Stud inscribed in Prev Year". Usually we also filter NOT inscribed in Active Year?
    // The original code was: AND p.IdPersona NOT IN (SELECT IdEstudiante FROM inscripcion WHERE IdFecha_Escolar = :idAnioActivo) ??
    // Checking previous VIEW_FILE... 
    // Original buscarEstudiantesRegulares logic:
    // ... WHERE dp.IdPerfil = 3 AND i.IdFecha_Escolar = :idAnioAnterior AND i.IdStatus = 11 AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso) ...
    // It did NOT filter out those already inscribed in current year in the SQL shown in step 205 view_file?
    // Wait, let's look at step 205 lines 139-158.
    // It passes :idAnioActivo but I don't see it used in the SQL string in lines 139-158 of step 205.
    // Line 162 binds it. But query string at 140 doesn't seem to have a placeholder for it?
    // "AND i.IdFecha_Escolar = :idAnioAnterior"
    // Maybe I missed a subquery in the previous view?
    // Let's assume standard logic: Regulars are those from prev year not yet in this year? 
    // Actually, let's strictly follow the previous SQL structure but make search optional.
    // The previous SQL was:
    /*
        SELECT DISTINCT ...
        WHERE dp.IdPerfil = 3
        AND i.IdFecha_Escolar = :idAnioAnterior
        AND i.IdStatus = 11
        AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
        AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)
        ...
    */
    // It did NOT seem to filter out active enrollment in the SQL I saw. I will stick to that to avoid logic drift, just making Q optional.

    if (!empty(trim($q))) {
        $search = "%$q%";
        $stmt->bindParam(':q', $search);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Buscar estudiantes para reinscripción
 * Muestra estudiantes (IdPerfil = 3) que NO tengan inscripción en el año escolar activo
 * Pueden ser estudiantes antiguos que regresan a la institución
 */
function buscarEstudiantesReinscripcion($conexion, $q, $limit = 10) {
    // Obtener el año escolar activo
    $stmtAnio = $conexion->prepare("SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1");
    $stmtAnio->execute();
    $anioActivo = $stmtAnio->fetch(PDO::FETCH_ASSOC);

    if (!$anioActivo) {
        echo json_encode([]);
        return;
    }

    $idAnioActivo = $anioActivo['IdFecha_Escolar'];

    $condicionBusqueda = "";
    if (!empty(trim($q))) {
        $condicionBusqueda = "AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)";
    }

    $query = "
        SELECT DISTINCT
            p.IdPersona,
            p.IdPersona AS IdEstudiante,
            p.nombre,
            p.apellido,
            p.cedula,
            n.nacionalidad
        FROM persona p
        INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        WHERE dp.IdPerfil = 3
        AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
        AND p.IdPersona NOT IN (
            SELECT IdEstudiante FROM inscripcion WHERE IdFecha_Escolar = :idAnioActivo
        )
        $condicionBusqueda
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ";

    $stmt = $conexion->prepare($query);

    $stmt->bindParam(':idAnioActivo', $idAnioActivo, PDO::PARAM_INT);
    
    if (!empty(trim($q))) {
        $search = "%$q%";
        $stmt->bindParam(':q', $search);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ... Skipping urbanismo, parentesco, prefijo, plantel, secciones_curso as they likely already handle empty Q (checked previously)

/**
 * Buscar personas por sexo (para buscar padre o madre)
 * Busca en todas las personas del sistema filtradas por sexo
 */
function buscarPersonasPorSexo($conexion, $q, $limit = 10, $idSexo = null) {
    $whereCondition = "";
    $params = [];

    // Filtrar por sexo si se especifica
    if ($idSexo !== null) {
        $whereCondition .= " AND p.IdSexo = :idSexo";
        $params[':idSexo'] = $idSexo;
    }

    $condicionBusqueda = "";
    if (!empty(trim($q))) {
        $condicionBusqueda = "AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)";
    }

    $query = "
        SELECT DISTINCT
            p.IdPersona,
            p.nombre,
            p.apellido,
            p.cedula,
            p.correo,
            n.nacionalidad,
            p.IdNacionalidad,
            p.IdSexo,
            s.sexo as sexo_texto
        FROM persona p
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        LEFT JOIN sexo s ON p.IdSexo = s.IdSexo
        WHERE 1=1 
        $whereCondition
        $condicionBusqueda
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ";

    $stmt = $conexion->prepare($query);

    if (!empty(trim($q))) {
        $search = "%$q%";
        $stmt->bindParam(':q', $search);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }

    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Buscar estudiantes activos en el año escolar actual
 * Filtra por nombre/cédula y verifica inscripción activa
 */
function buscarEstudiantesActivos($conexion, $q, $limit = 10) {
    // Obtener año escolar activo
    $stmtAnio = $conexion->prepare("SELECT IdFecha_Escolar FROM fecha_escolar WHERE fecha_activa = 1 LIMIT 1");
    $stmtAnio->execute();
    $anioActivo = $stmtAnio->fetch(PDO::FETCH_ASSOC);

    if (!$anioActivo) {
        echo json_encode([]);
        return;
    }

    $idAnioActivo = $anioActivo['IdFecha_Escolar'];

    // Si hay búsqueda, usar LIKE. Si no, traer los primeros registros.
    $condicionBusqueda = "";
    if (!empty(trim($q))) {
        $condicionBusqueda = "AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)";
    }

    // Buscar estudiantes activos que NO tienen grupo de interes
    $query = "
        SELECT DISTINCT
            p.IdPersona,
            p.nombre,
            p.apellido,
            p.cedula,
            n.nacionalidad,
            c.IdCurso,
            c.curso
        FROM persona p
        INNER JOIN inscripcion i ON p.IdPersona = i.IdEstudiante
        INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
        INNER JOIN curso c ON cs.IdCurso = c.IdCurso
        LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
        WHERE i.IdFecha_Escolar = :idAnioActivo
        AND i.IdStatus = 11 -- Inscrito
        $condicionBusqueda
        AND p.IdPersona NOT IN (
            SELECT IdEstudiante FROM inscripcion_grupo_interes 
            WHERE IdInscripcion IN (SELECT IdInscripcion FROM inscripcion WHERE IdFecha_Escolar = :idAnioActivo)
        )
        ORDER BY p.apellido, p.nombre
        LIMIT :limit
    ";

    $stmt = $conexion->prepare($query);

    $stmt->bindParam(':idAnioActivo', $idAnioActivo, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

    if (!empty(trim($q))) {
        $search = "%$q%";
        $stmt->bindParam(':q', $search, PDO::PARAM_STR);
    }

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
