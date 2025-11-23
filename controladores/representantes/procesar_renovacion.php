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
        $idStatus = isset($_POST['idStatus']) ? (int)$_POST['idStatus'] : 10; // Status por defecto: Pendiente de pago
        $idTipoInscripcion = isset($_POST['idTipoInscripcion']) ? (int)$_POST['idTipoInscripcion'] : 2; // Por defecto: Estudiante Regular
        $esReinscripcion = isset($_POST['esReinscripcion']) && $_POST['esReinscripcion'] == '1';
        $origen = isset($_POST['origen']) ? $_POST['origen'] : 'representante';
        $idPersonaUsuario = $_SESSION['idPersona'];

        // Validaciones básicas
        if ($idEstudiante <= 0 || $idFechaEscolar <= 0) {
            throw new Exception("Datos incompletos. Por favor complete todos los campos requeridos.");
        }

        // Para reinscripción, validar que el curso sea válido y obtener sección "Inscripcion" (IdSeccion = 1)
        if ($esReinscripcion) {
            if ($idCurso <= 0) {
                throw new Exception("Debe seleccionar un curso válido.");
            }

            // Obtener el IdCurso_Seccion con la sección "Inscripcion" (IdSeccion = 1)
            if ($idCursoSeccion <= 0) {
                $sqlSeccion = "SELECT cs.IdCurso_Seccion
                              FROM curso_seccion cs
                              WHERE cs.IdCurso = :idCurso
                              AND cs.IdSeccion = 1
                              LIMIT 1";
                $stmtSeccion = $conexion->prepare($sqlSeccion);
                $stmtSeccion->bindParam(':idCurso', $idCurso, PDO::PARAM_INT);
                $stmtSeccion->execute();
                $seccionResult = $stmtSeccion->fetch(PDO::FETCH_ASSOC);

                if (!$seccionResult) {
                    throw new Exception("No existe la sección 'Inscripcion' para el curso seleccionado.");
                }
                $idCursoSeccion = $seccionResult['IdCurso_Seccion'];
            }
        } elseif ($idCursoSeccion <= 0) {
            throw new Exception("Datos incompletos. Por favor complete todos los campos requeridos.");
        }

        // Determinar si es representante o administrativo
        $esAdministrativo = ($origen === 'administrativo');

        // Si es representante, verificar que tenga relación con el estudiante
        if (!$esAdministrativo) {
            $representanteModel = new Representante($conexion);
            $estudiantesRepresentados = $representanteModel->obtenerEstudiantesPorRepresentante($idPersonaUsuario);

            $esRepresentado = false;
            foreach ($estudiantesRepresentados as $est) {
                if ($est['IdEstudiante'] == $idEstudiante) {
                    $esRepresentado = true;
                    break;
                }
            }

            if (!$esRepresentado) {
                throw new Exception("No tiene permisos para renovar el cupo de este estudiante.");
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

        // Obtener el responsable de inscripción según el origen
        $idRelacionRepresentante = null;

        if ($esAdministrativo) {
            // Si es administrativo, obtener el responsable de la última inscripción del estudiante
            $sqlUltimoResponsable = "SELECT responsable_inscripcion
                                     FROM inscripcion
                                     WHERE IdEstudiante = :idEstudiante
                                     ORDER BY fecha_inscripcion DESC
                                     LIMIT 1";
            $stmtUltimo = $conexion->prepare($sqlUltimoResponsable);
            $stmtUltimo->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
            $stmtUltimo->execute();
            $ultimaInscripcion = $stmtUltimo->fetch(PDO::FETCH_ASSOC);

            if ($ultimaInscripcion && $ultimaInscripcion['responsable_inscripcion']) {
                $idRelacionRepresentante = $ultimaInscripcion['responsable_inscripcion'];
            } else {
                // Si no tiene inscripción previa, buscar cualquier representante del estudiante
                $sqlCualquierRep = "SELECT IdRepresentante
                                    FROM representante
                                    WHERE IdEstudiante = :idEstudiante
                                    AND IdParentesco IN (1, 2, 3)
                                    ORDER BY IdParentesco ASC
                                    LIMIT 1";
                $stmtRep = $conexion->prepare($sqlCualquierRep);
                $stmtRep->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
                $stmtRep->execute();
                $repEncontrado = $stmtRep->fetch(PDO::FETCH_ASSOC);

                if ($repEncontrado) {
                    $idRelacionRepresentante = $repEncontrado['IdRepresentante'];
                } else {
                    throw new Exception("No se encontró un representante válido para este estudiante.");
                }
            }
        } else {
            // Si es representante, obtener su relación con el estudiante
            $sqlRepresentante = "SELECT IdRepresentante
                                FROM representante
                                WHERE IdPersona = :idPersona
                                AND IdEstudiante = :idEstudiante
                                AND IdParentesco IN (1, 2, 3)
                                LIMIT 1";
            $stmtRep = $conexion->prepare($sqlRepresentante);
            $stmtRep->bindParam(':idPersona', $idPersonaUsuario, PDO::PARAM_INT);
            $stmtRep->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
            $stmtRep->execute();
            $representante = $stmtRep->fetch(PDO::FETCH_ASSOC);

            if (!$representante) {
                throw new Exception("No se encontró la relación de representación para este estudiante.");
            }

            $idRelacionRepresentante = $representante['IdRepresentante'];
        }

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

            // Preparar datos de inscripción
            $inscripcionModel->IdTipo_Inscripcion = $idTipoInscripcion; // 2=Regular, 3=Reinscripción
            $inscripcionModel->codigo_inscripcion = $codigoInscripcion;
            $inscripcionModel->IdEstudiante = $idEstudiante;
            $now = new DateTime('now', new DateTimeZone('America/Caracas'));
            $inscripcionModel->fecha_inscripcion = $now->format('Y-m-d H:i:s');
            $inscripcionModel->ultimo_plantel = $ultimoPlantel; // Puede ser null si no tiene inscripciones previas
            $inscripcionModel->responsable_inscripcion = $idRelacionRepresentante;
            $inscripcionModel->IdFecha_Escolar = $idFechaEscolar;
            $inscripcionModel->IdStatus = $idStatus; // Usar el status del formulario
            $inscripcionModel->IdCurso_Seccion = $idCursoSeccion;

            // Guardar inscripción
            $numeroSolicitud = $inscripcionModel->guardar();

            if (!$numeroSolicitud) {
                throw new Exception("Error al crear la solicitud de renovación.");
            }

            // Confirmar transacción
            $conexion->commit();

            // Redirigir con mensaje de éxito según el origen
            if ($esAdministrativo) {
                $_SESSION['alert'] = 'success';
                $_SESSION['message'] = "Inscripción registrada exitosamente. Código: $codigoInscripcion";
                header("Location: ../../vistas/inscripciones/inscripcion/inscripcion.php");
            } else {
                $tipoMensaje = $esReinscripcion ? "Reinscripción" : "Renovación de cupo";
                $_SESSION['mensaje_exito'] = "$tipoMensaje solicitada exitosamente. Código de seguimiento: $codigoInscripcion";
                header("Location: ../../vistas/representantes/representados/representado.php");
            }
            exit();

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollback();
            }
            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();

        // Redirigir según el origen
        if (isset($origen) && $origen === 'administrativo') {
            $_SESSION['alert'] = 'error';
            $_SESSION['message'] = $e->getMessage();
            header("Location: ../../vistas/inscripciones/inscripcion/nuevo_inscripcion.php");
        } else {
            // Si hay un ID de estudiante, redirigir a la página correspondiente
            if (isset($idEstudiante) && $idEstudiante > 0) {
                if (isset($esReinscripcion) && $esReinscripcion) {
                    header("Location: ../../vistas/representantes/representados/solicitar_reinscripcion.php?id=$idEstudiante");
                } else {
                    header("Location: ../../vistas/representantes/representados/renovar_cupo.php?id=$idEstudiante");
                }
            } else {
                header("Location: ../../vistas/representantes/representados/representado.php");
            }
        }
        exit();
    }
} else {
    // Si no es POST, redirigir según referer o a login
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'inscripciones') !== false) {
        header("Location: ../../vistas/inscripciones/inscripcion/inscripcion.php");
    } else {
        header("Location: ../../vistas/representantes/representados/representado.php");
    }
    exit();
}
