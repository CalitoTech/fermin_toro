<?php
require_once __DIR__ . '/../config/conexion.php';

class InscripcionHistorial {
    private $conn;
    public $IdHistorial;
    public $IdInscripcion;
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
        $query = "INSERT INTO inscripcion_historial SET
            IdInscripcion = :IdInscripcion,
            campo_modificado = :campo_modificado,
            valor_anterior = :valor_anterior,
            valor_nuevo = :valor_nuevo,
            descripcion = :descripcion,
            IdUsuario = :IdUsuario";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));
        $this->campo_modificado = htmlspecialchars(strip_tags($this->campo_modificado));
        $this->valor_anterior = $this->valor_anterior !== null ? htmlspecialchars(strip_tags($this->valor_anterior)) : null;
        $this->valor_nuevo = $this->valor_nuevo !== null ? htmlspecialchars(strip_tags($this->valor_nuevo)) : null;
        $this->descripcion = $this->descripcion !== null ? htmlspecialchars(strip_tags($this->descripcion)) : null;
        $this->IdUsuario = htmlspecialchars(strip_tags($this->IdUsuario));

        // Vincular valores
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);
        $stmt->bindParam(":campo_modificado", $this->campo_modificado);
        $stmt->bindParam(":valor_anterior", $this->valor_anterior);
        $stmt->bindParam(":valor_nuevo", $this->valor_nuevo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":IdUsuario", $this->IdUsuario);

        if ($stmt->execute()) {
            // === NOTIFICACIÓN AUTOMÁTICA DE CAMBIO DE ESTADO ===
            if ($this->campo_modificado === 'IdStatus') {
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

                    // Obtener código de inscripción para el título (opcional, requiere consulta extra)
                    // Por simplicidad, usamos el ID o un mensaje genérico
                    
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
     * @param int $idInscripcion ID de la inscripción
     * @param string $campo Nombre del campo modificado
     * @param mixed $valorAnterior Valor antes del cambio
     * @param mixed $valorNuevo Valor después del cambio
     * @param string $descripcion Descripción legible del cambio
     * @param int $idUsuario ID del usuario que realiza el cambio
     * @return bool
     */
    public static function registrarCambio($conn, $idInscripcion, $campo, $valorAnterior, $valorNuevo, $descripcion, $idUsuario) {
        $historial = new self($conn);
        $historial->IdInscripcion = $idInscripcion;
        $historial->campo_modificado = $campo;
        $historial->valor_anterior = $valorAnterior;
        $historial->valor_nuevo = $valorNuevo;
        $historial->descripcion = $descripcion;
        $historial->IdUsuario = $idUsuario;
        return $historial->guardar();
    }

    /**
     * Obtiene el historial de cambios de una inscripción
     * @param int $idInscripcion ID de la inscripción
     * @return array
     */
    public function obtenerPorInscripcion($idInscripcion) {
        $query = "SELECT ih.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM inscripcion_historial ih
                  INNER JOIN persona p ON ih.IdUsuario = p.IdPersona
                  WHERE ih.IdInscripcion = :idInscripcion
                  ORDER BY ih.fecha_cambio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el último cambio de una inscripción
     * @param int $idInscripcion ID de la inscripción
     * @return array|false
     */
    public function obtenerUltimoCambio($idInscripcion) {
        $query = "SELECT ih.*,
                         p.nombre AS usuario_nombre,
                         p.apellido AS usuario_apellido
                  FROM inscripcion_historial ih
                  INNER JOIN persona p ON ih.IdUsuario = p.IdPersona
                  WHERE ih.IdInscripcion = :idInscripcion
                  ORDER BY ih.fecha_cambio DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el número de cambios de una inscripción
     * @param int $idInscripcion ID de la inscripción
     * @return int
     */
    public function contarCambios($idInscripcion) {
        $query = "SELECT COUNT(*) as total FROM inscripcion_historial WHERE IdInscripcion = :idInscripcion";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idInscripcion', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
}
?>
