<?php
session_start();

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

// === VERIFICACIÓN DEL ID EN URL ===
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: representante.php");
    exit();
}

// === INCLUSIÓN DE DEPENDENCIAS ===
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Persona.php';
require_once __DIR__ . '/../../../modelos/Sexo.php';
require_once __DIR__ . '/../../../modelos/Urbanismo.php';
require_once __DIR__ . '/../../../modelos/Nacionalidad.php';
require_once __DIR__ . '/../../../modelos/Parentesco.php';
require_once __DIR__ . '/../../../modelos/Representante.php';
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';
require_once __DIR__ . '/../../../modelos/Telefono.php'; // 🔹 NUEVO
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// === CONEXIÓN Y MODELOS ===
$database = new Database();
$conexion = $database->getConnection();

$personaModel = new Persona($conexion);
$representanteModel = new Representante($conexion);
$tipoTelefonoModel = new TipoTelefono($conexion);
$telefonoModel = new Telefono($conexion); // 🔹 NUEVO

// Modelos auxiliares
$sexoModel = new Sexo($conexion);
$urbanismoModel = new Urbanismo($conexion);
$nacionalidadModel = new Nacionalidad($conexion);
$parentescoModel = new Parentesco($conexion);

// === CARGA DE DATOS ===
$representante = $representanteModel->obtenerPorIdPersona($id);
$persona = $personaModel->obtenerPorId($id);
$telefonos = $telefonoModel->obtenerPorPersona($id); // 🔹 TELÉFONOS ASOCIADOS

if (!$representante || !$persona) {
    header("Location: representante.php");
    exit();
}

// === CARGA DE CATÁLOGOS ===
$nacionalidades = $nacionalidadModel->obtenerTodos();
$sexos = $sexoModel->obtenerTodos();
$urbanismos = $urbanismoModel->obtenerTodos();
$parentescos = $parentescoModel->obtenerTodos();
$tiposTelefono = $tipoTelefonoModel->obtenerTodos();

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

// === FUNCIÓN AUXILIAR ===
function selected($a, $b) { return $a == $b ? 'selected' : ''; }

?>

<head>
    <title>Editar Representante</title>
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
    <style>
        .form-label { font-weight: 600; color: #333; }
        .add-phone { color: #c90000; cursor: pointer; font-size: 0.9rem; }
        .remove-phone { color: #dc3545; cursor: pointer; margin-left: 8px; }
    </style>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<section class="home-section">
    <div class="main-content container">
        <div class="row justify-content-center mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2 class="mb-1">Editar Representante</h2>
                    <a href="representante.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- FORMULARIO -->
                <form action="../../../controladores/RepresentanteController.php" method="POST" id="editar" class="card shadow-sm p-4">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="row g-3">
                        <!-- DATOS PERSONA -->
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($persona['nombre']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido" class="form-control" required value="<?= htmlspecialchars($persona['apellido']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cédula</label>
                            <input type="text" name="cedula" class="form-control" required value="<?= htmlspecialchars($persona['cedula']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <select name="idNacionalidad" class="form-select">
                                <?php foreach ($nacionalidades as $n): ?>
                                    <option value="<?= $n['IdNacionalidad'] ?>" <?= selected($n['IdNacionalidad'], $persona['IdNacionalidad']) ?>>
                                        <?= $n['nacionalidad'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select name="idSexo" class="form-select">
                                <option value="">Seleccione</option>
                                <?php foreach ($sexos as $s): ?>
                                    <option value="<?= $s['IdSexo'] ?>" <?= selected($s['IdSexo'], $persona['IdSexo']) ?>>
                                        <?= $s['sexo'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($persona['correo']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Parentesco</label>
                            <select name="idParentesco" class="form-select">
                                <?php foreach ($parentescos as $p): ?>
                                    <option value="<?= $p['IdParentesco'] ?>" <?= selected($p['IdParentesco'], $representante['IdParentesco']) ?>>
                                        <?= $p['parentesco'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Urbanismo</label>
                            <select name="urbanismo" class="form-select">
                                <?php foreach ($urbanismos as $u): ?>
                                    <option value="<?= $u['IdUrbanismo'] ?>" <?= selected($u['IdUrbanismo'], $persona['IdUrbanismo']) ?>>
                                        <?= $u['urbanismo'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($persona['direccion']) ?>">
                        </div>

                        <!-- DATOS LABORALES -->
                        <div class="col-md-6">
                            <label class="form-label">Ocupación</label>
                            <input type="text" name="ocupacion" class="form-control" value="<?= htmlspecialchars($representante['ocupacion']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lugar de trabajo</label>
                            <input type="text" name="lugar_trabajo" class="form-control" value="<?= htmlspecialchars($representante['lugar_trabajo']) ?>">
                        </div>

                        <!-- TELÉFONOS -->
                        <div class="col-12">
                            <label class="form-label">Teléfonos</label>
                            <div id="telefonos-container">
                                <?php if (!empty($telefonos)): ?>
                                    <?php foreach ($telefonos as $t): ?>
                                        <div class="row g-2 mb-2 phone-group">
                                            <div class="col-md-6">
                                                <input type="text" name="telefono[]" class="form-control" placeholder="Número" value="<?= htmlspecialchars($t['numero_telefono']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <select name="tipo_telefono[]" class="form-select">
                                                    <?php foreach ($tiposTelefono as $tipo): ?>
                                                        <option value="<?= $tipo['IdTipo_Telefono'] ?>" <?= selected($tipo['IdTipo_Telefono'], $t['IdTipo_Telefono']) ?>>
                                                            <?= $tipo['tipo_telefono'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <i class="fas fa-minus-circle remove-phone"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row g-2 mb-2 phone-group">
                                        <div class="col-md-6">
                                            <input type="text" name="telefono[]" class="form-control" placeholder="Número">
                                        </div>
                                        <div class="col-md-4">
                                            <select name="tipo_telefono[]" class="form-select">
                                                <option value="">Tipo</option>
                                                <?php foreach ($tiposTelefono as $tipo): ?>
                                                    <option value="<?= $tipo['IdTipo_Telefono'] ?>"><?= $tipo['tipo_telefono'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <i class="fas fa-minus-circle remove-phone"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="add-phone mt-2"><i class="fas fa-plus-circle me-1"></i> Añadir teléfono</div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script>
document.querySelector('.add-phone').addEventListener('click', () => {
    const container = document.getElementById('telefonos-container');
    const html = `
        <div class="row g-2 mb-2 phone-group">
            <div class="col-md-6">
                <input type="text" name="telefono[]" class="form-control" placeholder="Número">
            </div>
            <div class="col-md-4">
                <select name="tipo_telefono[]" class="form-select">
                    <option value="">Tipo</option>
                    <?php foreach ($tiposTelefono as $t): ?>
                        <option value="<?= $t['IdTipo_Telefono'] ?>"><?= $t['tipo_telefono'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <i class="fas fa-minus-circle remove-phone"></i>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-phone')) {
        e.target.closest('.phone-group').remove();
    }
});
</script>
