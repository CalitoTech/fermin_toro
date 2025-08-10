<?php
require_once __DIR__ . '/../config/conexion.php';

class Status {
    private $conn;
    public $IdStatus;
    public $IdTipo_Status;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT s.*, ts.tipo_status 
                 FROM status s
                 JOIN tipo_status ts ON s.IdTipo_Status = ts.IdTipo_Status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorTipo($idTipoStatus) {
        $query = "SELECT * FROM status WHERE IdTipo_Status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idTipoStatus, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idStatus) {
        $query = "SELECT * FROM status WHERE IdStatus = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idStatus, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}