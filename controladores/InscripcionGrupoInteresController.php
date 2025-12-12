<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    manejarError('Debe iniciar sesión para acceder', '../vistas/login/login.php');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/InscripcionGrupoInteres.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/GrupoInteres.php';

// Determinar acción
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

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

function manejarError($mensaje, $urlRedireccion = '../vistas/inscripciones/inscripcion_grupo_interes/inscripcion_grupo_interes.php') {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = $mensaje;
    header("Location: $urlRedireccion");
    exit();
}
?>
