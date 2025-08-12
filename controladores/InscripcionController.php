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

error_log("Ruta actual del error_log: " . ini_get('error_log'));
date_default_timezone_set('America/Caracas');

// Crear conexión
$database = new Database();
$conexion = $database->getConnection();

header('Content-Type: application/json');

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
            // Crear persona (estudiante)
            $personaEstudiante = new Persona($conexion);
            $personaEstudiante->IdStatus = 2; // Establecer IdStatus = 2 para el estudiante
            $personaEstudiante->IdNacionalidad = (int)$_POST['estudianteNacionalidad'];
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
            if (!$detallePerfilEstudiante->guardar()) {
                throw new Exception("Error al asignar perfil de estudiante");
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
            
            // Guardar información del padre (si se proporcionó)
            if (!empty($_POST['padreNombres']) || !empty($_POST['padreApellidos'])) {
                $personaPadre = new Persona($conexion);
                $personaPadre->IdStatus = 2; // Establecer IdStatus = 2 para el padre
                $personaPadre->IdNacionalidad = (int)$_POST['padreNacionalidad'];
                $personaPadre->cedula = $_POST['padreCedula'] ?? '';
                $personaPadre->nombre = $_POST['padreNombres'] ?? '';
                $personaPadre->apellido = $_POST['padreApellidos'] ?? '';
                $personaPadre->correo = $_POST['padreCorreo'] ?? '';
                $personaPadre->direccion = $_POST['padreDireccion'] ?? '';
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
                $representantePadre->lugar_trabajo = trim($_POST['padreLugarTrabajo'] ?? '');
                $idRepresentantePadre = $representantePadre->guardar();
                if (!$idRepresentantePadre) {
                    throw new Exception("Error al crear relación padre-estudiante");
                }

                // Asignar perfil de representante (IdPerfil = 4)
                $detallePerfil = new DetallePerfil($conexion);
                $detallePerfil->IdPerfil = 4;
                $detallePerfil->IdPersona = $idPadre;
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar perfil de representante (padre)");
                }
            }

            // Guardar información de la madre (si se proporcionó)
            if (!empty($_POST['madreNombres']) || !empty($_POST['madreApellidos'])) {
                $personaMadre = new Persona($conexion);
                $personaMadre->IdStatus = 2; // Establecer IdStatus = 2 para la madre
                $personaMadre->IdNacionalidad = (int)$_POST['madreNacionalidad'];
                $personaMadre->cedula = $_POST['madreCedula'] ?? '';
                $personaMadre->nombre = $_POST['madreNombres'] ?? '';
                $personaMadre->apellido = $_POST['madreApellidos'] ?? '';
                $personaMadre->correo = $_POST['madreCorreo'] ?? '';
                $personaMadre->direccion = $_POST['madreDireccion'] ?? '';
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
                $representanteMadre->lugar_trabajo = trim($_POST['madreLugarTrabajo'] ?? '');
                $idRepresentanteMadre = $representanteMadre->guardar();
                if (!$idRepresentanteMadre) {
                    throw new Exception("Error al crear relación madre-estudiante");
                }

                // Asignar perfil de representante (IdPerfil = 4)
                $detallePerfil = new DetallePerfil($conexion);
                $detallePerfil->IdPerfil = 4;
                $detallePerfil->IdPersona = $idMadre;
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

                $personaRepresentante = new Persona($conexion);
                $personaRepresentante->IdStatus = 2; // Establecer IdStatus = 2 para el representante
                $personaRepresentante->IdNacionalidad = (int)$_POST['representanteNacionalidad'];
                $personaRepresentante->cedula = $_POST['representanteCedula'];
                $personaRepresentante->nombre = $_POST['representanteNombres'];
                $personaRepresentante->apellido = $_POST['representanteApellidos'];
                $personaRepresentante->correo = $_POST['representanteCorreo'] ?? '';
                $personaRepresentante->direccion = $_POST['representanteDireccion'] ?? '';
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
                $representante->lugar_trabajo = trim($_POST['representanteLugarTrabajo'] ?? '');
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

            // Obtener año escolar activo
            $modeloFechaEscolar = new FechaEscolar($conexion);
            $anioEscolar = $modeloFechaEscolar->obtenerActivo();
            
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