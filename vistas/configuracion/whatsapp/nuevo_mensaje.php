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

// Cargar datos para el select de status (solo estados sin mensaje)
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/MensajeWhatsapp.php';

$database = new Database();
$conexion = $database->getConnection();

$mensajeModel = new MensajeWhatsapp($conexion);
$statusList = $mensajeModel->obtenerStatusSinMensaje();

// Si no hay estados disponibles, redirigir a la lista
if (empty($statusList)) {
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'Todos los estados ya tienen un mensaje configurado.';
    header('Location: mensajes.php');
    exit();
}
?>

<head>
    <title>UECFT Araure - Nuevo Mensaje WhatsApp</title>
</head>

<?php include '../../layouts/menu.php'; ?>

<?php
// Verificar perfiles internos (usa $todosLosPerfiles del menu.php)
$perfilesPermitidos = [1, 6, 7, 8, 9, 10, 11, 12];
if (empty(array_intersect($todosLosPerfiles, $perfilesPermitidos))) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "No tiene permisos para acceder a esta sección",
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
?>

<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bx-message-add'></i> Nuevo Mensaje de WhatsApp</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/MensajeWhatsappController.php" method="POST" id="formMensaje">
                                <input type="hidden" name="action" value="crear">

                                <div class="row">
                                    <!-- Status de Inscripción -->
                                    <div class="col-md-6 mb-3">
                                        <label for="id_status" class="form-label">Status de Inscripción *</label>
                                        <select class="form-select" name="id_status" id="id_status" required>
                                            <option value="">Seleccione un status...</option>
                                            <?php foreach ($statusList as $status): ?>
                                                <option value="<?= $status['IdStatus'] ?>">
                                                    <?= htmlspecialchars($status['status']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">El mensaje se enviará cuando la inscripción cambie a este estado</small>
                                    </div>

                                    <!-- Título -->
                                    <div class="col-md-6 mb-3">
                                        <label for="titulo" class="form-label">Título del Mensaje *</label>
                                        <input type="text" class="form-control" name="titulo" id="titulo"
                                               placeholder="Ej: Solicitud Recibida" required maxlength="100">
                                        <small class="text-muted">Identificador interno del mensaje</small>
                                    </div>

                                    <!-- Contenido -->
                                    <div class="col-12 mb-3">
                                        <label for="contenido" class="form-label">Contenido del Mensaje *</label>
                                        <textarea class="form-control" name="contenido" id="contenido"
                                                  rows="12" required
                                                  placeholder="Escriba el mensaje aquí..."></textarea>
                                        <small class="text-muted">
                                            Use las variables: {nombre_representante}, {nombre_estudiante}, {codigo_inscripcion}, {curso}, {seccion}, {cedula_representante}, {requisitos}, {login_url}
                                        </small>
                                    </div>

                                    <!-- Opciones -->
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="incluir_requisitos" id="incluir_requisitos">
                                            <label class="form-check-label" for="incluir_requisitos">
                                                <i class='bx bx-list-check'></i> Incluir lista de requisitos
                                            </label>
                                        </div>
                                        <small class="text-muted">Se reemplazará {requisitos} con la lista de requisitos del nivel</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
                                            <label class="form-check-label" for="activo">
                                                <i class='bx bx-check-circle'></i> Mensaje Activo
                                            </label>
                                        </div>
                                        <small class="text-muted">Solo un mensaje activo por status</small>
                                    </div>
                                </div>

                                <!-- Vista previa -->
                                <div class="card bg-light mb-4">
                                    <div class="card-header">
                                        <i class='bx bx-show'></i> Vista Previa (aproximada)
                                    </div>
                                    <div class="card-body">
                                        <div id="preview" class="bg-white p-3 rounded border"
                                             style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; max-height: 300px; overflow-y: auto;">
                                            El mensaje aparecerá aquí...
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="mensajes.php" class="btn btn-outline-danger btn-lg">
                                        <i class='bx bx-arrow-back'></i> Volver a Mensajes
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Mensaje
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contenido = document.getElementById('contenido');
    const preview = document.getElementById('preview');

    // Actualizar vista previa en tiempo real
    contenido.addEventListener('input', updatePreview);

    function updatePreview() {
        let texto = contenido.value;

        // Reemplazar variables de ejemplo
        texto = texto.replace(/{nombre_representante}/g, 'Juan Pérez');
        texto = texto.replace(/{nombre_estudiante}/g, 'María Pérez');
        texto = texto.replace(/{codigo_inscripcion}/g, 'INS-2024-00123');
        texto = texto.replace(/{curso}/g, '1er Grado');
        texto = texto.replace(/{seccion}/g, 'A');
        texto = texto.replace(/{cedula_representante}/g, '12345678');
        texto = texto.replace(/{requisitos}/g, '\n- Partida de nacimiento\n- Foto tipo carnet\n- Constancia de estudios');
        texto = texto.replace(/{login_url}/g, 'Acceda aquí: https://ejemplo.com/login');

        // Simular formato WhatsApp (negrita)
        texto = texto.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');

        preview.innerHTML = texto || 'El mensaje aparecerá aquí...';
    }
});
</script>
</body>
</html>
