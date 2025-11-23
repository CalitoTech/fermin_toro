<?php
session_start();

// Verificación de sesión
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

// Verificar que sea administrador
if (!isset($_SESSION['idPerfil']) || $_SESSION['idPerfil'] != 1) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Solo los administradores pueden acceder a esta sección",
                icon: "error",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../../inicio/inicio/inicio.php";
            });
        });
    </script>';
    exit();
}

// Incluir Notificaciones
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
$message = $_SESSION['message'] ?? '';
unset($_SESSION['alert']);
unset($_SESSION['message']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito($message ?: 'Operación realizada correctamente.');
            break;
        case 'deleted':
            $alerta = Notificaciones::exito($message ?: 'Configuración eliminada correctamente.');
            break;
        case 'error':
            $alerta = Notificaciones::advertencia($message ?: 'Ocurrió un error. Por favor verifique.');
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// Cargar datos
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/ConfigWhatsapp.php';

$database = new Database();
$conexion = $database->getConnection();

$configModel = new ConfigWhatsapp($conexion);
$config = $configModel->obtenerConfiguracionActiva();
?>

<head>
    <title>UECFT Araure - Configuración WhatsApp</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <!-- Card de Configuración API -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class='bx bxl-whatsapp'></i> Configuración de Evolution API
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="../../../controladores/ConfigWhatsappController.php" method="POST" id="formConfig">
                                <input type="hidden" name="action" value="<?= $config ? 'actualizar' : 'guardar' ?>">
                                <?php if ($config): ?>
                                    <input type="hidden" name="id" value="<?= $config['IdConfigWhatsapp'] ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <!-- URL de la API -->
                                    <div class="col-md-6 mb-3">
                                        <label for="api_url" class="form-label">URL de Evolution API *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bx-link'></i></span>
                                            <input type="url" class="form-control" name="api_url" id="api_url"
                                                   value="<?= htmlspecialchars($config['api_url'] ?? 'http://localhost:8080') ?>"
                                                   placeholder="http://localhost:8080" required>
                                        </div>
                                        <small class="text-muted">Ej: http://localhost:8080</small>
                                    </div>

                                    <!-- Nombre de Instancia -->
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_instancia" class="form-label">Nombre de Instancia *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bx-server'></i></span>
                                            <input type="text" class="form-control" name="nombre_instancia" id="nombre_instancia"
                                                   value="<?= htmlspecialchars($config['nombre_instancia'] ?? '') ?>"
                                                   placeholder="Nombre de la instancia" required maxlength="100">
                                        </div>
                                    </div>

                                    <!-- API Key -->
                                    <div class="col-md-6 mb-3">
                                        <label for="api_key" class="form-label">
                                            API Key <?= $config ? '(dejar vacío para no cambiar)' : '*' ?>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bx-key'></i></span>
                                            <input type="password" class="form-control" name="api_key" id="api_key"
                                                   placeholder="<?= $config ? '••••••••••••' : 'Ingrese la API Key' ?>"
                                                   <?= !$config ? 'required' : '' ?>>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('api_key')">
                                                <i class='bx bx-show' id="icon_api_key"></i>
                                            </button>
                                        </div>
                                        <?php if ($config && !empty($config['api_key'])): ?>
                                            <small class="text-success"><i class='bx bx-check-circle'></i> API Key configurada</small>
                                        <?php else: ?>
                                            <small class="text-warning"><i class='bx bx-error'></i> API Key no configurada</small>
                                        <?php endif; ?>
                                    </div>

                                    <!-- URL de Login -->
                                    <div class="col-md-6 mb-3">
                                        <label for="login_url" class="form-label">URL de Login (opcional)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bx-log-in'></i></span>
                                            <input type="url" class="form-control" name="login_url" id="login_url"
                                                   value="<?= htmlspecialchars($config['login_url'] ?? '') ?>"
                                                   placeholder="URL para incluir en mensajes de inscripción">
                                        </div>
                                        <small class="text-muted">Se incluirá en el mensaje cuando el estudiante quede inscrito</small>
                                    </div>

                                    <!-- Estado Activo -->
                                    <div class="col-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                                   <?= (!$config || $config['activo']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="activo">Configuración Activa</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="mensajes.php" class="btn btn-outline-success btn-lg">
                                        <i class='bx bx-message-dots'></i> Gestionar Mensajes
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class='bx bxs-save'></i> <?= $config ? 'Actualizar' : 'Guardar' ?> Configuración
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card de Información -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class='bx bx-info-circle'></i> Variables disponibles para mensajes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Variables de datos:</h6>
                                    <ul class="list-unstyled">
                                        <li><code>{nombre_representante}</code> - Nombre del representante</li>
                                        <li><code>{nombre_estudiante}</code> - Nombre del estudiante</li>
                                        <li><code>{codigo_inscripcion}</code> - Código de seguimiento</li>
                                        <li><code>{curso}</code> - Nombre del curso</li>
                                        <li><code>{seccion}</code> - Sección asignada</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Variables especiales:</h6>
                                    <ul class="list-unstyled">
                                        <li><code>{cedula_representante}</code> - Cédula del representante</li>
                                        <li><code>{requisitos}</code> - Lista de requisitos (si está activo)</li>
                                        <li><code>{login_url}</code> - URL de acceso al sistema</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class='bx bx-bulb'></i> <strong>Tip:</strong> Use asteriscos para texto en <strong>*negrita*</strong> en WhatsApp
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('icon_' + inputId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
}
</script>
</body>
</html>
