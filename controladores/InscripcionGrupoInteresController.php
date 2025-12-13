<?php
session_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/InscripcionGrupoInteres.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/GrupoInteres.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Acciones que NO requieren verificación de sesión (API pública)
$accionesPublicas = ['obtener_ocupacion'];

// Verificación de sesión (excepto para acciones públicas)
if (!in_array($action, $accionesPublicas)) {
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
        manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
    }
}

switch ($action) {
    case 'crear':
        crearInscripcion();
        break;
    case 'editar':
        editarInscripcion();
        break;
    case 'eliminar':
        eliminarInscripcion();
        break;
    case 'procesar_inscripcion_representante':
        procesarInscripcionRepresentante();
        break;
    case 'obtener_ocupacion':
        obtenerOcupacion();
        break;
    default:
        manejarError('Acción no válida', '../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php');
}

function crearInscripcion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', '../vistas/inscripciones/inscripcion_grupo_interes/nuevo_inscripcion_gi.php');
    }

    $idEstudiante = trim($_POST['IdEstudiante'] ?? '');
    $idGrupo = trim($_POST['IdGrupo_Interes'] ?? '');

    if (empty($idEstudiante) || empty($idGrupo)) {
        manejarError('Estudiante y Grupo son obligatorios');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // 1. Obtener año escolar activo
        $fechaModel = new FechaEscolar($conexion);
        $fechaActiva = $fechaModel->obtenerActivo();

        if (!$fechaActiva) {
            manejarError('No hay año escolar activo configurado.');
        }

        // 2. Buscar inscripción académica del estudiante en el año activo
        //    Usamos una consulta directa o un método específico. 
        //    Dado que no hay un método exacto "obtenerInscripcionActiva($idEstudiante, $idFecha)",
        //    iteramos sobre las del estudiante o hacemos query ad-hoc en el mismo modelo Inscripcion si lo permitiera,
        //    pero como estamos en controlador, mejor instanciar Inscripcion y buscar.
        
        $inscripcionModel = new Inscripcion($conexion);
        // obtenerPorEstudiante devuelve todas ordenadas por fecha DESC. Buscamos la que coincida con FechaActiva.
        $inscripciones = $inscripcionModel->obtenerPorEstudiante($idEstudiante);
        
        $idInscripcionAcademica = null;
        foreach ($inscripciones as $insc) {
            if ($insc['IdFecha_Escolar'] == $fechaActiva['IdFecha_Escolar'] && $insc['status'] != 'Rechazada' && $insc['status'] != 'Inactivo') { // Asumiendo validación simple
                $idInscripcionAcademica = $insc['IdInscripcion'];
                break;
            }
        }

        if (!$idInscripcionAcademica) {
            manejarError('El estudiante no tiene una inscripción académica activa para el año escolar actual.', '../vistas/inscripciones/inscripcion_grupo_interes/nuevo_inscripcion_gi.php');
        }

        // 3. Verificar si ya está inscrito en ESTE grupo
        $inscripcionGrupoModel = new InscripcionGrupoInteres($conexion);
        if ($inscripcionGrupoModel->existeInscripcion($idEstudiante, $idGrupo)) {
            manejarError('El estudiante ya está inscrito en este grupo.', '../vistas/inscripciones/inscripcion_grupo_interes/nuevo_inscripcion_gi.php');
        }
        
        // 4. Verificar cupo (Opcional, pero recomendado)
        $grupoModel = new GrupoInteres($conexion);
        $grupoData = $grupoModel->obtenerPorId($idGrupo);
        // Para verificar capacidad real necesitaríamos contar inscritos en ese grupo.
        // Por ahora omitimos validación estricta de cupo numérico para no complicar, salvo que se pida expicitamente.

        // 5. Guardar
        $inscripcionGrupoModel->IdGrupo_Interes = $idGrupo;
        $inscripcionGrupoModel->IdEstudiante = $idEstudiante;
        $inscripcionGrupoModel->IdInscripcion = $idInscripcionAcademica;

        if ($inscripcionGrupoModel->guardar()) {
            $_SESSION['alert'] = 'success';
            $_SESSION['message'] = 'Estudiante inscrito en el grupo correctamente';
            header("Location: ../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php");
            exit();
        } else {
            throw new Exception("Error al guardar en base de datos");
        }

    } catch (Exception $e) {
        manejarError('Error: ' . $e->getMessage(), '../vistas/inscripciones/inscripcion_grupo_interes/nuevo_inscripcion_gi.php');
    }
}

function editarInscripcion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido');
    }

    $id = (int)($_POST['id'] ?? 0);
    $idGrupo = trim($_POST['IdGrupo_Interes'] ?? '');
    // Normalmente no cambiamos al estudiante en la edición, solo el grupo.
    
    if ($id <= 0 || empty($idGrupo)) {
        manejarError('Datos incompletos', "../vistas/inscripciones/inscripcion_grupo_interes/editar_inscripcion_gi.php?id=$id");
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        
        $inscripcionGrupoModel = new InscripcionGrupoInteres($conexion);
        $actual = $inscripcionGrupoModel->obtenerPorId($id);
        
        if (!$actual) {
            manejarError('Registro no encontrado');
        }

        // Validar si cambia de grupo, que no esté ya en ese grupo (si el estudiante fuera el mismo)
        if ($actual['IdGrupo_Interes'] != $idGrupo) {
             if ($inscripcionGrupoModel->existeInscripcion($actual['IdEstudiante'], $idGrupo)) {
                manejarError('El estudiante ya está inscrito en el grupo seleccionado.', "../vistas/inscripciones/inscripcion_grupo_interes/editar_inscripcion_gi.php?id=$id");
            }
        }

        $inscripcionGrupoModel->IdInscripcion_Grupo = $id;
        $inscripcionGrupoModel->IdGrupo_Interes = $idGrupo;
        $inscripcionGrupoModel->IdEstudiante = $actual['IdEstudiante'];
        $inscripcionGrupoModel->IdInscripcion = $actual['IdInscripcion'];

        if ($inscripcionGrupoModel->actualizar()) {
            $_SESSION['alert'] = 'actualizar';
            $_SESSION['message'] = 'Inscripción actualizada correctamente';
            header("Location: ../vistas/inscripciones/inscripcion_grupo_interes/editar_inscripcion_gi.php?id=$id");
            exit();
        } else {
            throw new Exception("Error al actualizar");
        }

    } catch (Exception $e) {
        manejarError('Error: ' . $e->getMessage(), "../vistas/inscripciones/inscripcion_grupo_interes/editar_inscripcion_gi.php?id=$id");
    }
}

function eliminarInscripcion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido');
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        manejarError('ID inválido');
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();
        $inscripcionGrupoModel = new InscripcionGrupoInteres($conexion);

        if (!$inscripcionGrupoModel->obtenerPorId($id)) {
            manejarError('Registro no encontrado');
        }

        $inscripcionGrupoModel->IdInscripcion_Grupo = $id;

        if ($inscripcionGrupoModel->eliminar()) {
            $_SESSION['alert'] = 'deleted';
            $_SESSION['message'] = 'Inscripción eliminada correctamente';
            header("Location: ../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php");
            exit();
        } else {
            throw new Exception("Error al eliminar");
        }

    } catch (Exception $e) {
        manejarError('Error: ' . $e->getMessage(), '../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php');
    }
}

/**
 * Procesar inscripción desde el portal de representantes
 * Maneja tanto inscripciones nuevas como cambios de grupo
 */
function procesarInscripcionRepresentante() {
    // --- VALIDACIÓN DE PARÁMETROS ---
    if (!isset($_GET['idGrupo']) || !isset($_GET['idEstudiante'])) {
        $_SESSION['alerta_error'] = "Faltan parámetros para procesar la inscripción.";
        header("Location: ../vistas/representantes/representados/representado.php");
        exit();
    }

    $idGrupo = intval($_GET['idGrupo']);
    $idEstudiante = intval($_GET['idEstudiante']);

    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // 1. OBTENER AÑO ESCOLAR ACTIVO
        $fechaModel = new FechaEscolar($db);
        $fechaActiva = $fechaModel->obtenerActivo();
        
        if (!$fechaActiva) {
            throw new Exception("No hay un año escolar activo configurado.");
        }
        $idFechaEscolar = $fechaActiva['IdFecha_Escolar'];

        // 2. OBTENER INSCRIPCIÓN ACADÉMICA VIGENTE
        $sqlInscripcion = "SELECT IdInscripcion, IdCurso_Seccion FROM inscripcion WHERE IdEstudiante = :idEstudiante AND IdFecha_Escolar = :idFecha AND IdStatus IN (11, 8) LIMIT 1";
        $stmtInsc = $db->prepare($sqlInscripcion);
        $stmtInsc->bindParam(':idEstudiante', $idEstudiante);
        $stmtInsc->bindParam(':idFecha', $idFechaEscolar);
        $stmtInsc->execute();
        $inscripcionAcademica = $stmtInsc->fetch(PDO::FETCH_ASSOC);

        if (!$inscripcionAcademica) {
            throw new Exception("El estudiante no tiene una inscripción académica válida para el año escolar activo.");
        }
        $idInscripcionGeneral = $inscripcionAcademica['IdInscripcion'];

        // 3. BUSCAR SI YA TIENE GRUPO DE INTERÉS (Para eliminar el anterior si es cambio)
        $sqlPrevio = "SELECT igi.IdInscripcion_Grupo 
                      FROM inscripcion_grupo_interes igi
                      INNER JOIN grupo_interes gi ON igi.IdGrupo_Interes = gi.IdGrupo_Interes
                      WHERE igi.IdEstudiante = :idEstudiante AND gi.IdFecha_Escolar = :idFecha";
        $stmtPrevio = $db->prepare($sqlPrevio);
        $stmtPrevio->bindParam(':idEstudiante', $idEstudiante);
        $stmtPrevio->bindParam(':idFecha', $idFechaEscolar);
        $stmtPrevio->execute();
        $inscripcionPrevia = $stmtPrevio->fetch(PDO::FETCH_ASSOC);

        if ($inscripcionPrevia) {
            // Eliminar la inscripción anterior
            $inscripcionGIModel = new InscripcionGrupoInteres($db);
            $inscripcionGIModel->IdInscripcion_Grupo = $inscripcionPrevia['IdInscripcion_Grupo'];
            if (!$inscripcionGIModel->eliminar()) {
                throw new Exception("No se pudo eliminar la inscripción al grupo anterior.");
            }
        }

        // 4. CREAR NUEVA INSCRIPCIÓN
        $nuevaInscripcion = new InscripcionGrupoInteres($db);
        $nuevaInscripcion->IdGrupo_Interes = $idGrupo;
        $nuevaInscripcion->IdEstudiante = $idEstudiante;
        $nuevaInscripcion->IdInscripcion = $idInscripcionGeneral;

        if ($nuevaInscripcion->guardar()) {
            $db->commit();
            // Éxito
            $_SESSION['swal_success'] = isset($inscripcionPrevia) 
                ? "Se ha realizado el cambio de grupo de interés correctamente." 
                : "Estudiante inscrito en el grupo de interés exitosamente.";
        } else {
            throw new Exception("Error al guardar la nueva inscripción.");
        }

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['swal_error'] = "Error: " . $e->getMessage();
    }

    // Redireccionar
    header("Location: ../vistas/representantes/representados/inscripcion_grupo.php?id=" . $idEstudiante);
    exit();
}

/**
 * Endpoint API para obtener ocupación de grupos en tiempo real
 * Retorna JSON con información actualizada de inscritos y capacidad
 */
function obtenerOcupacion() {
    header('Content-Type: application/json');
    
    // Validar solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Obtener ID del año escolar activo
    $fechaModel = new FechaEscolar($db);
    $fechaActiva = $fechaModel->obtenerActivo();
    $idFechaEscolar = $fechaActiva ? $fechaActiva['IdFecha_Escolar'] : 0;

    if ($idFechaEscolar == 0) {
        echo json_encode([]);
        exit;
    }

    // IDs de los grupos de interés a consultar (opcional, para optimizar)
    $idsGrupos = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

    try {
        // Consulta optimizada para contar inscritos por grupo para el año activo
        $sql = "SELECT 
                    gi.IdGrupo_Interes,
                    (SELECT COUNT(*) FROM inscripcion_grupo_interes admin WHERE admin.IdGrupo_Interes = gi.IdGrupo_Interes) as total_estudiantes,
                    tgi.capacidad_maxima
                FROM grupo_interes gi
                JOIN tipo_grupo_interes tgi ON gi.IdTipo_Grupo = tgi.IdTipo_Grupo
                WHERE gi.IdFecha_Escolar = :idFecha";

        // Filtrar por IDs específicos si se proporcionan
        if (!empty($idsGrupos)) {
            // Sanitizar array de enteros para la cláusula IN
            $idsSafe = array_map('intval', $idsGrupos);
            // Evitar array vacío generando error SQL
            if(count($idsSafe) > 0){
                $inQuery = implode(',', $idsSafe);
                $sql .= " AND gi.IdGrupo_Interes IN ($inQuery)";
            }
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idFecha', $idFechaEscolar);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear respuesta
        $data = [];
        foreach ($resultados as $row) {
            $data[] = [
                'id' => $row['IdGrupo_Interes'],
                'inscritos' => (int)$row['total_estudiantes'],
                'capacidad' => (int)$row['capacidad_maxima']
            ];
        }

        echo json_encode($data);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

function manejarError($mensaje, $urlRedireccion = '../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
?>
