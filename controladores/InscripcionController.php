<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/Dificultad.php';
require_once __DIR__ . '/../modelos/Representante.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';
require_once __DIR__ . '/../modelos/Telefono.php';
require_once __DIR__ . '/../modelos/FechaEscolar.php';
require_once __DIR__ . '/../modelos/DetallePerfil.php';

// Crear conexión
$database = new Database();
$conexion = $database->getConnection();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (empty($_POST)) {
            throw new Exception("No se recibieron datos del formulario");
        }

        // Validar datos recibidos
        $requiredFields = [
            'estudianteApellidos', 'estudianteNombres', 'estudianteNacionalidad', 
            'estudianteCedula', 'estudianteSexo', 'estudianteFechaNacimiento',
            'estudianteLugarNacimiento', 'estudianteTelefono',
            'tipoRepresentante'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        $conexion->beginTransaction();

        try {
            // Crear persona (estudiante)
            $personaEstudiante = new Persona($conexion);
            $personaEstudiante->IdNacionalidad = $_POST['estudianteNacionalidad'] === 'V' ? 1 : 2;
            $personaEstudiante->cedula = $_POST['estudianteCedula'];
            $personaEstudiante->nombre = $_POST['estudianteNombres'];
            $personaEstudiante->apellido = $_POST['estudianteApellidos'];
            $personaEstudiante->fecha_nacimiento = $_POST['estudianteFechaNacimiento'];
            $personaEstudiante->correo = $_POST['estudianteCorreo'] ?? '';
            $personaEstudiante->direccion = $_POST['representanteDireccion'] ?? ''; // Usar dirección del representante
            $personaEstudiante->IdSexo = $_POST['estudianteSexo'] === 'M' ? 1 : 2;
            $urbanismoEstudiante = match($_POST['tipoRepresentante']) {
                'padre' => $_POST['padreUrbanismo'] ?? null,
                'madre' => $_POST['madreUrbanismo'] ?? null,
                'otro' => $_POST['representanteUrbanismo'] ?? null,
                default => null
            };

            if (!$urbanismoEstudiante) {
                throw new Exception("Debe seleccionar un urbanismo válido");
            }
            $personaEstudiante->IdUrbanismo = $urbanismoEstudiante;
            $idEstudiante = $personaEstudiante->guardar();
            if (!$idEstudiante) {
                throw new Exception("Error al guardar al estudiante");
            }

            // --- Asignar perfil de estudiante (IdPerfil = 3) ---
            $detallePerfilEstudiante = new DetallePerfil($conexion);
            $detallePerfilEstudiante->IdPerfil = 3; // Estudiante
            $detallePerfilEstudiante->IdPersona = $idEstudiante;
            $detallePerfilEstudiante->guardar();
            if (!$detallePerfilEstudiante->guardar()) {
                throw new Exception("Error al asignar perfil de estudiante");
            }
            
            $tieneDificultad = (
                !empty($_POST['dificultadVisual']) ||
                !empty($_POST['dificultadAuditiva']) ||
                !empty($_POST['dificultadMotora']) ||
                !empty($_POST['esAlergico']) ||
                !empty($_POST['tieneEnfermedad'])
            );

            if ($tieneDificultad) {
                $dificultad = new Dificultad($conexion);
                $dificultad->IdPersona = $idEstudiante;
                $dificultad->visual = isset($_POST['dificultadVisual']) ? 1 : 0;
                $dificultad->auditiva = isset($_POST['dificultadAuditiva']) ? 1 : 0;
                $dificultad->motora = isset($_POST['dificultadMotora']) ? 1 : 0;
                $dificultad->es_alergico = isset($_POST['esAlergico']) ? 1 : 0;
                $dificultad->alergia = !empty($_POST['alergia']) ? trim($_POST['alergia']) : null;
                $dificultad->tiene_enfermedad = isset($_POST['tieneEnfermedad']) ? 1 : 0;
                $dificultad->enfermedad = !empty($_POST['enfermedad']) ? trim($_POST['enfermedad']) : null;
                $dificultad->guardar();
                if (!$dificultad->guardar()) {
                    throw new Exception("Error al guardar dificultades");
                }
            }
            
            // Guardar información del padre (si se proporcionó)
            if (!empty($_POST['padreNombres']) || !empty($_POST['padreApellidos'])) {
                $personaPadre = new Persona($conexion);
                $personaPadre->IdNacionalidad = $_POST['padreNacionalidad'] === 'V' ? 1 : 2;
                $personaPadre->cedula = $_POST['padreCedula'] ?? '';
                $personaPadre->nombre = $_POST['padreNombres'] ?? '';
                $personaPadre->apellido = $_POST['padreApellidos'] ?? '';
                $personaPadre->correo = $_POST['padreCorreo'] ?? '';
                $personaPadre->direccion = $_POST['representanteDireccion'] ?? ''; // Misma dirección que el representante
                $personaPadre->lugar_trabajo = $_POST['padreLugarTrabajo'] ?? '';
                $personaPadre->IdSexo = 1; // Masculino
                $personaPadre->IdUrbanismo = $_POST['padreUrbanismo'];
                $idPadre = $personaPadre->guardar();
                if (!$idPadre) {
                    throw new Exception("Error al guardar al padre");
                }

                // Teléfonos del padre
                if (!Telefono::guardarTelefonosPersona($conexion, $idPadre, [
                    'TelefonoHabitacion' => $_POST['padreTelefonoHabitacion'] ?? '',
                    'Celular' => $_POST['padreCelular'] ?? '',
                    'TelefonoTrabajo' => $_POST['padreTelefonoTrabajo'] ?? ''
                ])) {
                    throw new Exception("Error al guardar teléfonos del padre");
                }

                // Crear relación padre-estudiante
                $representantePadre = new Representante($conexion);
                $representantePadre->IdPersona = $idPadre;
                $representantePadre->IdParentesco = 1; // Padre
                $representantePadre->IdEstudiante = $idEstudiante;
                $representantePadre->ocupacion = trim($_POST['padreOcupacion'] ?? '');
                $idRepresentantePadre = $representantePadre->guardar();
                if (!$idRepresentantePadre) {
                    throw new Exception("Error al crear relación padre-estudiante");
                }

                // Asignar perfil de representante (IdPerfil = 4)
                $detallePerfil = new DetallePerfil($conexion);
                $detallePerfil->IdPerfil = 4;
                $detallePerfil->IdPersona = $idPadre;
                $detallePerfil->guardar();
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar perfil de representante (padre)");
                }
                
            }

            // Guardar información de la madre (si se proporcionó)
            if (!empty($_POST['madreNombres']) || !empty($_POST['madreApellidos'])) {
                $personaMadre = new Persona($conexion);
                $personaMadre->IdNacionalidad = $_POST['madreNacionalidad'] === 'V' ? 1 : 2;
                $personaMadre->cedula = $_POST['madreCedula'] ?? '';
                $personaMadre->nombre = $_POST['madreNombres'] ?? '';
                $personaMadre->apellido = $_POST['madreApellidos'] ?? '';
                $personaMadre->correo = $_POST['madreCorreo'] ?? '';
                $personaMadre->direccion = $_POST['representanteDireccion'] ?? ''; // Misma dirección que el representante
                $personaMadre->lugar_trabajo = $_POST['madreLugarTrabajo'] ?? '';
                $personaMadre->IdSexo = 2; // Femenino
                $personaMadre->IdUrbanismo = $_POST['madreUrbanismo'];
                $idMadre = $personaMadre->guardar();
                if (!$idMadre) {
                    throw new Exception("Error al guardar a la madre");
                }

                // Teléfonos de la madre
                if (!Telefono::guardarTelefonosPersona($conexion, $idMadre, [
                    'TelefonoHabitacion' => $_POST['madreTelefonoHabitacion'] ?? '',
                    'Celular' => $_POST['madreCelular'] ?? '',
                    'TelefonoTrabajo' => $_POST['madreTelefonoTrabajo'] ?? ''
                ])) {
                    throw new Exception("Error al guardar teléfonos de la madre");
                }

                // Crear relación madre-estudiante
                $representanteMadre = new Representante($conexion);
                $representanteMadre->IdPersona = $idMadre;
                $representanteMadre->IdParentesco = 2; // Madre
                $representanteMadre->IdEstudiante = $idEstudiante;
                $representanteMadre->ocupacion = trim($_POST['madreOcupacion'] ?? '');
                $idRepresentanteMadre = $representanteMadre->guardar();
                if (!$idRepresentanteMadre) {
                    throw new Exception("Error al crear relación madre-estudiante");
                }

                // Asignar perfil de representante (IdPerfil = 4)
                $detallePerfil = new DetallePerfil($conexion);
                $detallePerfil->IdPerfil = 4;
                $detallePerfil->IdPersona = $idMadre;
                $detallePerfil->guardar();
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar perfil de representante (madre)");
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
                // === SOLO AQUÍ se crea y guarda el representante legal ===
                $camposRequeridos = ['representanteNombres', 'representanteApellidos', 'representanteCedula', 
                'representanteCorreo', 'representanteUrbanismo', 'representanteDireccion', 
                'representanteCelular'];
                foreach ($camposRequeridos as $campo) {
                    if (empty($_POST[$campo])) {
                        throw new Exception("El campo $campo es requerido para representante legal");
                    }
                }

                $personaRepresentante = new Persona($conexion);
                $personaRepresentante->IdNacionalidad = $_POST['representanteNacionalidad'] === 'V' ? 1 : 2;
                $personaRepresentante->cedula = $_POST['representanteCedula'];
                $personaRepresentante->nombre = $_POST['representanteNombres'];
                $personaRepresentante->apellido = $_POST['representanteApellidos'];
                $personaRepresentante->correo = $_POST['representanteCorreo'] ?? '';
                $personaRepresentante->direccion = $_POST['representanteDireccion'] ?? '';
                $personaRepresentante->lugar_trabajo = $_POST['representanteLugarTrabajo'] ?? '';
                $personaRepresentante->IdSexo = NULL;
                $personaRepresentante->IdUrbanismo = $_POST['representanteUrbanismo'];
                $idRepresentante = $personaRepresentante->guardar();

                if (!$idRepresentante) {
                    throw new Exception("Error al guardar al representante legal");
                }

                // Guardar teléfonos del representante
                if (!Telefono::guardarTelefonosPersona($conexion, $idRepresentante, [
                    'TelefonoHabitacion' => $_POST['representanteTelefonoHabitacion'] ?? '',
                    'Celular' => $_POST['representanteCelular'] ?? '',
                    'TelefonoTrabajo' => $_POST['representanteTelefonoTrabajo'] ?? ''
                ])) {
                    throw new Exception("Error al guardar teléfonos del representante");
                }

                // Crear relación representante-estudiante
                $representante = new Representante($conexion);
                $representante->IdPersona = $idRepresentante;
                $representante->IdParentesco = 3; // Representante Legal
                $representante->IdEstudiante = $idEstudiante;
                $representante->ocupacion = trim($_POST['representanteOcupacion'] ?? '');
                $idRelacionRepresentante = $representante->guardar();
                if (!$idRelacionRepresentante) {
                    throw new Exception("Error al crear relación representante-estudiante");
                }

                // Asignar perfil de representante (IdPerfil = 4)
                $detallePerfil = new DetallePerfil($conexion);
                $detallePerfil->IdPerfil = 4;
                $detallePerfil->IdPersona = $idRepresentante;
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar perfil de representante legal");
                }
            } 
            else {
                throw new Exception("Tipo de representante no válido");
            }

            if (!empty($_POST['emergenciaNombre'])) {
                $nombreCompleto = trim($_POST['emergenciaNombre']);
                $partesNombre = preg_split('/\s+/', $nombreCompleto);

                // Validar que haya al menos 2 palabras para que el apellido no quede vacío
                if (count($partesNombre) < 2) {
                    throw new Exception("El contacto de emergencia debe tener al menos un nombre y un apellido.");
                }

                // Aplicar las mismas reglas que en guardarContactoEmergencia()
                if (count($partesNombre) == 2) {
                    $apellido = $partesNombre[1];
                } else {
                    $apellido = implode(' ', array_slice($partesNombre, 2));
                }

                // Validar que el apellido no esté vacío
                if (empty($apellido)) {
                    throw new Exception("El contacto de emergencia debe tener un apellido válido.");
                }

                $emergencia = new Representante($conexion);
                $emergencia->IdParentesco = $_POST['emergenciaParentesco'];
                $emergencia->IdEstudiante = $idEstudiante;
                $emergencia->nombre_contacto = $_POST['emergenciaNombre'];
                $emergencia->telefono_contacto = $_POST['emergenciaCelular'];
                $emergencia->nacionalidad = NULL;
                $emergencia->telefono_contacto = $_POST['emergenciaCelular'];

                // --- INICIO DEBUG EN CONTROLADOR ---
                error_log("Datos de emergencia recibidos en el controlador: " . print_r($_POST, true));
                // --- FIN DEBUG EN CONTROLADOR ---
                
                if (!$emergencia->guardarContactoEmergencia()) {
                    throw new Exception("Error al guardar el contacto de emergencia");
                }
            }
            
            // --- Generar código de inscripción: AÑO-NRO_CORRELATIVO ---
            $anioActual = date('Y');
            $sql = "SELECT COUNT(*) FROM inscripcion WHERE YEAR(fecha_inscripcion) = :anio";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':anio', $anioActual, PDO::PARAM_INT);
            $stmt->execute();
            $correlativo = $stmt->fetchColumn() + 1;

            $codigo_inscripcion = "$anioActual-$correlativo";

            // Crear inscripción
            $inscripcion = new Inscripcion($conexion);
            $inscripcion->IdEstudiante = $idEstudiante;
            $inscripcion->fecha_inscripcion = date('Y-m-d H:i:s');
            $inscripcion->ultimo_plantel = $_POST['ultimoPlantel'] ?? '';
            $inscripcion->nro_hermanos = $_POST['nroHermanos'] ?? 0;
            if (!$idRelacionRepresentante) {
                throw new Exception("No se pudo determinar el responsable de la inscripción");
            }
            $inscripcion->responsable_inscripcion = $idRelacionRepresentante;
            
            // Obtener año escolar activo
            $modeloFechaEscolar = new FechaEscolar($conexion);
            $anioEscolar = $modeloFechaEscolar->obtenerActivo();
            $inscripcion->IdFecha_Escolar = $anioEscolar['IdFecha_Escolar'];
            
            $inscripcion->IdEstado_Inscripcion = 1; // Pendiente de aprobación
            $inscripcion->IdCurso = $_POST['idCurso'] ?? null;
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
            
        } catch (Exception $e) {
            $conexion->rollback();
            throw $e;

            if ($conexion->inTransaction()) {
                $conexion->rollback();
            }
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