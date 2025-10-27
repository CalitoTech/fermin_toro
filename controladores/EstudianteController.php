<?php
// controladores/EstudianteController.php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../modelos/Telefono.php';

error_log("=== INICIO CONTROLADOR ACTUALIZAR ESTUDIANTE (Optimizado) ===");

error_log("POST recibido:");
error_log(print_r($_POST, true));

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

    // --- Asignar valores a Persona ---
    $persona->IdPersona = $idPersona;
    $persona->cedula = $_POST['cedula'] ?? null;
    $persona->nombre = $_POST['nombre'] ?? null;
    $persona->apellido = $_POST['apellido'] ?? null;
    $persona->correo = $_POST['correo'] ?? null;
    $persona->direccion = $_POST['direccion'] ?? null;
    $persona->fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $persona->IdNacionalidad = !empty($_POST['IdNacionalidad']) ? (int)$_POST['IdNacionalidad'] : null;
    $persona->IdSexo = !empty($_POST['IdSexo']) ? (int)$_POST['IdSexo'] : null;
    $persona->IdUrbanismo = !empty($_POST['IdUrbanismo']) ? (int)$_POST['IdUrbanismo'] : null;

    // --- Datos de teléfonos ---
    $telefonos = $_POST['phone_numero'] ?? [];
    $tiposTelefono = $_POST['phone_tipo'] ?? [];

    // --- Datos de discapacidades ---
    $tiposDiscapacidad = $_POST['disc_tipo'] ?? [];
    $discapacidadesTexto = $_POST['disc_text'] ?? [];

    // --- Iniciar transacción ---
    $conn->beginTransaction();

    error_log("=== Valores antes de actualizar ===");
    error_log("IdPersona=" . $persona->IdPersona);
    error_log("IdNacionalidad=" . var_export($persona->IdNacionalidad, true));
    error_log("IdSexo=" . var_export($persona->IdSexo, true));
    error_log("IdUrbanismo=" . var_export($persona->IdUrbanismo, true));

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
    error_log("💥 ERROR actualizar estudiante: " . $e->getMessage());
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'Error al actualizar: ' . $e->getMessage();
    header("Location: ../vistas/estudiantes/estudiante/editar_estudiante.php?id=" . ($idPersona ?? 0));
    exit();
}
