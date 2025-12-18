<?php
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
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
                window.location.href = "../login/login.php";
            });
        });
    </script>';
    session_destroy();
    exit();
}

$idPersona = $_SESSION['idPersona'];

$personaModel = new Persona($conexion);
$telefonoModel = new Telefono($conexion);

// Obtener datos del representante
$representante = $personaModel->obtenerPorId($idPersona);
if (!$representante) {
    header("Location: ../../inicio/inicio/inicio.php");
    exit();
}

$srcFoto = '';
if (!empty($representante['foto_perfil'])) {
    $srcFoto = 'data:image/jpeg;base64,' . base64_encode($representante['foto_perfil']);
}

// Obtener teléfonos
$telefonos = $telefonoModel->obtenerPorPersona($idPersona);

// Obtener perfiles asignados
$queryPerfiles = "SELECT p.nombre_perfil
                  FROM detalle_perfil dp
                  INNER JOIN perfil p ON p.IdPerfil = dp.IdPerfil
                  WHERE dp.IdPersona = :idPersona";
$stmtPerfiles = $conexion->prepare($queryPerfiles);
$stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
$stmtPerfiles->execute();
$perfiles = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

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

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #c90000 0%, #a00000 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(201, 0, 0, 0.2);
    }

    .profile-photo-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 1rem;
    }

    .profile-photo-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        border: 5px solid rgba(255, 255, 255, 0.3);
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .profile-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-photo-default {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .profile-photo-default i {
        font-size: 4rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .profile-role {
        font-size: 1.1rem;
        opacity: 0.95;
        text-align: center;
        margin-bottom: 0;
    }

    .profile-role i {
        margin-right: 0.5rem;
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
        transition: all 0.3s ease;
    }

    .info-item:hover {
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
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

    .info-item .badge {
        color: #fff !important;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .section-title i {
        color: #c90000;
        margin-right: 0.5rem;
    }

    .badge-profile {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 20px;
    }

    .alert-info-profile {
        background: #e7f3ff;
        border-left: 4px solid #2196F3;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .alert-info-profile i {
        color: #2196F3;
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }
</style>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-12">

                    <!-- Header del Perfil -->
                    <div class="profile-header">
                        <div class="profile-photo-container">
                            <div class="profile-photo-wrapper">
                                <?php if (!empty($srcFoto)): ?>
                                    <img src="<?= $srcFoto ?>"
                                        alt="Foto de perfil"
                                        class="profile-photo">
                                <?php else: ?>
                                    <div class="profile-photo-default">
                                        <i class='bx bx-user'></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h1 class="profile-name">
                            <?= htmlspecialchars($representante['nombre'] . ' ' . $representante['apellido']) ?>
                        </h1>
                        <p class="profile-role">
                            <i class='bx bx-shield'></i>
                            <?= !empty($perfiles) ? implode(' | ', array_map('htmlspecialchars', $perfiles)) : 'Usuario' ?>
                        </p>
                    </div>

                    <!-- Alerta Informativa -->
                    <div class="alert-info-profile">
                        <i class='bx bx-info-circle'></i>
                        <strong>Vista de Solo Lectura:</strong> Esta es tu información personal registrada en el sistema. Si necesitas actualizar algún dato, contacta con la administración de la institución.
                    </div>

                    <!-- INFORMACIÓN PERSONAL -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class='bx bx-user-circle me-2'></i> Información Personal
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-user me-1'></i> Nombre Completo:</strong>
                                    <span><?= mostrar($representante['nombre'] . ' ' . $representante['apellido']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-id-card me-1'></i> Cédula:</strong>
                                    <span>
                                        <?php
                                            if (!empty($representante['cedula'])) {
                                                // Obtener nacionalidad
                                                $queryNac = "SELECT nacionalidad FROM nacionalidad WHERE IdNacionalidad = :id";
                                                $stmtNac = $conexion->prepare($queryNac);
                                                $stmtNac->bindParam(':id', $representante['IdNacionalidad']);
                                                $stmtNac->execute();
                                                $nacionalidad = $stmtNac->fetchColumn();
                                                echo htmlspecialchars($nacionalidad) . ' ' . number_format($representante['cedula'], 0, '', '.');
                                            } else {
                                                echo mostrar('');
                                            }
                                        ?>
                                    </span>
                                </div>
                                <?php if (!empty($representante['IdSexo'])): ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-male-female me-1'></i> Sexo:</strong>
                                        <span>
                                            <?php
                                                $querySexo = "SELECT sexo FROM sexo WHERE IdSexo = :id";
                                                $stmtSexo = $conexion->prepare($querySexo);
                                                $stmtSexo->bindParam(':id', $representante['IdSexo']);
                                                $stmtSexo->execute();
                                                echo htmlspecialchars($stmtSexo->fetchColumn());
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($representante['fecha_nacimiento'])): ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-cake me-1'></i> Fecha de Nacimiento:</strong>
                                        <span>
                                            <?php
                                                $edad = calcularEdad($representante['fecha_nacimiento']);
                                                echo date('d/m/Y', strtotime($representante['fecha_nacimiento']));
                                                if ($edad) {
                                                    echo ' <span class="badge bg-secondary ms-2">' . $edad . ' años</span>';
                                                }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($representante['lugar_nacimiento'])): ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-map-pin me-1'></i> Lugar de Nacimiento:</strong>
                                        <span><?= htmlspecialchars($representante['lugar_nacimiento']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <strong><i class='bx bx-envelope me-1'></i> Correo Electrónico:</strong>
                                    <span><?= mostrar($representante['correo']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMACIÓN DE CONTACTO -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class='bx bx-phone me-2'></i> Información de Contacto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <?php if (!empty($representante['IdUrbanismo'])): ?>
                                    <div class="info-item">
                                        <strong><i class='bx bx-map me-1'></i> Urbanismo:</strong>
                                        <span>
                                            <?php
                                                $queryUrb = "SELECT urbanismo FROM urbanismo WHERE IdUrbanismo = :id";
                                                $stmtUrb = $conexion->prepare($queryUrb);
                                                $stmtUrb->bindParam(':id', $representante['IdUrbanismo']);
                                                $stmtUrb->execute();
                                                echo htmlspecialchars($stmtUrb->fetchColumn());
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <strong><i class='bx bx-home me-1'></i> Dirección:</strong>
                                    <span><?= mostrar($representante['direccion']) ?></span>
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

                    <!-- INFORMACIÓN DE CUENTA -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class='bx bx-lock-alt me-2'></i> Información de Cuenta
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong><i class='bx bx-user-check me-1'></i> Usuario:</strong>
                                    <span><?= mostrar($representante['usuario']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-shield-check me-1'></i> Roles Asignados:</strong>
                                    <span>
                                        <?php if (!empty($perfiles)): ?>
                                            <?php foreach ($perfiles as $perfil): ?>
                                                <span class="badge bg-primary badge-profile me-1 mb-1">
                                                    <?= htmlspecialchars($perfil) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin roles asignados</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-check-circle me-1'></i> Estado de Acceso:</strong>
                                    <span>
                                        <?php
                                            $queryEstadoAcceso = "SELECT status FROM status WHERE IdStatus = :id";
                                            $stmtEstadoAcceso = $conexion->prepare($queryEstadoAcceso);
                                            $stmtEstadoAcceso->bindParam(':id', $representante['IdEstadoAcceso']);
                                            $stmtEstadoAcceso->execute();
                                            $estadoAcceso = $stmtEstadoAcceso->fetchColumn();

                                            $badgeClass = 'secondary';
                                            if ($estadoAcceso == 'Activo') $badgeClass = 'success';
                                            elseif ($estadoAcceso == 'Bloqueado') $badgeClass = 'danger';
                                            elseif ($estadoAcceso == 'Inactivo') $badgeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?> badge-profile">
                                            <?= htmlspecialchars($estadoAcceso) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong><i class='bx bx-building me-1'></i> Estado Institucional:</strong>
                                    <span>
                                        <?php
                                            $queryEstadoInst = "SELECT status FROM status WHERE IdStatus = :id";
                                            $stmtEstadoInst = $conexion->prepare($queryEstadoInst);
                                            $stmtEstadoInst->bindParam(':id', $representante['IdEstadoInstitucional']);
                                            $stmtEstadoInst->execute();
                                            $estadoInst = $stmtEstadoInst->fetchColumn();

                                            $badgeClass = 'secondary';
                                            if ($estadoInst == 'Activo') $badgeClass = 'success';
                                            elseif ($estadoInst == 'Inactivo') $badgeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?> badge-profile">
                                            <?= htmlspecialchars($estadoInst) ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Volver -->
                    <div class="text-center mt-4">
                        <a href="../../inicio/inicio/inicio.php" class="btn btn-lg btn-danger">
                            <i class='bx bx-arrow-back me-2'></i>
                            Volver
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
