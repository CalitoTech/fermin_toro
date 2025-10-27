<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';

$database = new Database();
$conexion = $database->getConnection();

// === VERIFICACIÓN DE SESIÓN ===
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: "Acceso Denegado",
            text: "Por favor, debes iniciar sesión",
            icon: "warning",
            confirmButtonText: "Aceptar",
            confirmButtonColor: "#c90000"
        }).then(() => {
            window.location.href = "../../login/login.php";
        });
    </script>';
    session_destroy();
    exit();
}

// === VERIFICACIÓN DE ID ===
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: estudiante.php");
    exit();
}

$idPersona = intval($_GET['id']);

require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Representante.php';

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);

// Consultas del estudiante
$estudiante = $personaModel->obtenerEstudiantePorId($idPersona);
if (!$estudiante) {
    header("Location: estudiante.php");
    exit();
}

$seccionActual = $personaModel->obtenerSeccionActualEstudiante($idPersona);
$discapacidades = $personaModel->obtenerDiscapacidadesEstudiante($idPersona);
$representantes = $representanteModel->obtenerPorEstudiante($idPersona);

// === FUNCIÓN DE LIMPIEZA ===
function mostrar($valor, $texto = 'No registrado') {
    return htmlspecialchars(!empty(trim($valor)) ? $valor : $texto);
}
?>

<head>
    <title>UECFT Araure - Ver Estudiante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h2 class="mb-1">Detalles del Estudiante</h2>
                            <p class="text-muted mb-0">
                                Asociado a <strong><?= count($representantes) ?></strong> representante(s)
                            </p>
                        </div>
                        <div>
                            <a href="estudiante.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <a href="editar_estudiante.php?id=<?= $estudiante['IdPersona'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        </div>
                    </div>

                    <!-- INFORMACIÓN DEL ESTUDIANTE -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fas fa-user-graduate me-2"></i> Información del Estudiante
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Nombre completo:</strong>
                                    <span><?= mostrar($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Cédula:</strong>
                                    <span><?= mostrar(($estudiante['nacionalidad'] ? $estudiante['nacionalidad'] . ' ' : '') . $estudiante['cedula']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Sexo:</strong>
                                    <span><?= mostrar($estudiante['sexo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Fecha de nacimiento:</strong>
                                    <span><?= $estudiante['fecha_nacimiento'] ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : 'No registrada' ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Urbanismo:</strong>
                                    <span><?= mostrar($estudiante['urbanismo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Dirección:</strong>
                                    <span><?= mostrar($estudiante['direccion']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Correo electrónico:</strong>
                                    <span><?= mostrar($estudiante['correo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Teléfonos:</strong>
                                    <span>
                                        <?php
                                        if (!empty($estudiante['numeros'])) {
                                            $nums = explode(' || ', $estudiante['numeros']);
                                            $tipos = explode(' || ', $estudiante['tipos']);
                                            foreach ($nums as $i => $num) {
                                                $tipo = $tipos[$i] ?? 'Teléfono';
                                                echo '<div><i class="fas fa-phone me-1 text-danger"></i>' . mostrar($tipo) . ': ' . mostrar($num) . '</div>';
                                            }
                                        } else {
                                            echo 'No registrados';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong>Estado de acceso:</strong>
                                    <span><?= mostrar($estudiante['estado_acceso']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Estado institucional:</strong>
                                    <span><?= mostrar($estudiante['estado_institucional']) ?></span>
                                </div>

                                <!-- SECCIÓN ACTUAL -->
                                <div class="info-item">
                                    <strong>Sección actual:</strong>
                                    <span>
                                        <?php if ($seccionActual): ?>
                                            <?= htmlspecialchars($seccionActual['curso'] . ' "' . $seccionActual['seccion'] . '"') ?>
                                        <?php else: ?>
                                            <span class="text-muted">No inscrito actualmente</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DISCAPACIDADES (tarjeta aparte) -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fas fa-wheelchair me-2"></i> Discapacidad(es)
                        </div>
                        <div class="card-body">
                            <?php if (count($discapacidades) > 0): ?>
                                <div class="row">
                                    <?php foreach ($discapacidades as $d): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="p-2 border rounded bg-light">
                                                <strong><?= htmlspecialchars($d['tipo_discapacidad']) ?>:</strong>
                                                <span><?= htmlspecialchars($d['discapacidad']) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted text-center">No posee discapacidades registradas.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- REPRESENTANTES ASOCIADOS -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fas fa-user-friends me-2"></i> Representantes Asociados
                        </div>
                        <div class="card-body">
                            <?php if (count($representantes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Cédula</th>
                                            <th>Parentesco</th>
                                            <th>Teléfonos</th>
                                            <th>Ocupación</th>
                                            <th>Lugar de trabajo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($representantes as $r): ?>
                                        <tr>
                                            <td><?= mostrar($r['nombre'] . ' ' . $r['apellido']) ?></td>
                                            <td><?= mostrar(($r['nacionalidad'] ? $r['nacionalidad'] . ' ' : '') . $r['cedula']) ?></td>
                                            <td><?= mostrar($r['parentesco']) ?></td>
                                            <td>
                                                <?php
                                                if (!empty($r['numeros'])) {
                                                    $nums = explode(' || ', $r['numeros']);
                                                    foreach ($nums as $num) {
                                                        echo '<div><i class="fas fa-phone me-1 text-danger"></i>' . mostrar($num) . '</div>';
                                                    }
                                                } else {
                                                    echo 'No registrados';
                                                }
                                                ?>
                                            </td>
                                            <td><?= mostrar($r['ocupacion']) ?></td>
                                            <td><?= mostrar($r['lugar_trabajo']) ?></td>
                                            <td>
                                                <a href="../representante/ver_representante.php?id=<?= $r['IdPersona'] ?>" class="btn btn-outline-danger btn-sm">
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
                                <p class="mb-0">Este estudiante no tiene representantes asociados.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
