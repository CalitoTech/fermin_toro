<?php
/**
 * Script asíncrono para crear inscripciones automáticas
 * Inscribe automáticamente a estudiantes del año anterior en el siguiente curso
 * Copia los requisitos cumplidos de la última inscripción (solo los aplicables al nuevo nivel)
 */

// Evitar timeout
set_time_limit(600); // 10 minutos máximo
ignore_user_abort(true);

// Obtener parámetros
$idAnoAnterior = isset($argv[1]) ? (int)$argv[1] : null;
$idAnoNuevo = isset($argv[2]) ? (int)$argv[2] : null;

if (!$idAnoAnterior || !$idAnoNuevo) {
    error_log("❌ Script inscripciones automáticas: Parámetros inválidos");
    exit(1);
}

error_log("📚 Iniciando inscripciones automáticas: Año $idAnoAnterior → Año $idAnoNuevo");

try {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../modelos/Inscripcion.php';
    require_once __DIR__ . '/../modelos/Egreso.php';

    $inscripcionModel = new Inscripcion($conexion);
    $egresoModel = new Egreso($conexion);

    // 1. Obtener estudiantes inscritos del año anterior (IdStatus = 11)
    // Incluir el IdInscripcion para poder copiar los requisitos
    $queryEstudiantes = "SELECT DISTINCT
        i.IdInscripcion,
        i.IdEstudiante,
        i.ultimo_plantel,
        i.responsable_inscripcion
    FROM inscripcion i
    WHERE i.IdFecha_Escolar = :idAnoAnterior
    AND i.IdStatus = 11";

    $stmt = $conexion->prepare($queryEstudiantes);
    $stmt->bindParam(':idAnoAnterior', $idAnoAnterior, PDO::PARAM_INT);
    $stmt->execute();
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("👥 Encontrados " . count($estudiantes) . " estudiantes inscritos");

    $conexion->beginTransaction();

    $inscritos = 0;
    $graduados = 0;
    $errores = 0;

    foreach ($estudiantes as $est) {
        try {
            // Obtener curso siguiente usando la función del modelo Persona
            require_once __DIR__ . '/../modelos/Persona.php';
            $personaModel = new Persona($conexion);
            $cursoSiguienteData = $personaModel->obtenerCursoSiguiente($est['IdEstudiante']);

            // Si es null, el estudiante se graduó
            if ($cursoSiguienteData === null) {
                // 1. Actualizar status a Graduado (IdStatus = 7)
                $updateGraduado = "UPDATE persona
                                  SET IdEstadoInstitucional = 7
                                  WHERE IdPersona = :idEstudiante";
                $stmtGrad = $conexion->prepare($updateGraduado);
                $stmtGrad->bindParam(':idEstudiante', $est['IdEstudiante'], PDO::PARAM_INT);
                $stmtGrad->execute();

                // 2. Crear registro de egreso
                $egresoModel->fecha_egreso = date('Y-m-d');
                $egresoModel->motivo = 'Graduación - Completó todos los niveles educativos';
                $egresoModel->IdPersona = $est['IdEstudiante'];
                $egresoModel->IdStatus = 7; // Status: Graduado
                $egresoModel->guardar();

                $graduados++;
                error_log("🎓 Estudiante #{$est['IdEstudiante']} graduado con registro de egreso");
                continue;
            }

            // Generar código de inscripción único
            $anioActual = date('Y');
            $sqlContador = "SELECT COUNT(*) FROM inscripcion WHERE YEAR(fecha_inscripcion) = :anio";
            $stmtContador = $conexion->prepare($sqlContador);
            $stmtContador->bindParam(':anio', $anioActual, PDO::PARAM_INT);
            $stmtContador->execute();
            $correlativo = $stmtContador->fetchColumn() + 1;
            $codigoInscripcion = "$anioActual-$correlativo";

            // Crear nueva inscripción
            $insertInscripcion = "INSERT INTO inscripcion (
                IdTipo_Inscripcion,
                codigo_inscripcion,
                IdEstudiante,
                fecha_inscripcion,
                ultimo_plantel,
                responsable_inscripcion,
                IdFecha_Escolar,
                IdStatus,
                IdCurso_Seccion
            ) VALUES (
                2,  -- Estudiante Regular
                :codigo,
                :idEstudiante,
                NOW(),
                :ultimoPlantel,
                :responsable,
                :idAnoNuevo,
                10, -- Pendiente de pago
                :idCursoSeccion
            )";

            $stmtIns = $conexion->prepare($insertInscripcion);
            $stmtIns->bindParam(':codigo', $codigoInscripcion);
            $stmtIns->bindParam(':idEstudiante', $est['IdEstudiante'], PDO::PARAM_INT);
            $stmtIns->bindParam(':ultimoPlantel', $est['ultimo_plantel'], PDO::PARAM_INT);
            $stmtIns->bindParam(':responsable', $est['responsable_inscripcion'], PDO::PARAM_INT);
            $stmtIns->bindParam(':idAnoNuevo', $idAnoNuevo, PDO::PARAM_INT);

            // Obtener el IdCurso_Seccion de la primera sección disponible
            $idCursoSeccion = $cursoSiguienteData['secciones'][0]['IdCurso_Seccion'] ?? null;
            if (!$idCursoSeccion) {
                throw new Exception("No hay secciones disponibles para el curso siguiente");
            }

            $stmtIns->bindParam(':idCursoSeccion', $idCursoSeccion, PDO::PARAM_INT);
            $stmtIns->execute();

            $inscritos++;

        } catch (Exception $e) {
            error_log("❌ Error inscribiendo estudiante #{$est['IdEstudiante']}: " . $e->getMessage());
            $errores++;
        }
    }

    $conexion->commit();

    error_log("✅ Inscripciones automáticas completadas:");
    error_log("   📝 Inscritos: $inscritos");
    error_log("   🎓 Graduados: $graduados");
    error_log("   ❌ Errores: $errores");

} catch (Exception $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log("❌ Error crítico en inscripciones automáticas: " . $e->getMessage());
    exit(1);
}

exit(0);
