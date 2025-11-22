<?php
require_once __DIR__ . '/../config/conexion.php';

class Egreso {
    private $conn;

    public $IdEgreso;
    public $fecha_egreso;
    public $motivo;
    public $IdPersona;
    public $IdStatus;

    public function __construct($db) {
        $this->conn = $db;
    }

    /** =============================
     *  GUARDAR NUEVO EGRESO
     *  ============================= */
    public function guardar() {
        $query = "INSERT INTO egreso (fecha_egreso, motivo, IdPersona, IdStatus) 
                  VALUES (:fecha_egreso, :motivo, :IdPersona, :IdStatus)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar datos
        $this->fecha_egreso = htmlspecialchars(strip_tags($this->fecha_egreso));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdStatus = htmlspecialchars(strip_tags($this->IdStatus));

        // Enlazar parámetros
        $stmt->bindParam(":fecha_egreso", $this->fecha_egreso);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdEgreso = $this->conn->lastInsertId();

            // ✅ Desactivar automáticamente la persona al egresar
            $this->desactivarPersona($this->IdPersona);

            return true;
        }
        return false;
    }

    /** =============================
     *  OBTENER EGRESO POR PERSONA
     *  ============================= */
    public function obtenerPorPersona($idPersona) {
        $query = "SELECT e.*, s.status
                  FROM egreso e
                  JOIN status s ON e.IdStatus = s.IdStatus
                  WHERE e.IdPersona = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** =============================
     *  OBTENER EGRESO POR ID
     *  ============================= */
    public function obtenerPorId($idEgreso) {
        $query = "SELECT e.*, s.status
                  FROM egreso e
                  JOIN status s ON e.IdStatus = s.IdStatus
                  WHERE e.IdEgreso = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idEgreso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** =============================
     *  ACTUALIZAR EGRESO
     *  ============================= */
    public function actualizar() {
        $query = "UPDATE egreso
                  SET fecha_egreso = :fecha_egreso,
                      motivo = :motivo,
                      IdPersona = :IdPersona,
                      IdStatus = :IdStatus
                  WHERE IdEgreso = :IdEgreso";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->fecha_egreso = htmlspecialchars(strip_tags($this->fecha_egreso));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdStatus = htmlspecialchars(strip_tags($this->IdStatus));
        $this->IdEgreso = htmlspecialchars(strip_tags($this->IdEgreso));

        // Enlazar parámetros
        $stmt->bindParam(":fecha_egreso", $this->fecha_egreso);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, PDO::PARAM_INT);
        $stmt->bindParam(":IdEgreso", $this->IdEgreso, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /** =============================
     *  OBTENER TODOS LOS EGRESOS
     *  ============================= */
    public function obtenerTodos() {
        $query = "SELECT e.*, s.status, p.nombre, p.apellido, p.cedula
                  FROM egreso e
                  JOIN status s ON e.IdStatus = s.IdStatus
                  JOIN persona p ON e.IdPersona = p.IdPersona
                  ORDER BY e.fecha_egreso DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** =============================
     *  ELIMINAR EGRESO
     *  ============================= */
    public function eliminar() {
        $query = "DELETE FROM egreso WHERE IdEgreso = :IdEgreso";
        $stmt = $this->conn->prepare($query);
        $this->IdEgreso = htmlspecialchars(strip_tags($this->IdEgreso));
        $stmt->bindParam(":IdEgreso", $this->IdEgreso, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function desactivarPersona($idPersona) {
        try {
            $query = "UPDATE persona 
                      SET IdEstadoInstitucional = 2, 
                          IdEstadoAcceso = 2 
                      WHERE IdPersona = :IdPersona";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":IdPersona", $idPersona, PDO::PARAM_INT);
            $stmt->execute();

        } catch (Exception $e) {
            error_log("Error al desactivar persona (ID: $idPersona): " . $e->getMessage());
        }
    }
}
