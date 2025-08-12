<!DOCTYPE html>
<html lang="es">
<head>
    <!-- basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- mobile metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <!-- site metas -->
    <title>UECFT Araure - Solicitud de Cupo</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- bootstrap css -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- style css -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Responsive-->
    <link rel="stylesheet" href="css/responsive.css">
    <!-- fevicon -->
    <link rel="icon" href="images/fermin.png"/>
    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
    <!-- Tweaks for older IEs-->
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="../../assets/css/solicitud_cupo.css">
</head>
<body class="main-layout">
    <!-- loader  -->
    <div class="loader_bg">
        <div class="loader"><img src="images/loading.gif" alt="#" /></div>
    </div>
    <!-- end loader -->
    <!-- header -->
    <header>
        <!-- header inner -->
        <div class="header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 col logo_section">
                        <div class="full">
                            <div class="center-desk">
                                <div class="logo">
                                    <a href="index.html"><img src="images/fermin.jpg" alt="#" /></a>
                                </div>
                                <div class="logo2">
                                    <a href="index.html">UECFT Araure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-9 col-lg-9 col-md-9 col-sm-9">
                        <div class="header_information">
                            <nav class="navigation navbar navbar-expand-md navbar-dark ">
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample04" aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                                </button>
                                <div class="collapse navbar-collapse" id="navbarsExample04">
                                    <ul class="navbar-nav mr-auto">
                                        <li class="nav-item">
                                            <a class="nav-link" href="about.html">¿Quiénes Somos?</a>
                                        </li> 
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownNiveles" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Niveles E.</a>
                                            <div class="dropdown-menu" aria-labelledby="navbarDropdownNiveles">
                                                <a class="dropdown-item" href="#">Preescolar</a>
                                                <a class="dropdown-item" href="#">Primaria</a>
                                                <a class="dropdown-item" href="#">Media General</a>
                                            </div>
                                        </li> 
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMultimedia" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Multimedia</a>
                                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMultimedia">
                                                <a class="dropdown-item" href="nuestravoz.html">Nuestra Voz</a>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="solicitud_cupo.php">Solicitud de Cupo</a>
                                        </li>
                                        <!-- Botón de Iniciar Sesión como elemento del menú en móviles -->
                                        <li class="nav-item d-block d-md-none">
                                            <a class="nav-link btn-login" href="../login/login.php">Iniciar Sesión</a>
                                        </li>
                                    </ul>
                                    <!-- Botón de Iniciar Sesión fuera del menú en desktop -->
                                    <div class="sign_btn d-none d-md-block"><a href="../login/login.php">Iniciar Sesión</a></div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- end header inner -->
    <!-- end header -->
    
    <!-- Sección de Cursos Disponibles -->
    <div class="section-container" style="padding-top: 30px;"> <!-- Añadido padding-top -->
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- Espacio adicional en móviles -->
                <div class="d-block d-md-none" style="height: 60px;"></div>
                
                <h2 style="color: #c90000; margin-bottom: 1rem;">Solicitud de Cupo</h2>
                
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
                $modeloFechaEscolar = new FechaEscolar($conexionPDO);
                $modeloParentesco = new Parentesco($conexionPDO);
                $modeloUrbanismo = new Urbanismo($conexionPDO);
                $modeloNacionalidad = new Nacionalidad($conexionPDO);
                $modeloSexo = new Sexo($conexionPDO);

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
                
                if ($anioEscolar) {
                    echo '<p class="text-muted mb-3"><i class="fas fa-calendar-alt mr-2"></i>Inscripción para el año escolar: <strong>' . $anioEscolar['fecha_escolar'] . '</strong></p>';
                }
                ?>
                    <p style="margin-bottom: 2rem;">Seleccione el curso al que desea inscribir a su representado:</p>
                    
                    <?php
                    
                    // Obtener todos los niveles educativos
                    $niveles = $modeloNivel->obtenerTodos();
                    
                    foreach ($niveles as $nivel) {
                        echo '<h3 class="nivel-title">Nivel ' . $nivel['nivel'] . '</h3>';
                        
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
                                echo '<button class="btn-registro" onclick="abrirFormulario(' . $curso['IdCurso'] . ')">Inscribir</button>';
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
                </div>
            </div>
        </div>
    </div>
    
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
                                            <input type="text" class="form-control" id="estudianteApellidos" name="estudianteApellidos" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteNombres">Nombres</label>
                                            <input type="text" class="form-control" id="estudianteNombres" name="estudianteNombres" required>
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
                                            <input type="text" class="form-control" id="estudianteCedula" name="estudianteCedula" required>
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
                                            <input type="text" class="form-control" id="estudianteLugarNacimiento" name="estudianteLugarNacimiento" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="estudianteTelefono">Teléfono</label>
                                            <input type="tel" class="form-control" id="estudianteTelefono" name="estudianteTelefono" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group required-field">
                                    <label for="estudianteCorreo">Correo electrónico</label>
                                    <input type="email" class="form-control" id="estudianteCorreo" name="estudianteCorreo" required>
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
                                            <input type="text" class="form-control" id="madreApellidos" name="madreApellidos" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreNombres">Nombres</label>
                                            <input type="text" class="form-control" id="madreNombres" name="madreNombres" required>
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
                                            <input type="text" class="form-control" id="madreCedula" name="madreCedula" required>
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
                                    <input type="text" class="form-control" id="madreOcupacion" name="madreOcupacion" required>
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
                                            <input type="text" class="form-control" id="madreDireccion" name="madreDireccion" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="madreTelefonoHabitacion" name="madreTelefonoHabitacion" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreCelular">Celular</label>
                                            <input type="tel" class="form-control" id="madreCelular" name="madreCelular" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="madreCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="madreCorreo" name="madreCorreo" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="madreLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="madreLugarTrabajo" name="madreLugarTrabajo" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="madreTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="madreTelefonoTrabajo" name="madreTelefonoTrabajo">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="emergenciaNombre">En caso de emergencia, llamar a:</label>
                                            <input type="text" class="form-control" id="emergenciaNombre" name="emergenciaNombre" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="emergenciaParentesco">Parentesco</label>
                                            <select class="form-control" id="emergenciaParentesco" name="emergenciaParentesco" required>
                                                <option value="">Seleccione un parentesco</option>
                                                <?php
                                                foreach ($parentescos as $parentesco) {
                                                    if ($parentesco['IdParentesco'] > 3) {
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
                                            <input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular" required>
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
                                            <input type="text" class="form-control" id="padreApellidos" name="padreApellidos" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreNombres">Nombres</label>
                                            <input type="text" class="form-control" id="padreNombres" name="padreNombres" required>
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
                                            <input type="text" class="form-control" id="padreCedula" name="padreCedula" required>
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
                                    <input type="text" class="form-control" id="padreOcupacion" name="padreOcupacion" required>
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
                                            <input type="text" class="form-control" id="padreDireccion" name="padreDireccion" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="padreTelefonoHabitacion" name="padreTelefonoHabitacion" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreCelular">Celular</label>
                                            <input type="tel" class="form-control" id="padreCelular" name="padreCelular" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="padreCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="padreCorreo" name="padreCorreo" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="padreLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="padreLugarTrabajo" name="padreLugarTrabajo" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="padreTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="padreTelefonoTrabajo" name="padreTelefonoTrabajo">
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
                                            <input type="text" class="form-control" id="representanteApellidos" name="representanteApellidos">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteNombres">Nombres</label>
                                            <input type="text" class="form-control" id="representanteNombres" name="representanteNombres">
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
                                            <input type="text" class="form-control" id="representanteCedula" name="representanteCedula">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="representanteParentesco">Parentesco</label>
                                            <select class="form-control required-field" id="representanteParentesco" name="representanteParentesco">
                                                <option value="">Seleccione un parentesco</option>
                                                <?php
                                                foreach ($parentescos as $parentesco) {
                                                    if ($parentesco['IdParentesco'] > 3) {
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
                                    <input type="text" class="form-control" id="representanteOcupacion" name="representanteOcupacion">
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
                                            <input type="text" class="form-control" id="representanteDireccion" name="representanteDireccion">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteTelefonoHabitacion">Teléfono de Habitación</label>
                                            <input type="tel" class="form-control" id="representanteTelefonoHabitacion" name="representanteTelefonoHabitacion">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteCelular">Celular</label>
                                            <input type="tel" class="form-control" id="representanteCelular" name="representanteCelular">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group required-field">
                                            <label for="representanteCorreo">Correo electrónico</label>
                                            <input type="email" class="form-control" id="representanteCorreo" name="representanteCorreo">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group required-field">
                                            <label for="representanteLugarTrabajo">Lugar de Trabajo</label>
                                            <input type="text" class="form-control" id="representanteLugarTrabajo" name="representanteLugarTrabajo">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="representanteTelefonoTrabajo">Teléfono del Trabajo</label>
                                            <input type="tel" class="form-control" id="representanteTelefonoTrabajo" name="representanteTelefonoTrabajo">
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

    <!-- Navbar with Social Media Icons -->
    <nav class="navbar navbar-custom" id="redes">
        <div class="container-fluid">
            <div class="d-flex w-100">
                <p class="navbar-text mb-0">
                    © 2025 Derechos Reservados.
                </p>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="https://www.instagram.com/uecftaraure/"><i class="fab fa-instagram"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="https://www.youtube.com/@NuestraVozRadioyTv"><i class="fab fa-youtube"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="mailto:fermin.toro.araure@gmail.com"><i class="fab fa-google"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="https://api.whatsapp.com/send?phone=584145641168"><i class="fab fa-whatsapp"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="https://maps.app.goo.gl/XRBXfB6ygZDZaS3t7"><i class="fas fa-map-marker-alt"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="padding: 0 0.25rem; font-size: 14px;" href="https://www.uefermintoroaraure.com/nuestravoz.html"><i class="fas fa-microphone"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar -->
    
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