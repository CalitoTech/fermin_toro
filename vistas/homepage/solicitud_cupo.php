
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
                require_once __DIR__ . '/../../config/conexion.php';
                $database = new Database();
                $conexionPDO = $database->getConnection();

                $modeloNivel = new Nivel($conexionPDO);
                $modeloCurso = new Curso($conexionPDO);
                $modeloParentesco = new Parentesco($conexionPDO);
                $modeloUrbanismo = new Urbanismo($conexionPDO);
                $modeloNacionalidad = new Nacionalidad($conexionPDO);
                $modeloSexo = new Sexo($conexionPDO);
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

                // Obtener todos las nacionalidades
                $nacionalidades = $modeloNacionalidad->obtenerTodos();

                // Obtener todos los urbanismos
                $urbanismos = $modeloUrbanismo->obtenerTodos();

                // Obtener todos los parentescos
                $parentescos = $modeloParentesco->obtenerTodos();
                
                // Obtener año escolar activo
                $anioEscolar = $modeloFechaEscolar->obtenerActivo();
                ?>  
                
                    <h2 style="color: #c90000; margin-bottom: 1rem;">Solicitud de Cupo</h2>
                    <?php if ($anioEscolar) {
                    echo '<p class="text-muted mb-3"><i class="fas fa-calendar-alt mr-2"></i>Inscripción para el año escolar: <strong>' . $anioEscolar['fecha_escolar'] . '</strong></p>';
                    }
                    ?>
                    <p style="margin-bottom: 2rem;">Seleccione el curso al que desea inscribir a su representado:</p>
                    
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
                                echo '<button class="btn-registro" onclick="mostrarInformacionModal(' . $curso['IdCurso'] . ')">Inscribir</button>';
                                echo ' ';
                                echo '<button class="btn-requisitos" onclick="mostrarRequisitos(' . $nivel['IdNivel'] . ')">Ver Requisitos</button>';
                                echo ' ';
                                echo '<button class="btn-descargar-planilla" onclick="abrirModalImprimir(' . $curso['IdCurso'] . ')"><i class="fas fa-file-alt"></i>Descargar Planilla</button>';
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
                    <p class="text-center mb-4" style="font-size: 1.1rem; color: #333;">
                        <strong>SIGA LAS SIGUIENTES INSTRUCCIONES PARA REALIZAR SU SOLICITUD DE CUPO:</strong>
                    </p>

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
                        <strong>Consejo:</strong> Tenga a mano los datos personales del estudiante, padres y representante legal para llenar el formulario sin interrupciones.
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
                                        <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
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
                                    maxlength="8" 
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
                    <form id="formInscripcion">
                        <input type="hidden" id="idCursoSeleccionado" name="idCurso">
                        
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
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteApellidos">Apellidos</label>
                                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos" 
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto2()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteNombres">Nombres</label>
                                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres" 
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="estudianteNacionalidad">Nacionalidad</label>
                                            <select class="form-control" id="estudianteNacionalidad" name="estudianteNacionalidad" required>
                                                <option value="">Seleccione una nacionalidad</option>
                                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="estudianteCedula">Cédula</label>
                                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula"
                                            minlength="7" maxlength="8"
                                            pattern="[0-9]+" onkeypress="return onlyNumber(event)" required>
                                        </div>
                                    </div>
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
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteLugarNacimiento">Lugar de Nacimiento</label>
                                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteTelefono">Teléfono</label>
                                            <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono" 
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="estudianteCorreo">Correo electrónico</label>
                                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo" 
                                    minlength="10" maxlength="50" required>
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
                        
                        <!-- Datos de la Madre -->
                        <div class="card mb-4">
                            <div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#datosMadre">
                                <h5><i class="fas fa-female mr-2"></i>Datos de la Madre</h5>
                            </div>
                            <div class="card-body collapse" id="datosMadre">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreApellidos">Apellidos</label>
                                            <input type="text" class="form-control" id="madreApellidos" name="madreApellidos"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto2()"
                                            required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreNombres">Nombres</label>
                                            <input type="text" class="form-control" id="madreNombres" name="madreNombres"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="madreNacionalidad">Nacionalidad</label>
                                            <select class="form-control" id="madreNacionalidad" name="madreNacionalidad" required>
                                                <option value="">Seleccione una nacionalidad</option>
                                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="madreCedula">Cédula</label>
                                            <input type="text" class="form-control" id="madreCedula" name="madreCedula"
                                            minlength="7" maxlength="8"
                                            pattern="[0-9]+" onkeypress="return onlyNumber(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="madreParentesco">Parentesco</label>
                                            <input type="text" class="form-control" id="madreParentesco" name="madreParentesco" value="Madre" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="madreOcupacion">Ocupación</label>
                                    <input type="text" class="form-control" id="madreOcupacion" name="madreOcupacion"
                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                    minlength="3" maxlength="40"
                                    onkeypress="return onlyText(event)"
                                    oninput="formatearTexto1()" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreUrbanismo">Urbanismo / Sector</label>
                                            <select class="form-control" id="madreUrbanismo" name="madreUrbanismo" required>
                                                <option value="">Seleccione un urbanismo</option>
                                                <?php
                                                foreach ($urbanismos as $urbanismo) {
                                                    echo '<option value="'.$urbanismo['IdUrbanismo'].'">'.$urbanismo['urbanismo'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreDireccion">Dirección de Habitación</label>
                                            <input type="text" class="form-control" id="madreDireccion" name="madreDireccion"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="madreTelefonoHabitacion" name="madreTelefonoHabitacion"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreCelular">Celular</label>
                                            <input type="tel" class="form-control" id="madreCelular" name="madreCelular"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="madreCorreo" name="madreCorreo"
                                            minlength="10" maxlength="50" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="madreLugarTrabajo" name="madreLugarTrabajo"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="madreTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="madreTelefonoTrabajo"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" name="madreTelefonoTrabajo">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="emergenciaNombre">En caso de emergencia, llamar a:</label>
                                            <input type="text" class="form-control" id="emergenciaNombre" name="emergenciaNombre"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="emergenciaParentesco">Parentesco</label>
                                            <select class="form-control" id="emergenciaParentesco" name="emergenciaParentesco" required>
                                                <option value="">Seleccione un parentesco</option>
                                                <?php
                                                foreach ($parentescos as $parentesco) {
                                                    if ($parentesco['IdParentesco'] >= 3) {
                                                        echo '<option value="'.$parentesco['IdParentesco'].'">'.$parentesco['parentesco'].'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="emergenciaCelular">Celular</label>
                                            <input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Padre -->
                        <div class="card mb-4">
                            <div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#datosPadre">
                                <h5><i class="fas fa-male mr-2"></i>Datos del Padre</h5>
                            </div>
                            <div class="card-body collapse" id="datosPadre">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreApellidos">Apellidos</label>
                                            <input type="text" class="form-control" id="padreApellidos" name="padreApellidos"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto2()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreNombres">Nombres</label>
                                            <input type="text" class="form-control" id="padreNombres" name="padreNombres"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="padreNacionalidad">Nacionalidad</label>
                                            <select class="form-control" id="padreNacionalidad" name="padreNacionalidad" required>
                                                <option value="">Seleccione una nacionalidad</option>
                                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="padreCedula">Cédula</label>
                                            <input type="text" class="form-control" id="padreCedula" name="padreCedula"
                                            minlength="7" maxlength="8"
                                            pattern="[0-9]+" onkeypress="return onlyNumber(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="padreParentesco">Parentesco</label>
                                            <input type="text" class="form-control" id="padreParentesco" name="padreParentesco" value="Padre" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="padreOcupacion">Ocupación</label>
                                    <input type="text" class="form-control" id="padreOcupacion" name="padreOcupacion"
                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                    minlength="3" maxlength="40"
                                    onkeypress="return onlyText(event)"
                                    oninput="formatearTexto1()" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreUrbanismo">Urbanismo / Sector</label>
                                            <select class="form-control" id="padreUrbanismo" name="padreUrbanismo" required>
                                                <option value="">Seleccione un urbanismo</option>
                                                <?php
                                                foreach ($urbanismos as $urbanismo) {
                                                    echo '<option value="'.$urbanismo['IdUrbanismo'].'">'.$urbanismo['urbanismo'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreDireccion">Dirección de Habitación</label>
                                            <input type="text" class="form-control" id="padreDireccion" name="padreDireccion"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="padreTelefonoHabitacion" name="padreTelefonoHabitacion"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreCelular">Celular</label>
                                            <input type="tel" class="form-control" id="padreCelular" name="padreCelular"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="padreCorreo" name="padreCorreo"
                                            minlength="10" maxlength="50" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="padreLugarTrabajo" name="padreLugarTrabajo"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="padreTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="padreTelefonoTrabajo"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)" name="padreTelefonoTrabajo">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
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
                        <div class="card mb-4" id="seccionRepresentante" style="display: none;">
                            <div class="card-header" style="background-color: #c90000; color: white;">
                                <h5><i class="fas fa-user-tie mr-2"></i>Datos del Representante Legal</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteApellidos">Apellidos</label>
                                            <input type="text" class="form-control" id="representanteApellidos" name="representanteApellidos"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto2()">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteNombres">Nombres</label>
                                            <input type="text" class="form-control" id="representanteNombres" name="representanteNombres"
                                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                            minlength="3" maxlength="40"
                                            onkeypress="return onlyText(event)"
                                            oninput="formatearTexto1()">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="representanteNacionalidad">Nacionalidad</label>
                                            <select class="form-control" id="representanteNacionalidad" name="representanteNacionalidad">
                                                <option value="">Seleccione una nacionalidad</option>
                                                <?php foreach ($nacionalidades as $nacionalidad): ?>
                                                    <option value="<?= $nacionalidad['IdNacionalidad'] ?>"><?= $nacionalidad['nacionalidad'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group required-field">
                                            <label for="representanteCedula">Cédula</label>
                                            <input type="text" class="form-control" id="representanteCedula" name="representanteCedula"
                                            minlength="7" maxlength="8"
                                            pattern="[0-9]+" onkeypress="return onlyNumber(event)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="representanteParentesco">Parentesco</label>
                                            <select class="form-control required-field" id="representanteParentesco" name="representanteParentesco">
                                                <option value="">Seleccione un parentesco</option>
                                                <?php
                                                foreach ($parentescos as $parentesco) {
                                                    if ($parentesco['IdParentesco'] >= 3) {
                                                        echo '<option value="'.$parentesco['IdParentesco'].'">'.$parentesco['parentesco'].'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="representanteOcupacion">Ocupación</label>
                                    <input type="text" class="form-control" id="representanteOcupacion" name="representanteOcupacion"
                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                                    minlength="3" maxlength="40"
                                    onkeypress="return onlyText(event)"
                                    oninput="formatearTexto1()">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteUrbanismo">Urbanismo / Sector</label>
                                            <select class="form-control" id="representanteUrbanismo" name="representanteUrbanismo">
                                                <option value="">Seleccione un urbanismo</option>
                                                <?php
                                                foreach ($urbanismos as $urbanismo) {
                                                    echo '<option value="'.$urbanismo['IdUrbanismo'].'">'.$urbanismo['urbanismo'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteDireccion">Dirección de Habitación</label>
                                            <input type="text" class="form-control" id="representanteDireccion" name="representanteDireccion"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="representanteTelefonoHabitacion" name="representanteTelefonoHabitacion"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteCelular">Celular</label>
                                            <input type="tel" class="form-control" id="representanteCelular" name="representanteCelular"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="representanteCorreo" name="representanteCorreo"
                                            minlength="10" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="representanteLugarTrabajo" name="representanteLugarTrabajo"
                                            minlength="3" maxlength="40"
                                            oninput="formatearTexto1()">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="representanteTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="representanteTelefonoTrabajo" name="representanteTelefonoTrabajo"
                                            minlength="11" maxlength="20"
                                            pattern="[0-9]+" onkeypress="return onlyNumber2(event)">
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    <?php include 'layouts/footer.php'; ?>
    
    <!-- Javascript files-->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery-3.0.0.min.js"></script>
  
    <!-- sidebar -->
    <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="js/custom.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../assets/js/solicitud_cupo.js"></script>
    <script src="../../assets/js/validacion.js"></script>
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
    </script>
</body>
</html>