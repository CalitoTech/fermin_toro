<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../modelos/Representante.php';
require_once __DIR__ . '/../../modelos/FechaEscolar.php';
require_once __DIR__ . '/../../modelos/CursoSeccion.php';

date_default_timezone_set('America/Caracas');

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../vistas/login/login.php");
    exit();
}

$database = new Database();
$conexion = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $idEstudiante = isset($_POST['IdEstudiante']) ? (int)$_POST['IdEstudiante'] : 0;
        $idFechaEscolar = isset($_POST['IdFechaEscolar']) ? (int)$_POST['IdFechaEscolar'] : 0;
        $idCurso = isset($_POST['IdCurso']) ? (int)$_POST['IdCurso'] : 0;
        $idCursoSeccion = isset($_POST['IdCursoSeccion']) ? (int)$_POST['IdCursoSeccion'] : 0;
        $idPersonaRepresentante = $_SESSION['idPersona'];

        // Validaciones básicas
        if ($idEstudiante <= 0 || $idFechaEscolar <= 0 || $idCurso <= 0 || $idCursoSeccion <= 0) {
            throw new Exception("Datos incompletos. Por favor complete todos los campos requeridos.");
        }

        // Verificar que el representante actual tenga relación con el estudiante
        $representanteModel = new Representante($conexion);
        $estudiantesRepresentados = $representanteModel->obtenerEstudiantesPorRepresentante($idPersonaRepresentante);

        $esRepresentado = false;
        foreach ($estudiantesRepresentados as $est) {
            if ($est['IdEstudiante'] == $idEstudiante) {
                $esRepresentado = true;
                break;
            }
        }

        // Verificar que el año escolar esté activo
        $fechaEscolarModel = new FechaEscolar($conexion);
        $anioEscolar = $fechaEscolarModel->obtenerActivo();

        if (!$anioEscolar || $anioEscolar['IdFecha_Escolar'] != $idFechaEscolar) {
            throw new Exception("El año escolar seleccionado no está activo.");
        }

        // Verificar que el estudiante no tenga ya una inscripción en este año escolar
        $sqlVerificar = "SELECT COUNT(*) FROM inscripcion
                        WHERE IdEstudiante = :idEstudiante
                        AND IdFecha_Escolar = :idFechaEscolar";
        $stmtVerificar = $conexion->prepare($sqlVerificar);
        $stmtVerificar->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
        $stmtVerificar->bindParam(':idFechaEscolar', $idFechaEscolar, PDO::PARAM_INT);
        $stmtVerificar->execute();

        if ($stmtVerificar->fetchColumn() > 0) {
            throw new Exception("El estudiante ya tiene una inscripción registrada para este año escolar.");
        }

        // Obtener el IdRepresentante (relación representante-estudiante) del usuario actual
        $sqlRepresentante = "SELECT IdRepresentante
                            FROM representante
                            WHERE IdPersona = :idPersona
                            AND IdEstudiante = :idEstudiante
                            AND IdParentesco IN (1, 2, 3)
                            LIMIT 1";
        $stmtRep = $conexion->prepare($sqlRepresentante);
        $stmtRep->bindParam(':idPersona', $idPersonaRepresentante, PDO::PARAM_INT);
        $stmtRep->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
        $stmtRep->execute();
        $representante = $stmtRep->fetch(PDO::FETCH_ASSOC);

        if (!$representante) {
            throw new Exception("No se encontró la relación de representación para este estudiante.");
        }

        $idRelacionRepresentante = $representante['IdRepresentante'];

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // Crear instancia del modelo de inscripción
            $inscripcionModel = new Inscripcion($conexion);

            // Obtener último plantel usando la función del modelo
            $ultimoPlantel = $inscripcionModel->obtenerUltimoPlantel($idEstudiante);

            // Generar código de inscripción
            $anioActual = date('Y');
            $sqlContador = "SELECT COUNT(*) FROM inscripcion WHERE YEAR(fecha_inscripcion) = :anio";
            $stmtContador = $conexion->prepare($sqlContador);
            $stmtContador->bindParam(':anio', $anioActual, PDO::PARAM_INT);
            $stmtContador->execute();
            $correlativo = $stmtContador->fetchColumn() + 1;
            $codigoInscripcion = "$anioActual-$correlativo";

            // Obtener el estado por defecto para inscripciones (IdTipo_Status = 2)
            $sqlStatus = "SELECT IdStatus FROM status WHERE IdTipo_Status = 2 ORDER BY IdStatus LIMIT 1";
            $stmtStatus = $conexion->prepare($sqlStatus);
            $stmtStatus->execute();
            $statusInscripcion = $stmtStatus->fetch(PDO::FETCH_ASSOC);

            if (!$statusInscripcion) {
                throw new Exception("No se encontró un estado válido para la inscripción.");
            }

            // Preparar datos de inscripción
            $inscripcionModel->IdTipo_Inscripcion = 2; // Estudiante Regular
            $inscripcionModel->codigo_inscripcion = $codigoInscripcion;
            $inscripcionModel->IdEstudiante = $idEstudiante;
            $now = new DateTime('now', new DateTimeZone('America/Caracas'));
            $inscripcionModel->fecha_inscripcion = $now->format('Y-m-d H:i:s');
            $inscripcionModel->ultimo_plantel = $ultimoPlantel; // Puede ser null si no tiene inscripciones previas
            $inscripcionModel->responsable_inscripcion = $idRelacionRepresentante;
            $inscripcionModel->IdFecha_Escolar = $idFechaEscolar;
            $inscripcionModel->IdStatus = 10;
            $inscripcionModel->IdCurso_Seccion = $idCursoSeccion;

            // Guardar inscripción
            $numeroSolicitud = $inscripcionModel->guardar();

            if (!$numeroSolicitud) {
                throw new Exception("Error al crear la solicitud de renovación.");
            }

            // Confirmar transacción
            $conexion->commit();

            // Redirigir con mensaje de éxito
            $_SESSION['mensaje_exito'] = "Renovación de cupo solicitada exitosamente. Código de seguimiento: $codigoInscripcion";
            header("Location: ../../vistas/representantes/representados/representado.php");
            exit();

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollback();
            }
            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();

        // Si hay un ID de estudiante, redirigir a la página de renovación
        if (isset($idEstudiante) && $idEstudiante > 0) {
            header("Location: ../../vistas/representantes/representados/renovar_cupo.php?id=$idEstudiante");
        } else {
            header("Location: ../../vistas/representantes/representados/representado.php");
        }
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: ../../vistas/representantes/representados/representado.php");
    exit();
}
