<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Telefono.php';


$database = new Database();
$db = $database->getConnection();

// === 🔐 Verificación de sesión ===
if (empty($_SESSION['usuario']) || empty($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: "Acceso Denegado",
            text: "Por favor, inicia sesión para continuar.",
            icon: "warning",
            confirmButtonText: "Aceptar",
            confirmButtonColor: "#c90000"
        }).then(() => window.location.href = "../../login/login.php");
    </script>';
    session_destroy();
    exit();
}

// === 🆔 Verificación de parámetro ID ===
$idPersona = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idPersona <= 0) {
    header("Location: representante.php");
    exit();
}

// === 📋 Obtener datos del representante ===
$representanteModel = new Representante($db);
$representante = $representanteModel->obtenerPorIdPersona($idPersona);

if (!$representante) {
    header("Location: representante.php");
    exit();
}

// === ☎️ Obtener teléfonos ===
$telefonoModel = new Telefono($db);
$telefonos = $telefonoModel->obtenerPorPersona($idPersona);

// === 👩‍🎓 Estudiantes asociados (optimizado) ===
$estudiantes = $representanteModel->obtenerEstudiantesPorRepresentante($idPersona);

// === 🔧 Funciones auxiliares ===
function mostrar($valor, $porDefecto = 'No registrado') {
    return htmlspecialchars(trim($valor) !== '' ? $valor : $porDefecto);
}
?>

<head>
    <title>UECFT Araure - Ver Representante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content container">
        <div class="row justify-content-center mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="mb-1">Detalles del Representante</h2>
                        <p class="text-muted mb-0">
                            Representante de <strong><?= count($estudiantes) ?></strong> estudiante(s)
                        </p>
                    </div>
                    <div>
                        <a href="representante.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="editar_representante.php?id=<?= $representante['IdPersona'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    </div>
                </div>

                <!-- 🧑 Información del Representante -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex align-items-center">
                        <i class="fas fa-user-tie me-2"></i> Información del Representante
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item"><strong>Nombre completo:</strong>
                                <span><?= mostrar($representante['nombre'] . ' ' . $representante['apellido'], 'No especificado') ?></span>
                            </div>
                            <div class="info-item"><strong>Cédula:</strong>
                                <span><?= mostrar(($representante['nacionalidad'] ? $representante['nacionalidad'] . ' ' : '') . $representante['cedula']) ?></span>
                            </div>
                            <div class="info-item"><strong>Sexo:</strong>
                                <span><?= mostrar($representante['sexo']) ?></span>
                            </div>
                            <div class="info-item"><strong>Parentesco:</strong>
                                <span><?= mostrar($representante['parentesco']) ?></span>
                            </div>
                            <div class="info-item"><strong>Correo electrónico:</strong>
                                <span><?= mostrar($representante['correo']) ?></span>
                            </div>
                            <div class="info-item"><strong>Teléfonos:</strong>
                                <span>
                                    <?php if ($telefonos): ?>
                                        <?php foreach ($telefonos as $t): ?>
                                            <div><i class="fas fa-phone me-1 text-danger"></i>
                                                <?= mostrar($t['tipo_telefono']) ?>: <?= mostrar(($t['codigo_prefijo'] ?? '') . $t['numero_telefono']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        No registrados
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item"><strong>Dirección:</strong>
                                <span><?= mostrar($representante['direccion'], 'No especificada') ?></span>
                            </div>
                            <div class="info-item"><strong>Urbanismo:</strong>
                                <span><?= mostrar($representante['urbanismo']) ?></span>
                            </div>
                            <div class="info-item"><strong>Tipo de trabajador:</strong>
                                <span><?= mostrar($representante['tipo_trabajador'] ?? 'No especificado', 'No especificado') ?></span>
                            </div>
                            <div class="info-item"><strong>Ocupación:</strong>
                                <span><?= mostrar($representante['ocupacion']) ?></span>
                            </div>
                            <div class="info-item"><strong>Lugar de trabajo:</strong>
                                <span><?= mostrar($representante['lugar_trabajo']) ?></span>
                            </div>
                            <div class="info-item"><strong>Estado de acceso:</strong>
                                <span><?= mostrar($representante['estado_acceso'], 'Desconocido') ?></span>
                            </div>
                            <div class="info-item"><strong>Estado institucional:</strong>
                                <span><?= mostrar($representante['estado_institucional'], 'Desconocido') ?></span>
                            </div>
                            <?php if (!empty($representante['contacto_emergencia'])): ?>
                            <div class="info-item">
                                <strong>Contacto de emergencia:</strong>
                                <span><i class="fas fa-check text-success"></i> Es contacto de emergencia</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 👩‍🎓 Estudiantes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex align-items-center">
                        <i class="fas fa-child me-2"></i> Estudiantes a Cargo
                    </div>
                    <div class="card-body">
                        <?php if ($estudiantes): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre Completo</th>
                                        <th>Cédula</th>
                                        <th>Sexo</th>
                                        <th>Fecha Nacimiento</th>
                                        <th>Curso / Sección</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estudiantes as $e): ?>
                                    <tr>
                                        <td><?= mostrar($e['nombre'] . ' ' . $e['apellido']) ?></td>
                                        <td><?= mostrar(($e['nacionalidad'] ? $e['nacionalidad'] . ' ' : '') . $e['cedula']) ?></td>
                                        <td><?= mostrar($e['sexo']) ?></td>
                                        <td><?= $e['fecha_nacimiento'] ? date('d/m/Y', strtotime($e['fecha_nacimiento'])) : 'No registrada' ?></td>
                                        <td><?= mostrar($e['curso_actual'], 'No inscrito actualmente') ?></td>
                                        <td class="text-center">
                                            <a href="../estudiante/ver_estudiante.php?id=<?= $e['IdEstudiante'] ?>" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-slash fa-3x mb-3"></i>
                            <p>Este representante no tiene estudiantes registrados a su cargo.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
