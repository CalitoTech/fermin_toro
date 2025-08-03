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
        $stmt->bindParam(':urbanismo', $this->urbanismo, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE urbanismo SET urbanismo = :urbanismo WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':urbanismo', $this->urbanismo, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->IdUrbanismo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM urbanismo WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdUrbanismo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM urbanismo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM urbanismo WHERE IdUrbanismo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>