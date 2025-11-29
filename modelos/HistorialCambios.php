<?php
require_once __DIR__ . '/../config/conexion.php';

class HistorialCambios {
    private $conn;
    public $IdHistorial;
    public $IdInscripcion;
    public $IdPersona;
    public $tipo_entidad;
    public $campo_modificado;
    public $valor_anterior;
    public $valor_nuevo;
    public $descripcion;
    public $fecha_cambio;
    public $IdUsuario;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Guarda un nuevo registro de historial
     */
    public function guardar() {
        // Validar que al menos uno de los IDs esté presente
        if (empty($this->IdInscripcion) && empty($this->IdPersona)) {
            throw new Exception("Debe especificar IdInscripcion o IdPersona");
        }

        if (!empty($this->IdInscripcion) && !empty($this->IdPersona)) {
            throw new Exception("Solo puede especificar IdInscripcion O IdPersona, no ambos");
        }

        $query = "INSERT INTO historial_cambios SET
            IdInscripcion = :IdInscripcion,
            IdPersona = :IdPersona,
            tipo_entidad = :tipo_entidad,
            campo_modificado = :campo_modificado,
            valor_anterior = :valor_anterior,
            valor_nuevo = :valor_nuevo,
            descripcion = :descripcion,
            IdUsuario = :IdUsuario";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdInscripcion = !empty($this->IdInscripcion) ? htmlspecialchars(strip_tags($this->IdInscripcion)) : null;
        $this->IdPersona = !empty($this->IdPersona) ? htmlspecialchars(strip_tags($this->IdPersona)) : null;
        $this->tipo_entidad = htmlspecialchars(strip_tags($this->tipo_entidad));
        $this->campo_modificado = htmlspecialchars(strip_tags($this->campo_modificado));
        $this->valor_anterior = $this->valor_anterior !== null ? htmlspecialchars(strip_tags($this->valor_anterior)) : null;
        $this->valor_nuevo = $this->valor_nuevo !== null ? htmlspecialchars(strip_tags($this->valor_nuevo)) : null;
        $this->descripcion = $this->descripcion !== null ? htmlspecialchars(strip_tags($this->descripcion)) : null;
        $this->IdUsuario = htmlspecialchars(strip_tags($this->IdUsuario));

        // Vincular valores
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);
        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":tipo_entidad", $this->tipo_entidad);
        $stmt->bindParam(":campo_modificado", $this->campo_modificado);
        $stmt->bindParam(":valor_anterior", $this->valor_anterior);
        $stmt->bindParam(":valor_nuevo", $this->valor_nuevo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":IdUsuario", $this->IdUsuario);

        if ($stmt->execute()) {
            // === NOTIFICACIÓN AUTOMÁTICA DE CAMBIO DE ESTADO EN INSCRIPCIÓN ===
            if ($this->tipo_entidad === 'inscripcion' && $this->campo_modificado === 'IdStatus') {
                try {
                    require_once __DIR__ . '/Notificacion.php';
                    $notificacion = new Notificacion($this->conn);

                    // Obtener nombre del nuevo status
                    $stmtStatus = $this->conn->prepare("SELECT status FROM status WHERE IdStatus = :id");
                    $stmtStatus->bindParam(":id", $this->valor_nuevo, PDO::PARAM_INT);
                    $stmtStatus->execute();
                    $statusData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
                    $nuevoStatus = $statusData ? $statusData['status'] : 'Desconocido';

                    // Obtener nombre del usuario que hizo el cambio
                    $stmtUser = $this->conn->prepare("SELECT nombre, apellido FROM persona WHERE IdPersona = :id");
                    $stmtUser->bindParam(":id", $this->IdUsuario, PDO::PARAM_INT);
                    $stmtUser->execute();
                    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    $nombreUsuario = $userData ? $userData['nombre'] . ' ' . $userData['apellido'] : 'Usuario';

                    $titulo = "Cambio de Estado de Inscripción";
                    $mensaje = "Inscripción #{$this->IdInscripcion} pasada a '{$nuevoStatus}' por {$nombreUsuario}.";
                    $enlace = "../../inscripciones/inscripcion/ver_inscripcion.php?id=" . $this->IdInscripcion;

                    $notificacion->crear($titulo, $mensaje, 'inscripcion', $enlace, 'admin');

                } catch (Exception $e) {
                    error_log("Error creando notificación de historial: " . $e->getMessage());
                }
            }
            // ===================================================
            return true;
        }
        return false;
    }

    /**
     * Registra un cambio en el historial de una inscripción
     */
    public static function registrarCambioInscripcion($conn, $idInscripcion, $campo, $valorAnterior, $valorNuevo, $descripcion, $idUsuario) {
        $historial = new self($conn);
        $historial->IdInscripcion = $idInscripcion;
        $historial->tipo_entidad = 'inscripcion';
        $historial->campo_modificado = $campo;
        $historial->valor_anterior = $valorAnterior;
        $historial->valor_nuevo = $valorNuevo;
        $historial->descripcion = $descripcion;
        $historial->IdUsuario = $idUsuario;
        return $historial->guardar();
    }

    /**
     * Registra un cambio en el historial de una persona
     */
    public static function registrarCambioPersona($conn, $idPersona, $campo, $valorAnterior, $valorNuevo, $descripcion, $idUsuario) {
        $historial = new self($conn);
        $historial->IdPersona = $idPersona;
        $historial->tipo_entidad = 'persona';
        $historial->campo_modificado = $campo;
        $historial->valor_anterior = $valorAnterior;
        $historial->valor_nuevo = $valorNuevo;
        $historial->descripcion = $descripcion;
        $historial->IdUsuario = $idUsuario;
        return $historial->guardar();
    }

    /**
     * Obtiene el historial de cambios de una inscripción
     */
    public function obtenerPorInscripcion($idInscripcion) {
        $query = "SELECT hc.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM historial_cambios hc
                  INNER JOIN persona p ON hc.IdUsuario = p.IdPersona
                  WHERE hc.IdInscripcion = :idInscripcion
                  ORDER BY hc.fecha_cambio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de cambios de una persona
     */
    public function obtenerPorPersona($idPersona) {
        $query = "SELECT hc.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM historial_cambios hc
                  INNER JOIN persona p ON hc.IdUsuario = p.IdPersona
                  WHERE hc.IdPersona = :idPersona
                  ORDER BY hc.fecha_cambio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el último cambio de una inscripción
     */
    public function obtenerUltimoCambioInscripcion($idInscripcion) {
        $query = "SELECT hc.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM historial_cambios hc
                  INNER JOIN persona p ON hc.IdUsuario = p.IdPersona
                  WHERE hc.IdInscripcion = :idInscripcion
                  ORDER BY hc.fecha_cambio DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el último cambio de una persona
     */
    public function obtenerUltimoCambioPersona($idPersona) {
        $query = "SELECT hc.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM historial_cambios hc
                  INNER JOIN persona p ON hc.IdUsuario = p.IdPersona
                  WHERE hc.IdPersona = :idPersona
                  ORDER BY hc.fecha_cambio DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el número de cambios de una inscripción
     */
    public function contarCambiosInscripcion($idInscripcion) {
        $query = "SELECT COUNT(*) as total FROM historial_cambios WHERE IdInscripcion = :idInscripcion";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Cuenta el número de cambios de una persona
     */
    public function contarCambiosPersona($idPersona) {
        $query = "SELECT COUNT(*) as total FROM historial_cambios WHERE IdPersona = :idPersona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
}
?>
