<?php
/**
 * API Endpoints para IA - Sistema de Inscripciones
 * Endpoints públicos optimizados para consultas mediante IA (n8n, ChatGPT, etc.)
 * 
 * Uso: /api/ia_consultas.php?action=<nombre_accion>&<parametros>
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/conexion.php';

// Crear conexión
$database = new Database();
$conexion = $database->getConnection();

// Obtener acción solicitada
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Enrutador de acciones
try {
    switch ($action) {
        // ========== CONSULTAS DE CUPOS ==========
        case 'cupos_disponibles':
            consultarCuposDisponibles($conexion);
            break;
            
        case 'cupos_por_curso':
            consultarCuposPorCurso($conexion);
            break;
            
        case 'todos_los_cupos':
            consultarTodosLosCupos($conexion);
            break;

        // ========== INFORMACIÓN GENERAL ==========
        case 'info_cursos':
            obtenerInformacionCursos($conexion);
            break;
            
        case 'info_niveles':
            obtenerInformacionNiveles($conexion);
            break;
            
        case 'periodo_escolar':
            obtenerPeriodoEscolar($conexion);
            break;

        // ========== REQUISITOS ==========
        case 'requisitos_inscripcion':
            obtenerRequisitosInscripcion($conexion);
            break;

        // ========== ESTADÍSTICAS ==========
        case 'estadisticas_inscripciones':
            obtenerEstadisticasInscripciones($conexion);
            break;
            
        case 'distribucion_estudiantes':
            obtenerDistribucionEstudiantes($conexion);
            break;

        // ========== CONSULTAS DE ESTADO ==========
        case 'estado_inscripcion':
            consultarEstadoInscripcion($conexion);
            break;
            
        case 'inscripciones_activas':
            verificarInscripcionesActivas($conexion);
            break;

        // ========== INFORMACIÓN DE CONTACTO ==========
        case 'info_plantel':
            obtenerInformacionPlantel($conexion);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida',
                'acciones_disponibles' => [
                    'cupos_disponibles' => 'Consultar cupos disponibles (requiere: nivel o curso)',
                    'cupos_por_curso' => 'Consultar cupos de un curso específico (requiere: id_curso)',
                    'todos_los_cupos' => 'Ver cupos de todos los cursos',
                    'info_cursos' => 'Obtener información de todos los cursos',
                    'info_niveles' => 'Obtener información de niveles educativos',
                    'periodo_escolar' => 'Obtener información del período escolar activo',
                    'requisitos_inscripcion' => 'Obtener requisitos de inscripción (requiere: nivel)',
                    'estadisticas_inscripciones' => 'Obtener estadísticas generales de inscripciones',
                    'distribucion_estudiantes' => 'Ver distribución de estudiantes por curso',
                    'estado_inscripcion' => 'Consultar estado de una inscripción (requiere: codigo_inscripcion o cedula)',
                    'inscripciones_activas' => 'Verificar si están abiertas las inscripciones',
                    'info_plantel' => 'Obtener información de contacto del plantel'
                ]
            ]);
    }
} catch (Exception $e) {
    error_log("Error en API IA: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

// ============================================================
// FUNCIONES DE ENDPOINTS
// ============================================================

/**
 * Consulta cupos disponibles por nivel o curso
 * GET params: nivel (nombre) o curso (nombre)
 */
function consultarCuposDisponibles($conexion) {
    $nivelNombre = $_GET['nivel'] ?? '';
    $cursoNombre = $_GET['curso'] ?? '';
    
    try {
        $query = "
            SELECT 
                n.nivel,
                c.curso,
                c.IdCurso,
                COUNT(DISTINCT cs.IdCurso_Seccion) as total_secciones,
                SUM(CASE WHEN a.capacidad IS NULL THEN 99999 ELSE a.capacidad END) as capacidad_total,
                COUNT(DISTINCT CASE WHEN i.IdStatus = 11 THEN i.IdInscripcion END) as estudiantes_inscritos,
                SUM(CASE WHEN a.capacidad IS NULL THEN 99999 ELSE a.capacidad END) - 
                COUNT(DISTINCT CASE WHEN i.IdStatus = 11 THEN i.IdInscripcion END) as cupos_disponibles,
                MAX(
                    CASE 
                        WHEN (a.capacidad IS NULL OR a.capacidad > 
                            (SELECT COUNT(*) 
                            FROM inscripcion i2 
                            WHERE i2.IdCurso_Seccion = cs.IdCurso_Seccion
                            AND i2.IdStatus = 11)
                        ) 
                        THEN 1 
                        ELSE 0 
                    END
                ) AS hay_cupos
            FROM curso c
            INNER JOIN nivel n ON c.IdNivel = n.IdNivel
            INNER JOIN curso_seccion cs ON c.IdCurso = cs.IdCurso
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            WHERE s.seccion != 'Inscripción'
        ";
        
        $params = [];
        
        if (!empty($nivelNombre)) {
            $query .= " AND LOWER(n.nivel) LIKE LOWER(:nivel)";
            $params[':nivel'] = "%{$nivelNombre}%";
        }
        
        if (!empty($cursoNombre)) {
            $query .= " AND LOWER(c.curso) LIKE LOWER(:curso)";
            $params[':curso'] = "%{$cursoNombre}%";
        }
        
        $query .= " GROUP BY c.IdCurso, n.nivel, c.curso
                    ORDER BY n.IdNivel, c.IdCurso";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute($params);
        $cupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $respuesta = [
            'success' => true,
            'mensaje' => empty($cupos) ? 'No se encontraron cursos con esos criterios' : 'Cupos encontrados',
            'total_cursos' => count($cupos),
            'cupos' => array_map(function($c) {
                return [
                    'nivel' => $c['nivel'],
                    'curso' => $c['curso'],
                    'total_secciones' => (int)$c['total_secciones'],
                    'capacidad_total' => (int)$c['capacidad_total'],
                    'estudiantes_inscritos' => (int)$c['estudiantes_inscritos'],
                    'cupos_disponibles' => (int)$c['cupos_disponibles'],
                    'hay_cupos' => $c['hay_cupos'],
                    'porcentaje_ocupacion' => round(((int)$c['estudiantes_inscritos'] / (int)$c['capacidad_total']) * 100, 2)
                ];
            }, $cupos)
        ];
        
        echo json_encode($respuesta, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Consulta cupos de un curso específico por ID o nombre
 * GET params: id_curso (número) o curso (nombre)
 */
function consultarCuposPorCurso($conexion) {
    $idCurso = $_GET['id_curso'] ?? 0;
    $nombreCurso = $_GET['curso'] ?? '';
    
    if ($idCurso <= 0 && empty($nombreCurso)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Debe proporcionar el ID del curso o el nombre del curso (ej: "1er Grado", "2do Año")'
        ]);
        return;
    }
    
    try {
        $query = "
            SELECT 
                c.curso,
                c.IdCurso,
                n.nivel,
                s.seccion,
                a.capacidad,
                COUNT(DISTINCT i.IdInscripcion) as estudiantes_inscritos,
                CASE 
                    WHEN a.capacidad IS NULL THEN 99999
                    ELSE a.capacidad - COUNT(DISTINCT i.IdInscripcion)
                END as cupos_disponibles,
                CASE 
                    WHEN a.capacidad IS NULL OR COUNT(DISTINCT i.IdInscripcion) < a.capacidad 
                    THEN 'SI' 
                    ELSE 'NO' 
                END as tiene_cupo
            FROM curso_seccion cs
            INNER JOIN curso c ON cs.IdCurso = c.IdCurso
            INNER JOIN nivel n ON c.IdNivel = n.IdNivel
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            WHERE 1=1
                AND s.seccion != 'Inscripción'
        ";
        
        $params = [];
        
        if ($idCurso > 0) {
            $query .= " AND c.IdCurso = :idCurso";
            $params[':idCurso'] = $idCurso;
        } else {
            $query .= " AND LOWER(c.curso) LIKE LOWER(:curso)";
            $params[':curso'] = "%{$nombreCurso}%";
        }
        
        $query .= " GROUP BY cs.IdCurso_Seccion, c.IdCurso, c.curso, n.nivel, s.seccion, a.capacidad
                    ORDER BY s.seccion";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute($params);
        $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($secciones)) {
            echo json_encode([
                'success' => false, 
                'message' => 'No se encontró ningún curso con ese nombre. Intenta con: "1er Grado", "2do Año", etc.'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'curso' => $secciones[0]['curso'],
            'id_curso' => $secciones[0]['IdCurso'],
            'nivel' => $secciones[0]['nivel'],
            'total_secciones' => count($secciones),
            'secciones' => $secciones
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Consulta todos los cupos disponibles de todos los cursos
 */
function consultarTodosLosCupos($conexion) {
    try {
        $query = "
            SELECT 
                n.nivel,
                n.IdNivel,
                c.curso,
                c.IdCurso,
                COUNT(DISTINCT cs.IdCurso_Seccion) as total_secciones,
                SUM(CASE WHEN a.capacidad IS NULL THEN 35 ELSE a.capacidad END) as capacidad_total,
                COUNT(DISTINCT CASE WHEN i.IdStatus = 11 THEN i.IdInscripcion END) as estudiantes_inscritos,
                SUM(CASE WHEN a.capacidad IS NULL THEN 35 ELSE a.capacidad END) - 
                COUNT(DISTINCT CASE WHEN i.IdStatus = 11 THEN i.IdInscripcion END) as cupos_disponibles
            FROM nivel n
            INNER JOIN curso c ON n.IdNivel = c.IdNivel
            INNER JOIN curso_seccion cs ON c.IdCurso = cs.IdCurso
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            WHERE s.seccion != 'Inscripción'
            GROUP BY n.IdNivel, n.nivel, c.IdCurso, c.curso
            ORDER BY n.IdNivel, c.IdCurso
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar por nivel
        $porNivel = [];
        foreach ($todos as $curso) {
            $nivel = $curso['nivel'];
            if (!isset($porNivel[$nivel])) {
                $porNivel[$nivel] = [];
            }
            $porNivel[$nivel][] = [
                'curso' => $curso['curso'],
                'id_curso' => $curso['IdCurso'],
                'secciones' => (int)$curso['total_secciones'],
                'capacidad' => (int)$curso['capacidad_total'],
                'inscritos' => (int)$curso['estudiantes_inscritos'],
                'disponibles' => (int)$curso['cupos_disponibles']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'resumen_por_nivel' => $porNivel
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene información de todos los cursos
 */
function obtenerInformacionCursos($conexion) {
    try {
        $query = "
            SELECT 
                c.IdCurso,
                c.curso,
                n.nivel,
                n.IdNivel
            FROM curso c
            INNER JOIN nivel n ON c.IdNivel = n.IdNivel
            ORDER BY n.IdNivel, c.IdCurso
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'total' => count($cursos),
            'cursos' => $cursos
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene información de los niveles educativos
 */
function obtenerInformacionNiveles($conexion) {
    try {
        $query = "
            SELECT 
                n.IdNivel,
                n.nivel,
                COUNT(DISTINCT c.IdCurso) as total_cursos,
                GROUP_CONCAT(c.curso ORDER BY c.IdCurso SEPARATOR ', ') as cursos
            FROM nivel n
            LEFT JOIN curso c ON n.IdNivel = c.IdNivel
            GROUP BY n.IdNivel, n.nivel
            ORDER BY n.IdNivel
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $niveles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'total_niveles' => count($niveles),
            'niveles' => $niveles
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene información del período escolar activo
 */
function obtenerPeriodoEscolar($conexion) {
    try {
        $query = "
            SELECT 
                fecha_escolar,
                fecha_activa,
                inscripcion_activa,
                renovacion_activa
            FROM fecha_escolar
            WHERE fecha_activa = 1
            LIMIT 1
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$periodo) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay un período escolar activo'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'periodo_escolar' => $periodo['fecha_escolar'],
            'inscripciones_abiertas' => (bool)$periodo['inscripcion_activa'],
            'renovaciones_abiertas' => (bool)$periodo['renovacion_activa']
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene requisitos de inscripción por nivel
 * GET params: nivel_id (número) o nivel (nombre como "Inicial", "Primaria", "Media General")
 */
function obtenerRequisitosInscripcion($conexion) {
    $nivelId = $_GET['nivel_id'] ?? 0;
    $nivelNombre = $_GET['nivel'] ?? '';
    
    if ($nivelId <= 0 && empty($nivelNombre)) {
        echo json_encode([
            'success' => false,
            'message' => 'Debe proporcionar el ID del nivel o el nombre del nivel (ej: "Inicial", "Primaria", "Media General")'
        ]);
        return;
    }
    
    try {
        $query = "
            SELECT 
                r.IdRequisito,
                r.requisito,
                r.obligatorio,
                n.nivel,
                n.IdNivel
            FROM requisito r
            INNER JOIN nivel n ON r.IdNivel = n.IdNivel
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($nivelId > 0) {
            $query .= " AND r.IdNivel = :nivelId";
            $params[':nivelId'] = $nivelId;
        } else {
            $query .= " AND LOWER(n.nivel) LIKE LOWER(:nivelNombre)";
            $params[':nivelNombre'] = "%{$nivelNombre}%";
        }
        
        $query .= " ORDER BY r.obligatorio DESC, r.IdRequisito";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute($params);
        $requisitos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($requisitos)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron requisitos para ese nivel. Los niveles disponibles son: Inicial, Primaria, Media General'
            ]);
            return;
        }
        
        $obligatorios = array_filter($requisitos, fn($r) => (bool)$r['obligatorio']);
        $opcionales = array_filter($requisitos, fn($r) => !(bool)$r['obligatorio']);
        
        echo json_encode([
            'success' => true,
            'nivel' => $requisitos[0]['nivel'],
            'id_nivel' => $requisitos[0]['IdNivel'],
            'total_requisitos' => count($requisitos),
            'total_obligatorios' => count($obligatorios),
            'total_opcionales' => count($opcionales),
            'obligatorios' => array_values($obligatorios),
            'opcionales' => array_values($opcionales)
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene estadísticas generales de inscripciones
 */
function obtenerEstadisticasInscripciones($conexion) {
    try {
        // Total de inscripciones por estado
        $queryEstados = "
            SELECT 
                s.status,
                COUNT(i.IdInscripcion) as total
            FROM inscripcion i
            INNER JOIN status s ON i.IdStatus = s.IdStatus
            INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
            WHERE fe.fecha_activa = 1
            GROUP BY s.status
            ORDER BY total DESC
        ";
        
        $stmt = $conexion->prepare($queryEstados);
        $stmt->execute();
        $porEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total por nivel
        $queryNiveles = "
            SELECT 
                n.nivel,
                COUNT(DISTINCT i.IdInscripcion) as total_inscripciones,
                COUNT(DISTINCT CASE WHEN i.IdStatus = 11 THEN i.IdInscripcion END) as inscritos
            FROM inscripcion i
            INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
            INNER JOIN curso c ON cs.IdCurso = c.IdCurso
            INNER JOIN nivel n ON c.IdNivel = n.IdNivel
            INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
            WHERE fe.fecha_activa = 1
            GROUP BY n.nivel
            ORDER BY n.IdNivel
        ";
        
        $stmt = $conexion->prepare($queryNiveles);
        $stmt->execute();
        $porNivel = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'por_estado' => $porEstado,
            'por_nivel' => $porNivel
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene la distribución de estudiantes por curso y sección
 */
function obtenerDistribucionEstudiantes($conexion) {
    try {
        $query = "
            SELECT 
                n.nivel,
                c.curso,
                s.seccion,
                COUNT(DISTINCT i.IdInscripcion) as total_estudiantes,
                a.capacidad
            FROM curso_seccion cs
            INNER JOIN curso c ON cs.IdCurso = c.IdCurso
            INNER JOIN nivel n ON c.IdNivel = n.IdNivel
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            WHERE s.seccion != 'Inscripción'
            GROUP BY n.nivel, c.curso, s.seccion, a.capacidad
            ORDER BY n.IdNivel, c.IdCurso, s.seccion
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'distribucion' => $distribucion
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Consulta el estado de una inscripción
 * GET params: codigo_inscripcion (string) o cedula (string) o nombre_estudiante (string)
 */
function consultarEstadoInscripcion($conexion) {
    $codigoInscripcion = $_GET['codigo_inscripcion'] ?? '';
    $cedula = $_GET['cedula'] ?? '';
    $nombreEstudiante = $_GET['nombre_estudiante'] ?? '';
    
    if (empty($codigoInscripcion) && empty($cedula) && empty($nombreEstudiante)) {
        echo json_encode([
            'success' => false,
            'message' => 'Debe proporcionar el código de inscripción, la cédula del estudiante o el nombre del estudiante'
        ]);
        return;
    }
    
    try {
        $query = "
            SELECT 
                i.IdInscripcion,
                i.codigo_inscripcion,
                i.fecha_inscripcion,
                s.status,
                p.nombre,
                p.apellido,
                p.cedula,
                n.nacionalidad,
                c.curso,
                niv.nivel,
                sec.seccion,
                fe.fecha_escolar
            FROM inscripcion i
            INNER JOIN persona p ON i.IdEstudiante = p.IdPersona
            INNER JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
            INNER JOIN status s ON i.IdStatus = s.IdStatus
            INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
            INNER JOIN curso c ON cs.IdCurso = c.IdCurso
            INNER JOIN nivel niv ON c.IdNivel = niv.IdNivel
            INNER JOIN seccion sec ON cs.IdSeccion = sec.IdSeccion
            INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
            WHERE fe.fecha_activa = 1
        ";
        
        $params = [];
        
        if (!empty($codigoInscripcion)) {
            $query .= " AND i.codigo_inscripcion = :codigo";
            $params[':codigo'] = $codigoInscripcion;
        } elseif (!empty($cedula)) {
            $query .= " AND p.cedula = :cedula";
            $params[':cedula'] = $cedula;
        } else {
            $query .= " AND (LOWER(p.nombre) LIKE LOWER(:nombre) 
                            OR LOWER(p.apellido) LIKE LOWER(:nombre) 
                            OR LOWER(CONCAT(p.nombre, ' ', p.apellido)) LIKE LOWER(:nombre))";
            $params[':nombre'] = "%{$nombreEstudiante}%";
        }
        
        $query .= " ORDER BY i.fecha_inscripcion DESC";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute($params);
        $inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($inscripciones)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró ninguna inscripción con esos datos en el período escolar activo'
            ]);
            return;
        }
        
        // Si hay múltiples resultados (búsqueda por nombre), devolver lista
        if (count($inscripciones) > 1) {
            echo json_encode([
                'success' => true,
                'multiple' => true,
                'total_encontrados' => count($inscripciones),
                'mensaje' => 'Se encontraron múltiples inscripciones. Por favor sea más específico o use la cédula.',
                'inscripciones' => array_map(function($i) {
                    return [
                        'codigo_inscripcion' => $i['codigo_inscripcion'],
                        'estudiante' => $i['nombre'] . ' ' . $i['apellido'],
                        'cedula' => $i['nacionalidad'] . '-' . $i['cedula'],
                        'curso' => $i['curso'],
                        'status' => $i['status']
                    ];
                }, $inscripciones)
            ], JSON_PRETTY_PRINT);
            return;
        }
        
        // Un solo resultado
        $inscripcion = $inscripciones[0];
        
        echo json_encode([
            'success' => true,
            'inscripcion' => [
                'codigo_inscripcion' => $inscripcion['codigo_inscripcion'],
                'fecha_inscripcion' => $inscripcion['fecha_inscripcion'],
                'estado' => $inscripcion['status'],
                'estudiante' => [
                    'nombre_completo' => $inscripcion['nombre'] . ' ' . $inscripcion['apellido'],
                    'cedula' => $inscripcion['nacionalidad'] . '-' . $inscripcion['cedula']
                ],
                'curso' => [
                    'nivel' => $inscripcion['nivel'],
                    'curso' => $inscripcion['curso'],
                    'seccion' => $inscripcion['seccion']
                ],
                'periodo_escolar' => $inscripcion['fecha_escolar']
            ]
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Verifica si las inscripciones están activas
 */
function verificarInscripcionesActivas($conexion) {
    try {
        $query = "
            SELECT 
                fecha_escolar,
                inscripcion_activa,
                renovacion_activa
            FROM fecha_escolar
            WHERE fecha_activa = 1
            LIMIT 1
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$periodo) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay período escolar activo'
            ]);
            return;
        }
        
        $inscripcionesAbiertas = (bool)$periodo['inscripcion_activa'];
        $renovacionesAbiertas = (bool)$periodo['renovacion_activa'];
        
        echo json_encode([
            'success' => true,
            'periodo' => $periodo['fecha_escolar'],
            'inscripciones_abiertas' => $inscripcionesAbiertas,
            'renovaciones_abiertas' => $renovacionesAbiertas,
            'mensaje' => $inscripcionesAbiertas 
                ? 'Las inscripciones están abiertas' 
                : 'Las inscripciones están cerradas actualmente'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obtiene información de contacto del plantel
 */
function obtenerInformacionPlantel($conexion) {
    echo json_encode([
        'success' => true,
        'plantel' => [
            'nombre' => 'U.E.C "Fermín Toro"',
            'direccion' => 'Avenida Eduardo Chollet, Calle 5, Araure, Estado Portuguesa',
            'telefono' => '+58 414-5641168',
            'email' => 'fermin.toro.araure@gmail.com',
            'horario_atencion' => 'Lunes a Viernes: 7:00 AM - 3:00 PM',
            'niveles_educativos' => [
                'Inicial (Preescolar)',
                'Primaria (1ro a 6to grado)',
                'Media General (1ro a 5to año)'
            ]
        ]
    ], JSON_PRETTY_PRINT);
}