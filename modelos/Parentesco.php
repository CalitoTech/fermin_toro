<?php
require_once __DIR__ . '/../config/conexion.php';
class Parentesco {
    private $conn;
    public $IdParentesco;
    public $parentesco;

    public function __construct($db) {
        $this->conn = $db;
    }

     public function guardar() {
        $query = "INSERT INTO parentesco (parentesco) VALUES (:parentesco)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parentesco', $this->parentesco);
        
        if ($stmt->execute()) {
            $this->IdParentesco = $this->conn->lastInsertId();
            return $this->IdParentesco;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE parentesco SET parentesco = :parentesco WHERE IdParentesco = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parentesco', $this->parentesco);
        $stmt->bindParam(':id', $this->IdParentesco);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el parentesco está siendo usado en la tabla representante
        $query = "SELECT COUNT(*) as total FROM representante WHERE IdParentesco = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdParentesco);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM parentesco WHERE IdParentesco = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdParentesco);
        return $stmt->execute();
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM parentesco";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM parentesco WHERE IdParentesco = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdParentesco = $row['IdParentesco'];
            $this->parentesco = $row['parentesco'];
            return $row;
        }
        
        return false;
    }
}
?>