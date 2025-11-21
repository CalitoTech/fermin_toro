<?php
// Iniciar buffer de salida para evitar que warnings/notices rompan el JSON
ob_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/Discapacidad.php';
require_once __DIR__ . '/../modelos/Representante.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/Telefono.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';
require_once __DIR__ . '/../modelos/DetallePerfil.php';
require_once __DIR__ . '/../modelos/CursoSeccion.php';
require_once __DIR__ . '/../modelos/TipoDiscapacidad.php';

date_default_timezone_set('America/Caracas');

// Limpiar buffer y establecer header JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Crear conexión
$database = new Database();
$conexion = $database->getConnection();

function actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario) {
    try {
        $query = "UPDATE inscripcion 
                  SET modificado_por = :modificado_por, 
                      ultima_modificacion = NOW() 
                  WHERE IdInscripcion = :idInscripcion";
        
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':modificado_por', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al actualizar auditoría: " . $e->getMessage());
        return false;
    }
}

// Función auxiliar para obtener el ID del usuario de la sesión
function obtenerIdUsuario() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['idPersona'] ?? null;
}

/**
 * Obtiene o crea un urbanismo
 * Si el ID es 'nuevo', crea un nuevo registro en la tabla urbanismo
 *
 * @param PDO $conexion Conexión a la base de datos
 * @param mixed $idUrbanismo ID del urbanismo o 'nuevo'
 * @param string $nombreUrbanismo Nombre del urbanismo (usado si es nuevo)
 * @return int ID del urbanismo
 */
function obtenerOCrearUrbanismo($conexion, $idUrbanismo, $nombreUrbanismo = '') {
    if ($idUrbanismo === 'nuevo' || $idUrbanismo === '0' || empty($idUrbanismo)) {
        // Verificar si ya existe un urbanismo con ese nombre
        $stmt = $conexion->prepare("SELECT IdUrbanismo FROM urbanismo WHERE LOWER(urbanismo) = LOWER(:nombre)");
        $stmt->execute([':nombre' => trim($nombreUrbanismo)]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            return (int)$existe['IdUrbanismo'];
        }

        // Crear nuevo urbanismo
        $stmt = $conexion->prepare("INSERT INTO urbanismo (urbanismo) VALUES (:nombre)");
        $stmt->execute([':nombre' => trim($nombreUrbanismo)]);
        return (int)$conexion->lastInsertId();
    }

    return (int)$idUrbanismo;
}

/**
 * Obtiene o crea un parentesco
 * Si el ID es 'nuevo', crea un nuevo registro en la tabla parentesco
 *
 * @param PDO $conexion Conexión a la base de datos
 * @param mixed $idParentesco ID del parentesco o 'nuevo'
 * @param string $nombreParentesco Nombre del parentesco (usado si es nuevo)
 * @return int ID del parentesco
 */
function obtenerOCrearParentesco($conexion, $idParentesco, $nombreParentesco = '') {
    if ($idParentesco === 'nuevo' || $idParentesco === '0' || empty($idParentesco)) {
        // Verificar si ya existe un parentesco con ese nombre
        $stmt = $conexion->prepare("SELECT IdParentesco FROM parentesco WHERE LOWER(parentesco) = LOWER(:nombre)");
        $stmt->execute([':nombre' => trim($nombreParentesco)]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            return (int)$existe['IdParentesco'];
        }

        // Crear nuevo parentesco
        $stmt = $conexion->prepare("INSERT INTO parentesco (parentesco) VALUES (:nombre)");
        $stmt->execute([':nombre' => trim($nombreParentesco)]);
        return (int)$conexion->lastInsertId();
    }

    return (int)$idParentesco;
}

/**
 * Obtiene o crea un prefijo telefónico
 * Si el ID es 'nuevo', crea un nuevo registro en la tabla prefijo
 *
 * @param PDO $conexion Conexión a la base de datos
 * @param mixed $idPrefijo ID del prefijo o 'nuevo'
 * @param string $jsonPrefijo JSON con datos del prefijo (codigo, pais, max_digitos)
 * @return int|null ID del prefijo o null si está vacío
 */
function obtenerOCrearPrefijo($conexion, $idPrefijo, $jsonPrefijo = '') {
    if (empty($idPrefijo) && empty($jsonPrefijo)) {
        return null;
    }

    if ($idPrefijo === 'nuevo' || $idPrefijo === '0' || empty($idPrefijo)) {
        // Decodificar JSON con datos del prefijo
        $datosPrefijo = json_decode($jsonPrefijo, true);

        if (!$datosPrefijo || empty($datosPrefijo['codigo'])) {
            return null;
        }

        // Verificar si ya existe un prefijo con ese código
        $stmt = $conexion->prepare("SELECT IdPrefijo FROM prefijo WHERE codigo_prefijo = :codigo");
        $stmt->execute([':codigo' => trim($datosPrefijo['codigo'])]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            return (int)$existe['IdPrefijo'];
        }

        // Crear nuevo prefijo
        $stmt = $conexion->prepare("INSERT INTO prefijo (codigo_prefijo, pais, max_digitos) VALUES (:codigo, :pais, :max_digitos)");
        $stmt->execute([
            ':codigo' => trim($datosPrefijo['codigo']),
            ':pais' => trim($datosPrefijo['pais'] ?? 'Desconocido'),
            ':max_digitos' => (int)($datosPrefijo['max_digitos'] ?? 10)
        ]);
        return (int)$conexion->lastInsertId();
    }

    return (int)$idPrefijo;
}

/**
 * Genera una cédula escolar para el estudiante basada en la cédula de la madre y el año de nacimiento.
 * Formato: <orden><año><cedulaMadre> (todo junto, sin separadores)
 * Donde:
 *   - orden: número del hijo (1, 2, 3...) según hermanos nacidos en el mismo año
 *   - año: últimos 2 dígitos del año de nacimiento (ej: 15 para 2015)
 *   - cedulaMadre: cédula completa de la madre
 * Ejemplo: 1157424268 = primer hijo nacido en 2015 de madre con cédula 7424268
 * Retorna la cédula como string o false si no pudo generarla.
 * IMPORTANTE: El campo cedula debe ser VARCHAR en la BD para evitar overflow de INT.
 */
function generarCedulaEscolar($conexion, $cedulaMadre, $anioNacimiento, $idNacionalidad = null) {
    // Limpiar la cédula de cualquier carácter no numérico
    $cedulaMadreClean = preg_replace('/[^0-9]/', '', $cedulaMadre);
    if (empty($cedulaMadreClean) || empty($anioNacimiento)) return false;

    // Obtener últimos 2 dígitos del año
    $dosDigitos = substr($anioNacimiento, -2);

    // Intentar prefijos del 1 al 99 (orden del hijo)
    for ($orden = 1; $orden <= 99; $orden++) {
        // Formato: ordenañocedulaMadre (ej: 1157424268 = primer hijo del 2015 de madre con cédula 7424268)
        // Guardamos como STRING para evitar overflow de INT
        $cand = (string)$orden . $dosDigitos . $cedulaMadreClean;

        // Verificar existencia en persona (mismo IdNacionalidad si se dio)
        if ($idNacionalidad) {
            $sql = "SELECT COUNT(*) FROM persona WHERE cedula = :cedula AND IdNacionalidad = :idNac";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':cedula' => $cand, ':idNac' => $idNacionalidad]);
        } else {
            $sql = "SELECT COUNT(*) FROM persona WHERE cedula = :cedula";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':cedula' => $cand]);
        }

        $count = (int)$stmt->fetchColumn();
        if ($count === 0) {
            return $cand; // Retornar la primera cédula disponible
        }
    }

    return false; // Si no se encontró una cédula disponible
}

function obtenerSeccionRecomendada($conexion, $idCurso, $idUrbanismo, $idCursoSeccionActual) {
    try {
        // Primero verificar si hay aulas con cupo disponible
        $aulasConCupo = verificarCapacidadAulas($conexion, $idCurso);
        
        $hayAulasConCupo = false;
        foreach ($aulasConCupo as $aula) {
            if ($aula['tiene_cupo'] == 1) {
                $hayAulasConCupo = true;
                break;
            }
        }
        
        if ($hayAulasConCupo) {
            // Buscar sección con cupo y mismo urbanismo
            $query = "SELECT 
                    cs.IdCurso_Seccion,
                    s.seccion,
                    COUNT(DISTINCT CASE WHEN e.IdUrbanismo = :id_urbanismo THEN i.IdInscripcion END) as mismos_urbanismo,
                    COUNT(DISTINCT i2.IdInscripcion) as total_estudiantes,
                    a.capacidad,
                    CASE 
                        WHEN COUNT(DISTINCT CASE WHEN e.IdUrbanismo = :id_urbanismo THEN i.IdInscripcion END) > 0 THEN 1
                        ELSE 0 
                    END as tiene_mismo_urbanismo,
                    CASE 
                        WHEN a.capacidad IS NULL OR COUNT(DISTINCT i2.IdInscripcion) < a.capacidad THEN 1
                        ELSE 0 
                    END as tiene_cupo
                FROM curso_seccion cs
                INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
                LEFT JOIN aula a ON cs.IdAula = a.IdAula
                LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                    AND i.IdStatus = 11
                LEFT JOIN persona e ON i.IdEstudiante = e.IdPersona
                LEFT JOIN inscripcion i2 ON cs.IdCurso_Seccion = i2.IdCurso_Seccion 
                    AND i2.IdStatus = 11
                WHERE cs.IdCurso = :id_curso
                AND s.seccion != 'Inscripción'
                AND cs.IdCurso_Seccion != :id_curso_seccion_actual
                GROUP BY cs.IdCurso_Seccion
                HAVING tiene_cupo = 1  -- Solo secciones con cupo disponible
                ORDER BY tiene_mismo_urbanismo DESC, mismos_urbanismo DESC, 
                        total_estudiantes ASC, RAND()
                LIMIT 1";
            
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':id_curso', $idCurso, PDO::PARAM_INT);
            $stmt->bindParam(':id_urbanismo', $idUrbanismo, PDO::PARAM_INT);
            $stmt->bindParam(':id_curso_seccion_actual', $idCursoSeccionActual, PDO::PARAM_INT);
            $stmt->execute();
            
            $seccion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($seccion) {
                return $seccion['IdCurso_Seccion'];
            }
        }
        
        // Si no hay aulas con cupo, buscar la sección con menor cantidad de estudiantes
        return obtenerSeccionConMenosEstudiantes($conexion, $idCurso, $idUrbanismo, $idCursoSeccionActual);
        
    } catch (Exception $e) {
        error_log("Error al obtener sección recomendada: " . $e->getMessage());
        return null;
    }
}

function verificarCapacidadAulas($conexion, $idCurso) {
    try {
        $query = "SELECT 
                cs.IdCurso_Seccion,
                s.seccion,
                COUNT(DISTINCT i.IdInscripcion) as total_estudiantes,
                a.capacidad,
                CASE 
                    WHEN a.capacidad IS NULL THEN 1
                    WHEN COUNT(DISTINCT i.IdInscripcion) < a.capacidad THEN 1
                    ELSE 0 
                END as tiene_cupo
            FROM curso_seccion cs
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            WHERE cs.IdCurso = :id_curso
            AND s.seccion != 'Inscripción'
            GROUP BY cs.IdCurso_Seccion
            ORDER BY tiene_cupo DESC, total_estudiantes ASC";
        
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':id_curso', $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error al verificar capacidad de aulas: " . $e->getMessage());
        return [];
    }
}

function obtenerSeccionConMenosEstudiantes($conexion, $idCurso, $idUrbanismo, $idCursoSeccionActual) {
    try {
        $query = "SELECT 
                cs.IdCurso_Seccion,
                s.seccion,
                COUNT(DISTINCT i2.IdInscripcion) as total_estudiantes,
                a.capacidad,
                CASE 
                    WHEN COUNT(DISTINCT CASE WHEN e.IdUrbanismo = :id_urbanismo THEN i.IdInscripcion END) > 0 THEN 1
                    ELSE 0 
                END as tiene_mismo_urbanismo
            FROM curso_seccion cs
            INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN aula a ON cs.IdAula = a.IdAula
            LEFT JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion 
                AND i.IdStatus = 11
            LEFT JOIN persona e ON i.IdEstudiante = e.IdPersona
            LEFT JOIN inscripcion i2 ON cs.IdCurso_Seccion = i2.IdCurso_Seccion 
                AND i2.IdStatus = 11
            WHERE cs.IdCurso = :id_curso
            AND s.seccion != 'Inscripción'
            AND cs.IdCurso_Seccion != :id_curso_seccion_actual
            GROUP BY cs.IdCurso_Seccion
            ORDER BY total_estudiantes ASC, tiene_mismo_urbanismo DESC, RAND()
            LIMIT 1";
        
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':id_curso', $idCurso, PDO::PARAM_INT);
        $stmt->bindParam(':id_urbanismo', $idUrbanismo, PDO::PARAM_INT);
        $stmt->bindParam(':id_curso_seccion_actual', $idCursoSeccionActual, PDO::PARAM_INT);
        $stmt->execute();
        
        $seccion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $seccion ? $seccion['IdCurso_Seccion'] : null;
        
    } catch (Exception $e) {
        error_log("Error al obtener sección con menos estudiantes: " . $e->getMessage());
        return null;
    }
}

function verificarInscripcion($conexion, $anio, $cedula, $nacionalidad) {
    try {
        $stmt = $conexion->prepare("
            SELECT 1
            FROM inscripcion i
            INNER JOIN persona p ON i.IdEstudiante = p.IdPersona
            WHERE p.cedula = :cedula 
              AND p.idNacionalidad = :nacionalidad
              AND i.IdFecha_Escolar = :anio
            LIMIT 1
        ");
        $stmt->execute([
            ':cedula' => $cedula,
            ':nacionalidad' => $nacionalidad,
            ':anio' => $anio
        ]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        error_log("Error al verificar inscripción: " . $e->getMessage());
        return false;
    }
}

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Si no es una acción específica, usar el procesamiento normal JSON
if (empty($action)) {
    procesarInscripcion($conexion);
} else {
    // Para acciones específicas, manejar según el método
    switch ($action) {
        case 'cambiarStatus':
            cambiarStatus($conexion);
            break;
        case 'toggleRequisito':
            toggleRequisito($conexion);
            break;
        case 'actualizarMultiplesRequisitos':
            actualizarMultiplesRequisitos($conexion);
            break;
        case 'cambiarSeccion':
            cambiarSeccion($conexion);
            break;
        case 'hayCupo':
            hayCupo($conexion);
            break;
        case 'verificar':
            $anio = intval($_GET['anio'] ?? 0);
            $cedula = trim($_GET['cedula'] ?? '');
            $nacionalidad = trim($_GET['nacionalidad'] ?? '');

            $existe = verificarInscripcion($conexion, $anio, $cedula, $nacionalidad);
            echo json_encode(['existe' => $existe]);
            exit;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit();
    }
}

function procesarInscripcion($conexion) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (empty($_POST)) {
                throw new Exception("No se recibieron datos del formulario");
            }

            // === Validación de campos obligatorios ===
            $camposFaltantes = [];
            // Para decidir si cedula/telefono del estudiante son obligatorios, verificamos el IdCurso
            // Solo el primer curso (IdCurso == 1) es nuevo ingreso sin antecedentes, el resto requiere cédula
            $esPrimerCurso = false;
            $idCurso = null;
            if (!empty($_POST['IdCurso'])) {
                $idCurso = (int)$_POST['IdCurso'];
                if ($idCurso === 1) {
                    $esPrimerCurso = true;
                }
            }

            // 1. Validación de campos del estudiante
            $camposEstudiante = [
                'estudianteNombres' => 'Nombres del estudiante',
                'estudianteApellidos' => 'Apellidos del estudiante',
                'estudianteFechaNacimiento' => 'Fecha de nacimiento del estudiante',
                'estudianteLugarNacimiento' => 'Lugar de nacimiento del estudiante',
                'estudianteCorreo' => 'Correo electrónico del estudiante'
            ];

            // Agregar cédula, teléfono y plantel solo si NO es el primer curso
            if (!$esPrimerCurso) {
                $camposEstudiante['estudianteCedula'] = 'Cédula del estudiante';
                $camposEstudiante['estudianteTelefono'] = 'Teléfono del estudiante';
                $camposEstudiante['estudiantePlantel'] = 'Plantel donde cursó el último año escolar';
            }

            foreach ($camposEstudiante as $campo => $nombre) {
                if (empty($_POST[$campo])) {
                    $camposFaltantes[] = $nombre;
                }
            }
            
            // 2. Validación de campos del padre (siempre requeridos)
            $camposPadre = [
                'padreNombres' => 'Nombres del padre',
                'padreApellidos' => 'Apellidos del padre',
                'padreCedula' => 'Cédula del padre',
                'padreNacionalidad' => 'Nacionalidad del padre',
                'padreOcupacion' => 'Ocupación del padre',
                'padreTipoTrabajador' => 'Tipo de trabajador del padre',
                'padreUrbanismo' => 'Urbanismo/Sector del padre',
                'padreDireccion' => 'Dirección del padre',
                'padreTelefonoHabitacion' => 'Teléfono de habitación del padre',
                'padreCelular' => 'Celular del padre',
                'padreCorreo' => 'Correo electrónico del padre',
                'padreLugarTrabajo' => 'Lugar de trabajo del padre'
            ];
            
            foreach ($camposPadre as $campo => $nombre) {
                if (empty($_POST[$campo])) {
                    $camposFaltantes[] = $nombre;
                }
            }
            
            // 3. Validación de campos de la madre (siempre requeridos)
            $camposMadre = [
                'madreNombres' => 'Nombres de la madre',
                'madreApellidos' => 'Apellidos de la madre',
                'madreCedula' => 'Cédula de la madre',
                'madreNacionalidad' => 'Nacionalidad de la madre',
                'madreOcupacion' => 'Ocupación de la madre',
                'madreTipoTrabajador' => 'Tipo de trabajador de la madre',
                'madreUrbanismo' => 'Urbanismo/Sector de la madre',
                'madreDireccion' => 'Dirección de la madre',
                'madreTelefonoHabitacion' => 'Teléfono de habitación de la madre',
                'madreCelular' => 'Celular de la madre',
                'madreCorreo' => 'Correo electrónico de la madre',
                'madreLugarTrabajo' => 'Lugar de trabajo de la madre',
                'emergenciaNombre' => 'Nombre de contacto de emergencia',
                'emergenciaParentesco' => 'Parentesco de contacto de emergencia',
                'emergenciaCelular' => 'Teléfono de contacto de emergencia'
            ];
            
            foreach ($camposMadre as $campo => $nombre) {
                if (empty($_POST[$campo])) {
                    $camposFaltantes[] = $nombre;
                }
            }
            
            // 4. Validación de representante legal (si es otro)
            if (isset($_POST['tipoRepresentante']) && $_POST['tipoRepresentante'] === 'otro') {
                $camposRepresentante = [
                    'representanteNombres' => 'Nombres del representante legal',
                    'representanteApellidos' => 'Apellidos del representante legal',
                    'representanteCedula' => 'Cédula del representante legal',
                    'representanteNacionalidad' => 'Nacionalidad del representante legal',
                    'representanteParentesco' => 'Parentesco del representante legal',
                    'representanteOcupacion' => 'Ocupación del representante legal',
                    'representanteTipoTrabajador' => 'Tipo de trabajador del representante legal',
                    'representanteUrbanismo' => 'Urbanismo/Sector del representante legal',
                    'representanteDireccion' => 'Dirección del representante legal',
                    'representanteTelefonoHabitacion' => 'Teléfono de habitación del representante legal',
                    'representanteCelular' => 'Celular del representante legal',
                    'representanteCorreo' => 'Correo electrónico del representante legal',
                    'representanteLugarTrabajo' => 'Lugar de trabajo del representante legal'
                ];
                
                foreach ($camposRepresentante as $campo => $nombre) {
                    if (empty($_POST[$campo])) {
                        $camposFaltantes[] = $nombre;
                    }
                }
            }
            
            // 5. Validación de discapacidades
            if (!empty($_POST['tipo_discapacidad']) && is_array($_POST['tipo_discapacidad'])) {
                foreach ($_POST['tipo_discapacidad'] as $index => $tipo) {
                    if (!empty($tipo) && empty($_POST['descripcion_discapacidad'][$index])) {
                        $camposFaltantes[] = 'Descripción para la discapacidad seleccionada';
                        break;
                    }
                }
            }
            
            // 6. Validación adicional del contacto de emergencia
            if (!empty($_POST['emergenciaNombre'])) {
                $nombreCompleto = trim($_POST['emergenciaNombre']);
                if (str_word_count($nombreCompleto) < 2) {
                    $camposFaltantes[] = 'Nombre y apellido completo para el contacto de emergencia';
                }
            }
            
            // Mostrar errores si hay campos faltantes
            if (!empty($camposFaltantes)) {
                $camposUnicos = array_unique($camposFaltantes);
                $mensaje = 'Datos incompletos. Por favor complete los siguientes campos requeridos:';
                $mensaje .= "\n- " . implode("\n- ", $camposUnicos);
                throw new Exception($mensaje);
            }

            // === Validación de cédulas de representantes duplicadas ===
            // Verificar que las cédulas de padre, madre y representante no existan en la base de datos
            $representantesParaValidar = [];

            // Validar padre
            if (!empty($_POST['padreCedula']) && !empty($_POST['padreNacionalidad'])) {
                $representantesParaValidar[] = [
                    'cedula' => $_POST['padreCedula'],
                    'nacionalidad' => $_POST['padreNacionalidad'],
                    'nombre' => 'Padre'
                ];
            }

            // Validar madre
            if (!empty($_POST['madreCedula']) && !empty($_POST['madreNacionalidad'])) {
                $representantesParaValidar[] = [
                    'cedula' => $_POST['madreCedula'],
                    'nacionalidad' => $_POST['madreNacionalidad'],
                    'nombre' => 'Madre'
                ];
            }

            // Validar representante legal (si es otro)
            if (isset($_POST['tipoRepresentante']) && $_POST['tipoRepresentante'] === 'otro') {
                if (!empty($_POST['representanteCedula']) && !empty($_POST['representanteNacionalidad'])) {
                    $representantesParaValidar[] = [
                        'cedula' => $_POST['representanteCedula'],
                        'nacionalidad' => $_POST['representanteNacionalidad'],
                        'nombre' => 'Representante Legal'
                    ];
                }
            }

            // Verificar cada representante
            foreach ($representantesParaValidar as $rep) {
                $sql = "SELECT p.IdPersona, p.nombre, p.apellido, p.cedula, p.IdNacionalidad,
                               n.nacionalidad,
                               p.usuario, p.password,
                               CASE
                                   WHEN p.usuario IS NOT NULL AND p.usuario != ''
                                        AND p.password IS NOT NULL AND p.password != ''
                                   THEN 1
                                   ELSE 0
                               END AS tiene_credenciales
                        FROM persona p
                        INNER JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
                        WHERE p.cedula = :cedula
                        AND p.IdNacionalidad = :nacionalidad";

                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':cedula', $rep['cedula']);
                $stmt->bindParam(':nacionalidad', $rep['nacionalidad'], PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nombreCompleto = $persona['nombre'] . ' ' . $persona['apellido'];
                    $cedulaCompleta = $persona['nacionalidad'] . '-' . $persona['cedula'];

                    if ((bool)$persona['tiene_credenciales']) {
                        // La persona tiene usuario y contraseña
                        throw new Exception("La persona con cédula {$cedulaCompleta} ({$nombreCompleto}) ya tiene una cuenta en el sistema. Por favor, solicite que inicie sesión en su cuenta para realizar la inscripción.");
                    } else {
                        // La persona existe pero no tiene credenciales
                        throw new Exception("La persona con cédula {$cedulaCompleta} ({$nombreCompleto}) ya está registrada en el sistema. No puede registrar nuevamente a una persona que ya existe en la base de datos.");
                    }
                }
            }

            $conexion->beginTransaction();

            try {
                // ========================================================
                // ======== ESTUDIANTE ====================================
                // ========================================================
                $personaEstudiante = new Persona($conexion);

                // Buscar estudiante por cédula + nacionalidad
                $estudianteExistente = $personaEstudiante->obtenerPorCedula(
                    $_POST['estudianteNacionalidad'],
                    $_POST['estudianteCedula']
                );

                if ($estudianteExistente) {
                    // Ya existe en persona → usar ese IdPersona
                    $idEstudiante = $estudianteExistente['IdPersona'];
                } else {
                    // Crear nuevo registro porque no existe
                    
                    $personaEstudiante->IdEstadoAcceso = 2;
                    $personaEstudiante->IdEstadoInstitucional = 2;
                    $personaEstudiante->cedula = $_POST['estudianteCedula'] ?? null;
                    $personaEstudiante->nombre = $_POST['estudianteNombres'] ?? '';
                    $personaEstudiante->apellido = $_POST['estudianteApellidos'] ?? '';
                    $personaEstudiante->correo = $_POST['estudianteCorreo'] ?? '';
                    $personaEstudiante->fecha_nacimiento = $_POST['estudianteFechaNacimiento'] ?? null;
                    $personaEstudiante->IdNacionalidad = isset($_POST['estudianteNacionalidad']) ? (int)$_POST['estudianteNacionalidad'] : null;
                    $personaEstudiante->lugar_nacimiento = $_POST['estudianteLugarNacimiento'] ?? null;
                    $personaEstudiante->IdSexo = isset($_POST['estudianteSexo']) ? (int)$_POST['estudianteSexo'] : null;

                    // Urbanismo y dirección según representante
                    // Obtener o crear el urbanismo correspondiente
                    $urbanismoId = match($_POST['tipoRepresentante']) {
                        'padre' => $_POST['padreUrbanismo'] ?? null,
                        'madre' => $_POST['madreUrbanismo'] ?? null,
                        'otro' => $_POST['representanteUrbanismo'] ?? null,
                        default => null
                    };

                    $urbanismoNombre = match($_POST['tipoRepresentante']) {
                        'padre' => $_POST['padreUrbanismo_nombre'] ?? '',
                        'madre' => $_POST['madreUrbanismo_nombre'] ?? '',
                        'otro' => $_POST['representanteUrbanismo_nombre'] ?? '',
                        default => ''
                    };

                    $personaEstudiante->IdUrbanismo = obtenerOCrearUrbanismo($conexion, $urbanismoId, $urbanismoNombre);

                    $personaEstudiante->direccion = match($_POST['tipoRepresentante']) {
                        'padre' => $_POST['padreDireccion'] ?? null,
                        'madre' => $_POST['madreDireccion'] ?? null,
                        'otro' => $_POST['representanteDireccion'] ?? null,
                        default => null
                    };

                    $idEstudiante = $personaEstudiante->guardar();

                    // Si es el primer curso y no se proporcionó cédula, generar una automáticamente usando la cédula de la madre
                    if ($esPrimerCurso && empty($_POST['estudianteCedula'])) {
                        // Necesitamos la cédula de la madre y la fecha de nacimiento del estudiante
                        $cedulaMadre = $_POST['madreCedula'] ?? null;
                        $anioNacimiento = null;
                        if (!empty($_POST['estudianteFechaNacimiento'])) {
                            $anioNacimiento = date('Y', strtotime($_POST['estudianteFechaNacimiento']));
                        }

                        if ($cedulaMadre && $anioNacimiento) {
                            // Generar la cédula compuesta
                            $nacionalidadEst = isset($_POST['estudianteNacionalidad']) ? (int)$_POST['estudianteNacionalidad'] : null;
                            $nuevaCedula = generarCedulaEscolar($conexion, $cedulaMadre, $anioNacimiento, $nacionalidadEst);
                            if ($nuevaCedula) {
                                // Actualizar persona con la cédula generada
                                $stmtUpd = $conexion->prepare("UPDATE persona SET cedula = :cedula WHERE IdPersona = :id");
                                $stmtUpd->execute([':cedula' => $nuevaCedula, ':id' => $idEstudiante]);
                                // También actualizar variable para que otras validaciones lo usen
                                $personaEstudiante->cedula = $nuevaCedula;
                            }
                        }
                    }

                    // Insertar perfil de estudiante (solo si no lo tiene ya)
                    if (!DetallePerfil::tienePerfil($conexion, $idEstudiante, 3)) {
                        $detallePerfilEstudiante = new DetallePerfil($conexion);
                        $detallePerfilEstudiante->IdPersona = $idEstudiante;
                        $detallePerfilEstudiante->IdPerfil = 3;
                        $detallePerfilEstudiante->guardar();
                    }
                }

                // ======== TELEFONO DEL ESTUDIANTE (CELULAR) =========
                // Usar el modelo Telefono que ya verifica duplicados.
                if (!empty(trim($_POST['estudianteTelefono'] ?? ''))) {
                    $telefonoEstudiante = new Telefono($conexion);
                    $telefonoEstudiante->IdPersona = $idEstudiante;
                    $telefonoEstudiante->IdTipo_Telefono = 2; // Celular
                    $telefonoEstudiante->numero_telefono = $_POST['estudianteTelefono'];
                    $telefonoEstudiante->IdPrefijo = obtenerOCrearPrefijo(
                        $conexion,
                        $_POST['estudianteTelefonoPrefijo'] ?? null,
                        $_POST['estudianteTelefonoPrefijo_nombre'] ?? ''
                    );
                    try {
                        $telefonoEstudiante->guardar();
                    } catch (Exception $e) {
                        // Si el teléfono ya existe para otra persona, esto debe ser crítico: rollback
                        throw $e;
                    }
                }
                
                // Guardar discapacidades si existen
                if (!empty($_POST['tipo_discapacidad']) && is_array($_POST['tipo_discapacidad'])) {
                    foreach ($_POST['tipo_discapacidad'] as $index => $tipo) {
                        if (empty($tipo)) continue;

                        $descripcion = $_POST['descripcion_discapacidad'][$index] ?? '';

                        $discapacidad = new Discapacidad($conexion);
                        $discapacidad->IdPersona = $idEstudiante;
                        $discapacidad->IdTipo_Discapacidad = (int)$tipo;
                        $discapacidad->discapacidad = trim($descripcion);
                        // Discapacidad::guardar ahora evita duplicados y retorna Id o false
                        $discapacidad->guardar();
                    }
                }
                // ========================================================
                // ======== PADRE =========================================
                // ========================================================
                if (!empty($_POST['padreNombres']) || !empty($_POST['padreApellidos'])) {
                    $personaPadre = new Persona($conexion);
                    $personaPadreExistente = $personaPadre->obtenerPorCedula($_POST['padreNacionalidad'], $_POST['padreCedula']);

                    if ($personaPadreExistente) {
                        $idPadre = $personaPadreExistente['IdPersona'];
                    } else {
                        $personaPadre->IdEstadoAcceso = 2;
                        $personaPadre->IdEstadoInstitucional = 2;
                        $personaPadre->IdNacionalidad = (int)$_POST['padreNacionalidad'];
                        $personaPadre->cedula = $_POST['padreCedula'];
                        $personaPadre->nombre = $_POST['padreNombres'];
                        $personaPadre->apellido = $_POST['padreApellidos'];
                        $personaPadre->correo = $_POST['padreCorreo'] ?? '';
                        $personaPadre->direccion = $_POST['padreDireccion'] ?? '';
                        $personaPadre->IdSexo = 1; // Masculino
                        $personaPadre->IdTipoTrabajador = isset($_POST['padreTipoTrabajador']) ? (int)$_POST['padreTipoTrabajador'] : null;
                        $personaPadre->IdUrbanismo = obtenerOCrearUrbanismo(
                            $conexion,
                            $_POST['padreUrbanismo'] ?? null,
                            $_POST['padreUrbanismo_nombre'] ?? ''
                        );
                        $idPadre = $personaPadre->guardar();
                    }

                    // Teléfonos del padre
                    Telefono::guardarTelefonosPersona($conexion, $idPadre, [
                        'TelefonoHabitacion' => $_POST['padreTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['padreCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['padreTelefonoTrabajo'] ?? ''
                    ], [
                        'TelefonoHabitacion' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['padreTelefonoHabitacionPrefijo'] ?? null,
                            $_POST['padreTelefonoHabitacionPrefijo_nombre'] ?? ''
                        ),
                        'Celular' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['padreCelularPrefijo'] ?? null,
                            $_POST['padreCelularPrefijo_nombre'] ?? ''
                        ),
                        'TelefonoTrabajo' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['padreTelefonoTrabajoPrefijo'] ?? null,
                            $_POST['padreTelefonoTrabajoPrefijo_nombre'] ?? ''
                        )
                    ]);

                    // Relación padre-estudiante
                    $representantePadre = new Representante($conexion);
                    $representantePadre->IdPersona = $idPadre;
                    $representantePadre->IdParentesco = 1; // Padre
                    $representantePadre->IdEstudiante = $idEstudiante;
                    $representantePadre->ocupacion = trim($_POST['padreOcupacion'] ?? '');
                    $representantePadre->lugar_trabajo = trim($_POST['padreLugarTrabajo'] ?? '');
                    $idRepresentantePadre = $representantePadre->guardar();

                    // Perfil de representante (IdPerfil = 4)
                    if (!DetallePerfil::tienePerfil($conexion, $idPadre, 4)) {
                        $detallePerfil = new DetallePerfil($conexion);
                        $detallePerfil->IdPerfil = 4;
                        $detallePerfil->IdPersona = $idPadre;
                        $detallePerfil->guardar();
                    }
                }

                // ========================================================
                // ======== MADRE =========================================
                // ========================================================
                if (!empty($_POST['madreNombres']) || !empty($_POST['madreApellidos'])) {
                    $personaMadre = new Persona($conexion);
                    $personaMadreExistente = $personaMadre->obtenerPorCedula($_POST['madreNacionalidad'], $_POST['madreCedula']);

                    if ($personaMadreExistente) {
                        $idMadre = $personaMadreExistente['IdPersona'];
                    } else {
                        $personaMadre->IdEstadoAcceso = 2;
                        $personaMadre->IdEstadoInstitucional = 2;
                        $personaMadre->IdNacionalidad = (int)$_POST['madreNacionalidad'];
                        $personaMadre->cedula = $_POST['madreCedula'];
                        $personaMadre->nombre = $_POST['madreNombres'];
                        $personaMadre->apellido = $_POST['madreApellidos'];
                        $personaMadre->correo = $_POST['madreCorreo'] ?? '';
                        $personaMadre->direccion = $_POST['madreDireccion'] ?? '';
                        $personaMadre->IdSexo = 2; // Femenino
                        $personaMadre->IdTipoTrabajador = isset($_POST['madreTipoTrabajador']) ? (int)$_POST['madreTipoTrabajador'] : null;
                        $personaMadre->IdUrbanismo = obtenerOCrearUrbanismo(
                            $conexion,
                            $_POST['madreUrbanismo'] ?? null,
                            $_POST['madreUrbanismo_nombre'] ?? ''
                        );
                        $idMadre = $personaMadre->guardar();
                    }

                    // Teléfonos de la madre
                    Telefono::guardarTelefonosPersona($conexion, $idMadre, [
                        'TelefonoHabitacion' => $_POST['madreTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['madreCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['madreTelefonoTrabajo'] ?? ''
                    ], [
                        'TelefonoHabitacion' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['madreTelefonoHabitacionPrefijo'] ?? null,
                            $_POST['madreTelefonoHabitacionPrefijo_nombre'] ?? ''
                        ),
                        'Celular' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['madreCelularPrefijo'] ?? null,
                            $_POST['madreCelularPrefijo_nombre'] ?? ''
                        ),
                        'TelefonoTrabajo' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['madreTelefonoTrabajoPrefijo'] ?? null,
                            $_POST['madreTelefonoTrabajoPrefijo_nombre'] ?? ''
                        )
                    ]);

                    // Relación madre-estudiante
                    $representanteMadre = new Representante($conexion);
                    $representanteMadre->IdPersona = $idMadre;
                    $representanteMadre->IdParentesco = 2; // Madre
                    $representanteMadre->IdEstudiante = $idEstudiante;
                    $representanteMadre->ocupacion = trim($_POST['madreOcupacion'] ?? '');
                    $representanteMadre->lugar_trabajo = trim($_POST['madreLugarTrabajo'] ?? '');
                    $idRepresentanteMadre = $representanteMadre->guardar();

                    // Perfil de representante (IdPerfil = 4)
                    if (!DetallePerfil::tienePerfil($conexion, $idMadre, 4)) {
                        $detallePerfil = new DetallePerfil($conexion);
                        $detallePerfil->IdPerfil = 4;
                        $detallePerfil->IdPersona = $idMadre;
                        $detallePerfil->guardar();
                    }
                }
                
                $tipoRepresentante = $_POST['tipoRepresentante'];
                $idRelacionRepresentante = null;

                if ($tipoRepresentante === 'padre') {
                    if (empty($idPadre)) {
                        throw new Exception("Debe proporcionar datos del padre cuando se selecciona como representante");
                    }
                    $idRelacionRepresentante = $idRepresentantePadre;
                } 
                elseif ($tipoRepresentante === 'madre') {
                    if (empty($idMadre)) {
                        throw new Exception("Debe proporcionar datos de la madre cuando se selecciona como representante");
                    }
                    $idRelacionRepresentante = $idRepresentanteMadre;
                } 
                elseif ($tipoRepresentante === 'otro') {
                    // Validar campos requeridos para representante legal
                    $camposRequeridos = [
                        'representanteNombres' => 'Nombres del representante legal',
                        'representanteApellidos' => 'Apellidos del representante legal',
                        'representanteCedula' => 'Cédula del representante legal',
                        'representanteNacionalidad' => 'Nacionalidad del representante legal',
                        'representanteParentesco' => 'Parentesco del representante legal',
                        'representanteOcupacion' => 'Ocupación del representante legal',
                        'representanteUrbanismo' => 'Urbanismo/Sector del representante legal',
                        'representanteDireccion' => 'Dirección del representante legal',
                        'representanteTelefonoHabitacion' => 'Teléfono de habitación del representante legal',
                        'representanteCelular' => 'Celular del representante legal',
                        'representanteCorreo' => 'Correo electrónico del representante legal',
                        'representanteLugarTrabajo' => 'Lugar de trabajo del representante legal'
                    ];
                    
                    foreach ($camposRequeridos as $campo => $nombre) {
                        if (empty($_POST[$campo])) {
                            throw new Exception("El campo $nombre es requerido para representante legal");
                        }
                    }

                    $personaRep = new Persona($conexion);
                    $personaRepExistente = $personaRep->obtenerPorCedula($_POST['representanteNacionalidad'], $_POST['representanteCedula']);

                    if ($personaRepExistente) {
                        $idRepresentante = $personaRepExistente['IdPersona'];
                    } else {
                        $personaRep->IdEstadoAcceso = 2;
                        $personaRep->IdEstadoInstitucional = 2;
                        $personaRep->IdNacionalidad = (int)$_POST['representanteNacionalidad'];
                        $personaRep->cedula = $_POST['representanteCedula'];
                        $personaRep->nombre = $_POST['representanteNombres'];
                        $personaRep->apellido = $_POST['representanteApellidos'];
                        $personaRep->correo = $_POST['representanteCorreo'] ?? '';
                        $personaRep->direccion = $_POST['representanteDireccion'] ?? '';
                        $personaRep->IdSexo = null; // puede venir del form si lo necesitas
                        $personaRep->IdTipoTrabajador = isset($_POST['representanteTipoTrabajador']) ? (int)$_POST['representanteTipoTrabajador'] : null;
                        $personaRep->IdUrbanismo = obtenerOCrearUrbanismo(
                            $conexion,
                            $_POST['representanteUrbanismo'] ?? null,
                            $_POST['representanteUrbanismo_nombre'] ?? ''
                        );
                        $idRepresentante = $personaRep->guardar();
                    }

                    // Teléfonos del representante
                    Telefono::guardarTelefonosPersona($conexion, $idRepresentante, [
                        'TelefonoHabitacion' => $_POST['representanteTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['representanteCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['representanteTelefonoTrabajo'] ?? ''
                    ], [
                        'TelefonoHabitacion' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['representanteTelefonoHabitacionPrefijo'] ?? null,
                            $_POST['representanteTelefonoHabitacionPrefijo_nombre'] ?? ''
                        ),
                        'Celular' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['representanteCelularPrefijo'] ?? null,
                            $_POST['representanteCelularPrefijo_nombre'] ?? ''
                        ),
                        'TelefonoTrabajo' => obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['representanteTelefonoTrabajoPrefijo'] ?? null,
                            $_POST['representanteTelefonoTrabajoPrefijo_nombre'] ?? ''
                        )
                    ]);

                    // Relación representante-estudiante
                    $representante = new Representante($conexion);
                    $representante->IdPersona = $idRepresentante;
                    // Obtener o crear el parentesco
                    $representante->IdParentesco = obtenerOCrearParentesco(
                        $conexion,
                        $_POST['representanteParentesco'] ?? null,
                        $_POST['representanteParentesco_nombre'] ?? ''
                    );
                    $representante->IdEstudiante = $idEstudiante;
                    $representante->ocupacion = trim($_POST['representanteOcupacion'] ?? '');
                    $representante->lugar_trabajo = trim($_POST['representanteLugarTrabajo'] ?? '');
                    $idRelacionRepresentante = $representante->guardar();

                    // Perfil de representante (IdPerfil = 4)
                    if (!DetallePerfil::tienePerfil($conexion, $idRepresentante, 4)) {
                        $detallePerfil = new DetallePerfil($conexion);
                        $detallePerfil->IdPerfil = 4;
                        $detallePerfil->IdPersona = $idRepresentante;
                        $detallePerfil->guardar();
                    }
                } 
                else {
                    throw new Exception("Tipo de representante no válido");
                }

                if (!empty($_POST['emergenciaNombre'])) {
                    try {
                        $emergencia = new Representante($conexion);
                        // Obtener o crear el parentesco de emergencia
                        $emergencia->IdParentesco = obtenerOCrearParentesco(
                            $conexion,
                            $_POST['emergenciaParentesco'] ?? null,
                            $_POST['emergenciaParentesco_nombre'] ?? ''
                        );
                        $emergencia->IdEstudiante = $idEstudiante;
                        $emergencia->nombre_contacto = trim($_POST['emergenciaNombre']);
                        $emergencia->telefono_contacto = trim($_POST['emergenciaCelular']);
                        $emergencia->IdPrefijo = obtenerOCrearPrefijo(
                            $conexion,
                            $_POST['emergenciaCelularPrefijo'] ?? null,
                            $_POST['emergenciaCelularPrefijo_nombre'] ?? ''
                        );
                        
                        // Validación adicional para el nombre de emergencia
                        if (str_word_count($emergencia->nombre_contacto) < 2) {
                            throw new Exception("Debe ingresar nombre y apellido para el contacto de emergencia");
                        }
                        
                        if (empty($emergencia->telefono_contacto)) {
                            throw new Exception("Debe ingresar un teléfono para el contacto de emergencia");
                        }

                        if (!$emergencia->guardarContactoEmergencia()) {
                            throw new Exception("Error al guardar el contacto de emergencia");
                        }

                        // Asignar perfil de contacto de emergencia (IdPerfil = 5)
                        $detallePerfilEmergencia = new DetallePerfil($conexion);
                        $detallePerfilEmergencia->IdPerfil = 5; // Contacto de Emergencia
                        $detallePerfilEmergencia->IdPersona = $emergencia->IdPersona;
                        if (!$detallePerfilEmergencia->guardar()) {
                            throw new Exception("Error al asignar perfil de contacto de emergencia");
                        }
                    } catch (Exception $e) {
                        throw $e;
                    }
                }
                
                // ========================================================
                // === OBTENER CURSO_SECCION ==============================
                // ========================================================
                $cursoSeccionModel = new CursoSeccion($conexion);
                if (!empty($_POST['idSeccion'])) {
                    $cursoSeccion = $cursoSeccionModel->obtenerPorCursoYSeccion($_POST['idCurso'], (int)$_POST['idSeccion']);
                } else {
                    // Si no viene, usar la sección 1 por defecto (inscripción inicial)
                    $cursoSeccion = $cursoSeccionModel->obtenerPorCursoYSeccion($_POST['idCurso'], 1);
                }
                
                if (!$cursoSeccion) {
                    throw new Exception("No se encontró una sección de inscripción para el curso seleccionado");
                }
                
                // Generar código de inscripción
                $anioActual = date('Y');
                $sql = "SELECT COUNT(*) FROM inscripcion WHERE YEAR(fecha_inscripcion) = :anio";
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':anio', $anioActual, PDO::PARAM_INT);
                $stmt->execute();
                $correlativo = $stmt->fetchColumn() + 1;
                $codigo_inscripcion = "$anioActual-$correlativo";

                // ========================================================
                // === VALIDACIONES DE INSCRIPCIÓN ========================
                // ========================================================

                // Obtener año escolar activo
                $modeloFechaEscolar = new FechaEscolar($conexion);
                $anioEscolar = $modeloFechaEscolar->obtenerActivo();

                if (!$anioEscolar) {
                    throw new Exception("No se encontró un año escolar activo");
                }

                // Validar si el año escolar permite inscripciones
                if (isset($anioEscolar['inscripcion_activa']) && (int)$anioEscolar['inscripcion_activa'] !== 1) {
                    throw new Exception("Ya no se aceptan inscripciones o no hay cupos disponibles.");
                }

                // Verificar si el estudiante ya tiene inscripción en este año escolar
                $sqlDup = "SELECT COUNT(*) 
                        FROM inscripcion 
                        WHERE IdEstudiante = :idEstudiante 
                        AND IdFecha_Escolar = :idFechaEscolar";
                $stmtDup = $conexion->prepare($sqlDup);
                $stmtDup->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
                $stmtDup->bindParam(':idFechaEscolar', $anioEscolar['IdFecha_Escolar'], PDO::PARAM_INT);
                $stmtDup->execute();

                if ($stmtDup->fetchColumn() > 0) {
                    throw new Exception("El estudiante ya posee una inscripción en este año escolar.");
                }

                // Obtener tipo de inscripción (por defecto 1 = Nuevo Ingreso si no se especifica)
                $idTipo_Inscripcion = isset($_POST['idTipoInscripcion']) ? (int)$_POST['idTipoInscripcion'] : 1;

                // Crear inscripción
                $inscripcion = new Inscripcion($conexion);

                $inscripcion->IdEstudiante = $idEstudiante;
                $inscripcion->IdTipo_Inscripcion = $idTipo_Inscripcion;
                $now = new DateTime('now', new DateTimeZone('America/Caracas')); // Ajusta la zona horaria
                $inscripcion->fecha_inscripcion = $now->format('Y-m-d H:i:s');

                // Para el primer curso, buscar el ID del plantel "U.E.C Fermín Toro"
                if ($esPrimerCurso) {
                    // Buscar el ID del plantel Fermín Toro
                    $stmtPlantel = $conexion->prepare("SELECT IdPlantel FROM plantel WHERE plantel LIKE '%Fermín Toro%' LIMIT 1");
                    $stmtPlantel->execute();
                    $plantelFerminToro = $stmtPlantel->fetch(PDO::FETCH_ASSOC);

                    if ($plantelFerminToro) {
                        $inscripcion->ultimo_plantel = $plantelFerminToro['IdPlantel'];
                    } else {
                        // Si no existe, crearlo
                        require_once __DIR__ . '/../modelos/Plantel.php';
                        $modeloPlantel = new Plantel($conexion);
                        $modeloPlantel->plantel = 'U.E.C "Fermín Toro"';
                        if ($modeloPlantel->insertar()) {
                            $inscripcion->ultimo_plantel = $modeloPlantel->IdPlantel;
                        } else {
                            $inscripcion->ultimo_plantel = null;
                        }
                    }
                } else {
                    // Usar el ID del plantel seleccionado (estudiantePlantel contiene el ID)
                    $inscripcion->ultimo_plantel = !empty($_POST['estudiantePlantel']) ? $_POST['estudiantePlantel'] : null;
                }

                $inscripcion->responsable_inscripcion = $idRelacionRepresentante;
                $inscripcion->IdFecha_Escolar = $anioEscolar['IdFecha_Escolar'];
                $inscripcion->IdCurso_Seccion = $cursoSeccion['IdCurso_Seccion'];
                $inscripcion->codigo_inscripcion = $codigo_inscripcion;

                // ========================================================
                // === OBTENER STATUS DE INSCRIPCIÓN ======================
                // ========================================================

                if (!empty($_POST['idStatus'])) {
                    // Si se envía explícitamente el IdStatus, usar ese
                    $inscripcion->IdStatus = (int)$_POST['idStatus'];
                } else {
                    // Caso contrario, usar el primer estado de tipo 'inscripción'
                    $sqlStatus = "SELECT IdStatus FROM status WHERE IdTipo_Status = 2 ORDER BY IdStatus LIMIT 1";
                    $stmtStatus = $conexion->prepare($sqlStatus);
                    $stmtStatus->execute();
                    $statusInscripcion = $stmtStatus->fetch(PDO::FETCH_ASSOC);

                    if (!$statusInscripcion) {
                        throw new Exception("No se encontró un estado válido para la inscripción");
                    }

                    $inscripcion->IdStatus = $statusInscripcion['IdStatus']; // Estado por defecto
                }
                
                $numeroSolicitud = $inscripcion->guardar();
                if (!$numeroSolicitud) {
                    throw new Exception("Error al crear la inscripción");
                }

                $conexion->commit();

                // Respuesta exitosa
                echo json_encode([
                    'success' => true,
                    'numeroSolicitud' => $numeroSolicitud,
                    'codigo_inscripcion' => $codigo_inscripcion,
                    'message' => 'Solicitud registrada correctamente'
                ]);

                try {
                    $idUsuario = obtenerIdUsuario(); // desde sesión o helper
                    if (!$idUsuario) {
                        $idUsuario = null;
                    }

                    // ✅ Usa el mismo IdStatus que acaba de asignarse arriba (ya sea el enviado o el por defecto)
                    activarInscripcionCompleta($conexion, $numeroSolicitud, $inscripcion->IdStatus, $idUsuario);

                } catch (Exception $e) {
                    error_log("Error al activar status inicial: " . $e->getMessage());
                    // No se lanza al cliente, pero se registra en logs
                }
                
            } catch (Exception $e) {
                if ($conexion->inTransaction()) {
                    $conexion->rollback();
                }
                throw $e;
            }

        } else {
            throw new Exception("Método no permitido");
        }
    } catch (Exception $e) {
        error_log("Error en InscripcionController: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function cambiarStatus($conexion) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit();
    }

    $idInscripcion = $_POST['idInscripcion'] ?? 0;
    $nuevoStatus = $_POST['nuevoStatus'] ?? 0;

    if ($idInscripcion <= 0 || $nuevoStatus <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    try {
        $idUsuario = obtenerIdUsuario();
        if (!$idUsuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit();
        }

        $resultado = activarInscripcionCompleta($conexion, $idInscripcion, $nuevoStatus, $idUsuario);
        echo json_encode($resultado);
        exit();

    } catch (Exception $e) {
        error_log("Error al cambiar status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}

function activarInscripcionCompleta($conexion, $idInscripcion, $nuevoStatus, $idUsuario = null) {
    try {
        // Obtener datos de inscripción
        $queryInscripcion = "SELECT i.*, e.IdUrbanismo, cs.IdCurso, i.IdEstudiante, i.IdStatus as status_actual
                            FROM inscripcion i
                            INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
                            INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                            WHERE i.IdInscripcion = :idInscripcion";
        $stmt = $conexion->prepare($queryInscripcion);
        $stmt->execute([':idInscripcion' => $idInscripcion]);
        $inscripcionData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inscripcionData) {
            throw new Exception('Inscripción no encontrada');
        }

        $estadoAnterior = $inscripcionData['status_actual'];
        $alertaCapacidad = '';
        $mensajesEnviados = false;
        $cambioRealizado = false;
        $seccionNueva = null;

        // ======================================================
        // === ACTIVAR ESTUDIANTE Y REPRESENTANTES ===============
        // ======================================================
        if ($nuevoStatus == 11) { // 11 = inscrito
            $idEstudiante = $inscripcionData['IdEstudiante'];

            // Activar estudiante
            $conexion->prepare("UPDATE persona 
                SET IdEstadoAcceso = 1, IdEstadoInstitucional = 1 
                WHERE IdPersona = :id")
                ->execute([':id' => $idEstudiante]);

            // Activar representantes
            $stmtRep = $conexion->prepare("
                SELECT r.IdPersona, p.cedula,
                    CASE WHEN dp.IdPerfil = 5 THEN 1 ELSE 0 END AS es_contacto_emergencia
                FROM representante r
                INNER JOIN persona p ON r.IdPersona = p.IdPersona
                LEFT JOIN detalle_perfil dp ON r.IdPersona = dp.IdPersona AND dp.IdPerfil = 5
                WHERE r.IdEstudiante = :idEstudiante
            ");
            $stmtRep->execute([':idEstudiante' => $idEstudiante]);
            $representantes = $stmtRep->fetchAll(PDO::FETCH_ASSOC);

            foreach ($representantes as $rep) {
                $idPersona = $rep['IdPersona'];
                $cedula = trim($rep['cedula']);
                $esEmergencia = (int)$rep['es_contacto_emergencia'];

                // Activar persona
                $conexion->prepare("UPDATE persona 
                    SET IdEstadoAcceso = 1, IdEstadoInstitucional = 1 
                    WHERE IdPersona = :id")
                    ->execute([':id' => $idPersona]);

                // Crear credenciales si no existen
                if ($idPersona != $idEstudiante && !$esEmergencia) {
                    $persona = new Persona($conexion);
                    $persona->IdPersona = $idPersona;
                    $credenciales = $persona->obtenerCredenciales();

                    if (empty($credenciales['usuario']) || empty($credenciales['password'])) {
                        $persona->actualizarCredenciales($cedula, $cedula);
                    }
                }
            }

            // ======================================================
            // === CHEQUEAR CAPACIDAD DE AULAS ======================
            // ======================================================
            $aulas = verificarCapacidadAulas($conexion, $inscripcionData['IdCurso']);
            $todasAulasLlenas = true;

            foreach ($aulas as $aula) {
                if ((int)$aula['tiene_cupo'] === 1) {
                    $todasAulasLlenas = false;
                    break;
                }
            }

            if ($todasAulasLlenas) {
                $alertaCapacidad = 'Todas las aulas han alcanzado su capacidad máxima.';
            }

            // ======================================================
            // === CAMBIO AUTOMÁTICO DE SECCIÓN =====================
            // ======================================================
            $seccionRecomendada = obtenerSeccionRecomendada(
                $conexion,
                $inscripcionData['IdCurso'],
                $inscripcionData['IdUrbanismo'],
                $inscripcionData['IdCurso_Seccion']
            );

            if ($seccionRecomendada && $seccionRecomendada != $inscripcionData['IdCurso_Seccion']) {
                $conexion->prepare("UPDATE inscripcion 
                    SET IdCurso_Seccion = :nuevaSeccion 
                    WHERE IdInscripcion = :id")
                    ->execute([':nuevaSeccion' => $seccionRecomendada, ':id' => $idInscripcion]);
                $cambioRealizado = true;
                $seccionNueva = $seccionRecomendada;
            }
        }

        // ======================================================
        // === ACTUALIZAR STATUS PRINCIPAL =======================
        // ======================================================
        $conexion->prepare("UPDATE inscripcion SET IdStatus = :status WHERE IdInscripcion = :id")
            ->execute([':status' => $nuevoStatus, ':id' => $idInscripcion]);

        // Auditoría
        if ($idUsuario) {
            actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);
        }

        // ======================================================
        // === ENVIAR WHATSAPP ==================================
        // ======================================================
        try {
            require_once __DIR__ . '/WhatsAppController.php';
            $whatsappController = new WhatsAppController($conexion);
            $whatsappController->enviarMensajesCambioEstado($idInscripcion, $nuevoStatus, $estadoAnterior);
            $mensajesEnviados = true;
        } catch (Exception $e) {
            error_log("Error enviando WhatsApp (no crítico): " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'cambioAutomatico' => $cambioRealizado,
            'seccionNueva' => $seccionNueva,
            'alertaCapacidad' => $alertaCapacidad,
            'whatsappEnviado' => $mensajesEnviados
        ];
    } catch (Exception $e) {
        error_log("Error en activarInscripcionCompleta: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}


function toggleRequisito($conexion) {
    $idInscripcion = intval($_POST['idInscripcion'] ?? 0);
    $idRequisito   = intval($_POST['idRequisito'] ?? 0);
    $cumplido      = intval($_POST['cumplido'] ?? 0);

    if ($idInscripcion <= 0 || $idRequisito <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    try {
        $idUsuario = obtenerIdUsuario();
        if (!$idUsuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        if ($cumplido === 1) {
            // Marcar requisito como cumplido → insertar o actualizar
            $stmt = $conexion->prepare("
                INSERT INTO inscripcion_requisito (IdInscripcion, IdRequisito, cumplido)
                VALUES (:idInscripcion, :idRequisito, 1)
                ON DUPLICATE KEY UPDATE cumplido = 1
            ");
            $stmt->execute([
                ':idInscripcion' => $idInscripcion,
                ':idRequisito'   => $idRequisito
            ]);

            $mensaje = 'Requisito marcado como cumplido';
        } else {
            // Desmarcar → eliminar el requisito
            $stmt = $conexion->prepare("
                DELETE FROM inscripcion_requisito
                WHERE IdInscripcion = :idInscripcion AND IdRequisito = :idRequisito
            ");
            $stmt->execute([
                ':idInscripcion' => $idInscripcion,
                ':idRequisito'   => $idRequisito
            ]);

            $mensaje = 'Requisito desmarcado y eliminado';
        }

        // Actualizar auditoría
        actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);

        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'idRequisito' => $idRequisito,
            'cumplido'    => $cumplido
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar: ' . $e->getMessage()
        ]);
    }

    exit;
}


function actualizarMultiplesRequisitos($conexion) {
    $idInscripcion = intval($_POST['idInscripcion'] ?? 0);
    $requisitos = $_POST['requisitos'] ?? [];

    if ($idInscripcion <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de inscripción inválido'
        ]);
        exit();
    }

    try {
        $idUsuario = obtenerIdUsuario();
        if (!$idUsuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit();
        }
        // Limpio los requisitos anteriores
        $stmtDelete = $conexion->prepare("DELETE FROM inscripcion_requisito WHERE IdInscripcion = :idInscripcion");
        $stmtDelete->execute([ ':idInscripcion' => $idInscripcion ]);

        // Inserto los seleccionados
        if (!empty($requisitos)) {
            $stmtInsert = $conexion->prepare("
                INSERT INTO inscripcion_requisito (IdInscripcion, IdRequisito, cumplido) 
                VALUES (:idInscripcion, :idRequisito, :cumplido)
            ");
            foreach ($requisitos as $idRequisito => $cumplido) {
                $stmtInsert->execute([
                    ':idInscripcion' => $idInscripcion,
                    ':idRequisito'   => $idRequisito,
                    ':cumplido'      => intval($cumplido)
                ]);
            }
        }

        // Actualizar campos de auditoría
        actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);

        echo json_encode([
            'success' => true,
            'message' => 'Requisitos actualizados correctamente',
            'idInscripcion' => $idInscripcion,
            'total' => count($requisitos)
        ]);
    } catch (Exception $e) {
        error_log("Error actualizarMultiplesRequisitos: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
}

function cambiarSeccion($conexion) {
    $idInscripcion = intval($_POST['idInscripcion'] ?? 0);
    $nuevaSeccion = intval($_POST['nuevaSeccion'] ?? 0);

    if ($idInscripcion <= 0 || $nuevaSeccion <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    try {
        $idUsuario = obtenerIdUsuario();
        if (!$idUsuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit();
        }

        // Verificar que la inscripción esté en estado "Inscrito"
        $queryVerificar = "SELECT IdStatus FROM inscripcion WHERE IdInscripcion = :idInscripcion";
        $stmtVerificar = $conexion->prepare($queryVerificar);
        $stmtVerificar->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmtVerificar->execute();
        $inscripcion = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

        if (!$inscripcion || $inscripcion['IdStatus'] != 11) { // 11 = Inscrito
            echo json_encode(['success' => false, 'message' => 'Solo se puede cambiar sección en estado "Inscrito"']);
            exit();
        }

        // Actualizar la sección
        $query = "UPDATE inscripcion SET IdCurso_Seccion = :nuevaSeccion WHERE IdInscripcion = :idInscripcion";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nuevaSeccion', $nuevaSeccion, PDO::PARAM_INT);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Actualizar auditoría
            actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sección actualizada correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar sección']);
        }
    } catch (Exception $e) {
        error_log("Error al cambiar sección: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}

function hayCupo($conexion) {
    $idCurso = intval($_POST['idCurso'] ?? 0);
    if ($idCurso <= 0) {
        echo json_encode(['success' => false, 'message' => 'Curso inválido']); exit();
    }

    try {
        $aulas = verificarCapacidadAulas($conexion, $idCurso);
        $todasAulasLlenas = !empty($aulas); // importante: mismo fix que arriba
        foreach ($aulas as $a) {
            if ((int)$a['tiene_cupo'] === 1) { $todasAulasLlenas = false; break; }
        }
        echo json_encode([
            'success' => true,
            'todasLlenas' => $todasAulasLlenas,
            'totalSecciones' => count($aulas)
        ]);
    } catch (Exception $e) {
        error_log("Error hayCupo: ".$e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
}
