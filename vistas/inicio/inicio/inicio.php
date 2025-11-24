<?php
session_start();

// Comprobación de sesión (aseguramos idPersona y usuario)
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar"
            }).then(() => window.location = "../../login/login.php");
        });
    </script>
    ';
    // destruimos sesión por seguridad
    session_unset();
    session_destroy();
    exit;
}

// Mensaje de bienvenida (solo si fue seteado durante el login)
if (isset($_SESSION['login_exitoso'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "¡Bienvenido/a!",
                text: "Gracias por visitar UECFT Araure.",
                icon: "success",
                confirmButtonText: "Aceptar",
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
    ';
    unset($_SESSION['login_exitoso']);
}

// --- Uso CONSISTENTE de claves de sesión ---
$userNombre  = $_SESSION['nombre']   ?? '';
$userApellido= $_SESSION['apellido'] ?? '';
$perfilId    = $_SESSION['idPerfil'] ?? 0;

// === DETERMINAR PERFIL DE MAYOR PRIORIDAD ===
require_once __DIR__ . '/../../../config/conexion.php';
$database = new Database();
$db = $database->getConnection();

$sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
$stmtPerfiles = $db->prepare($sqlPerfiles);
$stmtPerfiles->bindParam(':idPersona', $_SESSION['idPersona'], PDO::PARAM_INT);
$stmtPerfiles->execute();
$todosLosPerfiles = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

// Definir prioridades (menor número = mayor prioridad)
$prioridades = [
    1 => 1,  // Administrador
    6 => 2,  // Director
    7 => 3,  // Control de Estudios
    8 => 4,  // Coordinador Inicial
    9 => 4,  // Coordinador Primaria
    10 => 4, // Coordinador Media General
    2 => 5,  // Docente
    4 => 6,  // Representante
    5 => 7,  // Contacto de Emergencia
    3 => 8   // Estudiante
];

// Encontrar el perfil con mayor prioridad
$perfilPrioritario = $perfilId;
$menorPrioridad = PHP_INT_MAX;

foreach ($todosLosPerfiles as $perfil) {
    if (isset($prioridades[$perfil]) && $prioridades[$perfil] < $menorPrioridad) {
        $menorPrioridad = $prioridades[$perfil];
        $perfilPrioritario = $perfil;
    }
}

// Lógica del Dashboard (Admin, Director)
$perfilesConDashboard = [1, 6];
$showDashboard = in_array($perfilPrioritario, $perfilesConDashboard);
$stats = [];
$recentEnrollments = [];
$chartData = [];

if ($showDashboard) {

    try {
        // 1. Total Estudiantes Activos
        $stmt = $db->prepare("SELECT COUNT(DISTINCT p.IdPersona) FROM persona p 
                             JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona 
                             WHERE dp.IdPerfil = 3 AND p.IdEstadoAcceso = 1");
        $stmt->execute();
        $stats['estudiantes'] = $stmt->fetchColumn();

        // 2. Total Docentes Activos (COMENTADO POR AHORA)
        /*
        $stmt = $db->prepare("SELECT COUNT(DISTINCT p.IdPersona) FROM persona p 
                             JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona 
                             WHERE dp.IdPerfil = 2 AND p.IdEstadoAcceso = 1");
        $stmt->execute();
        $stats['docentes'] = $stmt->fetchColumn();
        */

        // 3. Inscripciones Pendientes (Diferentes de 11=Inscrito y 12=Rechazada)
        $stmt = $db->prepare("SELECT COUNT(*) FROM inscripcion WHERE IdStatus NOT IN (11, 12)");
        $stmt->execute();
        $stats['pendientes'] = $stmt->fetchColumn();

        // 4. Inscripciones Aprobadas/Inscritos (IdStatus = 11)
        $stmt = $db->prepare("SELECT COUNT(*) FROM inscripcion WHERE IdStatus = 11");
        $stmt->execute();
        $stats['inscritos'] = $stmt->fetchColumn();

        // 5. Datos para Gráfico: Inscripciones por Nivel (Solo inscritos)
        $stmt = $db->prepare("SELECT n.nivel, COUNT(*) as total
                             FROM inscripcion i
                             JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                             JOIN curso c ON cs.IdCurso = c.IdCurso
                             JOIN nivel n ON c.IdNivel = n.IdNivel
                             WHERE i.IdStatus = 11
                             GROUP BY n.nivel");
        $stmt->execute();
        $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Últimas 5 Inscripciones
        $stmt = $db->prepare("SELECT p.nombre, p.apellido, c.curso, i.fecha_inscripcion, s.status, s.IdStatus
                             FROM inscripcion i
                             JOIN persona p ON i.IdEstudiante = p.IdPersona
                             JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                             JOIN curso c ON cs.IdCurso = c.IdCurso
                             JOIN status s ON i.IdStatus = s.IdStatus
                             ORDER BY i.fecha_inscripcion DESC
                             LIMIT 5");
        $stmt->execute();
        $recentEnrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error Dashboard: " . $e->getMessage());
    }
}

?>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../../../assets/css/inicio.css">
    <?php if ($showDashboard): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
<?php if (in_array($perfilPrioritario, [3,4,5])): ?>
    <!-- === INTERFAZ REPRESENTANTE === -->
    <main class="rep-wrapper">
        <img src="../../../assets/images/fermin.png" alt="Logo UECFT Araure" class="img-logo">
        <h1 class="rep-welcome">¡Hola, <?php echo htmlspecialchars($userNombre ?: 'Representante'); ?>! 👋</h1>
        <p class="rep-subtitle">Bienvenido/a al portal del representante. Gestiona inscripciones, consulta información de tus representados y recibe comunicados oficiales.</p>

        <div class="rep-card-container">
            <a href="../../representantes/representados/ver_representado.php" class="rep-card" title="Mis Representados">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Mis Representados</h3>
                <p>Accede al perfil académico y contacta con el colegio.</p>
            </a>

            <a href="#" class="rep-card" title="Comunicados">
                <div class="icon"><i class="fas fa-bullhorn"></i></div>
                <h3>Comunicados</h3>
                <p>Últimas noticias y avisos del instituto.</p>
            </a>
        </div>
    </main>

<?php elseif ($showDashboard): ?>
    <!-- === DASHBOARD ADMINISTRATIVO (ADMIN / DIRECTOR) === -->
    <!-- Usamos 'home-section' para heredar el comportamiento del menú automáticamente -->
    <section class="home-section" style="background: transparent; min-height: auto;">
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Panel de Control</h2>
                    <p class="text-muted mb-0">Bienvenido de nuevo, <?php echo htmlspecialchars($userNombre); ?>.</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3><?php echo $stats['estudiantes'] ?? 0; ?></h3>
                            <p>Estudiantes Activos</p>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3><?php echo $stats['pendientes'] ?? 0; ?></h3>
                            <p>Solicitudes en Proceso</p>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card red">
                        <div class="stat-info">
                            <h3><?php echo $stats['inscritos'] ?? 0; ?></h3>
                            <p>Total Inscritos</p>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts & Tables Row -->
            <div class="row g-4">
                <!-- Chart -->
                <div class="col-lg-5">
                    <div class="chart-container">
                        <h5 class="fw-bold mb-4">Distribución por Nivel</h5>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-7">
                    <div class="table-container h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Últimas Inscripciones</h5>
                            <a href="../../inscripciones/inscripcion/inscripcion.php" class="btn btn-sm btn-outline-danger">Ver todas</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Curso</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentEnrollments)): ?>
                                        <?php foreach ($recentEnrollments as $insc): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($insc['nombre'] . ' ' . $insc['apellido']); ?></td>
                                                <td><?php echo htmlspecialchars($insc['curso']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($insc['fecha_inscripcion'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = 'status-default';
                                                    // Lógica mejorada para clases de estado
                                                    // Aseguramos que IdStatus sea entero para comparación estricta
                                                    $statusId = (int)$insc['IdStatus'];
                                                    
                                                    if (in_array($statusId, [8, 9, 10])) { // Pendiente, Aprobada reunión, Espera pago
                                                        $statusClass = 'status-pending';
                                                    } elseif ($statusId === 11) { // Inscrito
                                                        $statusClass = 'status-approved';
                                                    } elseif ($statusId === 12) { // Rechazada
                                                        $statusClass = 'status-rejected';
                                                    }
                                                    ?>
                                                    <!-- Usamos la nueva clase enrollment-status-badge -->
                                                    <span class="enrollment-status-badge <?php echo $statusClass; ?>">
                                                        <?php echo htmlspecialchars($insc['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No hay inscripciones recientes</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('enrollmentChart').getContext('2d');
            
            // Datos desde PHP
            const chartData = <?php echo json_encode($chartData); ?>;
            const labels = chartData.map(item => item.nivel);
            const data = chartData.map(item => item.total);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels.length ? labels : ['Sin datos'],
                    datasets: [{
                        data: data.length ? data : [1],
                        backgroundColor: [
                            '#c90000', // Rojo Corporativo
                            '#212529', // Dark
                            '#6c757d', // Gray
                            '#ffc107'  // Warning
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>

<?php else: ?>
    <!-- === INTERFAZ GENÉRICA PARA OTROS ROLES === -->
    <main class="main-content d-flex align-items-center justify-content-center">
        <div class="text-center py-5">
            <div class="mb-4"><img src="../../../assets/images/fermin.png" alt="Logo UECFT Araure" class="img-logo"></div>
            <h1 class="display-5 fw-bold text-gray-800 mb-3">
                Bienvenido/a, <span class="text-danger"><?php echo htmlspecialchars($userNombre . ' ' . $userApellido); ?></span> 👋
            </h1>
            <p class="lead text-muted mb-4">Ya puedes comenzar a gestionar el sistema educativo.</p>
            <div class="btn-group">
                <a href="../../inscripciones/inscripcion/inscripcion.php" class="btn btn-lg btn-danger px-4 shadow-sm">
                    <i class="fas fa-edit me-2"></i>Insc. Pendientes
                </a>
                <a href="../../estudiantes/estudiante/estudiante.php" class="btn btn-lg btn-outline-secondary px-4 shadow-sm">
                    <i class="fas fa-users me-2"></i>Estudiantes
                </a>
            </div>
        </div>
    </main>
<?php endif; ?>

<?php include '../../layouts/footer.php'; ?>