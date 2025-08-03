<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    header("Location: ../vistas/login/login.php");
    exit();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/DetallePerfil.php';
require_once __DIR__ . '/../modelos/Telefono.php';

// Determinar acción (crear o editar)
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'crear':
        crearUsuario();
        break;
    case 'editar':
        editarUsuario();
        break;
    case 'eliminar':
        eliminarUsuario();
        break;
    default:
        header("Location: ../vistas/configuracion/usuario/usuario.php");
        exit();
}

function crearUsuario() {
    // Solo POST para creación
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
        exit();
    }

    // Obtener datos
    $cedula = trim($_POST['cedula'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $condicion = $_POST['condicion'] ?? 1;
    $roles = $_POST['roles'] ?? [];
    $telefonos = $_POST['telefonos'] ?? [];

    // Validar campos requeridos
    if (empty($cedula) || empty($usuario) || empty($roles)) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Campos requeridos faltantes';
        header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar duplicados
        $persona = new Persona($conexion);
        
        // Verificar usuario duplicado
        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'El nombre de usuario ya existe';
            header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
            exit();
        }

        // Verificar cédula duplicada
        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE cedula = :cedula");
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'La cédula ya está registrada';
            header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
            exit();
        }

        // Configurar datos de la persona
        $persona->IdNacionalidad = $_POST['nacionalidad'] === 'V' ? 1 : 2;
        $persona->cedula = $cedula;
        $persona->nombre = trim($_POST['nombre'] ?? '');
        $persona->apellido = trim($_POST['apellido'] ?? '');
        $persona->correo = !empty($_POST['correo']) ? trim($_POST['correo']) : null;
        $persona->usuario = $usuario;
        $persona->password = $_POST['password'] ?? '';
        $persona->IdCondicion = $condicion;

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // Guardar persona
            $idPersona = $persona->guardar();
            if (!$idPersona) {
                throw new Exception("Error al guardar la persona");
            }

            // Guardar roles
            $detallePerfil = new DetallePerfil($conexion);
            foreach ($roles as $idPerfil) {
                $detallePerfil->IdPersona = $idPersona;
                $detallePerfil->IdPerfil = $idPerfil;
                if (!$detallePerfil->guardar()) {
                    throw new Exception("Error al asignar roles");
                }
            }

            // Guardar teléfonos
            if (!empty($telefonos)) {
                $telefonoModel = new Telefono($conexion);
                foreach ($telefonos as $tel) {
                    if (!empty(trim($tel['numero']))) {
                        $telefonoModel->IdPersona = $idPersona;
                        $telefonoModel->numero_telefono = trim($tel['numero']);
                        $telefonoModel->IdTipo_Telefono = (int)$tel['tipo'];
                        
                        if (!$telefonoModel->guardar()) {
                            throw new Exception("Error al guardar teléfono");
                        }
                    }
                }
            }

            // Confirmar transacción
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['success_message'] = 'Usuario creado exitosamente';
            header("Location: ../vistas/configuracion/usuario/usuario.php");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al crear usuario: " . $e->getMessage());
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Error al crear el usuario';
            header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general al crear usuario: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error interno del servidor';
        header("Location: ../vistas/configuracion/usuario/nuevo_usuario.php");
        exit();
    }
}

function editarUsuario() {
    // Solo POST para edición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Método no permitido';
        header("Location: ../vistas/configuracion/usuario/usuario.php");
        exit();
    }

    // Obtener ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'ID de usuario inválido';
        header("Location: ../vistas/configuracion/usuario/usuario.php");
        exit();
    }

    // Validar campos requeridos
    $required = ['nombre', 'apellido', 'cedula', 'usuario', 'condicion', 'roles'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = "El campo $field es requerido";
            header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            exit();
        }
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el usuario existe
        $persona = new Persona($conexion);
        if (!$persona->obtenerPorId($id)) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'Usuario no encontrado';
            header("Location: ../vistas/configuracion/usuario/usuario.php");
            exit();
        }

        // Verificar duplicados (excluyendo al usuario actual)
        $usuario = trim($_POST['usuario']);
        $cedula = trim($_POST['cedula']);

        $stmt = $conexion->prepare("SELECT IdPersona FROM persona WHERE (usuario = :usuario OR cedula = :cedula) AND IdPersona != :id");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = 'El usuario o cédula ya están en uso por otro registro';
            header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            exit();
        }

        // Configurar datos actualizados
        $persona->IdNacionalidad = $_POST['nacionalidad'] === 'V' ? 1 : 2;
        $persona->cedula = $cedula;
        $persona->nombre = trim($_POST['nombre']);
        $persona->apellido = trim($_POST['apellido']);
        $persona->correo = !empty($_POST['correo']) ? trim($_POST['correo']) : null;
        $persona->usuario = $usuario;
        $persona->IdCondicion = (int)$_POST['condicion'];
        $roles = $_POST['roles'];
        $telefonos = $_POST['telefonos'] ?? [];
        $password = $_POST['password'] ?? '';

        // Iniciar transacción PRINCIPAL (solo esta)
        $conexion->beginTransaction();

        try {
            // 1. Actualizar datos básicos
            if (!$persona->actualizar()) {
                throw new Exception("Error al actualizar datos básicos");
            }

            // 2. Actualizar contraseña si se proporcionó
            if (!empty($password)) {
                if (!$persona->actualizarPassword($password)) {
                    throw new Exception("Error al actualizar contraseña");
                }
            }

            // 3. Actualizar roles (MODIFICADO - sin transacción interna)
            $detallePerfil = new DetallePerfil($conexion);
            if (!$detallePerfil->actualizarRoles($id, $roles, false)) { // Pasamos false para no usar transacción
                throw new Exception("Error al actualizar roles");
            }

            // 4. Actualizar teléfonos (MODIFICADO - sin transacción interna)
            $telefonoModel = new Telefono($conexion);
            if (!$telefonoModel->actualizarTelefonos($id, $telefonos, false)) { // Pasamos false para no usar transacción
                throw new Exception("Error al actualizar teléfonos");
            }

            // Confirmar transacción PRINCIPAL
            $conexion->commit();

            $_SESSION['alert'] = 'success';
            $_SESSION['success_message'] = 'Usuario actualizado correctamente';
            header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error en transacción de actualización: " . $e->getMessage());
            $_SESSION['alert'] = 'error';
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general en edición: " . $e->getMessage());
        $_SESSION['alert'] = 'error';
        $_SESSION['error_message'] = 'Error interno del servidor';
        header("Location: ../vistas/configuracion/usuario/editar_usuario.php?id=$id");
        exit();
    }
}

function eliminarUsuario() {
    // Solo permitir método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=metodo_no_permitido");
        exit();
    }

    // Obtener ID
    $id = $_GET['id'] ?? 0;
    if ($id <= 0) {
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=id_invalido");
        exit();
    }

    try {
        $database = new Database();
        $conexion = $database->getConnection();

        // Verificar que el usuario existe
        $persona = new Persona($conexion);
        if (!$persona->obtenerPorId($id)) {
            header("Location: ../vistas/configuracion/usuario/usuario.php?error=usuario_no_encontrado");
            exit();
        }

        // Iniciar transacción
        $conexion->beginTransaction();

        try {
            // 1. Eliminar teléfonos asociados
            $telefonoModel = new Telefono($conexion);
            $telefonoModel->eliminarPorPersona($id);

            // 2. Eliminar roles asociados
            $detallePerfil = new DetallePerfil($conexion);
            $detallePerfil->eliminarPorPersona($id);

            // 3. Eliminar la persona
            if (!$persona->eliminar()) {
                throw new Exception("Error al eliminar el usuario");
            }

            // Confirmar transacción
            $conexion->commit();

            header("Location: ../vistas/configuracion/usuario/usuario.php?deleted=1");
            exit();

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al eliminar usuario: " . $e->getMessage());
            header("Location: ../vistas/configuracion/usuario/usuario.php?error=operacion_fallida");
            exit();
        }

    } catch (Exception $e) {
        error_log("Error general al eliminar usuario: " . $e->getMessage());
        header("Location: ../vistas/configuracion/usuario/usuario.php?error=error_interno");
        exit();
    }
}