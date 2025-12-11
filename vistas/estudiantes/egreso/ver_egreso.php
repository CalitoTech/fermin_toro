<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';

// 🔹 Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert'], $_SESSION['message']);

if ($alert) {
    $alerta = match ($alert) {
        'success' => Notificaciones::exito($message ?: 'Operación realizada correctamente.'),
        'error' => Notificaciones::advertencia($message ?: 'Ocurrió un error. Por favor verifique.'),
        default => null
    };
    if ($alerta) Notificaciones::mostrar($alerta);
}

// 🔹 Obtener ID de egreso
$idEgreso = $_GET['id'] ?? 0;
if ($idEgreso <= 0) {
    header("Location: egreso.php?error=id_invalido");
    exit();
}

// 🔹 Conexión
$database = new Database();
$conexion = $database->getConnection();

// === CONSULTA DE EGRESO CON DATOS DEL ESTUDIANTE ===
$query = "SELECT
            egreso.IdEgreso,
            egreso.fecha_egreso,
            egreso.motivo,
            egreso.IdStatus,
            status.status,
            persona.IdPersona,
            persona.nombre,
            persona.apellido,
            persona.cedula,
            persona.fecha_nacimiento,
            persona.lugar_nacimiento,
            persona.correo,
            persona.direccion,
            nacionalidad.nacionalidad,
            sexo.sexo,
            urbanismo.urbanismo,
            GROUP_CONCAT(DISTINCT CONCAT(telefono.numero_telefono, '||', tipo_telefono.tipo_telefono) SEPARATOR '###') AS telefonos
          FROM egreso
          INNER JOIN persona ON egreso.IdPersona = persona.IdPersona
          LEFT JOIN nacionalidad ON persona.IdNacionalidad = nacionalidad.IdNacionalidad
          LEFT JOIN sexo ON persona.IdSexo = sexo.IdSexo
          LEFT JOIN urbanismo ON persona.IdUrbanismo = urbanismo.IdUrbanismo
          INNER JOIN status ON egreso.IdStatus = status.IdStatus
          LEFT JOIN telefono ON persona.IdPersona = telefono.IdPersona
          LEFT JOIN tipo_telefono ON telefono.IdTipo_Telefono = tipo_telefono.IdTipo_Telefono
          WHERE egreso.IdEgreso = :id
          GROUP BY egreso.IdEgreso";

$stmt = $conexion->prepare($query);
$stmt->bindParam(':id', $idEgreso, PDO::PARAM_INT);
$stmt->execute();
$egreso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$egreso) {
    header("Location: egreso.php?error=egreso_no_encontrado");
    exit();
}

// 🔹 Helpers de teléfonos
function separarTelefonosPorTipo($telefonosStr) {
    if (empty($telefonosStr)) return ['personales' => [], 'laborales' => []];

    $telefonosArray = explode('###', $telefonosStr);
    $telefonos = ['personales' => [], 'laborales' => []];

    foreach ($telefonosArray as $tel) {
        if (empty($tel)) continue;
        list($numero, $tipo) = explode('||', $tel);
        $cat = ($tipo === 'Trabajo') ? 'laborales' : 'personales';
        $telefonos[$cat][] = ['numero' => $numero, 'tipo' => $tipo];
    }

    return $telefonos;
}

// Función para mostrar teléfonos con estilo
function mostrarTelefonos($telefonos) {
    if (empty($telefonos)) return '<span class="text-muted">No registrado</span>';
    $html = '<div class="telefonos-list">';
    foreach ($telefonos as $t) {
        $badge = match($t['tipo']) {
            'Celular' => 'bg-primary',
            'Trabajo' => 'bg-warning text-dark',
            'Habitación' => 'bg-info',
            default => 'bg-secondary'
        };
        $icon = match($t['tipo']) {
            'Celular' => 'fa-mobile-alt',
            'Trabajo' => 'fa-briefcase',
            'Habitación' => 'fa-phone',
            default => 'fa-phone'
        };
        $html .= "<div class='telefono-item d-flex align-items-center mb-2'>
                    <i class='fas {$icon} me-2 text-muted'></i>
                    <span class='telefono-numero fw-medium'>" . htmlspecialchars($t['numero']) . "</span>
                    <span class='badge {$badge} ms-2'>" . htmlspecialchars($t['tipo']) . "</span>
                 </div>";
    }
    return $html . '</div>';
}

$telefonos = separarTelefonosPorTipo($egreso['telefonos'] ?? '');
?>

<head>
    <title>UECFT Araure - Detalles de Egreso</title>
    <style>
    .egreso-header {
        background: linear-gradient(135deg, #dc3545 0%, #b71c1c 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px 15px 0 0;
        margin-bottom: 0;
    }

    .egreso-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.15);
        overflow: hidden;
        margin-bottom: 2rem;
        background: #fff;
    }

    .info-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #dc3545;
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1);
    }

    .info-section h6 {
        color: #dc3545;
        font-weight: 600;
        margin-bottom: 1rem;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
    }

    .info-section h6 i {
        margin-right: 0.5rem;
        color: #dc3545;
    }

    .info-row {
        display: flex;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #444;
        min-width: 180px;
    }

    .info-value {
        color: #333;
        flex: 1;
    }

    .status-badge-large {
        font-size: 1.2rem;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        display: inline-block;
        margin-top: 1rem;
    }

    .timeline-date {
        background: linear-gradient(135deg, #ff6f61 0%, #dc3545 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        display: inline-block;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .motivo-box {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
        padding: 1.5rem;
        border-radius: 10px;
        font-style: italic;
        color: #555;
        min-height: 80px;
    }

    .student-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .student-id {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    .telefonos-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .telefono-item {
        background: #fff5f5;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border-left: 3px solid #dc3545;
    }

    .icon-box {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #ff6f61 0%, #dc3545 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-right: 1rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #dc3545 0%, #b71c1c 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
    }

    .stat-card h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }

    .stat-card p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }

    /* Ajustes para impresión */
    @media print {
        .home-section {
            margin: 0 !important;
            padding: 0 !important;
        }

        .sidebar, .action-buttons, .btn {
            display: none !important;
        }

        .egreso-card {
            box-shadow: none !important;
            page-break-inside: avoid;
        }

        .egreso-header {
            background: #dc3545 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <!-- Card principal con header personalizado -->
                    <div class="egreso-card">
                        <div class="egreso-header">
                            <div class="d-flex justify-content-between align-items-start flex-wrap">
                                <div>
                                    <div class="student-name">
                                        <i class="fas fa-user-graduate me-2"></i>
                                        <?= htmlspecialchars($egreso['nombre'] . ' ' . $egreso['apellido']) ?>
                                    </div>
                                    <div class="student-id">
                                        <i class="fas fa-id-card me-2"></i>
                                        <?= htmlspecialchars($egreso['nacionalidad'] . '-' . $egreso['cedula']) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="timeline-date">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <span class="status-badge-large badge bg-<?= $egreso['IdStatus'] == 7 ? 'success' : 'info' ?>">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?= htmlspecialchars($egreso['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- Botones de acción -->
                            <div class="action-buttons">
                                <a href="egreso.php" class="btn btn-secondary btn-action">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>
                            </div>

                            <!-- Información del Egreso -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="info-section">
                                        <h6>
                                            <i class="fas fa-door-open"></i>
                                            Información del Egreso
                                        </h6>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-hashtag me-2"></i>ID de Egreso:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['IdEgreso']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-calendar me-2"></i>Fecha de Egreso:
                                            </div>
                                            <div class="info-value"><?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-flag me-2"></i>Status:
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-<?= $egreso['IdStatus'] == 7 ? 'success' : 'info' ?>">
                                                    <?= htmlspecialchars($egreso['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Motivo del Egreso -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="info-section">
                                        <h6>
                                            <i class="fas fa-comment-dots"></i>
                                            Motivo del Egreso
                                        </h6>
                                        <div class="motivo-box">
                                            <?php if (!empty($egreso['motivo'])): ?>
                                                <i class="fas fa-quote-left me-2"></i>
                                                <?= htmlspecialchars($egreso['motivo']) ?>
                                                <i class="fas fa-quote-right ms-2"></i>
                                            <?php else: ?>
                                                <span class="text-muted">No se especificó motivo de egreso</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos del Estudiante -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h6>
                                            <i class="fas fa-user"></i>
                                            Información Personal
                                        </h6>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-signature me-2"></i>Nombre Completo:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['nombre'] . ' ' . $egreso['apellido']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-id-card me-2"></i>Cédula:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['nacionalidad'] . '-' . $egreso['cedula']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-birthday-cake me-2"></i>Fecha de Nacimiento:
                                            </div>
                                            <div class="info-value">
                                                <?= $egreso['fecha_nacimiento'] ? date('d/m/Y', strtotime($egreso['fecha_nacimiento'])) : 'No registrado' ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-map-marker-alt me-2"></i>Lugar de Nacimiento:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['lugar_nacimiento'] ?: 'No registrado') ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-venus-mars me-2"></i>Sexo:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['sexo'] ?: 'No registrado') ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-section">
                                        <h6>
                                            <i class="fas fa-address-book"></i>
                                            Información de Contacto
                                        </h6>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-envelope me-2"></i>Correo:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['correo'] ?: 'No registrado') ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-home me-2"></i>Dirección:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['direccion'] ?: 'No registrado') ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-city me-2"></i>Urbanismo:
                                            </div>
                                            <div class="info-value"><?= htmlspecialchars($egreso['urbanismo'] ?: 'No registrado') ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-phone me-2"></i>Teléfonos:
                                            </div>
                                            <div class="info-value">
                                                <?php
                                                $todosLosTelefonos = array_merge($telefonos['personales'], $telefonos['laborales']);
                                                echo mostrarTelefonos($todosLosTelefonos);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<style media="print">
    .home-section {
        margin: 0 !important;
        padding: 0 !important;
    }

    .sidebar, .action-buttons, .btn {
        display: none !important;
    }

    .egreso-card {
        box-shadow: none !important;
        page-break-inside: avoid;
    }

    .egreso-header {
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
</style>

</body>
</html>
