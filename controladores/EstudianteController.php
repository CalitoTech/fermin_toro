<?php
// controladores/EstudianteController.php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/Telefono.php';
require_once __DIR__ . '/../utils/Validador.php';

try {
    // --- Validar sesión ---
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
        header("Location: ../vistas/login/login.php");
        exit();
    }

    // --- Validar método ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['alert'] = 'error';
        header("Location: ../vistas/estudiantes/estudiante/estudiante.php");
        exit();
    }

    // --- Obtener ID persona ---
    $idPersona = isset($_POST['IdPersona']) ? (int)$_POST['IdPersona'] : 0;
    if ($idPersona <= 0) {
        error_log("❌ ID inválido recibido: " . ($_POST['IdPersona'] ?? 'null'));
        $_SESSION['alert'] = 'error';
        header("Location: ../vistas/estudiantes/estudiante/estudiante.php");
        exit();
    }

    // --- Conexión y modelos ---
    $db = new Database();
    $conn = $db->getConnection();
    $persona = new Persona($conn);
    $telefonoModel = new Telefono($conn);

    // === 🔒 VALIDACIONES DEL LADO DEL SERVIDOR ===

    // Validar nombre (obligatorio)
    Validador::nombreValido($_POST['nombre'] ?? '');

    // Validar apellido (obligatorio)
    Validador::apellidoValido($_POST['apellido'] ?? '');

    // Validar fecha de nacimiento (obligatorio) y determinar si es cédula escolar
    $fechaNacimiento = $_POST['fecha_nacimiento'] ?? '';
    Validador::validarFechaNacimiento($fechaNacimiento, 3, 25);

    // Calcular edad para determinar tipo de cédula
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha)->y;
    $esCedulaEscolar = ($edad >= 3 && $edad <= 9);

    // Validar cédula (opcional, pero si se proporciona debe ser válida)
    Validador::validarCedula($_POST['cedula'] ?? '', false, $esCedulaEscolar);

    // Validar correo (opcional)
    Validador::validarCorreo($_POST['correo'] ?? '', false);

    // Validar dirección (opcional)
    Validador::validarDireccion($_POST['direccion'] ?? '', false);

    // --- Verificar duplicado de cédula ---
    if (!empty($_POST['cedula'])) {
        $stmtCheck = $conn->prepare("
            SELECT IdPersona FROM persona
            WHERE cedula = :cedula AND IdPersona != :id
            LIMIT 1
        ");
        $stmtCheck->execute([
            ':cedula' => $_POST['cedula'],
            ':id' => $idPersona
        ]);

        if ($stmtCheck->fetch()) {
            $_SESSION['alert'] = 'cedula_existente';
            $_SESSION['message'] = 'La cédula ya pertenece a otro registro.';
            header("Location: ../vistas/estudiantes/estudiante/editar_estudiante.php?id=" . $idPersona);
            exit();
        }
    }

    // --- Asignar valores a Persona (sanitizados) ---
    $persona->IdPersona = $idPersona;
    $persona->cedula = !empty($_POST['cedula']) ? Validador::sanitizar($_POST['cedula']) : null;
    $persona->nombre = Validador::sanitizar($_POST['nombre']);
    $persona->apellido = Validador::sanitizar($_POST['apellido']);
    $persona->correo = !empty($_POST['correo']) ? Validador::sanitizar($_POST['correo']) : null;
    $persona->direccion = !empty($_POST['direccion']) ? Validador::sanitizar($_POST['direccion']) : null;
    $persona->fecha_nacimiento = $fechaNacimiento;
    $persona->IdNacionalidad = !empty($_POST['IdNacionalidad']) ? (int)$_POST['IdNacionalidad'] : null;
    $persona->IdSexo = !empty($_POST['IdSexo']) ? (int)$_POST['IdSexo'] : null;
    $persona->IdUrbanismo = !empty($_POST['IdUrbanismo']) ? (int)$_POST['IdUrbanismo'] : null;

    // --- Datos de teléfonos ---
    $telefonos = $_POST['phone_numero'] ?? [];
    $tiposTelefono = $_POST['phone_tipo'] ?? [];

    // Validar teléfonos
    foreach ($telefonos as $telefono) {
        if (!empty(trim($telefono))) {
            Validador::validarTelefono($telefono, false);
        }
    }

    // --- Datos de discapacidades ---
    $tiposDiscapacidad = $_POST['disc_tipo'] ?? [];
    $discapacidadesTexto = $_POST['disc_text'] ?? [];

    // --- Iniciar transacción ---
    $conn->beginTransaction();

    // --- Actualizar persona ---
    if (!$persona->actualizar()) {
        throw new Exception("Error al actualizar los datos del estudiante");
    }
    error_log("✅ Persona actualizada correctamente (ID={$idPersona})");

    // --- Actualizar teléfonos ---
    $conn->prepare("DELETE FROM telefono WHERE IdPersona = :id")
        ->execute([':id' => $idPersona]);

    if (!empty($telefonos)) {
        $stmtTel = $conn->prepare("
            INSERT INTO telefono (IdPersona, IdTipo_Telefono, numero_telefono)
            VALUES (:idPersona, :tipo, :numero)
        ");
        foreach ($telefonos as $i => $num) {
            $num = trim($num);
            if ($num === '') continue;
            $tipo = $tiposTelefono[$i] ?? null;
            $stmtTel->execute([
                ':idPersona' => $idPersona,
                ':tipo' => $tipo ?: null,
                ':numero' => $num
            ]);
        }
    }

    // --- Actualizar discapacidades ---
    $conn->prepare("DELETE FROM discapacidad WHERE IdPersona = :id")
        ->execute([':id' => $idPersona]);

    if (!empty($tiposDiscapacidad)) {
        $stmtDisc = $conn->prepare("
            INSERT INTO discapacidad (IdPersona, IdTipo_Discapacidad, discapacidad)
            VALUES (:idPersona, :tipo, :detalle)
        ");
        foreach ($tiposDiscapacidad as $i => $tipoDisc) {
            if (empty($tipoDisc) && empty($discapacidadesTexto[$i])) continue;
            $stmtDisc->execute([
                ':idPersona' => $idPersona,
                ':tipo' => $tipoDisc ?: null,
                ':detalle' => $discapacidadesTexto[$i] ?? ''
            ]);
        }
    }

    // --- Confirmar cambios ---
    $conn->commit();

    error_log("🎉 Estudiante actualizado correctamente (ID={$idPersona})");
    $_SESSION['alert'] = 'actualizar';
    $_SESSION['message'] = 'Estudiante actualizado correctamente.';
    header("Location: ../vistas/estudiantes/estudiante/estudiante.php");
    exit();

} catch (Exception $e) {
    if (!empty($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'Error al actualizar: ' . $e->getMessage();
    header("Location: ../vistas/estudiantes/estudiante/editar_estudiante.php?id=" . ($idPersona ?? 0));
    exit();
}
