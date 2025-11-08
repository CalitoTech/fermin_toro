<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/Telefono.php';

$database = new Database();
$conexion = $database->getConnection();

// === VERIFICACIÓN DE SESIÓN ===
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../../login/login.php";
            });
        });
    </script>';
    session_destroy();
    exit();
}

// === VERIFICACIÓN DE ID ===
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: representado.php");
    exit();
}

$idEstudiante = intval($_GET['id']);
$idRepresentante = $_SESSION['idPersona'];

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$telefonoModel = new Telefono($conexion);

// Verificar que el estudiante sea realmente representado por este usuario
$estudiantesRepresentados = $representanteModel->obtenerEstudiantesPorRepresentante($idRepresentante);
$esRepresentado = false;
foreach ($estudiantesRepresentados as $est) {
    if ($est['IdEstudiante'] == $idEstudiante) {
        $esRepresentado = true;
        break;
    }
}

if (!$esRepresentado) {
    header("Location: representado.php");
    exit();
}

// Obtener datos del estudiante
$estudiante = $personaModel->obtenerEstudiantePorId($idEstudiante);
if (!$estudiante) {
    header("Location: representado.php");
    exit();
}

$seccionActual = $personaModel->obtenerSeccionActualEstudiante($idEstudiante);
$discapacidades = $personaModel->obtenerDiscapacidadesEstudiante($idEstudiante);
$telefonos = $telefonoModel->obtenerPorPersona($idEstudiante);

// === FUNCIÓN DE LIMPIEZA ===
function mostrar($valor, $texto = 'No registrado') {
    return !empty(trim($valor)) ? htmlspecialchars($valor) : '<span class="text-muted">' . $texto . '</span>';
}

// Función para calcular edad
function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return null;
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    return $edad->y;
}
?>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<style>
        .readonly-badge {
            background: #6c757d;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #c90000;
        }

        .info-item strong {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            font-size: 1rem;
            color: #333;
        }

        /* 🔹 Color blanco para el span de edad */
        .info-item .badge.bg-secondary {
            color: #fff !important;
        }

        /* 🔹 Color blanco para el span del status */
        .info-item .badge {
            color: #fff !important;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 2rem 0;
        }

        .alert-readonly {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-readonly i {
            color: #ffc107;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
</style>


<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-12">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h2 class="mb-1">
                                Información del Estudiante
                                <span class="readonly-badge ms-2">
                                    <i class='bx bx-lock-alt'></i> Solo Lectura
                                </span>
                            </h2>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                            </p>
                        </div>
                        <div>
                            <a href="representado.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back me-1'></i> Volver a Mis Representados
                            </a>
                        </div>
                    </div>

                    <!-- Alerta de Solo Lectura -->
                    <div class="alert-readonly">
                        <i class='bx bx-info-circle'></i>
                        <strong>Vista de Solo Lectura:</strong> Esta información es solo para consulta. Si necesitas actualizar algún dato, contacta con la administración de la institución.
                    </div>

                    <!-- INFORMACIÓN PERSONAL -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bx-user-circle me-2'></i> Información Personal
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-user me-1'></i> Nombre Completo:</strong>
                                    <span><?= mostrar($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-id-card me-1'></i> Cédula:</strong>
                                    <span>
                                        <?php
                                            if (!empty($estudiante['cedula'])) {
                                                echo mostrar($estudiante['nacionalidad'], '') . ' ' . number_format($estudiante['cedula'], 0, '', '.');
                                            } else {
                                                echo mostrar('');
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-male-female me-1'></i> Sexo:</strong>
                                    <span><?= mostrar($estudiante['sexo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-cake me-1'></i> Fecha de Nacimiento:</strong>
                                    <span>
                                        <?php
                                            if (!empty($estudiante['fecha_nacimiento'])) {
                                                $edad = calcularEdad($estudiante['fecha_nacimiento']);
                                                echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento']));
                                                if ($edad) {
                                                    echo ' <span class="badge bg-secondary ms-2">' . $edad . ' años</span>';
                                                }
                                            } else {
                                                echo mostrar('');
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-map-pin me-1'></i> Lugar de Nacimiento:</strong>
                                    <span><?= mostrar($estudiante['lugar_nacimiento'] ?? '') ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-envelope me-1'></i> Correo Electrónico:</strong>
                                    <span><?= mostrar($estudiante['correo']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN DE CONTACTO -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bx-phone me-2'></i> Información de Contacto
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-map me-1'></i> Urbanismo:</strong>
                                    <span><?= mostrar($estudiante['urbanismo']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-home me-1'></i> Dirección:</strong>
                                    <span><?= mostrar($estudiante['direccion']) ?></span>
                                </div>
                                <?php if (!empty($telefonos)): ?>
                                    <?php foreach ($telefonos as $tel): ?>
                                        <div class="info-item">
                                            <strong><i class='bx bx-phone me-1'></i> <?= htmlspecialchars($tel['tipo_telefono']) ?>:</strong>
                                            <span><?= htmlspecialchars($tel['numero_telefono']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-phone me-1'></i> Teléfonos:</strong>
                                        <span class="text-muted">No registrado</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN ACADÉMICA -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class='bx bxs-graduation me-2'></i> Información Académica
                        </div>
                        <div class="card-body">
                            <?php if ($seccionActual): ?>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <strong><i class='bx bx-layer me-1'></i> Nivel:</strong>
                                        <span><?= htmlspecialchars($seccionActual['nivel']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-book me-1'></i> Curso:</strong>
                                        <span><?= htmlspecialchars($seccionActual['curso']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-group me-1'></i> Sección:</strong>
                                        <span><?= htmlspecialchars($seccionActual['seccion']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-door-open me-1'></i> Aula:</strong>
                                        <span><?= mostrar($seccionActual['aula'] ?? '') ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-calendar me-1'></i> Año Escolar:</strong>
                                        <span><?= htmlspecialchars($seccionActual['fecha_escolar']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <strong><i class='bx bx-info-circle me-1'></i> Status:</strong>
                                        <span>
                                            <?php
                                                $statusClass = 'secondary';
                                                $statusText = $seccionActual['status'] ?? 'No registrado';
                                                if (stripos($statusText, 'Inscrito') !== false) $statusClass = 'success';
                                                elseif (stripos($statusText, 'Rechazado') !== false) $statusClass = 'danger';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= htmlspecialchars($statusText) ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class='bx bx-error me-2'></i>
                                    <strong>No hay información académica registrada</strong><br>
                                    El estudiante no tiene una inscripción activa en el sistema.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DISCAPACIDADES -->
                    <?php if (!empty($discapacidades)): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-danger text-white d-flex align-items-center">
                                <i class='bx bx-heart me-2'></i> Condiciones Especiales
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Condición</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($discapacidades as $disc): ?>
                                                <tr>
                                                    <td>
                                                        <i class='bx bx-heart text-danger me-2'></i>
                                                        <strong><?= htmlspecialchars($disc['tipo_discapacidad']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($disc['descripcion']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botón Volver -->
                    <div class="text-center mt-4">
                        <a href="representado.php" class="btn btn-lg btn-danger">
                            <i class='bx bx-arrow-back me-2'></i>
                            Volver a Mis Representados
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
