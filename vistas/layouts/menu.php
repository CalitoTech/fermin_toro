<?php

// Verificación de la sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    die('Error: La sesión no está configurada correctamente.');
}

$idPersona = $_SESSION['idPersona'];

// Usar la conexión ya definida en tu sistema
// Asumimos que $conexion ya existe (si no, lo creamos)
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
        die('Error: No se encontró el usuario en la base de datos.');
    }

    $userNombre = $userData['nombre'];
    $userApellido = $userData['apellido'];
    $userPerfil = $userData['nombre_perfil'];
    $idPerfil = $userData['IdPerfil']; // Si también necesitas el ID

    // Después de obtener los datos del usuario
    $_SESSION['nombre'] = $userData['nombre'];
    $_SESSION['apellido'] = $userData['apellido'];
    $_SESSION['nombre_completo'] = $userData['nombre'] . ' ' . $userData['apellido'];
    $_SESSION['perfil'] = $userData['nombre_perfil'];
    $_SESSION['idPerfil'] = $userData['IdPerfil'];

} catch (PDOException $e) {
    error_log("Error en menu.php: " . $e->getMessage());
    // Mostrar mensaje genérico al usuario
    $userNombre = 'Usuario';
    $userApellido = '';
    $userPerfil = 'Error';
    $idPerfil = null;
}
?>

<button class="mobile-menu-toggle" style="display: none;">
    <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Lateral -->
<div class="sidebar close">
    <div class="logo-details">
        <img src="../../../assets/images/fermin.png" alt="Logo">
        <span class="logo_name">UECFT Araure</span>
    </div>
    <ul class="nav-links">
        <!-- Inicio -->
        <li>
            <a href="../inicio/inicio.php">
                <i class='bx bx-home-alt-2'></i>
                <span class="link_name">Inicio</span>
            </a>
        </li>

        <!-- Estudiantes -->
        <li>
            <div class="icon-link">
                <a href="#">
                    <i class='bx bxs-graduation'></i>
                    <span class="link_name">Registro</span>
                </a>
                <i class='bx bxs-chevron-down arrow'></i>
            </div>
            <ul class="sub-menu">
                <!-- <li><a href="../../registros/nivel/nivel.php">Niveles</a></li> -->
                <li><a href="../../registros/curso/curso.php">Cursos</a></li>
                <li><a href="../../registros/seccion/seccion.php">Secciones</a></li>
                <!-- <li><a href="../../registros/aula/aula.php">Aulas</a></li> -->
                <li><a href="../../registros/curso_seccion/curso_seccion.php">Curso/Sección</a></li>
                <li><a href="../../registros/requisito/requisito.php">Requisitos</a></li>
                <li><a href="../../registros/urbanismo/urbanismo.php">Urbanismos</a></li>
                <li><a href="../../registros/status/status.php">Status</a></li>
                <li><a href="../../registros/parentesco/parentesco.php">Parentescos</a></li>
                <li><a href="../../registros/materia/materia.php">Materias</a></li>
                <li><a href="../../registros/bloque/bloque.php">Bloques</a></li>
                <li><a href="../../registros/tipo_grupo_interes/tipo_grupo_interes.php">Grupos de Interés</a></li>
            </ul>
        </li>

        <!-- Inscripciones -->
        <li>
            <div class="icon-link">
                <a href="#">
                    <i class='bx bx-edit'></i>
                    <span class="link_name">Estudiantes</span>
                </a>
                <i class='bx bxs-chevron-down arrow'></i>
            </div>
            <ul class="sub-menu">
                <li><a href="#">Estudiantes</a></li>
                <li><a href="#">Representantes</a></li>
                <li><a href="#">Est. con Dificultades</a></li>
                <li><a href="#">Horarios</a></li>
                <li><a href="#">Egresos</a></li>
            </ul>
        </li>

        <!-- Reportes (solo para Perfil 1 o 2) -->
        <?php if ($idPerfil == 1 || $idPerfil == 2): ?>
        <li>
            <div class="icon-link">
                <a href="#">
                    <i class='bx bx-line-chart'></i>
                    <span class="link_name">Inscripciones</span>
                </a>
                <i class='bx bxs-chevron-down arrow'></i>
            </div>
            <ul class="sub-menu">
                <li><a href="#">Inscripciones</a></li>
                <li><a href="#">Insc. Grupo C.</a></li>
                <li><a href="#">Insc. Pendientes</a></li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Configuración (solo para Perfil 1: Admin) -->
        <?php if ($idPerfil == 1): ?>
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
                <li><a href="../../configuracion/usuario/usuario.php">Usuarios</a></li>
                <li><a href="../../configuracion/fecha_escolar/fecha_escolar.php">Año Escolar</a></li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Cerrar sesión -->
        <li>
            <div class="icon-link">
                <a href="../../../controladores/login/cerrar_sesion.php">
                    <i class='bx bx-log-out-circle'></i>
                    <span class="link_name">Cerrar Sesión</span>
                </a>
            </div>
        </li>

        <!-- Perfil de usuario -->
        <li>
            <div class="profile-details">
                <div class="profile-content">
                    <img src="../../../assets/images/avatar.svg" alt="Perfil">
                </div>
                <div class="name-job">
                    <div class="profile_name"><?php echo htmlspecialchars($userNombre . ' ' . $userApellido); ?></div>
                    <div class="job"><?php echo htmlspecialchars($userPerfil); ?></div>
                </div>
                <a href="../../controladores/login/cerrar_sesion.php"><i class='bx bx-log-out'></i></a>
            </div>
        </li>
    </ul>
</div>

<!-- Sección Principal -->
<section class="home-section">
    <div class="home-content">
        <!-- Menú y Título -->
        <div class="left-section">
            <i class='bx bx-menu'></i>
            <span class="text">Sistema de Inscripción Escolar - UECFT Araure</span>
        </div>

        <!-- Contenido Derecho: Fecha, Año Escolar, Notificaciones -->
        <div class="right-section">
            <!-- Fecha y Hora -->
            <div class="datetime">
                <i class='bx bx-calendar'></i>
                <span id="current-date-time"></span>
            </div>

            <!-- Año Escolar Activo -->
            <div class="school-year">
                <i class='bx bxs-school'></i>
                <span>2025-2026</span>
            </div>

            <!-- Notificaciones -->
            <div class="notification" id="notification-btn">
                <i class='bx bxs-bell' id="notification-btn-icon"></i>
                <span class="badge" id="notification-badge">3</span>
            </div>
        </div>
    </div>

    <!-- Panel de Notificaciones (se muestra al hacer clic) -->
    <div class="notification-panel" id="notification-panel">
        <div class="panel-header">
            <h4>Notificaciones</h4>
            <i class='bx bx-x' id="close-panel"></i>
        </div>
        <div class="panel-body">
            <div class="notification-item">
                <div class="notif-icon bg-blue"><i class='bx bxs-user-plus'></i></div>
                <div class="notif-content">
                    <p><strong>Juan Pérez</strong> solicitó cupo para 1er Grado.</p>
                    <small>hace 2 minutos</small>
                </div>
            </div>
            <div class="notification-item">
                <div class="notif-icon bg-green"><i class='bx bxs-check-circle'></i></div>
                <div class="notif-content">
                    <p>Inscripción <strong>#2025-142</strong> aprobada.</p>
                    <small>hace 15 minutos</small>
                </div>
            </div>
            <div class="notification-item">
                <div class="notif-icon bg-orange"><i class='bx bxs-time'></i></div>
                <div class="notif-content">
                    <p>Inscripción <strong>#2025-141</strong> pendiente de revisión.</p>
                    <small>hace 30 minutos</small>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <a href="#">Ver todas las notificaciones</a>
        </div>
    </div>
</section>