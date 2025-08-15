<?php
require_once __DIR__ . '/../config/conexion.php';
class Seccion {
    private $conn;
    public $IdSeccion;
    public $seccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO seccion (seccion) VALUES (:seccion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seccion', $this->seccion);
        
        if ($stmt->execute()) {
            $this->IdSeccion = $this->conn->lastInsertId();
            return $this->IdSeccion;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE seccion SET seccion = :seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seccion', $this->seccion);
        $stmt->bindParam(':id', $this->IdSeccion);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdSeccion);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el seccion está siendo usado en la tabla curso_seccion
        $query = "SELECT COUNT(*) as total FROM curso_seccion WHERE IdSeccion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdSeccion);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM seccion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM seccion WHERE IdSeccion = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdSeccion = $row['IdSeccion'];
            $this->seccion = $row['seccion'];
            return $row;
        }
        
        return false;
    }
}
?>