
    <?php include 'layouts/header.php'; ?>
    
    <!-- Sección de Cursos Disponibles -->
    <div class="section-container" style="padding-top: 30px;"> <!-- Añadido padding-top -->
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- Espacio adicional en móviles -->
                <div class="d-block d-md-none" style="height: 60px;"></div>
                
                <?php
                require_once __DIR__ . '/../../modelos/Nivel.php';
                require_once __DIR__ . '/../../modelos/Curso.php';
                require_once __DIR__ . '/../../modelos/FechaEscolar.php';
                require_once __DIR__ . '/../../modelos/Parentesco.php';
                require_once __DIR__ . '/../../modelos/Urbanismo.php';
                require_once __DIR__ . '/../../modelos/Nacionalidad.php';
                require_once __DIR__ . '/../../modelos/Sexo.php';
                require_once __DIR__ . '/../../modelos/TipoTrabajador.php';
                require_once __DIR__ . '/../../config/conexion.php';
                $database = new Database();
                $conexionPDO = $database->getConnection();

                $modeloNivel = new Nivel($conexionPDO);
                $modeloCurso = new Curso($conexionPDO);
                $modeloParentesco = new Parentesco($conexionPDO);
                $modeloUrbanismo = new Urbanismo($conexionPDO);
                $modeloNacionalidad = new Nacionalidad($conexionPDO);
                $modeloSexo = new Sexo($conexionPDO);
                $modeloTipoTrabajador = new TipoTrabajador($conexionPDO);
                $modeloFechaEscolar = new FechaEscolar($conexionPDO);
                
                // Obtener año escolar activo
                $añoActivo = $modeloFechaEscolar->obtenerActivo();

                $inscripcionesAbiertas = $añoActivo && $añoActivo['inscripcion_activa'];
            
                ?>
                <?php if ($inscripcionesAbiertas): ?>
                <?php
                // Variable para preseleccionar el año
                $yearSelected = '';
                
                // Obtener todos los años escolares
                $añosEscolares = $modeloFechaEscolar->obtenerTodos();

                // Si hay un año activo, usarlo como predeterminado
                if ($añoActivo) {
                    $yearSelected = $añoActivo['IdFecha_Escolar'];
                } else {
                    // Si no hay año activo, usar el más reciente
                    if (!empty($añosEscolares)) {
                        $yearSelected = $añosEscolares[0]['IdFecha_Escolar'];
                    }
                }

                // Obtener todos los sexos
                $sexos = $modeloSexo->obtenerTodos();

                // Obtener todos las nacionalidades con nombres largos
                $nacionalidades = $modeloNacionalidad->obtenerConNombresLargos();

                // Obtener todos los urbanismos
                $urbanismos = $modeloUrbanismo->obtenerTodos();

                // Obtener todos los parentescos
                $parentescos = $modeloParentesco->obtenerTodos();

                // Obtener todos los tipos de trabajador
                $tiposTrabajador = $modeloTipoTrabajador->obtenerTodos();

                // Obtener año escolar activo
                $anioEscolar = $modeloFechaEscolar->obtenerActivo();
                ?>  
                
                    <h2 style="color: #c90000; margin-bottom: 1rem;">Solicitud de Cupo</h2>
                    <?php if ($anioEscolar) {
                    echo '<p class="text-muted mb-3"><i class="fas fa-calendar-alt mr-2"></i>Inscripción para el año escolar: <strong>' . $anioEscolar['fecha_escolar'] . '</strong></p>';
                    }
                    ?>
                    <p style="margin-bottom: 2rem;">Seleccione el curso al que desea solicitar cupo para su representado:</p>
                    
                    <?php
                    
                    // Obtener todos los niveles educativos
                    $niveles = $modeloNivel->obtenerTodos();
                    
                    foreach ($niveles as $nivel) {
                        echo '<h3 class="nivel-title">' . $nivel['nivel'] . '</h3>';
                        
                        // Obtener cursos para este nivel
                        $cursos = $modeloCurso->obtenerPorNivel($nivel['IdNivel']);
                        
                        if (count($cursos) > 0) {
                            echo '<table class="table-cursos">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Curso</th>';
                            echo '<th>Acciones</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            foreach ($cursos as $curso) {
                                echo '<tr>';
                                echo '<td>' . $curso['curso'] . '</td>';
                                echo '<td>';
                                echo '<button class="btn-registro" onclick="mostrarInformacionModal(' . $curso['IdCurso'] . ', ' . $nivel['IdNivel'] . ')">Solicitar Cupo</button>';
                                echo ' ';
                                echo '<button class="btn-requisitos" onclick="mostrarRequisitos(' . $nivel['IdNivel'] . ')">Ver Requisitos</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p>No hay cursos disponibles para este nivel.</p>';
                        }
                    }
                    ?>
    <?php else: ?>
        <!-- Mensaje cuando las inscripciones están cerradas -->
        <div class="text-center py-5 my-4" style="background-color: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); max-width: 800px; margin: 2rem auto; padding: 2rem;">
            <i class="fas fa-lock" style="font-size: 3rem; color: #c90000; margin-bottom: 1rem;"></i>
            <h4 style="color: #c90000; margin-bottom: 1rem;">Periodo de Inscripción Finalizado</h4>
            <p style="color: #555; font-size: 1.1rem; line-height: 1.6; max-width: 600px; margin: 0 auto;">
                El periodo de inscripción para el año escolar ha concluido. 
                <strong>No se aceptan nuevas solicitudes de cupo en este momento.</strong>
            </p>
            <p style="color: #666; font-size: 0.95rem; margin-top: 1.5rem;">
                Para más información, comuníquese con la oficina de administración al 
                <strong>+58 414-5641168</strong>.
            </p>
            <div class="mt-4">
                <i class="fas fa-info-circle" style="color: #c90000; margin-right: 8px;"></i>
                <small style="color: #888;">
                    Las inscripciones se reabrirán en el próximo periodo escolar.
                </small>
            </div>
        </div>
    <?php endif; ?>
    </div> <!-- Cierre de col-md-12 -->
    </div> <!-- Cierre de row -->
    </div> <!-- Cierre de container -->
    </div> <!-- Cierre de section-container -->
    
    <!-- Modal para mostrar requisitos -->
    <div class="modal fade modal-requisitos" id="requisitosModal" tabindex="-1" role="dialog" aria-labelledby="requisitosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requisitosModalLabel">
                        <i class="fas fa-file-alt mr-2"></i>Requisitos para Inscripción
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="requisitosModalBody">
                    <!-- Los requisitos se cargarán aquí dinámicamente -->
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2">Cargando requisitos...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cerrar-modal" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                    <a class="btn btn-requisitos" 
                        href="../registros/requisito/reporte_requisitos.php?nivel=<?= $nivel['IdNivel'] ?>" 
                        target="_blank">
                        Imprimir Requisitos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal informativo antes del formulario -->
    <div class="modal fade modal-informacion" id="informacionModal" tabindex="-1" role="dialog" aria-labelledby="informacionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 10px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="background-color: #c90000; color: white; border-radius: 10px 10px 0 0; padding: 1rem 1.5rem;">
                    <h5 class="modal-title" id="informacionModalLabel" style="color: white !important;">
                        <i class="fas fa-info-circle mr-2"></i> Instrucciones para la Solicitud de Cupo
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <!-- <p class="text-center mb-4" style="font-size: 1.1rem; color: #333;">
                        <strong>SIGA LAS SIGUIENTES INSTRUCCIONES PARA REALIZAR SU SOLICITUD DE CUPO:</strong>
                    </p> -->

                    <!-- Tabla para los pasos -->
                    <div class="table-responsive">
                        <table class="table table-bordered" style="border: 2px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 1.5rem;">
                            <thead>
                                <tr style="background-color: #c90000; color: white; text-align: center;">
                                    <th style="width: 10%; padding: 0.75rem; vertical-align: middle; font-size: 1.1rem;">#</th>
                                    <th style="padding: 0.75rem; vertical-align: middle; font-size: 1.05rem;">Instrucción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; background-color: #f8f9fa;">1</td>
                                    <td style="color: #444; font-size: 0.95rem; line-height: 1.6;">
                                        <strong>Siga las instrucciones</strong> a continuación, para realizar satisfactoriamente su solicitud de cupo.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; background-color: #f8f9fa;">2</td>
                                    <td style="color: #444; font-size: 0.95rem; line-height: 1.6;">
                                        <strong>Descargue y lea los requisitos</strong> para el nivel correspondiente, que deberán ser consignados en la institución el <strong>día en que se le notifique</strong>, de <strong>8:00 AM a 12:00 PM</strong>, en la oficina de Administración. <strong><a href="#" id="linkDescargarRequisitos" target="_blank">[Descargar requisitos]</a></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; background-color: #f8f9fa;">3</td>
                                    <td style="color: #444; font-size: 0.95rem; line-height: 1.6;">
                                        Oprima <strong>“Continuar”</strong> para llenar en línea la Solicitud de Cupo. Debe completar todos los campos obligatorios. Al finalizar, imprima el formulario para consignarlo junto con los demás recaudos.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; background-color: #f8f9fa;">4</td>
                                    <td style="color: #444; font-size: 0.95rem; line-height: 1.6;">
                                        Consigne <strong>todos los recaudos solicitados</strong> en la oficina de Administración. Si tiene dudas, comuníquese al <strong>+58 414-5641168</strong>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-3" style="background-color: #f0f7ff; border-color: #c9e2ff; color: #004085; font-size: 0.9rem; border-radius: 8px;">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Recordatorio:</strong> Tenga a mano los datos personales del estudiante, padres y representante legal para llenar el formulario sin interrupciones.
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: none; border-radius: 0 0 10px 10px; padding: 1rem;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 4px; padding: 0.5rem 1.2rem;">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnContinuarFormulario" style="background-color: #c90000; border-color: #c90000; border-radius: 4px; padding: 0.5rem 1.2rem;">
                        <i class="fas fa-arrow-right mr-1"></i> Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para imprimir planilla -->
    <div class="modal fade modal-imprimir-planilla" id="imprimirPlanillaModal" tabindex="-1" role="dialog" aria-labelledby="imprimirPlanillaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 10px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="background-color: #c90000; color: white; border-radius: 10px 10px 0 0;">
                    <h5 class="modal-title" id="imprimirPlanillaModalLabel" style="color: white !important;">
                        <i class="fas fa-print mr-2"></i> Imprimir planilla de Solicitud de Cupo
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="formImprimirPlanilla">
                        <!-- Año Escolar -->
                        <div class="form-group required-field mb-3">
                            <label for="anioEscolar">Indique Año Escolar en el que solicitó cupo</label>
                            <select class="form-control" id="anioEscolar" name="anioEscolar" required>
                                <?php foreach ($añosEscolares as $año): ?>
                                    <option value="<?= $año['IdFecha_Escolar'] ?>" <?= ($año['IdFecha_Escolar'] == $yearSelected) ? 'selected' : '' ?>>
                                        <?= $año['fecha_escolar'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nacionalidad y Cédula -->
                        <div class="form-group required-field mb-3">
                            <label>Documento del Estudiante o Representante</label>
                            <div class="input-group">
                                <!-- Selector de nacionalidad -->
                                <select class="form-select form-select-sm" name="nacionalidad" id="nacionalidad" style="
                                    max-width: 60px;
                                    border-top-right-radius: 0;
                                    border-bottom-right-radius: 0;
                                    border-right: none;
                                    text-align: center;
                                    font-weight: bold;
                                    background: #f8f9fa;
                                    color: #c90000;
                                    font-size: 0.9rem;
                                ">
                                    <?php foreach ($nacionalidades as $nacionalidad): ?>
                                        <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nombre_largo'] ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Campo de cédula -->
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="documentoEstudiante" 
                                    name="documentoEstudiante"
                                    placeholder="Ej: 12345678"
                                    minlength="7" 
                                    maxlength="11" 
                                    pattern="[0-9]+" 
                                    onkeypress="return onlyNumber(event)"
                                    required
                                    style="border-top-left-radius: 0; border-bottom-left-radius: 0;"
                                >
                            </div>
                            <p class="text-muted mt-1" style="font-size: 0.8rem;">
                                <i class="fas fa-info-circle mr-1"></i>
                                Ingrese solo el número de cédula.
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: none; border-radius: 0 0 10px 10px; padding: 1rem;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 4px; padding: 0.5rem 1.2rem;">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnImprimirPlanilla" style="background-color: #c90000; border-color: #c90000; border-radius: 4px; padding: 0.5rem 1.2rem;">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para el formulario de inscripción -->
    <div class="modal fade modal-formulario" id="formularioModal" tabindex="-1" role="dialog" aria-labelledby="formularioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #c90000; color: white;">
                    <h5 class="modal-title" id="formularioModalLabel">Formulario de Inscripción</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formInscripcion" data-origen="modal">
                        <input type="hidden" id="idCursoSeleccionado" name="idCurso">
                        <input type="hidden" id="idNivelSeleccionado" name="idNivelSeleccionado">
                        <input type="hidden" name="idTipoInscripcion" value="1">

                        <!-- Datos del Estudiante -->
                        <div class="card mb-4">
                            <div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#datosEstudiante">
                                <h5><i class="fas fa-child mr-2"></i>Datos del Estudiante</h5>
                            </div>
                            <div class="card-body collapse show" id="datosEstudiante">

                                <!-- Agregar esta leyenda al inicio del formulario -->
                                <div class="form-legend">
                                    <i class="fas fa-asterisk"></i> Campos obligatorios
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="estudianteSexo">Sexo</label>
                                            <select class="form-control" id="estudianteSexo" name="estudianteSexo" required>
                                                <option value="">Seleccione un sexo</option>
                                                <?php foreach ($sexos as $sexo): ?>
                                                    <option value="<?= $sexo['IdSexo'] ?>"><?= $sexo['sexo'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="estudianteFechaNacimiento">Fecha Nacimiento</label>
                                            <input type="date" class="form-control" id="estudianteFechaNacimiento" name="estudianteFechaNacimiento" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="estudianteNacionalidad">Nacionalidad</label>
                                            <select class="form-control" id="estudianteNacionalidad" name="estudianteNacionalidad" required>
                                                <option value="">Seleccione una nacionalidad</option>
                                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nombre_largo'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="estudianteCedulaContainer">
                                        <div class="form-group required-field">
                                            <label for="estudianteCedula">Cédula</label>
                                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula"
                                            minlength="7" maxlength="8"
                                            pattern="[0-9]+" onkeypress="return onlyNumber(event)" readonly required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Primero ingrese la fecha de nacimiento
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteApellidos">Apellidos</label>
                                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto2(this)" placeholder="Ej: Rodríguez Gómez" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteNombres">Nombres</label>
                                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1(this)" placeholder="Ej: Juan Carlos" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteLugarNacimiento">Lugar de Nacimiento</label>
                                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1(this)" placeholder="Ej: Araure, Portuguesa" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="estudianteTelefonoContainer" style="display: none;">
                                        <div class="form-group">
                                            <label for="estudianteTelefono">Teléfono <small class="text-muted">(Opcional)</small></label>
                                            <div class="input-group">
                                                <!-- Prefix selector -->
                                                <div class="position-relative" style="max-width: 90px;">
                                                    <input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                                                           id="estudianteTelefonoPrefijo_input" maxlength="4" data-prefijo-tipo="internacional"
                                                           onkeypress="return /[0-9+]/.test(event.key)"
                                                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                                           style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;"
                                                           value="+58">
                                                    <input type="hidden" id="estudianteTelefonoPrefijo" name="estudianteTelefonoPrefijo">
                                                    <input type="hidden" id="estudianteTelefonoPrefijo_nombre" name="estudianteTelefonoPrefijo_nombre">
                                                    <div id="estudianteTelefonoPrefijo_resultados" class="autocomplete-results d-none"></div>
                                                </div>

                                                <!-- Phone number input -->
                                                <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono"
                                                       minlength="10" maxlength="10"
                                                       pattern="[0-9]+" onkeypress="return onlyNumber(event)"
                                                       placeholder="4121234567"
                                                       style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            </div>
                                            <!-- <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Campo disponible para estudiantes de 10 años o más
                                            </small> -->
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="estudianteCorreo">Correo electrónico</label>
                                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo"
                                    minlength="10" maxlength="50" placeholder="Ej: estudiante@correo.com" required>
                                </div>

                                <div class="form-group required-field" id="estudiantePlantelContainer">
                                    <label for="estudiantePlantel">Plantel donde cursó el último año escolar</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control buscador-input" id="estudiantePlantel_input" autocomplete="off" placeholder="Buscar o escribir nuevo plantel...">
                                        <input type="hidden" id="estudiantePlantel" name="estudiantePlantel" required>
                                        <input type="hidden" id="estudiantePlantel_nombre" name="estudiantePlantel_nombre">
                                        <div id="estudiantePlantel_resultados" class="autocomplete-results d-none"></div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Busque o escriba el nombre del plantel educativo
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Discapacidades o condiciones especiales:</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="discapacidadesTable">
                                            <thead>
                                                <tr>
                                                    <th>Tipo de Discapacidad</th>
                                                    <th>Descripción</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="discapacidadesBody">
                                                <!-- Fila inicial se genera automáticamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" id="btn-agregar-discapacidad" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Agregar otra discapacidad
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Incluir funciones auxiliares para renderizar formularios
                        require_once __DIR__ . '/includes/form_persona_fields.php';

                        // Preparar opciones para los selects
                        $data_options = [
                            'nacionalidades' => $nacionalidades,
                            'urbanismos' => $urbanismos,
                            'parentescos' => $parentescos,
                            'tiposTrabajador' => $tiposTrabajador
                        ];

                        // Renderizar bloque de la Madre (con contacto de emergencia)
                        renderizarBloquePersona('madre', 'Datos de la Madre', 'fa-female', 'datosMadre', 'Madre', $data_options, '', true);

                        // Renderizar bloque del Padre
                        renderizarBloquePersona('padre', 'Datos del Padre', 'fa-male', 'datosPadre', 'Padre', $data_options, '', false);
                        ?>
                        
                        <div id="repAutoInfo" class="representante-auto" style="display: none;">
                            <i class="fas fa-info-circle mr-2"></i>
                            Se usará <span id="repSeleccionado">la madre</span> como representante legal
                        </div>

                        <div class="card mb-4">
                            <div class="card-header" style="background-color: #c90000; color: white;">
                                <h5><i class="fas fa-user-tie mr-2"></i>Representante Legal</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>El representante legal es:</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="repMadre" name="tipoRepresentante" class="custom-control-input" value="madre" checked>
                                                <label class="custom-control-label" for="repMadre">
                                                    <i class="fas fa-female mr-1"></i> La Madre
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="repPadre" name="tipoRepresentante" class="custom-control-input" value="padre">
                                                <label class="custom-control-label" for="repPadre">
                                                    <i class="fas fa-male mr-1"></i> El Padre
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" id="repOtro" name="tipoRepresentante" class="custom-control-input" value="otro">
                                                <label class="custom-control-label" for="repOtro">
                                                    <i class="fas fa-user-tie mr-1"></i> Otro
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Representante Legal -->
                        <div id="seccionRepresentante" style="display: none;">
                            <?php
                            // Renderizar bloque del Representante Legal (con Parentesco como select)
                            echo '<div class="card mb-4">';
                            echo '<div class="card-header" style="background-color: #c90000; color: white;">';
                            echo '<h5><i class="fas fa-user-tie mr-2"></i>Datos del Representante Legal</h5>';
                            echo '</div>';
                            echo '<div class="card-body">';

                            // Renderizar campos organizados por filas
                            $fila_actual = [];
                            $cols_actuales = 0;

                            foreach ($campos_persona as $nombre_campo => $config) {
                                $fila_actual[] = ['nombre' => $nombre_campo, 'config' => $config];
                                $cols_actuales += $config['col'];

                                // Si completamos 12 columnas o es el último campo, renderizamos la fila
                                if ($cols_actuales >= 12 || $nombre_campo === array_key_last($campos_persona)) {
                                    echo '<div class="row">';
                                    foreach ($fila_actual as $item) {
                                        // Para el representante, el Parentesco debe ser un buscador
                                        if ($item['nombre'] === 'Parentesco') {
                                            $id = 'representanteParentesco';
                                            $name = 'representanteParentesco';
                                            $inputId = $id . '_input';
                                            $hiddenId = $id;
                                            $hiddenNombre = $id . '_nombre';
                                            $resultadosId = $id . '_resultados';

                                            echo '<div class="col-md-6">';
                                            echo '<div class="form-group required-field">';
                                            echo '<label for="' . $id . '">Parentesco</label>';
                                            echo '<div class="position-relative">';
                                            echo '<input type="text" class="form-control buscador-input" id="' . $inputId . '" autocomplete="off" placeholder="Buscar o escribir nuevo parentesco...">';
                                            echo '<input type="hidden" id="' . $hiddenId . '" name="' . $name . '" required>';
                                            echo '<input type="hidden" id="' . $hiddenNombre . '" name="' . $name . '_nombre">';
                                            echo '<div id="' . $resultadosId . '" class="autocomplete-results d-none"></div>';
                                            echo '</div>';
                                            echo '<script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                new BuscadorGenerico("' . $inputId . '", "' . $resultadosId . '", "parentesco", "' . $hiddenId . '", "' . $hiddenNombre . '");
                                            });
                                            </script>';
                                            echo '</div>';
                                            echo '</div>';
                                        } else {
                                            renderizarCampoPersona('representante', $item['nombre'], $item['config'], $data_options, '');
                                        }
                                    }
                                    echo '</div>';

                                    // Resetear para la siguiente fila
                                    $fila_actual = [];
                                    $cols_actuales = 0;
                                }
                            }

                            echo '</div>';
                            echo '</div>';
                            ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" style="background-color: #c90000; border-color: #c90000;" id="btnEnviarFormulario">Enviar Solicitud</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'layouts/social_menu.php'; ?>
    <?php include 'layouts/footer.php'; ?>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Tus scripts personalizados -->
    <script src="../../assets/js/buscador_generico.js"></script>
    <script src="../../assets/js/validaciones_solicitud.js?v=8"></script>
    <script src="../../assets/js/solicitud_cupo.js?v=18"></script>
    <script src="../../assets/js/validacion.js?v=4"></script>

    <script>
        // Configuración global de Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Inicializar buscadores
        document.addEventListener("DOMContentLoaded", function() {
            // Buscador de plantel
            new BuscadorGenerico(
                "estudiantePlantel_input",
                "estudiantePlantel_resultados",
                "plantel",
                "estudiantePlantel",
                "estudiantePlantel_nombre"
            );

            // Buscador de prefijo para teléfono de estudiante
            new BuscadorGenerico(
                "estudianteTelefonoPrefijo_input",
                "estudianteTelefonoPrefijo_resultados",
                "prefijo",
                "estudianteTelefonoPrefijo",
                "estudianteTelefonoPrefijo_nombre"
            );

            // Configurar restricciones de fecha de nacimiento (6-18 años)
            const fechaNacimientoInput = document.getElementById('estudianteFechaNacimiento');
            if (fechaNacimientoInput) {
                const hoy = new Date();

                // Fecha máxima: hace 6 años
                const fechaMax = new Date(hoy.getFullYear() - 6, hoy.getMonth(), hoy.getDate());

                // Fecha mínima: hace 18 años
                const fechaMin = new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate());

                // Formatear fechas a YYYY-MM-DD
                const formatoFecha = (fecha) => {
                    const year = fecha.getFullYear();
                    const month = String(fecha.getMonth() + 1).padStart(2, '0');
                    const day = String(fecha.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                fechaNacimientoInput.setAttribute('min', formatoFecha(fechaMin));
                fechaNacimientoInput.setAttribute('max', formatoFecha(fechaMax));

                // Validar cuando se cambia el valor
                fechaNacimientoInput.addEventListener('blur', function() {
                    const valorSeleccionado = this.value;
                    if (!valorSeleccionado) {
                        // Si borra la fecha, volver a bloquear cédula y ocultar teléfono
                        const cedulaInput = document.getElementById('estudianteCedula');
                        const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                        const telefonoInput = document.getElementById('estudianteTelefono');
                        const cedulaHelpText = cedulaInput?.nextElementSibling;

                        if (cedulaInput) {
                            cedulaInput.setAttribute('readonly', true);
                            cedulaInput.value = '';
                            if (cedulaHelpText) {
                                cedulaHelpText.style.display = 'block';
                            }
                        }

                        if (telefonoContainer) {
                            telefonoContainer.style.display = 'none';
                            if (telefonoInput) {
                                telefonoInput.value = '';
                                telefonoInput.removeAttribute('required');
                            }
                        }
                        return;
                    }

                    const fechaSeleccionada = new Date(valorSeleccionado + 'T00:00:00');
                    const hoy = new Date();

                    // Calcular edad
                    let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
                    const mes = hoy.getMonth() - fechaSeleccionada.getMonth();

                    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
                        edad--;
                    }

                    // Validar rango de edad
                    if (edad < 6 || edad > 18) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Edad no válida',
                            html: `La fecha de nacimiento seleccionada no es válida.<br><br>
                                   <strong>El estudiante debe tener entre 6 y 18 años.</strong><br>
                                   <small class="text-muted">Edad calculada: ${edad} años</small>`,
                            confirmButtonColor: '#c90000',
                            confirmButtonText: 'Entendido'
                        });
                        this.value = '';
                        this.classList.add('is-invalid');

                        // Bloquear cédula y ocultar teléfono si la edad no es válida
                        const cedulaInput = document.getElementById('estudianteCedula');
                        const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                        const telefonoInput = document.getElementById('estudianteTelefono');

                        if (cedulaInput) {
                            cedulaInput.setAttribute('readonly', true);
                            cedulaInput.value = '';
                        }

                        if (telefonoContainer) {
                            telefonoContainer.style.display = 'none';
                            if (telefonoInput) {
                                telefonoInput.value = '';
                                telefonoInput.removeAttribute('required');
                            }
                        }
                    } else {
                        this.classList.remove('is-invalid');

                        // Habilitar campo de cédula
                        const cedulaInput = document.getElementById('estudianteCedula');
                        const cedulaLabel = document.querySelector('label[for="estudianteCedula"]');
                        const cedulaHelpText = cedulaInput?.nextElementSibling;

                        if (cedulaInput) {
                            cedulaInput.removeAttribute('readonly');

                            // Ocultar el mensaje de ayuda
                            if (cedulaHelpText) {
                                cedulaHelpText.style.display = 'none';
                            }

                            // Ajustar label y maxlength según la edad
                            if (cedulaLabel) {
                                if (edad < 10) {
                                    // Menores de 10 años: Cédula escolar con maxlength 11, minlength 10
                                    cedulaLabel.textContent = 'Cédula escolar';
                                    cedulaInput.setAttribute('maxlength', '11');
                                    cedulaInput.setAttribute('minlength', '10');
                                } else {
                                    // 10 años o más: Cédula normal con maxlength 8, minlength 7
                                    cedulaLabel.textContent = 'Cédula';
                                    cedulaInput.setAttribute('maxlength', '8');
                                    cedulaInput.setAttribute('minlength', '7');
                                }
                            }
                        }

                        // Manejar visibilidad del campo teléfono según edad
                        const telefonoContainer = document.getElementById('estudianteTelefonoContainer');
                        const telefonoInput = document.getElementById('estudianteTelefono');

                        if (telefonoContainer && telefonoInput) {
                            if (edad < 10) {
                                // Menores de 10 años: ocultar teléfono
                                telefonoContainer.style.display = 'none';
                                telefonoInput.value = '';
                                telefonoInput.removeAttribute('required');

                                // Limpiar también el prefijo
                                const prefijoInput = document.getElementById('estudianteTelefonoPrefijo');
                                const prefijoInputVisible = document.getElementById('estudianteTelefonoPrefijo_input');
                                if (prefijoInput) prefijoInput.value = '';
                                if (prefijoInputVisible) prefijoInputVisible.value = '+58';
                            } else {
                                // 10 años o más: mostrar teléfono como opcional
                                telefonoContainer.style.display = 'block';
                                // No es required, es opcional
                                telefonoInput.removeAttribute('required');
                            }
                        }
                    }
                });
            }

            // Validación de correo electrónico en tiempo real
            const correoInput = document.getElementById('estudianteCorreo');
            if (correoInput) {
                correoInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    if (!email) return;

                    // Expresión regular para validar correo electrónico
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

                    if (!emailRegex.test(email)) {
                        this.classList.add('is-invalid');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Correo no válido',
                            html: 'Por favor ingrese un correo electrónico válido.<br><small class="text-muted">Ejemplo: estudiante@correo.com</small>',
                            confirmButtonColor: '#c90000',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            }
        });
    </script>
</body>
</html>