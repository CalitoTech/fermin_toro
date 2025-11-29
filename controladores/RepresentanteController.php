<?php
// controladores/RepresentanteController.php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/Persona.php';
require_once __DIR__ . '/../utils/Validador.php';


try {
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
        header("Location: ../vistas/login/login.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['alert'] = 'error';
        header("Location: ../vistas/estudiantes/representante/representante.php");
        exit();
    }

    $idPersona = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($idPersona <= 0) {
        error_log("❌ ID inválido recibido: " . ($_POST['id'] ?? 'null'));
        $_SESSION['alert'] = 'error';
        header("Location: ../vistas/estudiantes/representante/representante.php");
        exit();
    }

    // Conexión y modelo
    $db = new Database();
    $conn = $db->getConnection();
    $persona = new Persona($conn);

    // === 🔒 VALIDACIONES DEL LADO DEL SERVIDOR ===

    // Validar nombre (obligatorio)
    Validador::nombreValido($_POST['nombre'] ?? '');

    // Validar apellido (obligatorio)
    Validador::apellidoValido($_POST['apellido'] ?? '');

    // Validar cédula (obligatoria para representantes, siempre 7-8 dígitos)
    Validador::validarCedula($_POST['cedula'] ?? '', true, false);

    // Validar correo (opcional)
    Validador::validarCorreo($_POST['correo'] ?? '', false);

    // Validar dirección (opcional)
    Validador::validarDireccion($_POST['direccion'] ?? '', false);

    // Validar ocupación (opcional)
    Validador::validarTexto($_POST['ocupacion'] ?? '', 'La ocupación', 3, 100, false);

    // Validar lugar de trabajo (opcional)
    Validador::validarTexto($_POST['lugar_trabajo'] ?? '', 'El lugar de trabajo', 3, 100, false);

    // Verificar duplicado de cédula (excepto él mismo)
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
        header("Location: ../vistas/estudiantes/representante/editar_representante.php?id=" . $idPersona);
        exit();
    }

    // Asignar valores al modelo Persona (sanitizados)
    $persona->IdPersona = $idPersona;
    $persona->cedula = Validador::sanitizar($_POST['cedula']);
    $persona->nombre = Validador::sanitizar($_POST['nombre']);
    $persona->apellido = Validador::sanitizar($_POST['apellido']);
    $persona->correo = !empty($_POST['correo']) ? Validador::sanitizar($_POST['correo']) : null;
    $persona->direccion = !empty($_POST['direccion']) ? Validador::sanitizar($_POST['direccion']) : null;
    $persona->IdNacionalidad = $_POST['idNacionalidad'] ?? null;
    $persona->IdSexo = $_POST['idSexo'] ?? null;
    $persona->IdUrbanismo = $_POST['urbanismo'] ?? null;
    $persona->IdEstadoAcceso = isset($_POST['idEstadoAcceso']) && $_POST['idEstadoAcceso'] !== ''
        ? (int)$_POST['idEstadoAcceso']
        : null;

    $persona->IdEstadoInstitucional = isset($_POST['idEstadoInstitucional']) && $_POST['idEstadoInstitucional'] !== ''
        ? (int)$_POST['idEstadoInstitucional']
        : null;

    $telefonos = $_POST['telefono'] ?? [];
    $tipos = $_POST['tipo_telefono'] ?? [];

    // Validar teléfonos
    foreach ($telefonos as $telefono) {
        if (!empty(trim($telefono))) {
            Validador::validarTelefono($telefono, false);
        }
    }

    $conn->beginTransaction();

    // ✅ Actualizar persona usando el modelo
    if (!$persona->actualizar()) {
        throw new Exception("Error al actualizar la persona");
    }

    // ✅ Actualizar representante
    $sqlRep = "
        UPDATE representante SET
            IdParentesco = :IdParentesco,
            ocupacion = :ocupacion,
            lugar_trabajo = :lugar_trabajo
        WHERE IdPersona = :idPersona
    ";
    $stmtRep = $conn->prepare($sqlRep);
    $stmtRep->execute([
        ':IdParentesco' => $_POST['idParentesco'] ?? null,
        ':ocupacion' => $_POST['ocupacion'] ?? null,
        ':lugar_trabajo' => $_POST['lugar_trabajo'] ?? null,
        ':idPersona' => $idPersona
    ]);
    error_log("✅ Representante actualizado");

    // ✅ Teléfonos
    $conn->prepare("DELETE FROM telefono WHERE IdPersona = :id")
        ->execute([':id' => $idPersona]);

    if (!empty($telefonos)) {
        $insTel = $conn->prepare("
            INSERT INTO telefono (IdPersona, IdTipo_Telefono, numero_telefono)
            VALUES (:idPersona, :tipo, :num)
        ");
        foreach ($telefonos as $i => $num) {
            $num = trim($num);
            if ($num === '') continue;
            $tipo = $tipos[$i] ?? null;
            $insTel->execute([
                ':idPersona' => $idPersona,
                ':tipo' => $tipo ?: null,
                ':num' => $num
            ]);
        }
    }

    $conn->commit();

    $_SESSION['alert'] = 'actualizar';
    $_SESSION['message'] = 'Representante actualizado correctamente.';
    header("Location: ../vistas/estudiantes/representante/representante.php");
    exit();

} catch (Exception $e) {
    if (!empty($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("💥 ERROR actualizar representante: " . $e->getMessage());
    $_SESSION['alert'] = 'error';
    $_SESSION['message'] = 'Error al actualizar: ' . $e->getMessage();
    header("Location: ../vistas/estudiantes/representante/editar_representante.php?id=" . ($idPersona ?? 0));
    exit();
}
