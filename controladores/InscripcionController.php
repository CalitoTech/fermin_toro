<?php
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
    header('Content-Type: application/json');
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
            header('Content-Type: application/json');
            $anio = intval($_GET['anio'] ?? 0);
            $cedula = trim($_GET['cedula'] ?? '');
            $nacionalidad = trim($_GET['nacionalidad'] ?? '');

            $existe = verificarInscripcion($conexion, $anio, $cedula, $nacionalidad);
            echo json_encode(['existe' => $existe]);
            exit;
        default:
            header('Content-Type: application/json');
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
            
            // 1. Validación de campos del estudiante (siempre requeridos)
            $camposEstudiante = [
                'estudianteNombres' => 'Nombres del estudiante',
                'estudianteApellidos' => 'Apellidos del estudiante',
                'estudianteCedula' => 'Cédula del estudiante',
                'estudianteFechaNacimiento' => 'Fecha de nacimiento del estudiante',
                'estudianteLugarNacimiento' => 'Lugar de nacimiento del estudiante',
                'estudianteTelefono' => 'Teléfono del estudiante',
                'estudianteCorreo' => 'Correo electrónico del estudiante'
            ];
            
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
                    $personaEstudiante->IdUrbanismo = match($_POST['tipoRepresentante']) {
                        'padre' => $_POST['padreUrbanismo'] ?? null,
                        'madre' => $_POST['madreUrbanismo'] ?? null,
                        'otro' => $_POST['representanteUrbanismo'] ?? null,
                        default => null
                    };

                    $personaEstudiante->direccion = match($_POST['tipoRepresentante']) {
                        'padre' => $_POST['padreDireccion'] ?? null,
                        'madre' => $_POST['madreDireccion'] ?? null,
                        'otro' => $_POST['representanteDireccion'] ?? null,
                        default => null
                    };

                    $idEstudiante = $personaEstudiante->guardar();

                    // Insertar perfil de estudiante (solo si no lo tiene ya)
                    if (!DetallePerfil::tienePerfil($conexion, $idEstudiante, 3)) {
                        $detallePerfilEstudiante = new DetallePerfil($conexion);
                        $detallePerfilEstudiante->IdPersona = $idEstudiante;
                        $detallePerfilEstudiante->IdPerfil = 3;
                        $detallePerfilEstudiante->guardar();
                    }
                }

                // ======== TELEFONO DEL ESTUDIANTE (CELULAR) =========
                if (!empty($_POST['estudianteTelefono'])) {
                    $telefonoEstudiante = new Telefono($conexion);
                    $telefonoEstudiante->IdPersona = $idEstudiante;
                    $telefonoEstudiante->IdTipo_Telefono = 2; // Celular
                    $telefonoEstudiante->numero_telefono = $_POST['estudianteTelefono'];
                    $telefonoEstudiante->guardar();
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
                        $personaPadre->IdUrbanismo = $_POST['padreUrbanismo'];
                        $idPadre = $personaPadre->guardar();
                    }

                    // Teléfonos del padre
                    Telefono::guardarTelefonosPersona($conexion, $idPadre, [
                        'TelefonoHabitacion' => $_POST['padreTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['padreCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['padreTelefonoTrabajo'] ?? ''
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
                        $personaMadre->IdUrbanismo = $_POST['madreUrbanismo'];
                        $idMadre = $personaMadre->guardar();
                    }

                    // Teléfonos de la madre
                    Telefono::guardarTelefonosPersona($conexion, $idMadre, [
                        'TelefonoHabitacion' => $_POST['madreTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['madreCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['madreTelefonoTrabajo'] ?? ''
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
                        $personaRep->IdUrbanismo = $_POST['representanteUrbanismo'];
                        $idRepresentante = $personaRep->guardar();
                    }

                    // Teléfonos del representante
                    Telefono::guardarTelefonosPersona($conexion, $idRepresentante, [
                        'TelefonoHabitacion' => $_POST['representanteTelefonoHabitacion'] ?? '',
                        'Celular' => $_POST['representanteCelular'] ?? '',
                        'TelefonoTrabajo' => $_POST['representanteTelefonoTrabajo'] ?? ''
                    ]);

                    // Relación representante-estudiante
                    $representante = new Representante($conexion);
                    $representante->IdPersona = $idRepresentante;
                    $representante->IdParentesco = 3; // Representante Legal
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
                        $emergencia->IdParentesco = $_POST['emergenciaParentesco'];
                        $emergencia->IdEstudiante = $idEstudiante;
                        $emergencia->nombre_contacto = trim($_POST['emergenciaNombre']);
                        $emergencia->telefono_contacto = trim($_POST['emergenciaCelular']);
                        
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
                
                // Obtener curso_seccion para la inscripción (IdSeccion = 1 para inscripción)
                $cursoSeccionModel = new CursoSeccion($conexion);
                $cursoSeccion = $cursoSeccionModel->obtenerPorCursoYSeccion($_POST['idCurso'], 1);
                
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
                
                // Obtener status de inscripción (IdTipo_Status = 2 para inscripciones)
                $sqlStatus = "SELECT IdStatus FROM status WHERE IdTipo_Status = 2 ORDER BY IdStatus LIMIT 1";
                $stmtStatus = $conexion->prepare($sqlStatus);
                $stmtStatus->execute();
                $statusInscripcion = $stmtStatus->fetch(PDO::FETCH_ASSOC);
                
                if (!$statusInscripcion) {
                    throw new Exception("No se encontró un estado válido para la inscripción");
                }

                // Crear inscripción
                $inscripcion = new Inscripcion($conexion);
                $inscripcion->IdEstudiante = $idEstudiante;
                $now = new DateTime('now', new DateTimeZone('America/Caracas')); // Ajusta la zona horaria
                $inscripcion->fecha_inscripcion = $now->format('Y-m-d H:i:s');
                $inscripcion->ultimo_plantel = $_POST['ultimoPlantel'] ?? '';
                $inscripcion->nro_hermanos = $_POST['nroHermanos'] ?? 0;
                $inscripcion->responsable_inscripcion = $idRelacionRepresentante;
                $inscripcion->IdFecha_Escolar = $anioEscolar['IdFecha_Escolar'];
                $inscripcion->IdStatus = $statusInscripcion['IdStatus']; // Primer estado de inscripción
                $inscripcion->IdCurso_Seccion = $cursoSeccion['IdCurso_Seccion'];
                $inscripcion->codigo_inscripcion = $codigo_inscripcion;
                
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

                // ========================================================
                // === ENVÍO DE MENSAJE POR WHATSAPP (NO BLOQUEANTE) =====
                // ========================================================
                try {
                    require_once __DIR__ . '/../controladores/WhatsAppController.php';
                    $whatsAppCtrl = new WhatsAppController($conexion);

                    // 👇 Estado inicial 7 = "Solicitud en Proceso"
                    $whatsAppCtrl->enviarMensajesCambioEstado($numeroSolicitud, 8);
                } catch (Exception $e) {
                    // Registrar error pero NO interrumpir el flujo
                    error_log("Error enviando WhatsApp en inscripción $numeroSolicitud: " . $e->getMessage());
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

        // Obtener datos actuales de la inscripción
        $queryInscripcion = "SELECT i.*, e.IdUrbanismo, cs.IdCurso, i.IdEstudiante, i.IdStatus as status_actual
                           FROM inscripcion i
                           INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
                           INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                           WHERE i.IdInscripcion = :idInscripcion";
        $stmtInscripcion = $conexion->prepare($queryInscripcion);
        $stmtInscripcion->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmtInscripcion->execute();
        $inscripcionData = $stmtInscripcion->fetch(PDO::FETCH_ASSOC);

        if (!$inscripcionData) {
            echo json_encode(['success' => false, 'message' => 'Inscripción no encontrada']);
            exit();
        }

        $estadoAnterior = $inscripcionData['status_actual'];

        // =========================================
        // BLOQUE EXTRA → activar estudiante/representantes
        // =========================================
        $alertaCapacidad = '';
        if ($nuevoStatus == 11) {
            $idEstudiante = $inscripcionData['IdEstudiante'];

            // 1) Activar estudiante
            $stmt = $conexion->prepare("UPDATE persona SET IdEstadoAcceso = 1, IdEstadoInstitucional = 1 WHERE IdPersona = :id");
            $stmt->execute([':id' => $idEstudiante]);

            // 2) Activar representantes y crear usuario/clave
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
                $cedula = $rep['cedula'];
                $esEmergencia = (int)$rep['es_contacto_emergencia'];

                // Activar representante
                $stmt = $conexion->prepare("UPDATE persona SET IdEstadoAcceso = 1, IdEstadoInstitucional = 1 WHERE IdPersona = :id");
                $stmt->execute([':id' => $idPersona]);

                // Crear credenciales solo si NO es el estudiante y NO es contacto de emergencia
                if ($idPersona != $idEstudiante && !$esEmergencia) {
                    $persona = new Persona($conexion);
                    $persona->IdPersona = $idPersona;
                    
                    $credenciales = $persona->obtenerCredenciales();

                    if (empty($credenciales['usuario']) || empty($credenciales['password'])) {
                        // Crear usuario = cédula, contraseña = cédula (hasheada)
                        $cedula = trim($cedula); // quita espacios al inicio y fin
                        $persona->actualizarCredenciales($cedula, $cedula);
                    }
                }
            }

            // =========================================
            // Chequear capacidad de aulas
            // =========================================
            $aulas = verificarCapacidadAulas($conexion, $inscripcionData['IdCurso']);
            $todasAulasLlenas = !empty($aulas);

            foreach ($aulas as $aula) {
                if ((int)$aula['tiene_cupo'] === 1) {
                    $todasAulasLlenas = false;
                    break;
                }
            }

            if ($todasAulasLlenas) {
                $alertaCapacidad = 'Todas las aulas han alcanzado su capacidad máxima. Se recomienda considerar un cambio de aula o remodelación del espacio antes de continuar las inscripciones.';
            }
        }

        // =========================================
        // Actualizar status de la inscripción
        // =========================================
        $query = "UPDATE inscripcion SET IdStatus = :status WHERE IdInscripcion = :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':status', $nuevoStatus, PDO::PARAM_INT);
        $stmt->bindParam(':id', $idInscripcion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Si el nuevo estado es "Inscrito" (IdStatus = 11), intentar cambio automático de sección
            $cambioRealizado = false;
            $seccionAnterior = $inscripcionData['IdCurso_Seccion'];
            $seccionNueva = null;

            if ($nuevoStatus == 11) {
                try {
                    $seccionRecomendada = obtenerSeccionRecomendada(
                        $conexion,
                        $inscripcionData['IdCurso'],
                        $inscripcionData['IdUrbanismo'],
                        $inscripcionData['IdCurso_Seccion']
                    );

                    if ($seccionRecomendada && $seccionRecomendada != $inscripcionData['IdCurso_Seccion']) {
                        $queryUpdateSeccion = "UPDATE inscripcion SET IdCurso_Seccion = :nuevaSeccion WHERE IdInscripcion = :idInscripcion";
                        $stmtUpdate = $conexion->prepare($queryUpdateSeccion);
                        $stmtUpdate->bindParam(':nuevaSeccion', $seccionRecomendada, PDO::PARAM_INT);
                        $stmtUpdate->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);

                        if ($stmtUpdate->execute()) {
                            $cambioRealizado = true;
                            $seccionNueva = $seccionRecomendada;
                            error_log("Cambio automático realizado: " . $inscripcionData['IdCurso_Seccion'] . " → " . $seccionRecomendada);
                        }
                    } else {
                        error_log("No se realizó cambio automático. Razón: " .
                            (!$seccionRecomendada ? "No hay sección recomendada" : "Ya está en la sección recomendada"));
                    }
                } catch (Exception $e) {
                    error_log("Error en cambio automático de sección: " . $e->getMessage());
                }
            }

            // Auditoría
            actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);

            // =========================================
            // ✅ NUEVO: Enviar mensajes de WhatsApp
            // =========================================
            $mensajesEnviados = false;
            try {
                require_once __DIR__ . '/WhatsAppController.php';
                $whatsappController = new WhatsAppController($conexion);
                $resultadoWhatsApp = $whatsappController->enviarMensajesCambioEstado(
                    $idInscripcion, 
                    $nuevoStatus, 
                    $estadoAnterior
                );
                
                if ($resultadoWhatsApp) {
                    $mensajesEnviados = true;
                    error_log("Mensajes de WhatsApp enviados exitosamente para inscripción ID: $idInscripcion");
                }
            } catch (Exception $e) {
                error_log("Error enviando WhatsApp (no crítico): " . $e->getMessage());
                // No fallar la operación principal por error en WhatsApp
            }

            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado correctamente' . ($mensajesEnviados ? ' y notificaciones enviadas' : ''),
                'nuevoStatus' => $nuevoStatus,
                'cambioAutomatico' => $cambioRealizado,
                'seccionAnterior' => $seccionAnterior,
                'seccionNueva' => $seccionNueva,
                'alertaCapacidad' => $alertaCapacidad,
                'whatsappEnviado' => $mensajesEnviados
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    } catch (Exception $e) {
        error_log("Error al cambiar status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}

function toggleRequisito($conexion) {
    // Fuerza salida en JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Limpia cualquier output previo
    ob_clean();

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
            exit();
        }
        $stmt = $conexion->prepare("
            REPLACE INTO inscripcion_requisito (IdInscripcion, IdRequisito, cumplido) 
            VALUES (:idInscripcion, :idRequisito, :cumplido)
        ");
        $stmt->execute([
            ':idInscripcion' => $idInscripcion,
            ':idRequisito'   => $idRequisito,
            ':cumplido'      => $cumplido
        ]);
        
        // Actualizar campos de auditoría
        actualizarAuditoriaInscripcion($conexion, $idInscripcion, $idUsuario);

        echo json_encode([
            'success' => true,
            'message' => 'Requisito actualizado',
            'idRequisito' => $idRequisito,
            'cumplido'    => $cumplido
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar: '.$e->getMessage()
        ]);
    }

    exit;
}


function actualizarMultiplesRequisitos($conexion) {
    header('Content-Type: application/json');

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
    header('Content-Type: application/json');

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
    header('Content-Type: application/json');
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
