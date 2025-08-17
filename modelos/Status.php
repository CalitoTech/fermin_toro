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

    public function guardar() {
        $query = "INSERT INTO status (status, IdTipo_Status) VALUES (:status, :tipo_status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':tipo_status', $this->IdTipo_Status);
        
        if ($stmt->execute()) {
            $this->IdStatus = $this->conn->lastInsertId();
            return $this->IdStatus;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE status SET status = :status, IdTipo_Status = :tipo_status WHERE IdStatus = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':tipo_status', $this->IdTipo_Status);
        $stmt->bindParam(':id', $this->IdStatus);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM status WHERE IdStatus = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdStatus);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar en múltiples tablas
        $tablas = ['persona', 'inscripcion']; // Agrega todas las tablas relevantes
        
        foreach ($tablas as $tabla) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE IdStatus = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->IdStatus);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                return true;
            }
        }
        
        return false;
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