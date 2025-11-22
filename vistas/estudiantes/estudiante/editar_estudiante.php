<?php
session_start();
require_once __DIR__ . '/../../../controladores/Notificaciones.php';
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';
require_once __DIR__ . '/../../../modelos/TipoDiscapacidad.php';
// === CONEXIÓN ===
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
$id = intval($_GET['id']);

// === INSTANCIAS DE MODELOS ===
$personaModel = new Persona($conexion);
$nacionalidadModel = new Nacionalidad($conexion);
$sexoModel = new Sexo($conexion);
$urbanismoModel = new Urbanismo($conexion);
$tipoTelefonoModel = new TipoTelefono($conexion);
$tipoDiscapacidadModel = new TipoDiscapacidad($conexion);

// === OBTENER DATOS ===
$estudiante = $personaModel->obtenerEstudiantePorId($id);
if (!$estudiante) {
    header("Location: estudiante.php");
    exit();
}

$seccionActual = $personaModel->obtenerSeccionActualEstudiante($id);
$discapacidades = $personaModel->obtenerDiscapacidadesEstudiante($id);
$nacionalidades = $nacionalidadModel->obtenerTodos();
$sexos = $sexoModel->obtenerTodos();
$urbanismos = $urbanismoModel->obtenerTodos();
$tiposTelefono = $tipoTelefonoModel->obtenerTodos();
$tiposDiscapacidad = $tipoDiscapacidadModel->obtenerTodos();

// === TELÉFONOS EXISTENTES ===
require_once __DIR__ . '/../../../modelos/Telefono.php';
$telefonoModel = new Telefono($conexion);
$telefonos = $telefonoModel->obtenerPorPersona($id);

// === ALERTAS ===
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    $alerta = match ($alert) {
        'success' => Notificaciones::exito("El representante se actualizó correctamente."),
        'error' => Notificaciones::advertencia("Error al actualizar el representante."),
        default => null
    };
    if ($alerta) Notificaciones::mostrar($alerta);
}

// === FUNCIONES UTILES ===
function selected($a, $b) { return $a == $b ? 'selected' : ''; }
function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>UECFT Araure - Editar Estudiante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
    <style>
        .telefonos-list .telefono-row, .discap-list .disc-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
        .telefono-row input[type="text"], .disc-row input[type="text"], .disc-row select { width:100%; }
        .btn-small { padding:.25rem .5rem; font-size:.85rem; }
        .actions-col { width:120px; text-align:center; }
    </style>
</head>
<body>
<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content">
        <div class="container">
            <h2 class="mb-4">Editar Estudiante</h2>

            <!-- 🚫 Sin lógica de actualización, solo muestra formulario -->
            <form id="form-editar-estudiante" method="POST" action="../../../controladores/EstudianteController.php">
                <input type="hidden" name="accion" value="actualizar_estudiante">
                <input type="hidden" name="IdPersona" value="<?= esc($id) ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-user-graduate me-2"></i> Datos personales
                    </div>
                    <div class="card-body info-grid">
                        <div class="info-item">
                            <strong>Nombre:</strong>
                            <input type="text" name="nombre" class="form-control" value="<?= esc($estudiante['nombre']) ?>" required>
                        </div>
                        <div class="info-item">
                            <strong>Apellido:</strong>
                            <input type="text" name="apellido" class="form-control" value="<?= esc($estudiante['apellido']) ?>" required>
                        </div>
                        <div class="info-item">
                            <strong>Cédula:</strong>
                            <input type="text" name="cedula" class="form-control" value="<?= esc($estudiante['cedula']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Correo:</strong>
                            <input type="email" name="correo" class="form-control" value="<?= esc($estudiante['correo']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Dirección:</strong>
                            <input type="text" name="direccion" class="form-control" value="<?= esc($estudiante['direccion']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Fecha de nacimiento:</strong>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= esc($estudiante['fecha_nacimiento']) ?>">
                        </div>
                        <div class="info-item">
                            <strong>Nacionalidad:</strong>
                            <select name="IdNacionalidad" class="form-select">
                                <?php foreach ($nacionalidades as $n): ?>
                                    <option value="<?= $n['IdNacionalidad'] ?>" <?= selected($estudiante['IdNacionalidad'], $n['IdNacionalidad']) ?>><?= esc($n['nacionalidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="info-item">
                            <strong>Sexo:</strong>
                            <select name="IdSexo" class="form-select">
                                <?php foreach ($sexos as $s): ?>
                                    <option value="<?= $s['IdSexo'] ?>" <?= selected($estudiante['IdSexo'], $s['IdSexo']) ?>><?= esc($s['sexo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="info-item">
                            <strong>Urbanismo:</strong>
                            <select name="IdUrbanismo" class="form-select">
                                <?php foreach ($urbanismos as $u): ?>
                                    <option value="<?= $u['IdUrbanismo'] ?>" <?= selected($estudiante['IdUrbanismo'], $u['IdUrbanismo']) ?>><?= esc($u['urbanismo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="info-item">
                            <strong>Sección actual:</strong>
                            <input type="text" class="form-control" 
                                value="<?= $seccionActual ? esc($seccionActual['curso'].' "'.$seccionActual['seccion'].'"') : 'No inscrito actualmente' ?>" 
                                readonly>
                        </div>
                    </div>
                </div>

                <!-- Teléfonos -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-phone me-2"></i> Teléfonos</div>
                        <button type="button" id="btn-add-phone" class="btn btn-light btn-small">Agregar teléfono</button>
                    </div>
                    <div class="card-body">
                        <div id="telefonos-container" class="telefonos-list">
                            <?php if (!empty($telefonos)): ?>
                                <?php foreach ($telefonos as $t): ?>
                                    <div class="telefono-row">
                                        <select name="phone_tipo[]" class="form-select" style="width:180px;">
                                            <?php foreach ($tiposTelefono as $tt): ?>
                                                <option value="<?= $tt['IdTipo_Telefono'] ?>" <?= selected($t['IdTipo_Telefono'], $tt['IdTipo_Telefono']) ?>><?= esc($tt['tipo_telefono']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="phone_numero[]" class="form-control" value="<?= esc($t['numero_telefono']) ?>" placeholder="Número" style="min-width: 250px;">
                                        <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="telefono-row">
                                    <select name="phone_tipo[]" class="form-select" style="width:180px;">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tiposTelefono as $tt): ?>
                                            <option value="<?= $tt['IdTipo_Telefono'] ?>"><?= esc($tt['tipo_telefono']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="phone_numero[]" class="form-control" placeholder="Número" style="min-width: 250px;">
                                    <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Discapacidades -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-wheelchair me-2"></i> Discapacidad(es)</div>
                        <button type="button" id="btn-add-disc" class="btn btn-light btn-small">Agregar discapacidad</button>
                    </div>
                    <div class="card-body">
                        <div id="discap-container" class="discap-list">
                            <?php if (!empty($discapacidades)): ?>
                                <?php foreach ($discapacidades as $d): ?>
                                    <div class="disc-row">
                                        <select name="disc_tipo[]" class="form-select" style="width:200px;">
                                            <?php foreach ($tiposDiscapacidad as $td): ?>
                                                <option value="<?= $td['IdTipo_Discapacidad'] ?>" <?= selected($d['IdTipo_Discapacidad'], $td['IdTipo_Discapacidad']) ?>><?= esc($td['tipo_discapacidad']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="disc_text[]" class="form-control" value="<?= esc($d['discapacidad']) ?>" placeholder="Descripción / detalle">
                                        <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="disc-row">
                                    <select name="disc_tipo[]" class="form-select" style="width:200px;">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tiposDiscapacidad as $td): ?>
                                            <option value="<?= $td['IdTipo_Discapacidad'] ?>"><?= esc($td['tipo_discapacidad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="disc_text[]" class="form-control" placeholder="Descripción / detalle">
                                    <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="estudiante.php" class="btn btn-secondary">Volver</a>
                    <button type="submit" class="btn btn-danger">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script>
// --- Dinámica de teléfonos y discapacidades ---
document.addEventListener('DOMContentLoaded', () => {
    const telContainer = document.getElementById('telefonos-container');
    document.getElementById('btn-add-phone').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'telefono-row';
        row.innerHTML = `
            <select name="phone_tipo[]" class="form-select" style="width:180px;">
                <option value="">Seleccione</option>
                <?php foreach ($tiposTelefono as $tt): ?>
                    <option value="<?= $tt['IdTipo_Telefono'] ?>"><?= esc($tt['tipo_telefono']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="phone_numero[]" class="form-control" placeholder="Número" style="min-width: 250px;">
            <button type="button" class="btn btn-outline-danger btn-small btn-remove-phone">Eliminar</button>
        `;
        telContainer.appendChild(row);
    });
    telContainer.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-phone')) e.target.closest('.telefono-row').remove();
    });

    const discContainer = document.getElementById('discap-container');
    document.getElementById('btn-add-disc').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'disc-row';
        row.innerHTML = `
            <select name="disc_tipo[]" class="form-select" style="width:200px;">
                <option value="">Seleccione</option>
                <?php foreach ($tiposDiscapacidad as $td): ?>
                    <option value="<?= $td['IdTipo_Discapacidad'] ?>"><?= esc($td['tipo_discapacidad']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="disc_text[]" class="form-control" placeholder="Descripción / detalle">
            <button type="button" class="btn btn-outline-danger btn-small btn-remove-disc">Eliminar</button>
        `;
        discContainer.appendChild(row);
    });
    discContainer.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-disc')) e.target.closest('.disc-row').remove();
    });
});
</script>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
