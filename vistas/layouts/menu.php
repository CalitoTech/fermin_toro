<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de la sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    // Destruir sesión y redirigir al login
    session_unset();
    session_destroy();

    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Sesión Inválida",
                text: "Tu sesión ha expirado o no es válida. Por favor, inicia sesión nuevamente.",
                icon: "warning",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../../login/login.php";
            });
        });
    </script>';
    exit;
}

$idPersona = $_SESSION['idPersona'];

// Conexión
global $conexion;
if (!isset($conexion)) {
    require_once __DIR__ . '/../../config/conexion.php';
    $database = new Database();
    $conexion = $database->getConnection();
}

try {
    // Consulta única para obtener nombre, apellido y perfil
    $sql = "SELECT
                p.nombre,
                p.apellido,
                pr.nombre_perfil,
                pr.IdPerfil
            FROM persona p
            INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
            INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
            WHERE p.IdPersona = :idPersona";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
    $stmt->execute();

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        // Usuario no encontrado - destruir sesión y redirigir
        error_log("Usuario no encontrado en BD - IdPersona: " . $idPersona);
        session_unset();
        session_destroy();

        echo '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "Usuario No Encontrado",
                    text: "No se encontró información del usuario. Por favor, contacta al administrador.",
                    icon: "error",
                    confirmButtonText: "Ir al Login",
                    confirmButtonColor: "#c90000"
                }).then(() => {
                    window.location.href = "../../login/login.php";
                });
            });
        </script>';
        exit;
    }

    // Variables de sesión adicionales
    $_SESSION['nombre'] = $userData['nombre'];
    $_SESSION['apellido'] = $userData['apellido'];
    $_SESSION['nombre_completo'] = $userData['nombre'] . ' ' . $userData['apellido'];
    $_SESSION['perfil'] = $userData['nombre_perfil'];
    $_SESSION['idPerfil'] = $userData['IdPerfil'];

    // Variables locales para mostrar en la vista
    $userNombre = $_SESSION['nombre'];
    $userApellido = $_SESSION['apellido'];
    $userPerfil = $_SESSION['perfil'];
    $idPerfil = $_SESSION['idPerfil'];

} catch (PDOException $e) {
    // Error de base de datos - registrar y redirigir
    error_log("Error crítico en menu.php: " . $e->getMessage());
    session_unset();
    session_destroy();

    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Error del Sistema",
                text: "Ocurrió un error al cargar tu información. Por favor, intenta nuevamente.",
                icon: "error",
                confirmButtonText: "Ir al Login",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../../login/login.php";
            });
        });
    </script>';
    exit;
}

// === CLASIFICAR PERFILES ===
// 1=Administrador, 2=Docente, 3=Estudiante, 4=Representante, 5=Contacto de Emergencia
// 6=Director, 7=Control de estudios, 8=Coordinador Inicial, 9=Coordinador Primaria, 10=Coordinador Media General

$perfilesExternos = [3, 4, 5]; // Estudiante, Representante, Contacto de Emergencia
$perfilesInternos = [1, 2, 6, 7, 8, 9, 10]; // Todos los trabajadores
$perfilesSinAcceso = [2, 3]; // Docente y Estudiante - sin acceso al menú completo
$perfiles_autorizados = [1, 6, 7]; // Administrador, Director, Control de Estudios

// Obtener TODOS los perfiles del usuario
$sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
$stmtPerfiles = $conexion->prepare($sqlPerfiles);
$stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
$stmtPerfiles->execute();
$todosLosPerfiles = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

// Determinar si el usuario tiene perfiles internos o externos
$tienePerfilInterno = !empty(array_intersect($todosLosPerfiles, $perfilesInternos));
$tienePerfilExterno = !empty(array_intersect($todosLosPerfiles, $perfilesExternos));
$esSinAcceso = in_array($idPerfil, $perfilesSinAcceso); // Solo el perfil activo actual

?>

<button class="mobile-menu-toggle" style="display: none;">
    <i class='bx bx-menu'></i>
</button>

<!-- Sidebar -->
<div class="sidebar close">
    <div class="logo-details">
        <img src="../../../assets/images/fermin.png" alt="Logo">
        <span class="logo_name">UECFT Araure</span>
    </div>

    <div class="sidebar-scroll">
        <ul class="nav-links">

            <!-- === MENÚ PARA DOCENTE Y ESTUDIANTE (Sin acceso completo) === -->
            <?php if ($esSinAcceso): ?>
                <li>
                    <a href="../../inicio/inicio/inicio.php">
                        <i class='bx bx-home-alt'></i>
                        <span class="link_name">Inicio</span>
                    </a>
                </li>
                
                <li>
                    <a href="../../configuracion/contrasena/contrasena.php">
                        <i class='bx bx-lock-alt'></i>
                        <span class="link_name">Cambiar Contraseña</span>
                    </a>
                </li>

            <!-- === MENÚ INTERNO (TRABAJADORES CON ACCESO) === -->
            <?php elseif ($tienePerfilInterno): ?>
                <!-- Inicio -->
                <li>
                    <a href="../../inicio/inicio/inicio.php">
                        <i class='bx bx-home-alt-2'></i>
                        <span class="link_name">Inicio</span>
                    </a>
                </li>

                <!-- Registro -->
                <li>
                    <div class="icon-link">
                        <a href="#">
                            <i class='bx bxs-graduation'></i>
                            <span class="link_name">Registro</span>
                        </a>
                        <i class='bx bxs-chevron-down arrow'></i>
                    </div>
                    <ul class="sub-menu">
                        
                        <?php if (in_array($idPerfil, $perfiles_autorizados)): ?>
                            <li><a href="../../registros/nivel/nivel.php">Niveles</a></li>
                            <li><a href="../../registros/seccion/seccion.php">Secciones</a></li>
                        <?php endif; ?>
                        <li><a href="../../registros/curso/curso.php">Cursos</a></li>
                        <li><a href="../../registros/aula/aula.php">Aulas</a></li>
                        <li><a href="../../registros/curso_seccion/curso_seccion.php">Curso/Sección</a></li>
                        <li><a href="../../registros/requisito/requisito.php">Requisitos</a></li>
                        <!-- <li><a href="../../registros/materia/materia.php">Materias</a></li> -->
                        <!-- Enlaces visibles SOLO para Administrador, Director y Control de Estudios -->
                        <?php if (in_array($idPerfil, $perfiles_autorizados)): ?>
                            <li><a href="../../registros/urbanismo/urbanismo.php">Urbanismos</a></li>
                            <li><a href="../../registros/status/status.php">Status</a></li>
                            <li><a href="../../registros/parentesco/parentesco.php">Parentescos</a></li>
                            <!-- <li><a href="../../registros/bloque/bloque.php">Bloques</a></li> -->
                            <!-- <li><a href="../../registros/tipo_grupo_interes/tipo_grupo_interes.php">Grupos de Interés</a></li> -->
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Estudiantes -->
                <li>
                    <div class="icon-link">
                        <a href="#">
                            <i class='bx bx-edit'></i>
                            <span class="link_name">Estudiantes</span>
                        </a>
                        <i class='bx bxs-chevron-down arrow'></i>
                    </div>
                    <ul class="sub-menu">
                        <li><a href="../../estudiantes/estudiante/estudiante.php">Estudiantes</a></li>
                        <li><a href="../../estudiantes/representante/representante.php">Representantes</a></li>
                        <!-- Enlaces visibles SOLO para Administrador, Director y Control de Estudios -->
                        <?php if (in_array($idPerfil, $perfiles_autorizados)): ?>
                            <li><a href="../../estudiantes/egreso/egreso.php">Egresos</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Inscripciones -->
                <li>
                    <div class="icon-link">
                        <a href="#">
                            <i class='bx bx-line-chart'></i>
                            <span class="link_name">Inscripciones</span>
                        </a>
                        <i class='bx bxs-chevron-down arrow'></i>
                    </div>
                    <ul class="sub-menu">
                        <li><a href="../../inscripciones/inscripcion/inscripcion.php">Inscripciones</a></li>
                    </ul>
                </li>

                <!-- Configuración -->
                <li>
                    <div class="icon-link">
                        <a href="#">
                            <i class='bx bx-cog'></i>
                            <span class="link_name">Configuración</span>
                        </a>
                        <i class='bx bxs-chevron-down arrow'></i>
                    </div>
                    <ul class="sub-menu">
                        <li><a href="../../configuracion/contrasena/contrasena.php">Contraseña</a></li>
                        <!-- Solo para Administrador, Director y Control de Estudios -->
                        <?php if (in_array($idPerfil, $perfiles_autorizados)): ?>
                            <li><a href="../../configuracion/usuario/usuario.php">Usuarios</a></li>
                            <li><a href="../../configuracion/fecha_escolar/fecha_escolar.php">Año Escolar</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>


            <!-- === MENÚ EXTERNO (ESTUDIANTE / REPRESENTANTE / CONTACTO) === -->
            <?php if ($tienePerfilExterno && !$esSinAcceso): ?>
                <?php if ($tienePerfilInterno): ?>
                <?php endif; ?>

                <?php if (!$tienePerfilInterno): ?>
                    <li>
                        <a href="../../inicio/inicio/inicio.php">
                            <i class='bx bx-home-alt'></i>
                            <span class="link_name">Inicio</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (in_array(4, $todosLosPerfiles) || in_array(5, $todosLosPerfiles)): ?>
                    <!-- Mis Representados (solo para Representante o Contacto de Emergencia) -->
                    <li>
                        <a href="../../representantes/representados/representado.php">
                            <i class='bx bx-user-voice'></i>
                            <span class="link_name">Mis Representados</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (!$tienePerfilInterno): ?>
                    <!-- Cambiar Contraseña (solo si no tiene perfil interno) -->
                    <li>
                        <a href="../../configuracion/contrasena/contrasena.php">
                            <i class='bx bx-lock-alt'></i>
                            <span class="link_name">Cambiar Contraseña</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Cerrar sesión (siempre visible) -->
            <li>
                <div class="icon-link">
                    <a href="../../../controladores/login/cerrar_sesion.php">
                        <i class='bx bx-log-out-circle'></i>
                        <span class="link_name">Cerrar Sesión</span>
                    </a>
                </div>
            </li>

            <!-- Perfil de usuario (siempre visible) -->
            <li>
                <div class="profile-details">
                    <div class="profile-content">
                        <img src="../../../assets/images/avatar.svg" alt="Perfil">
                    </div>
                    <div class="name-job">
                        <div class="profile_name"><?php echo htmlspecialchars($userNombre . ' ' . $userApellido); ?></div>
                        <div class="job"><?php echo htmlspecialchars($userPerfil); ?></div>
                    </div>
                    <a href="../../../controladores/login/cerrar_sesion.php"><i class='bx bx-log-out'></i></a>
                </div>
            </li>
        </ul>
    </div>
</div>

<!-- Sección Principal -->
<section class="home-section">
    <div class="home-content">
        <div class="left-section">
            <i class='bx bx-menu'></i>
            <div class="brand-text">
                <span class="acronym">SIGECI</span>
                <span class="separator">|</span>
                <span class="full-name">Sistema de Gestión y Control de Inscripciones</span>
            </div>
        </div>

        <div class="right-section">
            <div class="datetime">
                <i class='bx bx-calendar'></i>
                <span id="current-date-time"></span>
            </div>

            <div class="school-year">
                <i class='bx bxs-school'></i>
                <span>2025-2026</span>
            </div>
            <?php if (!$esSinAcceso && !in_array($idPerfil, [3, 4, 5])): ?>
                <div class="notification" id="notification-btn">
                    <i class='bx bxs-bell' id="notification-btn-icon"></i>
                    <span class="badge" id="notification-badge" style="display: none;">0</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="notification-panel" id="notification-panel">
        <div class="panel-header">
            <h4>Notificaciones</h4>
            <i class='bx bx-x' id="close-panel"></i>
        </div>
        <div class="panel-body">
            <!-- Contenido dinámico -->
        </div>
        <div class="panel-footer">
            <a href="#">Ver todas las notificaciones</a>
        </div>
    </div>
</section>
<script src="../../../assets/js/notificaciones.js"></script>