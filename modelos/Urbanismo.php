<?php
require_once __DIR__ . '/../config/conexion.php';

class Urbanismo {
    private $conn;
    public $IdUrbanismo;
    public $urbanismo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO urbanismo (urbanismo) VALUES (:urbanismo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':urbanismo', $this->urbanismo);
        
        if ($stmt->execute()) {
            $this->IdUrbanismo = $this->conn->lastInsertId();
            return $this->IdUrbanismo;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE urbanismo SET urbanismo = :urbanismo WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':urbanismo', $this->urbanismo);
        $stmt->bindParam(':id', $this->IdUrbanismo);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el urbanismo está siendo usado en la tabla persona
        $query = "SELECT COUNT(*) as total FROM persona WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdUrbanismo);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM urbanismo WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdUrbanismo);
        return $stmt->execute();
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM urbanismo ORDER BY urbanismo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM urbanismo WHERE IdUrbanismo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdUrbanismo = $row['IdUrbanismo'];
            $this->urbanismo = $row['urbanismo'];
            return $row;
        }
        
        return false;
    }
}