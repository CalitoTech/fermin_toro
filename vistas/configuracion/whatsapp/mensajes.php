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
        case 'deleted':
            $alerta = Notificaciones::exito($message ?: 'Mensaje eliminado correctamente.');
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
require_once __DIR__ . '/../../../modelos/MensajeWhatsapp.php';

$database = new Database();
$conexion = $database->getConnection();

$mensajeModel = new MensajeWhatsapp($conexion);
$mensajes = $mensajeModel->obtenerTodos();
$statusDisponibles = $mensajeModel->obtenerStatusSinMensaje();

// Solo admin puede ver configuración API
$esAdmin = ($_SESSION['idPerfil'] == 1);
?>

<head>
    <title>UECFT Araure - Mensajes WhatsApp</title>
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class='bx bx-message-dots'></i> Mensajes de WhatsApp por Estado de Inscripción
                        </div>
                        <div class="card-body">
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <?php if ($esAdmin): ?>
                                    <a href="whatsapp.php" class="btn btn-success d-flex align-items-center">
                                        <i class='bx bx-cog me-1'></i> Configuración API
                                    </a>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                <?php if (!empty($statusDisponibles)): ?>
                                    <a href="nuevo_mensaje.php" class="btn btn-danger d-flex align-items-center">
                                        <i class='bx bx-plus-medical me-1'></i> Nuevo Mensaje
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">
                                        <i class='bx bx-info-circle'></i> Todos los estados ya tienen mensaje
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Búsqueda y Entradas -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div class="flex-grow-1" style="max-width: 300px;">
                                    <input type="text" class="search-input" id="buscar" placeholder="Buscar...">
                                </div>
                                <div class="d-flex align-items-center">
                                    <label for="entries" class="me-2">Entradas por página:</label>
                                    <select id="entries" class="form-select" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="tabla-mensajes">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Título</th>
                                            <th>Requisitos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php foreach ($mensajes as $msg): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($msg['IdMensajeWhatsapp']) ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($msg['nombre_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($msg['titulo']) ?></td>
                                                <td>
                                                    <?php if ($msg['incluir_requisitos']): ?>
                                                        <span class="badge bg-info"><i class='bx bx-check'></i> Sí</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($msg['activo']): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="editar_mensaje.php?id=<?= $msg['IdMensajeWhatsapp'] ?>"
                                                       class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                                        <i class='bx bxs-edit'></i>
                                                    </a>
                                                    <?php if ($esAdmin): ?>
                                                        <?php if ($msg['activo']): ?>
                                                            <button class="btn btn-sm btn-outline-warning"
                                                                    onclick="cambiarEstado(<?= $msg['IdMensajeWhatsapp'] ?>, 0)"
                                                                    title="Desactivar">
                                                                <i class='bx bx-hide'></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-success"
                                                                    onclick="cambiarEstado(<?= $msg['IdMensajeWhatsapp'] ?>, 1)"
                                                                    title="Activar">
                                                                <i class='bx bx-show'></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-center mt-3">
                                <div class="pagination" id="pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabla = document.getElementById('tabla-mensajes');
        const tbody = document.getElementById('table-body');
        const buscarInput = document.getElementById('buscar');
        const entriesSelect = document.getElementById('entries');
        const paginationDiv = document.getElementById('pagination');

        let filas = Array.from(tbody.querySelectorAll('tr'));
        let filasFiltradas = [...filas];
        let paginaActual = 1;
        let entradasPorPagina = parseInt(entriesSelect.value);

        // Función para filtrar
        function filtrar() {
            const termino = buscarInput.value.toLowerCase();
            filasFiltradas = filas.filter(fila => {
                return fila.textContent.toLowerCase().includes(termino);
            });
            paginaActual = 1;
            mostrar();
        }

        // Función para mostrar filas con paginación
        function mostrar() {
            const inicio = (paginaActual - 1) * entradasPorPagina;
            const fin = inicio + entradasPorPagina;

            filas.forEach(fila => fila.style.display = 'none');
            filasFiltradas.slice(inicio, fin).forEach(fila => fila.style.display = '');

            renderizarPaginacion();
        }

        // Función para renderizar paginación
        function renderizarPaginacion() {
            const totalPaginas = Math.ceil(filasFiltradas.length / entradasPorPagina);
            paginationDiv.innerHTML = '';

            if (totalPaginas <= 1) return;

            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.className = `btn btn-sm ${i === paginaActual ? 'btn-danger' : 'btn-outline-danger'} mx-1`;
                btn.textContent = i;
                btn.onclick = () => {
                    paginaActual = i;
                    mostrar();
                };
                paginationDiv.appendChild(btn);
            }
        }

        // Event listeners
        buscarInput.addEventListener('input', filtrar);
        entriesSelect.addEventListener('change', function() {
            entradasPorPagina = parseInt(this.value);
            paginaActual = 1;
            mostrar();
        });

        // Mostrar inicial
        mostrar();
    });

    function cambiarEstado(id, activo) {
        const mensaje = activo ? 'activar' : 'desactivar';
        Swal.fire({
            title: `¿Desea ${mensaje} este mensaje?`,
            icon: "question",
            showDenyButton: true,
            confirmButtonText: "Sí",
            denyButtonText: "No"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../../controladores/MensajeWhatsappController.php?action=cambiar_estado&id=${id}&activo=${activo}`;
            }
        });
    }
</script>
</body>
</html>
