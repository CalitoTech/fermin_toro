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

    public function guardar() {
        $query = "INSERT INTO egreso (fecha_egreso, motivo, IdPersona, IdStatus) 
                 VALUES (:fecha_egreso, :motivo, :IdPersona, :IdStatus)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->fecha_egreso = htmlspecialchars(strip_tags($this->fecha_egreso));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdStatus = htmlspecialchars(strip_tags($this->IdStatus));

        $stmt->bindParam(":fecha_egreso", $this->fecha_egreso);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdEgreso = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

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
}